<?php

namespace Workdo\Aamarpay\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\User;
use App\Models\Plan;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Workdo\Aamarpay\Services\AamarpayService;
use Workdo\Aamarpay\Events\AamarpayPaymentStatus;

class AamarpayController extends Controller
{
    public function planPayWithAamarpay(Request $request)
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
                return redirect()->route('plans.index')->with('error', __('Plan activation failed. Please try again.'));
            }
        }

        try {
            $aamarpayService = new AamarpayService([
                'store_id' => $admin_settings['aamarpay_store_id'] ?? '',
                'signature_key' => $admin_settings['aamarpay_signature_key'] ?? '',
                'environment' => $admin_settings['aamarpay_mode'] ?? 'sandbox'
            ]);

            $data = [
                'order_id' => $orderID,
                'plan_id' => $plan->id,
                'duration' => $duration,
                'user_module' => $user_module,
                'user_id' => $request->user_id,
                'counter' => $counter,
                'coupon_code' => $request->coupon_code
            ];

            $paymentData = [
                'amount' => $price,
                'currency' => $admin_currency,
                'tran_id' => $orderID,
                'desc' => __('Plan: ') . $plan->name . ' - ' . $duration,
                'cus_name' => $user->name,
                'cus_email' => $user->email,
                'success_url' => route('payment.aamarpay.status', array_merge($data, ['return_type' => 'success'])),
                'fail_url' => route('payment.aamarpay.status', array_merge($data, ['return_type' => 'fail'])),
                'cancel_url' => route('payment.aamarpay.status', array_merge($data, ['return_type' => 'cancel']))
            ];


            $response = $aamarpayService->createPayment($paymentData);

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
            $order->payment_type = 'Aamarpay';
            $order->payment_status = 'pending';
            $order->receipt = json_encode([
                'duration' => $duration,
                'user_id' => $request->user_id,
                'counter' => $counter,
                'coupon_code' => $request->coupon_code
            ]);
            $order->created_by = $user->id;
            $order->save();

            if (isset($response['payment_url'])) {
                return redirect($response['payment_url']);
            }

            return redirect()->route('plans.index')->with('error', __('Aamarpay payment initialization failed.'));
        } catch (\Exception $e) {
            return redirect()->route('plans.index')->with('error', $e->getMessage());
        }
    }

    public function planGetAamarpayStatus(Request $request)
    {
        try {
            if ($request->return_type == 'success') {
                $Order = Order::where('order_id', $request->order_id)->first();
                if (!$Order) {
                    return redirect()->route('plans.index')->with('error', __('Order not found for payment verification.'));
                }

                $plan = Plan::find($Order->plan_id);
                $counter = [
                    'user_counter' => -1,
                    'storage_counter' => 0,
                ];

                $assignPlan = assignPlan($plan->id, $request->duration, $request->user_module ?? '', $counter, $Order->created_by);

                if ($assignPlan['is_success']) {
                    if ($request->coupon_code) {
                        $coupon = Coupon::where('code', $request->coupon_code)->first();
                        if ($coupon) {
                            recordCouponUsage($coupon->id, $Order->created_by, $request->order_id);
                        }
                    }

                    $type = 'Subscription';
                    try {
                        AamarpayPaymentStatus::dispatch($plan, $type, $Order);
                    } catch (\Exception $e) {
                        return redirect()->back()->with('error', $e->getMessage());
                    }

                    $Order->payment_status = 'succeeded';
                    $Order->save();

                    return redirect()->route('plans.index')->with('success', __('The plan payment has been completed and activated successfully.'));
                } else {
                    return redirect()->route('plans.index')->with('error', __('Plan activation failed. Please try again.'));
                }
            }

            return redirect()->route('plans.index')->with('error', __('Your Aamarpay payment has failed!'));
        } catch (\Exception $exception) {
            return redirect()->route('plans.index')->with('error', $exception->getMessage());
        }
    }
}
