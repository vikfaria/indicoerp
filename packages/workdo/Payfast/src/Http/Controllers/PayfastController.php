<?php

namespace Workdo\Payfast\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;
use Workdo\Payfast\Services\PayfastPaymentService;
use Workdo\Payfast\Events\PayfastPaymentStatus;

class PayfastController extends Controller
{
    public function planPayWithPayfast(Request $request)
    {
        $plan = Plan::find($request->plan_id);
        $user = User::find($request->user_id);
        $admin_settings = getAdminAllSetting();
        $admin_currency = !empty($admin_settings['defaultCurrency']) ? $admin_settings['defaultCurrency'] : '';

        if (!$plan) {
            return redirect()->route('plans.index')->with('error', __('The plan has been deleted.'));
        }

        $user_module = !empty($request->user_module_input) ? $request->user_module_input : '';
        $duration = !empty($request->time_period) ? $request->time_period : 'Month';

        $user_module_price = 0;
        if (!empty($user_module)) {
            $user_module_array = explode(',', $user_module);
            foreach ($user_module_array as $value) {
                $temp = ($duration == 'Year') ? ModulePriceByName($value)['yearly_price'] : ModulePriceByName($value)['monthly_price'];
                $user_module_price += $temp;
            }
        }

        $plan_price = ($duration == 'Year') ? $plan->package_price_yearly : $plan->package_price_monthly;
        $counter = [
            'user_counter' => -1,
            'storage_counter' => 0,
        ];

        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
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
            $payfastService = new PayfastPaymentService([
                'payfast_merchant_id' => $admin_settings['payfast_merchant_id'] ?? '',
                'payfast_merchant_key' => $admin_settings['payfast_merchant_key'] ?? '',
                'payfast_salt_passphrase' => $admin_settings['payfast_salt_passphrase'] ?? '',
                'payfast_mode' => $admin_settings['payfast_mode'] ?? 'sandbox'
            ]);

            $response = $payfastService->checkout([
                'name' => $user->name,
                'email' => $user->email,
                'url' => route('payment.payfast.status', $orderID),
                'price' => $price,
                'order_id' => $orderID,
                'product' => !empty($plan->name) ? $plan->name : 'Basic Package',
                'session' => [
                    'plan' => $plan->toArray(),
                    'order_id' => $orderID,
                    'amount' => $price,
                    'user_module' => $user_module,
                    'counter' => $counter,
                    'duration' => $duration,
                    'coupon_code' => $request->coupon_code,
                    'user_id' => $user->id,
                    'currency' => $admin_currency,
                ]
            ]);

            if ($response->success) {
                $order = new Order();
                $order->order_id = $orderID;
                $order->name = $user->name;
                $order->email = $user->email;
                $order->card_number = null;
                $order->card_exp_month = null;
                $order->card_exp_year = null;
                $order->plan_name = !empty($plan->name) ? $plan->name : 'Basic Package';
                $order->plan_id = $plan->id;
                $order->price = !empty($price) ? $price : 0;
                $order->currency = $admin_currency;
                $order->txn_id = $orderID;
                $order->payment_type = 'Payfast';
                $order->payment_status = 'pending';
                $order->receipt = null;
                $order->created_by = $user->id;
                $order->save();

                return redirect($response->url);
            }

            return redirect()->route('plans.index')->with('error', $response->message ?? __('Payment initialization failed.'));
        } catch (\Exception $e) {
            return redirect()->route('plans.index')->with('error', $e->getMessage());
        }
    }

    public function planGetPayfastStatus(Request $request, $order_id)
    {
        if ($request->status == 'success') {
            try {
                $session = Session::get($order_id);
                $data = $session['other'] ?? null;
                $planData = is_string($data['plan']) ? json_decode($data['plan'], true) : $data['plan'];

                $plan = $planData ?? '';
                $product = !empty($plan['name']) ? $plan['name'] : 'Basic Package';

                $order = Order::where('order_id', $order_id)->first();
                if ($order) {
                    $order->payment_status = 'succeeded';
                    $order->save();
                } else {
                    $order = new Order();
                    $order->order_id = $order_id;
                    $order->name = null;
                    $order->email = null;
                    $order->card_number = null;
                    $order->card_exp_month = null;
                    $order->card_exp_year = null;
                    $order->plan_name = $product;
                    $order->plan_id = $plan['id'];
                    $order->price = !empty($data['amount']) ? $data['amount'] : 0;
                    $order->currency = !empty($data['currency']) ? $data['currency'] : 'USD';
                    $order->txn_id = '';
                    $order->payment_type = 'Payfast';
                    $order->payment_status = 'succeeded';
                    $order->receipt = null;
                    $order->created_by = $data['user_id'] ?? auth()->id();
                    $order->save();
                }

                $counter = [
                    'user_counter' => -1,
                    'storage_counter' => 0,
                ];

                $assignPlan = assignPlan($plan['id'], $data['duration'], $data['user_module'], $counter, $data['user_id']);

                if ($assignPlan['is_success']) {
                    if ($data['coupon_code']) {
                        $coupon = Coupon::where('code', $data['coupon_code'])->first();
                        if ($coupon) {
                            recordCouponUsage($coupon->id, $data['user_id'], $order_id);
                        }
                    }

                    $type = 'Subscription';
                    $planModel = Plan::find($plan['id']);

                    try {
                        PayfastPaymentStatus::dispatch($planModel, $type, $order);
                    } catch (\Exception $e) {
                        return redirect()->back()->with('error', $e->getMessage());
                    }

                    Session::forget($order_id);
                    return redirect()->route('plans.index')->with('success', __('The plan has been activated successfully.'));
                } else {
                    Session::forget($order_id);
                    return redirect()->route('plans.index')->with('error', __('Something went wrong, Please try again'));
                }
            } catch (\Exception $e) {
                Session::forget($order_id);
                return redirect()->route('plans.index')->with('error', __('The Transaction was successful, but something went wrong.'));
            }
        }
        
        Session::forget($order_id);
        return redirect()->route('plans.index')->with('error', __('Your Payment has failed!'));
    }
}
