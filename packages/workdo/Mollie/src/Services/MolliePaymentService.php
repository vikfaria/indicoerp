<?php

namespace Workdo\Mollie\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MolliePaymentService
{
    private $apiKey;
    private $profileId;
    private $partnerId;
    private $baseUrl;
    protected $supported_currency = ['AED', 'AUD', 'BGN', 'BRL', 'CAD', 'CHF', 'CZK', 'HRK', 'HUF', 'ILS', 'ISK', 'JPY', 'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'RON', 'RUB', 'SEK', 'SGD', 'THB', 'TWD', 'USD', 'ZAR','EUR','GBP','DKK','HKD'];

    public function __construct($config = null)
    {
        $this->apiKey = $config['mollie_api_key'] ?? null;
        $this->profileId = $config['mollie_profile_id'] ?? null;
        $this->partnerId = $config['mollie_partner_id'] ?? null;
        $this->baseUrl = 'https://api.mollie.com';
    }

    /**
     * Create a payment
     * 
     * @param array $paymentData
     * @return array
     * @throws Exception
     */
    public function createPayment(array $paymentData): array
    {
        try {
            if (!in_array($paymentData['currency'], $this->supported_currency)) {
                throw new Exception("Currency {$paymentData['currency']} is not supported");
            }

            $payload = [
                'amount' => [
                    'currency' => $paymentData['currency'],
                    'value' => number_format($paymentData['amount'], 2, '.', '')
                ],
                'description' => $paymentData['description'] ?? 'Payment',
                'redirectUrl' => $paymentData['redirectUrl']
            ];

            $response = $this->makeRequest('POST', '/v2/payments', $payload);
            return $response->json();
        } catch (Exception $e) {
            Log::error('Mollie Create Payment Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retrieve a payment
     * 
     * @param string $paymentId
     * @return array
     * @throws Exception
     */
    public function retrievePayment(string $paymentId): array
    {
        try {
            $response = $this->makeRequest('GET', '/v2/payments/' . $paymentId);
            return $response->json();
        } catch (Exception $e) {
            Log::error('Mollie Retrieve Payment Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if payment is paid
     * 
     * @param string $paymentId
     * @return bool
     */
    public function isPaymentPaid(string $paymentId): bool
    {
        try {
            $payment = $this->retrievePayment($paymentId);
            return isset($payment['status']) && $payment['status'] === 'paid';
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Make HTTP request to Mollie API
     * 
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return \Illuminate\Http\Client\Response
     * @throws Exception
     */
    private function makeRequest(string $method, string $endpoint, array $data = [])
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json'
        ])->{strtolower($method)}($this->baseUrl . $endpoint, $data);

        if (!$response->successful()) {
            throw new Exception('Mollie API Error: ' . $response->body());
        }

        return $response;
    }
}