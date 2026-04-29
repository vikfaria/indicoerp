<?php

namespace Workdo\Cashfree\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\User;
use App\Models\Plan;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Workdo\Cashfree\Services\CashfreeService;
use Workdo\Cashfree\Events\CashfreePaymentStatus;

class CashfreeController extends Controller
{
    public function planPayWithCashfree(Request $request)
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
            $cashfreeService = new CashfreeService([
                'app_id' => $admin_settings['cashfree_key'] ?? '',
                'secret_key' => $admin_settings['cashfree_secret'] ?? '',
                'mode' => $admin_settings['cashfree_mode'] ?? 'sandbox'
            ]);

            $paymentData = [
                'link_id' => $orderID,
                'amount' => $price,
                'currency' => $admin_currency,
                'purpose' => __('Plan: :plan_name - :duration', ['plan_name' => $plan->name, 'duration' => $duration]),
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'customer_phone' => $user->phone,
                'return_url' => route('payment.cashfree.status', [
                    'order_id' => $orderID,
                    'plan_id' => $plan->id,
                    'user_module' => $user_module,
                    'duration' => $duration,
                    'coupon_code' => $request->coupon_code,
                    'user_id' => $user->id,
                    'return_type' => 'success'
                ])
            ];

            $response = $cashfreeService->createPaymentLink($paymentData);

            $order = new Order();
            $order->order_id = $orderID;
            $order->name = $user->name;
            $order->email = $user->email;
            $order->card_number = null;
            $order->card_exp_month = null;
            $order->card_exp_year = null;
            $order->plan_name = !empty($plan->name) ? $plan->name : __('Basic Package');
            $order->plan_id = $plan->id;
            $order->price = !empty($price) ? $price : 0;
            $order->currency = $admin_currency;
            $order->txn_id = $response->link_id ?? null;
            $order->payment_type = 'Cashfree';
            $order->payment_status = 'pending';
            $order->receipt = null;
            $order->created_by = $user->id;
            $order->save();

            if (isset($response->link_url)) {
                return redirect($response->link_url);
            }

            return redirect()->route('plans.index')->with('error', __('The payment initialization has failed.'));
        } catch (\Exception $e) {
            return redirect()->route('plans.index')->with('error', $e->getMessage());
        }
    }

    public function planGetCashfreeStatus(Request $request)
    {
        $Order = Order::where('order_id', $request->order_id)->first();
        $linkId = $Order->txn_id ?? null;

        try {
            if ($request->return_type == 'success' && $linkId) {
                $admin_settings = getAdminAllSetting();
                $cashfreeService = new CashfreeService([
                    'app_id' => $admin_settings['cashfree_key'] ?? '',
                    'secret_key' => $admin_settings['cashfree_secret'] ?? '',
                    'mode' => $admin_settings['cashfree_mode'] ?? 'sandbox'
                ]);

                $paymentLink = $cashfreeService->getPaymentLink($linkId);

                if ($paymentLink && $paymentLink->link_status === 'PAID') {
                    $Order->payment_status = 'succeeded';
                    $Order->save();

                    $plan = Plan::find($request->plan_id);
                    $counter = [
                        'user_counter' => -1,
                        'storage_counter' => 0,
                    ];

                    $assignPlan = assignPlan($plan->id, $request->duration, $request->user_module, $counter, $request->user_id);

                    if ($assignPlan['is_success']) {
                        if ($request->coupon_code) {
                            $coupon = Coupon::where('code', $request->coupon_code)->first();
                            if ($coupon) {
                                recordCouponUsage($coupon->id, $request->user_id, $request->order_id);
                            }
                        }

                        $type = 'Subscription';
                        try {
                            CashfreePaymentStatus::dispatch($plan, $type, $Order);
                        } catch (\Exception $e) {
                            return redirect()->back()->with('error', $e->getMessage());
                        }

                        return redirect()->route('plans.index')->with('success', __('The plan has been activated successfully.'));
                    } else {
                        return redirect()->route('plans.index')->with('error', __('The plan activation has failed.'));
                    }
                }
            }

            return redirect()->route('plans.index')->with('error', __('The payment has failed.'));
        } catch (\Exception $exception) {
            return redirect()->route('plans.index')->with('error', $exception->getMessage());
        }
    }
}
