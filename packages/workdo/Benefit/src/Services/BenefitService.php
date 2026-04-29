<?php

namespace Workdo\Benefit\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BenefitService
{
    private $apiKey;
    private $secretKey;
    private $processingChannelId;
    private $baseUrl;

    public function __construct($config = null)
    {
        $this->apiKey = $config['api_key'] ?? null;
        $this->secretKey = $config['secret_key'] ?? null;
        $this->processingChannelId = $config['processing_channel_id'] ?? null;
        $this->baseUrl = 'https://api.sandbox.checkout.com';
    }

    /**
     * Create a payment link
     * 
     * @param array $benefitData
     * @param array $checkoutOptions
     * @return array
     * @throws Exception
     */
    public function createPaymentLink(array $benefitData, array $checkoutOptions = []): array
    {
        try {
            $payload = [
                "amount" => (int)($benefitData['amount'] * 100),
                "currency" => $benefitData['currency'],
                "reference" => 'order_' . time(),
                "description" => $benefitData['name'],
                "processing_channel_id" => $this->processingChannelId,
                "customer" => [
                    "name" => $benefitData['customer']['name'] ?? 'Customer',
                    "email" => $benefitData['customer']['email'] ?? 'customer@example.com'
                ],
                "billing" => [
                    "address" => [
                        "country" => $benefitData['billing']['country'] ?? 'BH'
                    ]
                ],
                "return_url" => $checkoutOptions['redirect_url']
            ];

            $response = $this->makeRequest('POST', '/payment-links', $payload);
            return $response->json();
        } catch (Exception $e) {
            Log::error('Benefit Create Payment Link Error: ' . $e->getMessage());
            throw new Exception(__('Failed to create payment. Please try again.'));
        }
    }

    /**
     * Retrieve a payment link
     * 
     * @param string $paymentLinkId
     * @return array
     * @throws Exception
     */
    public function retrievePaymentLink(string $paymentLinkId): array
    {
        try {
            $response = $this->makeRequest('GET', '/payment-links/' . $paymentLinkId);
            return $response->json();
        } catch (Exception $e) {
            Log::error('Benefit Retrieve Payment Link Error: ' . $e->getMessage());
            throw new Exception(__('Failed to retrieve payment information. Please try again.'));
        }
    }

    /**
     * Get payment details
     * 
     * @param string $paymentId
     * @return array
     * @throws Exception
     */
    public function getPayment(string $paymentId): array
    {
        return $this->retrievePaymentLink($paymentId);
    }

    /**
     * Make HTTP request to Checkout.com API
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
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->{strtolower($method)}($this->baseUrl . $endpoint, $data);

        if (!$response->successful()) {
            throw new Exception(__('Checkout.com API Error: ') . $response->body());
        }

        return $response;
    }
}