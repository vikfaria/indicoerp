<?php

namespace Workdo\Cashfree\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class CashfreeService
{
    private $appId;
    private $secretKey;
    private $baseUrl;
    private $mode;

    public function __construct($config = null)
    {
        $this->appId = $config['app_id'] ?? null;
        $this->secretKey = $config['secret_key'] ?? null;
        $this->mode = $config['mode'] ?? 'sandbox';

        $this->baseUrl = $this->mode === 'production'
            ? 'https://api.cashfree.com'
            : 'https://sandbox.cashfree.com';

        if (!$this->appId || !$this->secretKey) {
            throw new InvalidArgumentException(__('The Cashfree App ID and Secret Key are required.'));
        }
    }

    /**
     * Create a payment link
     * 
     * @param array $paymentData
     * @return object
     * @throws Exception
     */
    public function createPaymentLink(array $paymentData): object
    {

        try {
            $payload = [
                'link_id' => $paymentData['link_id'],
                'link_amount' => $paymentData['amount'],
                'link_currency' => $paymentData['currency'] ?? 'INR',
                'link_purpose' => $paymentData['purpose'] ?? 'Payment',
                'customer_details' => [
                    'customer_name' => $paymentData['customer_name'],
                    'customer_email' => $paymentData['customer_email'],
                    'customer_phone' => $paymentData['customer_phone']  ?? '9999999999',
                ],
                'link_notify' => [
                    'send_sms' => false,
                    'send_email' => true
                ],
                'link_auto_reminders' => false,
                'link_expiry_time' => $paymentData['expiry_time'] ?? date('Y-m-d\TH:i:s\Z', strtotime('+1 day')),
                'link_meta' => [
                    'return_url' => $paymentData['return_url'] ?? '',
                    'notify_url' => $paymentData['notify_url'] ?? ''
                ]
            ];

            $response = $this->makeRequest('POST', '/pg/links', $payload);

            if (isset($response['link_url'])) {
                return (object) $response;
            }

            throw new Exception(__('The response from Cashfree is invalid.'));
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get payment link details
     * 
     * @param string $linkId
     * @return object
     * @throws Exception
     */
    public function getPaymentLink(string $linkId): object
    {
        if (!$linkId) {
            throw new InvalidArgumentException(__('The Link ID is required.'));
        }

        try {
            $response = $this->makeRequest('GET', '/pg/links/' . $linkId);
            return (object) $response;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get payment details by order ID
     * 
     * @param string $orderId
     * @return object
     * @throws Exception
     */
    public function getPaymentDetails(string $orderId): object
    {
        if (!$orderId) {
            throw new InvalidArgumentException(__('The Order ID is required.'));
        }

        try {
            $response = $this->makeRequest('GET', '/pg/orders/' . $orderId);
            return (object) $response;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Verify payment signature
     * 
     * @param array $postData
     * @param string $signature
     * @return bool
     */
    public function verifySignature(array $postData, string $signature): bool
    {
        try {
            ksort($postData);
            $signatureData = '';

            foreach ($postData as $key => $value) {
                $signatureData .= $key . $value;
            }

            $computedSignature = base64_encode(hash_hmac('sha256', $signatureData, $this->secretKey, true));

            return hash_equals($computedSignature, $signature);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Make HTTP request to Cashfree API
     * 
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws Exception
     */
    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'x-client-id' => $this->appId,
            'x-client-secret' => $this->secretKey,
            'x-api-version' => '2023-08-01'
        ];

        $response = Http::withHeaders($headers)
            ->{strtolower($method)}($this->baseUrl . $endpoint, $data);

        if (!$response->successful()) {
            $errorMessage = __('The Cashfree request has failed.');

            if ($response->json()) {
                $errorData = $response->json();
                $errorMessage = $errorData['message'] ?? __('An error has occurred while processing the request.');
            }

            throw new Exception($errorMessage);
        }

        return $response->json();
    }
}
