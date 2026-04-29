<?php

namespace Workdo\Toyyibpay\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\User;
use App\Models\Plan;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Workdo\Toyyibpay\Services\ToyyibpayService;
use Workdo\Toyyibpay\Events\ToyyibpayPaymentStatus;

class ToyyibpayController extends Controller
{
    public function planPayWithToyyibpay(Request $request)
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
            'storage_limit' => 0,
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
                return redirect()->route('plans.index')->with('error', __('Something went wrong, Please try again,'));
            }
        }

        try {
            $toyyibpayService = new ToyyibpayService([
                'user_secret_key' => $admin_settings['toyyibpay_secret_key'] ?? '',
                'category_code' => $admin_settings['toyyibpay_category_code'] ?? ''
            ]);

            $billData = [
                'billName' => $plan->name,
                'billDescription' => "Payment for {$plan->name} plan",
                'billAmount' => $price,
                'billCurrency' => $admin_currency,
                'billReturnUrl' => route('payment.toyyibpay.status', ['order_id' => $orderID, 'return_type' => 'success']),
                'billCallbackUrl' => route('payment.toyyibpay.status', ['order_id' => $orderID, 'return_type' => 'callback']),
                'billTo' => $user->name,
                'billEmail' => $user->email,
                'billPhone' => $user->mobile_no ?? '0000000000',
            ];

            Session::put($orderID, [
                'plan_id' => $plan->id,
                'duration' => $duration,
                'user_module' => $user_module,
                'user_id' => $request->user_id,
                'counter' => $counter,
                'coupon_code' => $request->coupon_code
            ]);

            $response = $toyyibpayService->createBill($billData);

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
            $order->currency       = $admin_currency;
            $order->txn_id         = $response['BillCode'] ?? null;
            $order->payment_type   = 'Toyyibpay';
            $order->payment_status = 'pending';
            $order->receipt        = null;
            $order->created_by     = $user->id;
            $order->save();

            if (isset($response['BillCode'])) {
                $paymentUrl = $toyyibpayService->getPaymentUrl($response['BillCode']);
                return redirect($paymentUrl);
            }

            return redirect()->route('plans.index')->with('error', __('Payment initialization failed.'));
        } catch (\Exception $e) {
            dd($e);
            return redirect()->route('plans.index')->with('error', $e->getMessage());
        }
    }

    public function planGetToyyibpayStatus(Request $request)
    {
        $Order = Order::where('order_id', $request->order_id)->first();
        $sessionData = Session::get($request->order_id);
        if ($sessionData) {
            $request->merge($sessionData);
        }
        Session::forget($request->order_id);

        try {
            // Check payment status based on status_id from Toyyibpay callback
            // status_id: 1 = success, 2 = pending, 3 = failed/cancelled
            if ($request->status_id == '1') {
                    $Order->payment_status = 'succeeded';
                    $Order->save();

                $plan = Plan::find($request->plan_id);
                $counter = [
                    'user_counter' => -1,
                    'storage_limit' => 0,
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
                        ToyyibpayPaymentStatus::dispatch($plan, $type, $Order);
                    } catch (\Exception $e) {
                        return redirect()->route('plans.index')->with('error', $e->getMessage());
                    }

                    return redirect()->route('plans.index')->with('success', __('The plan has been activated successfully.'));
                } else {
                    return redirect()->route('plans.index')->with('error', __('Something went wrong, Please try again,'));
                }
            }
            return redirect()->route('plans.index')->with('error', __('Payment was cancelled or failed.'));
        } catch (\Exception $exception) {
            return redirect()->route('plans.index')->with('error', $exception->getMessage());
        }
    }
}
