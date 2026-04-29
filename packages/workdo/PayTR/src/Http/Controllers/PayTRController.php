<?php

namespace Workdo\PayTR\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Plan;
use App\Models\Order;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;
use Workdo\PayTR\Services\PayTRService;
use Workdo\PayTR\Events\PayTRPaymentStatus;

class PayTRController extends Controller
{
    public $supported_currencies = ['GBP', 'USD', 'EUR', 'TL', 'RUB'];

    public function planPayWithPayTR(Request $request)
    {
        $plan = Plan::find($request->plan_id);
        $user = User::find($request->user_id);
        $admin_settings = getAdminAllSetting();
        $admin_currancy = !empty($admin_settings['defaultCurrency']) ? $admin_settings['defaultCurrency'] : '';

        if (!in_array($admin_currancy, $this->supported_currencies)) {
            return redirect()->route('plans.index')->with('error', __('Currency is not supported by PayTR.'));
        }

        if (!$plan) {
            return redirect()->route('plans.index')->with('error', __('The plan has been deleted.'));
        }

        $user_module = !empty($request->user_module_input) ? $request->user_module_input : '';
        $duration = !empty($request->time_period) ? $request->time_period : 'Month';
        $user_module_price = 0;

        if (!empty($user_module) && $plan->custom_plan == 1) {
            $user_module_array = explode(',', $user_module);
            foreach ($user_module_array as $key => $value) {
                $temp = ($duration == 'Year') ? ModulePriceByName($value)['yearly_price'] : ModulePriceByName($value)['monthly_price'];
                $user_module_price = $user_module_price + floatval($temp);
            }
        }

        $plan_price = ($duration == 'Year') ? $plan->package_price_yearly : $plan->package_price_monthly;
        $counter = [
            'user_counter' => -1,
            'storage_limit' => 0,
        ];

        if ($plan) {
            $price = $plan_price + $user_module_price;

            if ($request->coupon_code) {
                $validation = applyCouponDiscount($request->coupon_code, $price, auth()->id());
                if ($validation['valid']) {
                    $price = floatval($validation['final_amount']);
                }
            }

            if ($price <= 0) {
                $assignPlan = assignPlan($plan->id, $duration, $user_module, $counter, $request->user_id);
                if ($assignPlan['is_success']) {
                    return redirect()->route('plans.index')->with('success', __('The plan has been activated successfully.'));
                } else {
                    return redirect()->route('plans.index')->with('error', __('Something went wrong. Please try again.'));
                }
            }

            try {
                $orderID = strtoupper(substr(uniqid(), -12));

                // Store plan data in session
                Session::put('paytr_plan_data', [
                    'plan_id' => $plan->id,
                    'user_module' => $user_module,
                    'duration' => $duration,
                    'counter' => $counter,
                    'coupon_code' => $request->coupon_code,
                    'user_id' => $user->id,
                ]);

                // Create order record
                $order                 = new Order();
                $order->order_id       = $orderID;
                $order->name           = $user->name;
                $order->email          = $user->email;
                $order->card_number    = null;
                $order->card_exp_month = null;
                $order->card_exp_year  = null;
                $order->plan_name      = $plan->name ?? 'Basic Package';
                $order->plan_id        = $plan->id;
                $order->price          = $price;
                $order->currency       = $admin_currancy;
                $order->txn_id         = '';
                $order->payment_type   = 'PayTR';
                $order->payment_status = 'pending';
                $order->receipt        = null;
                $order->created_by     = $user->id;
                $order->save();

                // Initialize PayTR Service
                $paytrService = new PayTRService(
                    $admin_settings['paytr_merchant_id'] ?? '',
                    $admin_settings['paytr_merchant_key'] ?? '',
                    $admin_settings['paytr_merchant_salt'] ?? '',
                    $admin_settings['paytr_mode'] ?? 'sandbox'
                );

                $paymentParams = [
                    'name' => $plan->name ?? 'Plan',
                    'price' => $price,
                    'currency' => $admin_currancy,
                    'max_installment' => $price,
                    'user_name' => $user->name,
                    'email' => $user->email,
                    'callback_link' => route('payment.paytr.status', ['order_id' => $orderID]),
                    'callback_id' => $orderID,
                ];

                $paymentResponse = $paytrService->createPayment($paymentParams);

                if ($paymentResponse['status'] === 'success') {
                    return $paytrService->redirectToCheckout($paymentResponse, $user, $orderID);
                } else {
                    return redirect()->route('plans.index')->with('error', __('Failed to create payment link.'));
                }
            } catch (\Exception $e) {
                return redirect()->route('plans.index')->with('error', $e->getMessage());
            }
        } else {
            return redirect()->route('plans.index')->with('error', __('The plan has been deleted.'));
        }
    }

    public function planGetPayTRStatus(Request $request)
    {
        $planData = Session::get('paytr_plan_data');

        if (!$planData) {
            return redirect()->route('plans.index')->with('error', __('Session data not found.'));
        }

        Session::forget('paytr_plan_data');

        try {
            if ($request->success) {
                $Order = Order::where('order_id', $request->order_id)->first();
                if (!$Order) {
                    return redirect()->route('plans.index')->with('error', __('The order has not been found.'));
                }

                $Order->payment_status = 'succeeded';
                $Order->save();

                $plan = Plan::find($planData['plan_id']);
                $counter = [
                    'user_counter' => -1,
                    'storage_limit' => 0,
                ];
                $assignPlan = assignPlan($plan->id, $planData['duration'], $planData['user_module'], $counter, $planData['user_id']);
                if ($assignPlan['is_success']) {
                    if ($planData['coupon_code']) {
                        $coupon = Coupon::where('code', $planData['coupon_code'])->first();
                        if ($coupon) {
                            recordCouponUsage($coupon->id, $planData['user_id'], $request->order_id);
                        }
                    }
                    $type = 'Subscription';
                    try {
                        PayTRPaymentStatus::dispatch($plan, $type, $Order);
                    } catch (\Exception $e) {
                        return redirect()->back()->with('error', $e->getMessage());
                    }

                    $value = Session::get('user-module-selection');
                    if (!empty($value)) {
                        Session::forget('user-module-selection');
                    }
                    return redirect()->route('plans.index')->with('success', __('The plan has been activated successfully.'));
                } else {
                    return redirect()->route('plans.index')->with('error', __('Something went wrong. Please try again.'));
                }
            } else {
                return redirect()->route('plans.index')->with('error', __('The payment has failed.'));
            }
        } catch (\Exception $exception) {
            return redirect()->route('plans.index')->with('error', $exception->getMessage());
        }
    }
}
