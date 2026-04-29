<?php

namespace Workdo\Razorpay\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Plan;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;
use Workdo\Razorpay\Events\RazorpayPaymentStatus;
use Razorpay\Api\Api;
use Inertia\Inertia;

class RazorpayController extends Controller
{
    public function planPayWithRazorpay(Request $request)
    {
        $plan = Plan::find($request->plan_id);
        $user = User::find($request->user_id);
        $admin_settings = getAdminAllSetting();

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

        if ($plan) {
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
            $paymentData = [
                'success' => true,
                'key' => $admin_settings['razorpay_public_key'],
                'amount' => $price * 100,
                'currency' => $admin_settings['defaultCurrency'] ?? '',
                'name' => config('app.name', 'WorkDo'),
                'description' => $plan->name . ' Plan',
                'prefill' => [
                    'name' => Auth::user()->name,
                    'email' => Auth::user()->email,
                ],
                'theme' => [
                    'color' => '#3399cc'
                ],
                'callback_url' => route('payment.razorpay.status')
            ];

            return Inertia::render('Razorpay/RazorpayPayment', [
                'paymentData' => $paymentData,
                'planId' => $plan->id,
                'userId' => Auth::id(),
                'duration' => $duration,
                'userModule' => $user_module,
                'couponCode' => $request->coupon_code,
                'counter' => $counter,
                'userSlug' => null
            ]);
        } else {
            return redirect()->route('plans.index')->with('error', __('Plan is deleted.'));
        }
    }

    protected function verify($pay_id)
    {
        try {
            $admin_settings = getAdminAllSetting();
            $public_key = $admin_settings['razorpay_public_key'];
            $secret_key = $admin_settings['razorpay_secret_key'];
            $currency = $admin_settings['defaultCurrency'] ?? '';

            $api = new Api($public_key, $secret_key);
            $payment = $api->payment->fetch($pay_id);

            if ($payment->status === 'authorized') {
                $payment->capture(['amount' => $payment->amount, 'currency' => $currency]);
                return true;
            }

            return $payment->status === 'captured';
        } catch (\Exception $e) {
            return false;
        }
    }

    public function planGetRazorpayStatus(Request $request)
    {
        try {
            if ($request->razorpay_payment_id) {
                if ($this->verify($request->razorpay_payment_id)) {
                    $plan = Plan::find($request->plan_id);
                    $user = User::find($request->user_id);
                    $orderID = strtoupper(str_pad(mt_rand(1, 999999999999), 12, '0', STR_PAD_LEFT));

                    $order = new Order();
                    $order->order_id = $orderID;
                    $order->name = $user->name ?? '';
                    $order->email = $user->email ?? '';
                    $order->card_number = null;
                    $order->card_exp_month = null;
                    $order->card_exp_year = null;
                    $order->plan_name = $plan->name ?? 'Basic Package';
                    $order->plan_id = $plan->id;
                    $order->price = $request->amount ?? 0;
                    $order->currency = admin_setting('defaultCurrency') ?? '';
                    $order->txn_id = $request->razorpay_payment_id;
                    $order->payment_type = 'Razorpay';
                    $order->payment_status = 'succeeded';
                    $order->receipt = null;
                    $order->created_by = $user->id;
                    $order->save();
                    
                    $type = 'Subscription';
                    $counter = [
                        'user_counter' => -1,
                        'storage_counter' => 0,
                    ];

                    $assignPlan = assignPlan($plan->id, $request->duration, $request->user_module, $counter, $request->user_id);
                    if ($assignPlan['is_success']) {
                        if ($request->coupon_code) {
                            $coupon = Coupon::where('code', $request->coupon_code)->first();
                            if ($coupon) {
                                recordCouponUsage($coupon->id, $request->user_id, $orderID);
                            }
                        }
                        $type = 'Subscription';

                        try {
                            RazorpayPaymentStatus::dispatch($plan, $type, $order);
                        } catch (\Throwable $th) {
                            return back()->with('error', $th->getMessage());
                        }

                        return redirect()->route('plans.index')->with('success', __('The plan has been activated successfully.'));
                    } else {
                        return redirect()->route('plans.index')->with('error', __('Something went wrong, Please try again,'));
                    }
                } else {
                    return redirect()->route('plans.index')->with('error', __('Payment verification failed!'));
                }
            } else {
                return redirect()->route('plans.index')->with('error', __('Payment was cancelled or failed!'));
            }
        } catch (\Exception $exception) {
            return redirect()->route('plans.index')->with('error', $exception->getMessage());
        }
    }
}
