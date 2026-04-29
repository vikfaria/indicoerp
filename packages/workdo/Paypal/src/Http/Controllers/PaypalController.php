<?php

namespace Workdo\Paypal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Plan;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Workdo\Paypal\Events\PaypalPaymentStatus;
use Inertia\Inertia;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PaypalController extends Controller
{
    /**
     * Create PayPal order with reusable parameters
     */
    private function createPaypalOrder($provider, $routeParams, $currency, $price, $routeName)
    {
        return $provider->createOrder([
            "intent" => "CAPTURE",
            "application_context" => [
                "return_url" => route($routeName, $routeParams),
                "cancel_url" => route($routeName, $routeParams),
            ],
            "purchase_units" => [
                0 => [
                    "amount" => [
                        "currency_code" => $currency,
                        "value" => $price,
                    ]
                ]
            ]
        ]);
    }
    public function planPayWithPaypal(Request $request)
    {
        $plan = Plan::find($request->plan_id);
        $user = User::find($request->user_id);
        $admin_settings = getAdminAllSetting();
        $admin_currancy = !empty($admin_settings['defaultCurrency']) ? $admin_settings['defaultCurrency'] : '';

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
        if ($admin_settings['paypal_mode'] == 'live') {
            config(
                [
                    'paypal.live.client_id' => isset($admin_settings['paypal_client_id']) ? $admin_settings['paypal_client_id'] : '',
                    'paypal.live.client_secret' => isset($admin_settings['paypal_secret_key']) ? $admin_settings['paypal_secret_key'] : '',
                    'paypal.mode' => isset($admin_settings['paypal_mode']) ? $admin_settings['paypal_mode'] : '',
                ]
            );
        } else {
            config(
                [
                    'paypal.sandbox.client_id' => isset($admin_settings['paypal_client_id']) ? $admin_settings['paypal_client_id'] : '',
                    'paypal.sandbox.client_secret' => isset($admin_settings['paypal_secret_key']) ? $admin_settings['paypal_secret_key'] : '',
                    'paypal.mode' => isset($admin_settings['paypal_mode']) ? $admin_settings['paypal_mode'] : '',
                ]
            );
        }
        $provider = app(PayPalClient::class);
        $provider->setApiCredentials(config('paypal'));

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
                    return redirect()->route('plans.index')->with('success', __('Plan activated Successfully!'));
                } else {
                    return redirect()->route('plans.index')->with('error', __('Something went wrong, Please try again,'));
                }
            }
            $provider->getAccessToken();

            $routeParams = [
                $plan->id,
                'amount' => $price,
                'user_module' => $user_module,
                'counter' => $counter,
                'duration' => $duration,
                'coupon_code' => $request->coupon_code,
            ];
            $routeName = 'payment.paypal.status';
            $response = $this->createPaypalOrder($provider, $routeParams, $admin_currancy, $price,$routeName);

            if (isset($response['id']) && $response['id'] != null) {
                // redirect to approve href
                foreach ($response['links'] as $links) {
                    if ($links['rel'] == 'approve') {
                        return redirect()->away($links['href']);
                    }
                }
                return redirect()
                    ->route('plans.index', Crypt::encrypt($plan->id))
                    ->with('error', 'Something went wrong. OR Unknown error occurred');
            } else {
                return redirect()
                    ->route('plans.index', Crypt::encrypt($plan->id))
                    ->with('error', $response['message'] ?? 'Something went wrong.');
            }
        } else {
            return redirect()->route('plans.index')->with('error', __('The plan has been deleted.'));
        }
    }

    public function planGetPaypalStatus(Request $request, $plan_id)
    {
        $user = Auth::user();
        $plan = Plan::find($plan_id);
        if ($plan) {
            $admin_settings = getAdminAllSetting();
            if ($admin_settings['paypal_mode'] == 'live') {
                config(
                    [
                        'paypal.live.client_id' => isset($admin_settings['paypal_client_id']) ? $admin_settings['paypal_client_id'] : '',
                        'paypal.live.client_secret' => isset($admin_settings['paypal_secret_key']) ? $admin_settings['paypal_secret_key'] : '',
                        'paypal.mode' => isset($admin_settings['paypal_mode']) ? $admin_settings['paypal_mode'] : '',
                    ]
                );
            } else {
                config(
                    [
                        'paypal.sandbox.client_id' => isset($admin_settings['paypal_client_id']) ? $admin_settings['paypal_client_id'] : '',
                        'paypal.sandbox.client_secret' => isset($admin_settings['paypal_secret_key']) ? $admin_settings['paypal_secret_key'] : '',
                        'paypal.mode' => isset($admin_settings['paypal_mode']) ? $admin_settings['paypal_mode'] : '',
                    ]
                );
            }
            $admin_currancy = !empty($admin_settings['defaultCurrency']) ? $admin_settings['defaultCurrency'] : '';

            $provider = app(PayPalClient::class);
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();
            $response = $provider->capturePaymentOrder($request['token']);

            if (isset($response['status']) && $response['status'] == 'COMPLETED') {
                $orderID = strtoupper(substr(uniqid(), -12));
                try {
                    $order = new Order();
                    $order->order_id = $orderID;
                    $order->name = $user->name ?? null;
                    $order->email = $user->email ?? null;
                    $order->card_number = null;
                    $order->card_exp_month = null;
                    $order->card_exp_year = null;
                    $order->plan_name = !empty($plan->name) ? $plan->name : 'Basic Package';
                    $order->plan_id = $plan->id;
                    $order->price = !empty($request->amount) ? $request->amount : 0;
                    $order->currency = $admin_currancy;
                    $order->txn_id = '';
                    $order->payment_type = 'Paypal';
                    $order->payment_status = 'succeeded';
                    $order->receipt = null;
                    $order->created_by = $user->id;
                    $order->save();

                    $type = 'Subscription';
                    $user = User::find($user->id);
                    $counter = [
                        'user_counter' => -1,
                        'storage_counter' => 0,
                    ];
                    $assignPlan = assignPlan($plan->id, $request->duration, $request->user_module, $counter, $user->id);
                    if ($request->coupon_code) {
                        $coupon = Coupon::where('code', $request->coupon_code)->first();
                        if ($coupon) {
                            recordCouponUsage($coupon->id, $user->id, $orderID);
                        }
                    }

                    try {
                        PaypalPaymentStatus::dispatch($plan, $type, $order);
                    } catch (\Exception $e) {
                        return redirect()->back()->with('error', $e->getMessage());
                    }

                    if ($assignPlan['is_success']) {
                        return redirect()->route('plans.index')->with('success', __('Plan activated Successfully.'));
                    } else {
                        return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
                    }
                } catch (\Exception $e) {
                    return redirect()->route('plans.index')->with('error', __('Transaction has been failed.'));
                }
            } else {
                return redirect()->route('plans.index')->with('error', __('Payment failed.'));
            }
        } else {
            return redirect()->route('plans.index')->with('error', __('Plan is deleted.'));
        }
    }
}
