<?php

namespace Workdo\Flutterwave\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Plan;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Workdo\Flutterwave\Events\FlutterwavePaymentStatus;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;

class FlutterwaveController extends Controller
{
    protected $secret_key, $public_key, $currancy, $is_enabled, $payment_mode;
    protected $url = 'https://api.flutterwave.com/v3';

    protected function checkout($pay)
    {
        $data = decrypt($pay);
        return Inertia::render('Flutterwave/FlutterwavePayment', compact('data'));
    }

    protected function verify($transaction_id, $user_id = null)
    {
        if (!empty($user_id)) {
            $this->configuration($user_id);
        } else {
            $admin_settings = getAdminAllSetting();
            $this->secret_key = $admin_settings['flutterwave_secret_key'] ?? '';
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secret_key,
            'Content-Type' => 'application/json',
            'accept' => 'application/json'
        ])->get("{$this->url}/transactions/{$transaction_id}/verify");

        if ($response->successful()) {
            $transactions = json_decode($response->body());
            return (object) [
                'status' => $transactions->data->status,
                'message' => $transactions->message ?? __('The transaction has been successful.'),
                'data' => $transactions->data,
            ];
        } else {
            return (object) [
                'status' => 'error',
                'message' => $response->json()['message'] ?? __('The transaction has failed.'),
                'data' => $response->json()
            ];
        }
    }

    protected function configuration($id = null)
    {
        $company_settings = getCompanyAllSetting($id);
        $this->currancy = !empty($company_settings['defaultCurrency']) ? $company_settings['defaultCurrency'] : '';
        $this->public_key = !empty($company_settings['flutterwave_public_key']) ? $company_settings['flutterwave_public_key'] : '';
        $this->secret_key = !empty($company_settings['flutterwave_secret_key']) ? $company_settings['flutterwave_secret_key'] : '';
        $this->is_enabled = !empty($company_settings['flutterwave_enabled']) ? $company_settings['flutterwave_enabled'] : 'off';
        return $company_settings;
    }

    public function planPayWithFlutterwave(Request $request)
    {
        $plan = Plan::find($request->plan_id);
        $user = User::find($request->user_id);
        $admin_settings = getAdminAllSetting();
        $admin_currancy = !empty($admin_settings['defaultCurrency']) ? $admin_settings['defaultCurrency'] : '';
        $supported_currencies = ['GBP', 'CAD', 'XAF', 'COP', 'EGP', 'EUR', 'GHS','USD','KES','IRN','NGN','RWF','SLL','ZAR','TZS','UGX','XOF','ZMW'];

        if (!in_array($admin_currancy, $supported_currencies)) {
            return redirect()->back()->with('error', __('Currency is not supported.'));
        }

        $user_module = !empty($request->user_module_input) ? $request->user_module_input : '';
        $duration = !empty($request->time_period) ? $request->time_period : 'Month';
        $user_module_price = 0;

        if (!empty($user_module)) {
            $user_module_array = explode(',', $user_module);
            foreach ($user_module_array as $key => $value) {
                $temp = ($duration == 'Year') ? ModulePriceByName($value)['yearly_price'] : ModulePriceByName($value)['monthly_price'];
                $user_module_price = $user_module_price + $temp;
            }
        }

        $plan_price = ($duration == 'Year') ? $plan->package_price_yearly : $plan->package_price_monthly;
        $counter = [
            'user_counter' => -1,
            'storage_counter' => 0,
        ];

        $orderID = strtoupper(substr(uniqid(), -12));

        if ($plan) {
            $price = $plan_price + $user_module_price;

            if ($request->coupon_code) {
                $validation = applyCouponDiscount($request->coupon_code, $price, auth()->id());
                if ($validation['valid']) {
                    $price = $validation['final_amount'];
                }
            }

            if ($price <= 0) {
                $assignPlan = assignPlan($plan->id, $duration, $user_module, $counter, $request->user_id);
                if ($assignPlan['is_success']) {
                    return redirect()->route('plans.index')->with('success', __('The plan has been activated successfully.'));
                } else {
                    return redirect()->route('plans.index')->with('error', __('Something went wrong, Please try again,'));
                }
            }

            try {
                $order = new Order();
                $order->order_id = $orderID;
                $order->name = $user->name ?? '';
                $order->email = $user->email ?? '';
                $order->card_number = null;
                $order->card_exp_month = null;
                $order->card_exp_year = null;
                $order->plan_name = $plan->name ?? 'Basic Package';
                $order->plan_id = $plan->id;
                $order->price = $price ?? 0;
                $order->currency = $admin_currancy;
                $order->txn_id = '';
                $order->payment_type = 'Flutterwave';
                $order->payment_status = 'pending';
                $order->receipt = null;
                $order->created_by = $user->id;
                $order->save();

                Session::put($orderID, [
                    'plan_id' => $plan->id,
                    'duration' => $duration,
                    'coupon_code' => $request->coupon_code,
                    'user_module' => $user_module,
                    'user_id' => $user->id
                ]);

                return $this->checkout(encrypt([
                    'email' => Auth::user()->email,
                    'name' => Auth::user()->name,
                    'price' => $price ?? 0,
                    'redirect_url' => route('payment.flutterwave.status', $orderID),
                    'cancel_url' => route('payment.flutterwave.cancel', $orderID),
                    'currency' => $admin_currancy,
                    'public_key' => !empty($admin_settings['flutterwave_public_key']) ? $admin_settings['flutterwave_public_key'] : '',
                ]));
            } catch (\Exception $e) {
                return redirect()->route('plans.index')->with('error', __('oops, something went wrong, please try again.'));
            }
        } else {
            return redirect()->route('plans.index')->with('error', __('The plan has been deleted.'));
        }
    }

    public function planCancelFlutterwavePayment($order_id)
    {
        $Order = Order::where('order_id', $order_id)->first();
        if ($Order) {
            $Order->payment_status = 'failed';
            $Order->save();
        }
        Session::forget($order_id);
        return redirect()->route('plans.index')->with('error', __('Payment cancelled by user.'));
    }

    public function planGetFlutterwaveStatus(Request $request, $order_id)
    {
        $sessionData = Session::get($order_id);

        try {
            if ($request->status == 'successful' && $request->transaction_id) {
                $verification = $this->verify($request->transaction_id);

                if ($verification->status == 'successful') {
                    $Order = Order::where('order_id', $order_id)->first();
                    if ($Order) {
                        $Order->payment_status = 'succeeded';
                        $Order->txn_id = $request->transaction_id ?? '';
                        $Order->save();
                    }

                    if ($sessionData) {
                        $plan = Plan::find($sessionData['plan_id']);
                        if (!$plan) {
                            return redirect()->route('plans.index')->with('error', __('The plan has been deleted.'));
                        }
                        
                        $counter = [
                            'user_counter' => -1,
                            'storage_counter' => 0,
                        ];

                        $assignPlan = assignPlan($plan->id, $sessionData['duration'], $sessionData['user_module'], $counter, $sessionData['user_id']);

                        if ($assignPlan['is_success']) {
                            if ($sessionData['coupon_code']) {
                                $coupon = Coupon::where('code', $sessionData['coupon_code'])->first();
                                if ($coupon) {
                                    recordCouponUsage($coupon->id, $sessionData['user_id'], $order_id);
                                }
                            }
                            $type = 'Subscription';
                            try {
                                FlutterwavePaymentStatus::dispatch($plan, $type, $Order);
                            } catch (\Throwable $th) {
                                return back()->with('error', $th->getMessage());
                            }

                            Session::forget($order_id);
                            return redirect()->route('plans.index')->with('success', __('The plan has been activated successfully.'));
                        } else {
                            return redirect()->route('plans.index')->with('error', __('Something went wrong, Please try again,'));
                        }
                    } else {
                        return redirect()->route('plans.index')->with('error', __('Session data not found.'));
                    }
                } else {
                    return redirect()->route('plans.index')->with('error', __('Payment verification failed!'));
                }
            } else {
                return redirect()->route('plans.index')->with('error', __('Your Payment has failed!'));
            }
        } catch (\Exception $exception) {
            return redirect()->route('plans.index')->with('error', $exception->getMessage());
        }
    }
}
