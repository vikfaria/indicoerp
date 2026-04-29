<?php

namespace Workdo\CinetPay\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\User;
use App\Models\Plan;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Workdo\CinetPay\Services\CinetPayService;
use Workdo\CinetPay\Events\CinetPayPaymentStatus;

class CinetPayController extends Controller
{
    // Plan Payment
    public function planPayWithCinetPay(Request $request)
    {
        $plan = Plan::find($request->plan_id);
        $user = User::find($request->user_id);
        $admin_settings = getAdminAllSetting();
        $admin_currency = !empty($admin_settings['defaultCurrency']) ? $admin_settings['defaultCurrency'] : '';

        if ($plan) {

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
                $validation = applyCouponDiscount($request->coupon_code, $price, Auth::id());
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
                if (empty($admin_settings['cinetpay_enabled']) || $admin_settings['cinetpay_enabled'] !== 'on') {
                    return redirect()->route('plans.index')->with('error', __('CinetPay is not enabled. Please enable CinetPay in settings.'));
                }

                if (empty($admin_settings['cinetpay_api_key']) || empty($admin_settings['cinetpay_site_id'])) {
                    return redirect()->route('plans.index')->with('error', __('CinetPay API Key or Site ID is missing. Please configure CinetPay settings.'));
                }

                $cinetPayService = new CinetPayService([
                    'cinetpay_api_key' => $admin_settings['cinetpay_api_key'],
                    'cinetpay_site_id' => $admin_settings['cinetpay_site_id']
                ]);

                $transactionId = $cinetPayService->generateTransactionId();

                $statusUrl = route('payment.cinetpay.status', [
                    'order_id' => $orderID,
                    'plan_id' => $plan->id,
                    'user_module' => $user_module,
                    'duration' => $duration,
                    'counter' => $counter,
                    'coupon_code' => $request->coupon_code,
                    'user_id' => $user->id
                ]);

                // Prepare customer data with fallbacks
                $customerData = $cinetPayService->prepareCustomerData([
                    'name' => $user->name ?? '',
                    'email' => $user->email ?? '',
                    'phone' => $user->phone ?? '',
                    'address' => $user->address ?? '',
                    'city' => $user->city ?? '',
                    'country' => $user->country ?? '',
                    'state' => $user->state ?? '',
                    'zip_code' => $user->zip_code ?? '',
                ]);

                $paymentData = array_merge([
                    'transaction_id' => $transactionId,
                    'amount' => $cinetPayService->formatAmount($price, $admin_currency),
                    'currency' => $admin_currency,
                    'description' => "Plan: {$plan->name} - {$duration}",
                    'return_url' => $statusUrl,
                    'notify_url' => $statusUrl,
                ], $customerData);

                $response = $cinetPayService->checkout($paymentData);
                if (isset($response->data->payment_url)) {

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
                    $order->txn_id = $transactionId;
                    $order->payment_type = 'CinetPay';
                    $order->payment_status = 'pending';
                    $order->receipt = null;
                    $order->created_by = $user->id;
                    $order->save();

                    return redirect()->away($response->data->payment_url);
                }

                return redirect()->route('plans.index')->with('error', __('Payment initialization failed.'));
            } catch (\Exception $e) {
                return redirect()->route('plans.index')->with('error', $e->getMessage());
            }
        }

        return redirect()->route('plans.index')->with('error', __('The plan has been deleted.'));
    }

    public function planGetCinetPayStatus(Request $request)
    {
        try {
            $order = Order::where('order_id', $request->order_id)->first();
            $transactionId = $order->txn_id ?? null;
            if ($transactionId && $order) {

                $cinetPayService = new CinetPayService([
                    'cinetpay_api_key' => admin_setting('cinetpay_api_key'),
                    'cinetpay_site_id' => admin_setting('cinetpay_site_id')
                ]);

                $transaction = $cinetPayService->getTransaction($transactionId);

                if ($transaction && isset($transaction->data) && $transaction->data->status === 'ACCEPTED') {

                    $order->payment_status = 'succeeded';
                    $order->save();

                    $plan = Plan::find($request->plan_id);
                    if ($plan) {
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
                                CinetPayPaymentStatus::dispatch($plan, $type, $order);
                            } catch (\Exception $e) {
                            }

                            return redirect()->route('plans.index')->with('success', __('The plan has been activated successfully.'));
                        }
                        return redirect()->route('plans.index')->with('error', __('Something went wrong. Please try again.'));
                    }

                    return redirect()->route('plans.index')->with('error', __('The plan has been deleted.'));
                }
            }

            return redirect()->route('plans.index')->with('error', __('The payment has failed.'));
        } catch (\Exception $exception) {
            return redirect()->route('plans.index')->with('error', $exception->getMessage());
        }
    }
}
