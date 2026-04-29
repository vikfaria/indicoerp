<?php

namespace Workdo\Midtrans\Services;

use Exception;
use InvalidArgumentException;

class MidtransPaymentService
{
    protected $config;
    
    protected $requiredConfigVars = ['midtrans_secret_key' => true];
    protected $configVars = ['midtrans_secret_key' => null, 'midtrans_mode' => 'sandbox'];

    public function __construct($config = [])
    {
        $this->config = $config;
        foreach ($this->requiredConfigVars as $param => $required) {
            if (array_key_exists($param, $config)) {
                $this->configVars[$param] = $config[$param];
            } elseif ($required) {
                throw new InvalidArgumentException(__("Missing required parameter: :param", ['param' => $param]));
            }
        }
        
        if (isset($config['midtrans_mode'])) {
            $this->configVars['midtrans_mode'] = $config['midtrans_mode'];
        }
    }

    public function createTransaction($data = [])
    {
        $this->validateTransactionData($data);

        $baseUrl = $this->configVars['midtrans_mode'] === 'live' 
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
        
        $postData = [
            'transaction_details' => [
                'order_id' => $data['order_id'],
                'gross_amount' => $data['gross_amount'],
                'currency' => $data['currency'] ?? 'IDR'
            ],
            'customer_details' => [
                'first_name' => $data['customer']['first_name'],
                'email' => $data['customer']['email']
            ],
            'item_details' => $data['item_details'] ?? [[
                'id' => $data['order_id'],
                'price' => $data['gross_amount'],
                'quantity' => 1,
                'name' => $data['description'] ?? 'Payment'
            ]],
            'callbacks' => [
                'finish' => $data['finish_url'] ?? '',
                'unfinish' => $data['unfinish_url'] ?? '',
                'error' => $data['error_url'] ?? ''
            ]
        ];
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic " . base64_encode($this->configVars['midtrans_secret_key'] . ':'),
                "Content-Type: application/json",
                "Accept: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            throw new Exception(__("cURL Error: :error", ['error' => $err]));
        }

        $jsonResponse = json_decode($response);
        
        if (isset($jsonResponse->error_messages) && count($jsonResponse->error_messages) > 0) {
            throw new Exception($jsonResponse->error_messages[0] ?? __('Payment error'));
        }

        if (isset($jsonResponse->token) && isset($jsonResponse->redirect_url)) {
            return $jsonResponse;
        }

        throw new Exception(__("Invalid response from Midtrans: :response", ['response' => $response]));
    }

    public function getTransactionStatus($orderId)
    {
        if (!$orderId) {
            return false;
        }

        $baseUrl = $this->configVars['midtrans_mode'] === 'live' 
            ? "https://api.midtrans.com/v2/$orderId/status"
            : "https://api.sandbox.midtrans.com/v2/$orderId/status";

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic " . base64_encode($this->configVars['midtrans_secret_key'] . ':'),
                "Accept: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            throw new Exception(__("cURL Error: :error", ['error' => $err]));
        }

        $jsonResponse = json_decode($response);
        
        if (isset($jsonResponse->status_message) && $jsonResponse->status_code !== '200') {
            throw new Exception($jsonResponse->status_message ?? __('Error retrieving transaction status'));
        }

        if (isset($jsonResponse->order_id)) {
            return $jsonResponse;
        }

        throw new Exception(__("Invalid transaction status response: :response", ['response' => $response]));
    }

    protected function validateTransactionData($data)
    {
        $required = ['order_id', 'gross_amount', 'customer'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException(__("Missing required field: :field", ['field' => $field]));
            }
        }

        if (!isset($data['customer']['first_name']) || !isset($data['customer']['email'])) {
            throw new InvalidArgumentException(__('Customer first_name and email are required'));
        }

        if ($data['gross_amount'] <= 0) {
            throw new InvalidArgumentException(__('Gross amount must be greater than 0'));
        }
    }
}