<?php

namespace Workdo\Fedapay\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Workdo\Fedapay\Services\FedapayPaymentService;
use Workdo\Fedapay\Events\FedapayPaymentStatus;

class FedapayController extends Controller
{
    public function planPayWithFedapay(Request $request)
    {
        try {
            $plan = Plan::find($request->plan_id);
            $user = User::find($request->user_id);

            if ($plan && $user) {
            $admin_settings = getAdminAllSetting();
            $admin_currency = !empty($admin_settings['defaultCurrency']) ? $admin_settings['defaultCurrency'] : '';

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
                    }
                    return redirect()->route('plans.index')->with('error', __('Something went wrong, Please try again,'));
                }

                $fedapayService = FedapayPaymentService::createFromSettings();

                $response = $fedapayService->initializeTransaction([
                    'name' => $user->name,
                    'email' => $user->email,
                    'url' => route('payment.fedapay.status', ['plan_id' => $plan->id]),
                    'price' => $price,
                    'order_id' => $orderID,
                    'product' => __('Plan') . ' - ' . $plan->name,
                    'currency' => $admin_currency,
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
                    $order->plan_name = !empty($plan->name) ? $plan->name : __('Basic Package');
                    $order->plan_id = $plan->id;
                    $order->price = !empty($price) ? $price : 0;
                    $order->currency = $admin_currency;
                    $order->txn_id = $orderID;
                    $order->payment_type = 'Fedapay';
                    $order->payment_status = 'pending';
                    $order->created_by = $user->id;
                    $order->save();
                    return redirect($response->url);
                }

                return redirect()->route('plans.index')->with('error', $response->message ?? __('Payment initialization failed.'));
            }

            return redirect()->route('plans.index')->with('error', __('The plan has been deleted.'));
        } catch (\Exception $e) {
            return redirect()->route('plans.index')->with('error', __($e->getMessage()));
        }
    }

    public function planGetFedapayStatus(Request $request, $plan_id)
    {
        try {
            $orderId = $request->input('order_id');
            $session = Session::get($orderId);
            Session::forget($orderId);
            $data = $session['other'] ?? null;

            $transactionId = $session['transaction_id'] ?? null;

            if ($orderId && $data) {
                $fedapayService = FedapayPaymentService::createFromSettings();
                $result = $fedapayService->verifyTransaction($transactionId);

                if ($fedapayService->isPaymentSuccessful($result)) {
                    $order = Order::where('order_id', $orderId)->first();
                    if ($order) {
                        $order->payment_status = 'succeeded';
                        $order->save();
                    }

                    $plan = Plan::find($plan_id);
                    $counter = [
                        'user_counter' => -1,
                        'storage_counter' => 0,
                    ];
                    
                    $assignPlan = assignPlan($plan->id, $data['duration'], $data['user_module'] ?? '', $counter, $data['user_id']);
                    
                    if ($assignPlan['is_success']) {
                        if (!empty($data['coupon_code'])) {
                            $coupon = Coupon::where('code', $data['coupon_code'])->first();
                            if ($coupon) {
                                recordCouponUsage($coupon->id, $data['user_id'], $orderId);
                            }
                        }
                        
                        $type = 'Subscription';

                        try {
                            FedapayPaymentStatus::dispatch($plan, $type, $order);
                        } catch (\Exception $exception) {
                        }

                        return redirect()->route('plans.index')->with('success', __('The plan has been activated successfully.'));
                    }
                    
                    return redirect()->route('plans.index')->with('error', __('Something went wrong, Please try again'));
                }

                return redirect()->route('plans.index')->with('error', __('Payment was cancelled or failed.'));
            }

            return redirect()->route('plans.index')->with('error', __('Payment was cancelled or failed.'));
        } catch (\Exception $exception) {
            return redirect()->route('plans.index')->with('error', $exception->getMessage());
        }
    }
}
