<?php

namespace Workdo\PayTR\Services;
use Inertia\Inertia;

class PayTRService
{
    private $merchant_id;
    private $merchant_key;
    private $merchant_salt;
    private $mode;
    private $api_base_url;

    public function __construct($merchant_id, $merchant_key, $merchant_salt, $mode = 'sandbox')
    {
        $this->merchant_id = $merchant_id;
        $this->merchant_key = $merchant_key;
        $this->merchant_salt = $merchant_salt;
        $this->mode = $mode;
        $this->api_base_url = 'https://www.paytr.com/odeme/api';
    }

    public function createPayment($params)
    {
        // Validate required parameters
        $required_params = ['name', 'price', 'currency', 'user_name', 'email', 'callback_link', 'callback_id'];
        foreach ($required_params as $param) {
            if (empty($params[$param])) {
                throw new \Exception(__("Required parameter missing:param", ['param' => $param]));
            }
        }


        $name = substr($params['name'], 0, 200);
        $price = intval($params['price']);
        $currency = $params['currency'];
        $max_installment = isset($params['max_installment']) ? intval($params['max_installment']) : $price;
        $user_name = $params['user_name'];
        $email = $params['email'];
        $callback_link = $params['callback_link'];
        $callback_id = $params['callback_id'];
        $lang = $params['lang'] ?? 'tr';
        $required_for_token = $name . $price . $currency . $max_installment . 'product' . $lang . '1';

        // Generate authentication token
        $paytr_token = base64_encode(hash_hmac('sha256', $required_for_token . $this->merchant_salt, $this->merchant_key, true));

        $post_vals = [
            'merchant_id' => $this->merchant_id,
            'name' => $name,
            'price' => $price,
            'currency' => $currency,
            'max_installment' => $max_installment,
            'link_type' => 'product',
            'lang' => $lang,
            'paytr_token' => $paytr_token,
            'user_name' => $user_name,
            'email' => $email,
            'callback_link' => $callback_link,
            'callback_id' => $callback_id,
            'min_count' => 1,
        ];

        try {
            $response = $this->makeCurlRequest($this->api_base_url . '/link/create', $post_vals);

            if ($response['status'] == 'error') {
                throw new \Exception(__("PayTR Error:message", ['message' => ($response['err_msg'] ?? __('Unknown error'))]));
            }

            if ($response['status'] == 'failed') {
                throw new \Exception(__("PayTR Failed:response", ['response' => json_encode($response)]));
            }

            return [
                'status' => 'success',
                'callback_link' => $callback_link ?? '',
                'paytr_token' => $paytr_token ?? '',
            ];
        } catch (\Exception $e) {
            throw new \Exception(__("Payment link creation failed:message", ['message' => $e->getMessage()]));
        }
    }

    public function redirectToCheckout(array $paymentResponse, $user, $orderID=null)
    {
        return Inertia::render('PayTR/PayTRPayment', [
            'callback_link' => $paymentResponse['callback_link'],
            'token' => $paymentResponse['paytr_token'] ?? '',
            'user' => $user,
            'order_id' => $orderID ?? '',
            'iframe_redirect' => true,
        ]);
    }

    /**
     * Make CURL request to PayTR API
     * 
     * @param string $url
     * @param array $post_data
     * @return array
     */
    private function makeCurlRequest($url, $post_data)
    {
        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);

            $result = @curl_exec($ch);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new \Exception(__("CURL Error:error", ['error' => $error]));
            }

            curl_close($ch);

            $decoded_result = json_decode($result, true);

            if (!is_array($decoded_result)) {
                throw new \Exception(__("Invalid API response:response", ['response' => $result]));
            }

            return $decoded_result;
        } catch (\Exception $e) {
            throw new \Exception(__("CURL Request failed:message", ['message' => $e->getMessage()]));
        }
    }
}
