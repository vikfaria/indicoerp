<?php

namespace Workdo\Stripe\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Plan;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Workdo\Stripe\Events\StripePaymentStatus;
use Inertia\Inertia;
use Stripe\StripeClient;
class StripeController extends Controller
{
    public function planPayWithStripe(Request $request)
    {
        $plan = Plan::find($request->plan_id);
        $user = User::find($request->user_id);
        $admin_settings = getAdminAllSetting();
        $admin_currancy = !empty($admin_settings['defaultCurrency']) ? $admin_settings['defaultCurrency'] : '';
        $supported_currencies = ['EUR', 'GBP', 'USD', 'CAD', 'AUD', 'JPY', 'INR', 'CNY', 'SGD', 'HKD', 'BRL'];

        if (!in_array($admin_currancy, $supported_currencies)) {
            return redirect()->back()->with('error', __('Currency is not supported.'));
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

        $stripe_session = '';
        $orderID = strtoupper(substr(uniqid(), -12));

        if ($plan) {
            /* Check for code usage */
            $plan->discounted_price = false;
            $payment_frequency = $plan->duration;
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
                    return redirect()->route('plans.index')->with('success', __('Plan activated Successfully!'));
                } else {
                    return redirect()->route('plans.index')->with('error', __('Something went wrong, Please try again,'));
                }
            }

            try {

                $payment_plan = $duration;
                $payment_type = 'onetime';
                /* Payment details */
                $code = '';

                /* Final price */
                $stripe_formatted_price = in_array(
                    $admin_currancy,
                    [
                        'MGA',
                        'BIF',
                        'CLP',
                        'PYG',
                        'DJF',
                        'RWF',
                        'GNF',
                        'UGX',
                        'JPY',
                        'VND',
                        'VUV',
                        'XAF',
                        'KMF',
                        'KRW',
                        'XOF',
                        'XPF',
                        'BRL'
                    ]
                ) ? number_format($price, 2, '.', '') : number_format($price, 2, '.', '') * 100;
                $return_url_parameters = function ($return_type) use ($payment_frequency, $payment_type) {
                    return '&return_type=' . $return_type . '&payment_processor=stripe&payment_frequency=' . $payment_frequency . '&payment_type=' . $payment_type;
                };
                /* Initiate Stripe */
                $stripe_session = $this->createStripeSession([
                    'api_key' => $admin_settings['stripe_secret'] ?? '',
                    'currency' => $admin_currancy,
                    'amount' => $stripe_formatted_price,
                    'product_name' => $plan->name ?? 'Basic Package',
                    'description' => $payment_plan,
                    'metadata' => [
                        'user_id' => $user->id,
                        'package_id' => $plan->id,
                        'payment_frequency' => $payment_frequency,
                        'code' => $code,
                    ],
                    'success_url' => route('payment.stripe.status', [
                        'order_id' => $orderID,
                        'plan_id' => $plan->id,
                        'user_module' => $user_module,
                        'duration' => $duration,
                        'counter' => $counter,
                        'coupon_code' => $request->coupon_code,
                        'user_id' => $user->id,
                        $return_url_parameters('success'),
                    ]),
                    'cancel_url' => route('payment.stripe.status', [
                        'plan_id' => $orderID,
                        'order_id' => $plan->id,
                        $return_url_parameters('cancel'),
                    ]),
                ]);

                $order = new Order();
                $order->order_id = $orderID;
                $order->name = $user->name ?? '';
                $order->email = $user->email ?? '';
                $order->card_number = null;
                $order->card_exp_month = null;
                $order->card_exp_year = null;
                $order->plan_name = !empty($plan->name) ? $plan->name : 'Basic Package';
                $order->plan_id = $plan->id;
                $order->price = !empty($price) ? $price : 0;
                $order->currency = $admin_currancy;
                $order->txn_id = '';
                $order->payment_type = 'Stripe';
                $order->payment_status = 'pending';
                $order->receipt = null;
                $order->created_by = $user->id;
                $order->save();

                Session::put('stripe_session', $stripe_session);
                $stripe_session = $stripe_session ?? false;
            } catch (\Exception $e) {
                return redirect()->route('plans.index')->with('error', $e->getMessage());
            }
            return Inertia::render('Stripe/StripePayment', [
                'stripe_session' => $stripe_session,
                'stripe_key' => $admin_settings['stripe_key'] ?? ''
            ]);
        } else {
            return redirect()->route('plans.index')->with('error', __('The Plan has been deleted.'));
        }
    }

    public function planGetStripeStatus(Request $request)
    {
        $admin_settings = getAdminAllSetting();
        try {
            $stripe = new StripeClient(!empty($admin_settings['stripe_secret']) ? $admin_settings['stripe_secret'] : '');
            $stripe_session = Session::get('stripe_session');
            if ($stripe_session && isset($stripe_session->payment_intent)) {
                $paymentIntents = $stripe->paymentIntents->retrieve(
                    $stripe_session->payment_intent,
                    []
                );
                $receipt_url = $paymentIntents->charges->data[0]->receipt_url;
            } else {
                $receipt_url = "";
            }
        } catch (\Exception $exception) {
            $receipt_url = "";
        }
        Session::forget('stripe_session');
        try {
            if ($request->return_type == 'success') {
                $Order = Order::where('order_id', $request->order_id)->first();
                $Order->payment_status = 'succeeded';
                $Order->receipt = $receipt_url;
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
                        StripePaymentStatus::dispatch($plan, $type, $Order);
                    } catch (\Exception $e) {
                        return redirect()->back()->with('error', $e->getMessage());
                    }

                    return redirect()->route('plans.index')->with('success', __('Plan activated Successfully!'));
                } else {
                    return redirect()->route('plans.index')->with('error', __('Something went wrong, Please try again,'));
                }
            } else {
                return redirect()->route('plans.index')->with('error', __('Your Payment has failed!'));
            }
        } catch (\Exception $exception) {
            return redirect()->route('plans.index')->with('error', $exception->getMessage());
        }
    }

    /**
     * Create Stripe checkout session - Dynamic for both plans and invoices
     */
    private function createStripeSession($params)
    {
        $api_key = $params['api_key'] ??
            $params['admin_settings']['stripe_secret'] ??
            ($params['company_settings'] ? company_setting('stripe_secret', $params['user_id'] ?? null) : null) ??
            '';
        \Stripe\Stripe::setApiKey($api_key);

        // Build session data
        $session_data = [
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $params['currency'],
                    'unit_amount' => (int) $params['amount'],
                    'product_data' => [
                        'name' => $params['product_name'],
                        'description' => $params['description'] ?? '',
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'metadata' => $params['metadata'],
            'success_url' => $params['success_url'],
            'cancel_url' => $params['cancel_url'],
        ];

        return \Stripe\Checkout\Session::create($session_data);
    }

}
