<?php

namespace Workdo\Coingate\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\User;
use App\Models\Plan;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Workdo\Coingate\Services\CoingateService;
use Workdo\Coingate\Events\CoingatePaymentStatus;

class CoingateController extends Controller
{
    // Plan Payment
    public function planPayWithCoingate(Request $request)
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

        $orderID = strtoupper(substr(uniqid(), -12));
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
                return redirect()->route('plans.index')->with('error', __('The plan activation has failed.'));
            }
        }

        try {
            $coingateService = new CoingateService([
                'auth_token' => $admin_settings['coingate_auth_token'] ?? '',
                'environment' => $admin_settings['coingate_mode'] ?? 'sandbox'
            ]);

            $paymentData = [
                'order_id' => $orderID,
                'price_amount' => $price,
                'price_currency' => $admin_currency,
                'title' => __("Plan: :name - :duration", ['name' => $plan->name, 'duration' => $duration]),
                'description' => __("Payment for :name plan", ['name' => $plan->name]),
                'callback_url' => route('plan.payment.coingate.status'),
                'cancel_url' => route('plan.payment.coingate.status'),
                'success_url' => route('plan.payment.coingate.status', [
                    'order_id' => $orderID,
                    'plan_id' => $plan->id,
                    'user_module' => $user_module,
                    'duration' => $duration,
                    'coupon_code' => $request->coupon_code,
                    'user_id' => $user->id,
                    'return_type' => 'success'
                ])
            ];

            $response = $coingateService->createPayment($paymentData);

            if (isset($response['status_code']) && $response['status_code'] == 200) {
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
                $order->txn_id = $response['response']['id'] ?? null;
                $order->payment_type = 'Coingate';
                $order->payment_status = 'pending';
                $order->receipt = null;
                $order->created_by = $user->id;
                $order->save();

                Session::put('coingate_payment_data', [
                    'order_id' => $orderID,
                    'plan_id' => $plan->id,
                    'user_module' => $user_module,
                    'duration' => $duration,
                    'coupon_code' => $request->coupon_code,
                    'user_id' => $user->id
                ]);

                return redirect($response['response']['payment_url']);
            }

            return redirect()->route('plans.index')->with('error', __('The payment initialization has failed.'));
        } catch (\Exception $e) {
            return redirect()->route('plans.index')->with('error', $e->getMessage());
        }
    }

    public function planGetCoingateStatus(Request $request)
    {
        $paymentData = Session::get('coingate_payment_data');

        if (!$paymentData) {
            return redirect()->route('plans.index')->with('error', __('The payment data is not found.'));
        }

        try {
            $Order = Order::where('order_id', $paymentData['order_id'])->first();
            $coingateOrderId = $Order->txn_id ?? null;

            if ($request->return_type == 'success' && $coingateOrderId) {
                $admin_settings = getAdminAllSetting();
                $coingateService = new CoingateService([
                    'auth_token' => $admin_settings['coingate_auth_token'] ?? '',
                    'environment' => $admin_settings['coingate_mode'] ?? 'sandbox'
                ]);

                $payment = $coingateService->getPayment($coingateOrderId);

                if ($payment && in_array($payment['status'], ['paid', 'confirming'])) {
                    $Order->payment_status = 'succeeded';
                    $Order->save();

                    $plan = Plan::find($paymentData['plan_id']);
                    $counter = [
                        'user_counter' => -1,
                        'storage_counter' => 0,
                    ];

                    $assignPlan = assignPlan($plan->id, $paymentData['duration'], $paymentData['user_module'], $counter, $paymentData['user_id']);

                    if ($assignPlan['is_success']) {
                        if ($paymentData['coupon_code']) {
                            $coupon = Coupon::where('code', $paymentData['coupon_code'])->first();
                            if ($coupon) {
                                recordCouponUsage($coupon->id, $paymentData['user_id'], $paymentData['order_id']);
                            }
                        }

                        $type = 'Subscription';
                        try {
                            CoingatePaymentStatus::dispatch($plan, $type, $Order);
                        } catch (\Exception $e) {
                            return redirect()->route('plans.index')->with('error', $e->getMessage());
                        }

                        Session::forget('coingate_payment_data');
                        return redirect()->route('plans.index')->with('success', __('The plan has been activated successfully.'));
                    } else {
                        return redirect()->route('plans.index')->with('error', __('The plan activation has failed.'));
                    }
                }
            }

            Session::forget('coingate_payment_data');
            return redirect()->route('plans.index')->with('error', __('The payment has failed.'));
        } catch (\Exception $exception) {
            Session::forget('coingate_payment_data');
            return redirect()->route('plans.index')->with('error', $exception->getMessage());
        }
    }
}
