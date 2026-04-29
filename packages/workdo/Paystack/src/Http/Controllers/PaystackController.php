<?php

namespace Workdo\Paystack\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Plan;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;
use Workdo\Paystack\Events\PaystackPaymentStatus;
use Illuminate\Support\Facades\Http;

class PaystackController extends Controller
{
    private function initializePaystack($publicKey,$secretKey)
    {
        return [
            'public_key' => $publicKey ?? '',
            'secret_key' => $secretKey ?? '',
            'base_url' => 'https://api.paystack.co'
        ];
    }

    private function verifyPaystackPayment($reference, $secretKey)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $secretKey,
            'Content-Type' => 'application/json',
        ])->get("https://api.paystack.co/transaction/verify/{$reference}");

        return $response->json();
    }

    public function planPayWithPaystack(Request $request)
    {
        $plan = Plan::find($request->plan_id);
        $user = User::find($request->user_id);
        $admin_settings = getAdminAllSetting();
        $admin_currency = !empty($admin_settings['defaultCurrency']) ? $admin_settings['defaultCurrency'] : '';

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
            $publicKey = $admin_settings['paystack_public_key'] ?? '';
            $secretKey = $admin_settings['paystack_secret_key'] ?? '';
            $paystack_config = $this->initializePaystack($publicKey,$secretKey);
            $amount = $price * 100; // Paystack expects amount in kobo

            $paymentData = [
                'email' => $user->email,
                'amount' => (int)$amount,
                'currency' => $admin_currency,
                'reference' => 'plan_' . time() . '_' . $plan->id,
                'callback_url' => route('payment.paystack.status', $plan->id),
                'amount_original' => $price,
                'user_module' => $user_module,
                'duration' => $duration,
                'coupon_code' => $request->coupon_code,
            ];

            return inertia('Paystack/PaystackPayment', [
                'payment_data' => $paymentData,
                'public_key' => $paystack_config['public_key'],
                'plan_id' => $plan->id,
                'user_module' => $user_module,
                'duration' => $duration,
                'coupon_code' => $request->coupon_code,
            ]);
        } else {
            return redirect()->route('plans.index')->with('error', __('The plan has been deleted.'));
        }
    }

    public function planGetPaystackStatus(Request $request, $plan_id)
    {
        $user = Auth::user();
        $plan = Plan::find($plan_id);

        if ($plan) {
            $admin_settings = getAdminAllSetting();
            $publicKey = $admin_settings['paystack_public_key'] ?? '';
            $secretKey = $admin_settings['paystack_secret_key'] ?? '';
            $paystack_config = $this->initializePaystack($publicKey,$secretKey);

            $reference = $request->reference;

            if (!$reference) {
                return redirect()->route('plans.index')->with('error', __('Payment reference not found.'));
            }

            $response = $this->verifyPaystackPayment($reference, $paystack_config['secret_key']);
            if ($response['status'] && $response['data']['status'] === 'success') {
                $orderID = strtoupper(substr(uniqid(), -12));

                try {
                    $order                 = new Order();
                    $order->order_id       = $orderID;
                    $order->name           = $user->name ?? '';
                    $order->email          = $user->email ?? '';
                    $order->card_number    = null;
                    $order->card_exp_month = null;
                    $order->card_exp_year  = null;
                    $order->plan_name      = !empty($plan->name) ? $plan->name : 'Basic Package';
                    $order->plan_id        = $plan->id;
                    $order->price          = !empty($request->amount_original) ? $request->amount_original : 0;
                    $order->currency       = $admin_settings['defaultCurrency'] ?? '';
                    $order->txn_id         = $reference;
                    $order->payment_type   = 'Paystack';
                    $order->payment_status = 'succeeded';
                    $order->receipt        = null;
                    $order->created_by     = $user->id;
                    $order->save();

                    $counter = [
                        'user_counter' => -1,
                        'storage_counter' => 0,
                    ];

                    $assignPlan = assignPlan($plan->id, $request->duration, $request->user_module, $counter, $user->id);

                    if ($request->coupon_code) {
                        $coupon = Coupon::where('code', $request->coupon_code)->first();
                        if ($coupon) {
                            recordCouponUsage($coupon->id, $user->id, $orderID);
                        }
                    }

                    $type = 'Subscription';
                    try {
                        PaystackPaymentStatus::dispatch($plan, $type, $order);
                    } catch (\Throwable $th) {
                        return back()->with('error', $th->getMessage());
                    }

                    if ($assignPlan['is_success']) {
                        return redirect()->route('plans.index')->with('success', __('The plan has been activated successfully.'));
                    } else {
                        return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
                    }
                } catch (\Exception $e) {
                    return redirect()->route('plans.index')->with('error', __('Transaction has been failed.'));
                }
            } else {
                return redirect()->route('plans.index')->with('error', __('Payment verification failed.'));
            }
        } else {
            return redirect()->route('plans.index')->with('error', __('The plan has been deleted.'));
        }
    }
}
