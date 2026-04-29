<?php

namespace Workdo\Xendit\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Models\Order;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Workdo\Xendit\Services\XenditService;
use Workdo\Xendit\Events\XenditPaymentStatus;

class XenditController extends Controller
{
    public function planPayWithXendit(Request $request)
    {
        $plan = Plan::find($request->plan_id);
        $user = User::find($request->user_id);
        $admin_settings = getAdminAllSetting();
        $xendit_key = isset($admin_settings['xendit_key']) ? $admin_settings['xendit_key'] : '';
        $admin_currancy = !empty($admin_settings['defaultCurrency']) ? $admin_settings['defaultCurrency'] : '';
        $supported_currencies = ['IDR', 'PHP', 'USD', 'SGD', 'MYR'];

        if (!in_array($admin_currancy, $supported_currencies)) {
            return redirect()->route('plans.index')->with('error', __('Currency is not supported.'));
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
            'storage_limit' => 0,
        ];

        $xendit_session = '';
        $orderID = strtoupper(substr(uniqid(), -12));

        if ($plan) {
            $plan->discounted_price = false;
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
                $response = [
                    'orderId' => $orderID,
                    'user' => $user->id,
                    'get_amount' => $price,
                    'plan' => $plan->id,
                    'duration' => $duration,
                    'user_module' => $user_module,
                    'counter' => $counter,
                    'currency' => $admin_currancy,
                    'coupon_code' => $request->coupon_code,
                    'return_type' => 'success'
                ];

                XenditService::setApiKey($xendit_key);
                $params = [
                    'external_id' => $orderID,
                    'payer_email' => $user->email,
                    'description' => 'Payment for order ' . $orderID,
                    'amount' => $price,
                    'callback_url' => route('payment.xendit.status'),
                    'success_redirect_url' => route('payment.xendit.status', $response),
                    'failure_redirect_url' => route('plans.index'),
                ];

                $xendit_session = XenditService::createInvoice($params);

                $order                 = new Order();
                $order->order_id       = $orderID;
                $order->name           = $user->name;
                $order->email          = $user->email;
                $order->card_number    = null;
                $order->card_exp_month = null;
                $order->card_exp_year  = null;
                $order->plan_name      = !empty($plan->name) ? $plan->name : 'Basic Package';
                $order->plan_id        = $plan->id;
                $order->price          = !empty($price) ? $price : 0;
                $order->currency       = $admin_currancy;
                $order->txn_id         = '';
                $order->payment_type   = 'Xendit';
                $order->payment_status = 'pending';
                $order->receipt        = null;
                $order->created_by     = $user->id;
                $order->save();

                Session::put('xendit_session', $xendit_session);
                $xendit_session = $xendit_session ?? false;

                return Inertia::render('Xendit/XenditPayment', [
                'xendit_session' => $xendit_session,
                    'xendit_key' => $xendit_key
                ]);

            } catch (\Exception $e) {
                return redirect()->route('plans.index')->with('error', $e->getMessage());
            }
        } else {
            return redirect()->route('plans.index')->with('error', __('The plan has been deleted.'));
        }
    }

    public function planGetXenditStatus(Request $request)
    {
        $admin_settings = getAdminAllSetting();
        $xendit_key = $admin_settings['xendit_key'] ?? '';
        XenditService::setApiKey($xendit_key);

        $xendit_session = Session::get('xendit_session');
        if ($xendit_session && isset($xendit_session['id'])) {
            $getInvoice = XenditService::getInvoice($xendit_session['id']);
        }

        Session::forget('xendit_session');

        try {
            if ($request->return_type == 'success' || (isset($getInvoice) && $getInvoice['status'] == 'PAID')) {
                $Order = Order::where('order_id', $request->orderId ?? $request->order_id)->first();
                if ($Order) {
                    $Order->payment_status = 'succeeded';
                    $Order->receipt = $getInvoice['invoice_url'] ?? '';
                    $Order->save();

                    $plan = Plan::find($request->plan ?? $request->plan_id);
                    $counter = [
                        'user_counter' => -1,
                        'storage_counter' => 0,
                    ];
                    $assignPlan = assignPlan($plan->id, $request->duration, $request->user_module, $counter, $request->user ?? $request->user_id);
                    
                    if ($assignPlan['is_success']) {
                        if ($request->coupon_code) {
                            $coupon = Coupon::where('code', $request->coupon_code)->first();
                            if ($coupon) {
                                recordCouponUsage($coupon->id, $request->user ?? $request->user_id, $request->orderId ?? $request->order_id);
                            }
                        }
                        $type = 'Subscription';
                        try {
                            XenditPaymentStatus::dispatch($plan, $type, $Order);
                        } catch (\Exception $e) {
                            return redirect()->route('plans.index')->with('error', $e->getMessage());
                        }

                        $value = Session::get('user-module-selection');
                        if (!empty($value)) {
                            Session::forget('user-module-selection');
                        }
                        return redirect()->route('plans.index')->with('success', __('The plan has been activated successfully.'));
                    } else {
                        return redirect()->route('plans.index')->with('error', __('Something went wrong, Please try again,'));
                    }
                } else {
                    return redirect()->route('plans.index')->with('error', __('Order not found.'));
                }
            } else {
                return redirect()->route('plans.index')->with('error', __('Your Payment has failed!'));
            }
        } catch (\Exception $exception) {
            return redirect()->route('plans.index')->with('error', $exception->getMessage());
        }
    }
}
