<?php

namespace Workdo\Aamarpay\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class AamarpayService
{
    private $storeId;
    private $signatureKey;
    private $requestUrl;
    private $redirectUrl;

    public function __construct($config = null)
    {
        $this->storeId = $config['store_id'] ?? null;
        $this->signatureKey = $config['signature_key'] ?? null;
        $environment = $config['environment'] ?? 'sandbox';

        if ($environment === 'live') {
            $this->requestUrl = 'https://secure.aamarpay.com/request.php';
            $this->redirectUrl = 'https://secure.aamarpay.com/';
        } else {
            $this->requestUrl = 'https://sandbox.aamarpay.com/request.php';
            $this->redirectUrl = 'https://sandbox.aamarpay.com/';
        }
    }

    /**
     * Create a payment request
     * 
     * @param array $paymentData
     * @return array
     * @throws Exception
     */
    public function createPayment(array $paymentData): array
    {
        try {
            $fields = [
                'store_id' => $this->storeId,
                'amount' => $paymentData['amount'],
                'payment_type' => '',
                'currency' => $paymentData['currency'],
                'tran_id' => $paymentData['tran_id'],
                'cus_name' => $paymentData['cus_name'],
                'cus_email' => $paymentData['cus_email'],
                'cus_add1' => $paymentData['cus_add1'] ?? '',
                'cus_add2' => $paymentData['cus_add2'] ?? '',
                'cus_city' => $paymentData['cus_city'] ?? '',
                'cus_state' => $paymentData['cus_state'] ?? '',
                'cus_postcode' => $paymentData['cus_postcode'] ?? '',
                'cus_country' => $paymentData['cus_country'] ?? '',
                'cus_phone' => $paymentData['cus_phone'] ?? '1234567890',
                'success_url' => $paymentData['success_url'],
                'fail_url' => $paymentData['fail_url'],
                'cancel_url' => $paymentData['cancel_url'],
                'signature_key' => $this->signatureKey,
                'desc' => $paymentData['desc'],
            ];

            $fields_string = http_build_query($fields);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_URL, $this->requestUrl);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $url_forward = curl_exec($ch);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($curl_error) {
                throw new Exception($curl_error);
            }

            $url_forward = str_replace('"', '', stripslashes($url_forward));

            if (empty($url_forward)) {
                throw new Exception(__('Empty response from Aamarpay'));
            }

            return [
                'payment_url' => $this->redirectUrl . $url_forward,
                'tran_id' => $paymentData['tran_id']
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Verify payment status
     * 
     * @param array $responseData
     * @return array|null
     * @throws Exception
     */
    public function verifyPayment(array $responseData): ?array
    {
        try {
            if (isset($responseData['response']) && $responseData['response'] === 'success') {
                return [
                    'status' => 'success',
                    'transaction_id' => $responseData['order_id'] ?? null,
                    'amount' => $responseData['price'] ?? null,
                    'currency' => null
                ];
            }

            return [
                'status' => 'failed',
                'message' => __('Payment verification failed')
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }
}
