<?php

namespace Workdo\AuthorizeNet\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Plan;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Workdo\AuthorizeNet\Services\AuthorizeNetService;
use Workdo\AuthorizeNet\Events\AuthorizeNetPaymentStatus;

class AuthorizeNetController extends Controller
{
    public function planPayWithAuthorizeNet(Request $request)
    {
        $plan = Plan::find($request->plan_id);
        $user = User::find($request->user_id);
        $admin_settings = getAdminAllSetting();
        $admin_currancy = !empty($admin_settings['defaultCurrency']) ? $admin_settings['defaultCurrency'] : '';

        if (!$plan) {
            return redirect()->route('plans.index')->with('error', __('The plan has been deleted.'));
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
                return redirect()->route('plans.index')->with('error', __('Something went wrong. Please try again.'));
            }
        }

        try {
            $orderID = strtoupper(substr(uniqid(), -12));

            // Create order first
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
            $order->currency = $admin_currancy;
            $order->txn_id = '';
            $order->payment_type = 'AuthorizeNet';
            $order->payment_status = 'pending';
            $order->receipt = null;
            $order->created_by = $user->id;
            $order->save();

            $paymentData = [
                'order_id' => $orderID,
                'plan_id' => $plan->id,
                'user_id' => $user->id,
                'amount' => $price,
                'currency' => $admin_currancy,
                'plan_name' => $plan->name ?? 'Basic Package',
                'description' => __('Plan Purchase: ') . ($plan->name ?? __('Basic Package')),
                'duration' => $duration,
                'user_module' => $user_module,
                'counter' => $counter,
                'coupon_code' => $request->coupon_code,
                'redirect_route' => 'payment.authorizenet.status',
                'back_route' => 'plans.index',
                'created_by' => creatorId(),
                'config' => [
                    'api_login_id' => $admin_settings['authorizenet_merchant_login_id'] ?? '',
                    'transaction_key' => $admin_settings['authorizenet_merchant_transaction_key'] ?? '',
                    'mode' => $admin_settings['authorizenet_mode'] ?? 'sandbox'
                ]
            ];

            $authorizeNetService = new AuthorizeNetService();
            return $authorizeNetService->redirectToCheckout($paymentData, $user);
        } catch (\Exception $e) {
            return redirect()->route('plans.index')->with('error', $e->getMessage());
        }
    }

    public function planGetAuthorizeNetStatus(Request $request)
    {
        try {
            if ($request->success) {
                $Order = Order::where('order_id', $request->order_id)->first();
                if (!$Order) {
                    return redirect()->route('plans.index')->with('error', __('The order has not been found.'));
                }

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
                        AuthorizeNetPaymentStatus::dispatch($plan, $type, $Order);
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
