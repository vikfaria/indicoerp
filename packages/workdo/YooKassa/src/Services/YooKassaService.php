<?php

namespace Workdo\YooKassa\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YooKassaService
{
    private $shopId;
    private $secretKey;
    private $baseUrl;

    public function __construct($config = null)
    {
        $this->shopId = $config['shop_id'] ?? null;
        $this->secretKey = $config['secret_key'] ?? null;
        $this->baseUrl = 'https://api.yookassa.ru';
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
            $payload = array_merge($paymentData, [
                'metadata' => [
                    'order_id' => uniqid('yookassa_', true)
                ]
            ]);

            $response = $this->makeRequest('POST', '/v3/payments', $payload);
            return $response->json();
        } catch (Exception $e) {
            Log::error(__('YooKassa Create Payment Error: ') . $e->getMessage());
            throw new Exception(__('Failed to create payment. Please try again.'));
        }
    }

    /**
     * Get payment information
     * 
     * @param string $paymentId
     * @return array
     * @throws Exception
     */
    public function getPayment(string $paymentId): array
    {
        try {
            $response = $this->makeRequest('GET', '/v3/payments/' . $paymentId);
            return $response->json();
        } catch (Exception $e) {
            Log::error('YooKassa Get Payment Error: ' . $e->getMessage());
            throw new Exception(__('Failed to retrieve payment information. Please try again.'));
        }
    }

    /**
     * Make HTTP request to YooKassa API
     * 
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return \Illuminate\Http\Client\Response
     * @throws Exception
     */
    private function makeRequest(string $method, string $endpoint, array $data = [])
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Idempotence-Key' => uniqid('', true)
        ];

        $response = Http::withHeaders($headers)
            ->withBasicAuth($this->shopId, $this->secretKey)
            ->{strtolower($method)}($this->baseUrl . $endpoint, $data);

        if (!$response->successful()) {
            throw new Exception(__('YooKassa API Error: ') . $response->body());
        }

        return $response;
    }
}