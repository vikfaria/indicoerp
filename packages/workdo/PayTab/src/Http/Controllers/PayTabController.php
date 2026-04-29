<?php

namespace Workdo\PayTab\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Plan;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use Workdo\PayTab\Events\PayTabPaymentStatus;
use Workdo\PayTab\Models\paypage;

class PayTabController extends Controller
{
    protected $profile_id, $server_key, $region, $is_enabled, $currancy, $invoiceData;

    public function getUrl($region, $endpoint = null)
    {
        $url = [
            'ARE' => 'https://secure.paytabs.com/',
            'SAU' => 'https://secure.paytabs.sa/',
            'OMN' => 'https://secure-oman.paytabs.com/',
            'JOR' => 'https://secure-jordan.paytabs.com/',
            'EGY' => 'https://secure-egypt.paytabs.com/',
            'GLOBAL' => 'https://secure-global.paytabs.com/',
        ];
        $base = $url[$region] ?? 'https://secure-global.paytabs.com/';
        return $base . $endpoint;
    }

    public function Verify($order_id)
    {
        $session = Session::get($order_id);

        // Retry logic for connection timeouts
        $maxRetries = 3;
        $retryCount = 0;

        while ($retryCount < $maxRetries) {
            try {
                $response = Http::timeout(60)
                    ->connectTimeout(30)
                    ->retry(2, 1000)
                    ->withHeaders([
                        'Authorization' => $session['server_key'] ?? '',
                    ])->post($this->getUrl($session['region'] ?? 'GLOBAL', "payment/query"), [
                        "profile_id" => $session['profile_id'] ?? '',
                        "tran_ref" => $session['tran_ref'] ?? '',
                    ]);

                if ($response->successful()) {
                    break;
                }

                if ($response->failed()) {
                    $retryCount++;
                    if ($retryCount >= $maxRetries) {
                        return [
                            'success' => false,
                            'status' => 'error',
                            'message' => __('Connection timeout. Please check your internet connection and try again.')
                        ];
                    }
                    sleep(2);
                    continue;
                }
            } catch (\Exception $e) {
                $retryCount++;
                if ($retryCount >= $maxRetries) {
                    return [
                        'success' => false,
                        'status' => 'error',
                        'message' => 'Network error: ' . $e->getMessage()
                    ];
                }
                sleep(2);
                continue;
            }
        }

        $result = $response->json();

        if ($result['payment_result']['response_status'] == "A" && $result['payment_result']['response_message'] == "Authorised") {
            return [
                'success' => true,
                'status' => 'success',
                'message' => __('The Payment has been added successfully.'),
            ];
        } else if ($result['payment_result']['response_status'] == "C" && $result['payment_result']['response_message'] == "Cancelled") {
            return [
                'success' => false,
                'status' => 'error',
                'message' => __('The transaction has been failed'),
            ];
        } else {
            return [
                'success' => false,
                'status' => 'error',
                'message' => __('oops Something went wrong!'),
            ];
        }
    }
    
    public function planPayWithPayTab(Request $request)
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
                $order->payment_type = 'PayTab';
                $order->payment_status = 'pending';
                $order->receipt = null;
                $order->created_by = $user->id;
                $order->save();

                return $this->createPayTabPayment([
                    'profile_id' => $admin_settings['paytab_profile_id'] ?? '',
                    'server_key' => $admin_settings['paytab_server_key'] ?? '',
                    'region' => $admin_settings['paytab_region'] ?? 'GLOBAL',
                    'currency' => $admin_currancy,
                    'amount' => $price,
                    'description' => 'plan payment',
                    'customer_name' => $user->name ?? '',
                    'customer_email' => $user->email ?? '',
                    'return_url' => route('payment.paytab.status', [
                        'order_id' => $orderID,
                        'plan_id' => $plan->id,
                        'amount' => $price,
                        'coupon_code' => $request->coupon_code,
                        'duration' => $duration,
                        'user_module' => $user_module,
                        'user_id' => $user->id
                    ]),
                    'order_id' => $orderID
                ]);

            } catch (\Exception $e) {
                return redirect()->route('plans.index')->with('error', $e->getMessage());
            }
        } else {
            return redirect()->route('plans.index')->with('error', __('The plan has been deleted.'));
        }
    }

    public function planGetPayTabStatus(Request $request)
    {
        $admin_settings = getAdminAllSetting();
        config([
            'paytabs.profile_id' => $admin_settings['paytab_profile_id'] ?? '',
            'paytabs.server_key' => $admin_settings['paytab_server_key'] ?? '',
            'paytabs.region' => $admin_settings['paytab_region'] ?? '',
            'paytabs.currency' => $admin_settings['defult_currancy'] ?? '',
        ]);
        
        $status = $this->Verify($request['order_id']);

        if ($status['success']) {
            try {
                $plan = Plan::find($request['plan_id']);
                $Order = Order::where('order_id', $request['order_id'])->first();
                $Order->payment_status = 'succeeded';
                $Order->save();

                $counter = [
                    'user_counter' => -1,
                    'storage_counter' => 0,
                ];
                
                $assignPlan = assignPlan($plan->id, $request['duration'], $request['user_module'], $counter, $request['user_id']);
                
                if ($assignPlan['is_success']) {
                    if ($request['coupon_code']) {
                        $coupon = Coupon::where('code', $request['coupon_code'])->first();
                        if ($coupon) {
                            recordCouponUsage($coupon->id, $request['user_id'], $request['order_id']);
                        }
                    }
                    $type = 'Subscription';
                    try {
                        PayTabPaymentStatus::dispatch($plan, $type, $Order);
                    } catch (\Throwable $th) {
                        return back()->with('error', $th->getMessage());
                    }

                    if ($assignPlan['is_success']) {
                        return redirect()->route('plans.index')->with('success', __('The plan has been activated successfully.'));
                    } else {
                        return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
                    }
                } else {
                    return redirect()->route('plans.index')->with('error', __('Something went wrong, Please try again,'));
                }
            } catch (\Exception $exception) {
                return redirect()->route('plans.index')->with('error', $exception->getMessage());
            }
        } else {
            return redirect()->route('plans.index')->with('error', __('Your Payment has failed!'));
        }
    }

    public function createPayTabPayment($params)
    {
        config([
            'paytabs.profile_id' => $params['profile_id'],
            'paytabs.server_key' => $params['server_key'],
            'paytabs.region' => $params['region'],
            'paytabs.currency' => $params['currency'],
        ]);


        $paypage = new paypage();
        $pay = $paypage->sendPaymentCode('all')
            ->sendTransaction('sale')
            ->sendCart(1, $params['amount'], $params['description'])
            ->sendCustomerDetails($params['customer_name'], $params['customer_email'], '', '', '', '', '', '', '')
            ->sendURLs($params['return_url'])
            ->sendLanguage('en')
            ->sendFramed(false)
            ->sendHideShipping(true)
            ->create_pay_page($params['order_id']);

        if (empty(trim($pay))) {
            return redirect()->back()->with('error', __('Payment credentials are incorrect or currency is unavailable.'));
        }

        return $pay;
    }
}
