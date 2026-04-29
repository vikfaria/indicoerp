<?php

namespace Workdo\Midtrans\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\User;
use App\Models\Plan;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Workdo\Midtrans\Services\MidtransPaymentService;
use Workdo\Midtrans\Events\MidtransPaymentStatus;

class MidtransController extends Controller
{
    public function planPayWithMidtrans(Request $request)
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
            $midtransService = new MidtransPaymentService([
                'midtrans_secret_key' => $admin_settings['midtrans_secret_key'] ?? '',
                'midtrans_mode' => $admin_settings['midtrans_mode'] ?? 'sandbox'
            ]);

            $transactionData = [
                'order_id' => $orderID,
                'gross_amount' => $price * 100,
                'currency' => $admin_currency,
                'description' => "Plan: {$plan->name} - {$duration}",
                'customer' => [
                    'first_name' => $user->name,
                    'email' => $user->email
                ],
                'finish_url' => route('payment.midtrans.status', [
                    'order_id' => $orderID,
                    'return_type' => 'success'
                ]),
                'unfinish_url' => route('payment.midtrans.status', [
                    'order_id' => $orderID,
                    'return_type' => 'unfinish'
                ]),
                'error_url' => route('payment.midtrans.status', [
                    'order_id' => $orderID,
                    'return_type' => 'error'
                ])
            ];

            Session::put($orderID, [
                'plan_id' => $plan->id,
                'duration' => $duration,
                'user_module' => $user_module,
                'user_id' => $request->user_id,
                'counter' => $counter,
                'coupon_code' => $request->coupon_code
            ]);

            $response = $midtransService->createTransaction($transactionData);

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
            $order->txn_id         = $response->token ?? null;
            $order->payment_type   = 'Midtrans';
            $order->payment_status = 'pending';
            $order->receipt        = null;
            $order->created_by     = $user->id;
            $order->save();

            if (isset($response->redirect_url)) {
                return redirect($response->redirect_url);
            }

            return redirect()->route('plans.index')->with('error', __('Payment initialization failed.'));
        } catch (\Exception $e) {
            return redirect()->route('plans.index')->with('error', $e->getMessage());
        }
    }

    public function planGetMidtransStatus(Request $request)
    {
        $Order = Order::where('order_id', $request->order_id)->first();
        $sessionData = Session::get($request->order_id);
        if ($sessionData) {
            $request->merge($sessionData);
        }
        Session::forget($request->order_id);
        
        try {
            if ($request->return_type == 'success') {
                $admin_settings = getAdminAllSetting();
                $midtransService = new MidtransPaymentService([
                    'midtrans_secret_key' => $admin_settings['midtrans_secret_key'] ?? '',
                    'midtrans_mode' => $admin_settings['midtrans_mode'] ?? 'sandbox'
                ]);

                try {
                    $transaction = $midtransService->getTransactionStatus($request->order_id);
                } catch (\Exception $e) {
                    return redirect()->route('plans.index')->with('error', __('Payment was not completed.'));
                }

                if ($transaction && $transaction->status_code == '200' && in_array($transaction->transaction_status, ['capture', 'settlement'])) {
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
                            MidtransPaymentStatus::dispatch($plan, $type, $Order);
                        } catch (\Exception $e) {
                            return redirect()->route('plans.index')->with('error', $e->getMessage());
                        }

                        return redirect()->route('plans.index')->with('success', __('The plan has been activated successfully.'));
                    } else {
                        return redirect()->route('plans.index')->with('error', __('Something went wrong, Please try again,'));
                    }
                }
            }
            return redirect()->route('plans.index')->with('error', __('Your Payment has failed!'));
        } catch (\Exception $exception) {
            return redirect()->route('plans.index')->with('error', $exception->getMessage());
        }
    }
}
