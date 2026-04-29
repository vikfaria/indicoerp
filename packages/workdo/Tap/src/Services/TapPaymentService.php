<?php

namespace Workdo\Tap\Services;

use Illuminate\Http\Request;
use Exception;
use InvalidArgumentException;

class TapPaymentService
{
    protected $config;
    
    protected $requiredConfigVars = ['tap_secret_key' => true];
    protected $configVars = ['tap_secret_key' => null];

    public function __construct($config = [])
    {
        $this->config = $config;
        foreach ($this->requiredConfigVars as $param => $required) {
            if (array_key_exists($param, $config)) {
                $this->configVars[$param] = $config[$param];
            } elseif ($required) {
                throw new InvalidArgumentException("Missing required parameter: $param");
            }
        }
    }

    public function createCharge($data = [])
    {
        $this->validateChargeData($data);
        
        $postData = [
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'threeDSecure' => true,
            'save_card' => false,
            'description' => $data['description'],
            'metadata' => [
                'udf1' => $data['reference']['order'] ?? '',
                'udf2' => 'plan_payment'
            ],
            'reference' => [
                'transaction' => $data['reference']['transaction'] ?? '',
                'order' => $data['reference']['order'] ?? ''
            ],
            'receipt' => [
                'email' => true,
                'sms' => false
            ],
            'customer' => [
                'first_name' => $data['customer']['first_name'],
                'email' => $data['customer']['email']
            ],
            'source' => [
                'id' => 'src_all'
            ],
            'post' => [
                'url' => $data['post']['url']
            ],
            'redirect' => [
                'url' => $data['redirect']['url']
            ]
        ];
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.tap.company/v2/charges",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $this->configVars['tap_secret_key'],
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            throw new Exception("cURL Error: $err");
        }

        $jsonResponse = json_decode($response);
        
        if (isset($jsonResponse->errors) && count($jsonResponse->errors) > 0) {
            throw new Exception($jsonResponse->errors[0]->description ?? 'Payment error');
        }

        if (isset($jsonResponse->object) && $jsonResponse->object === "charge" && isset($jsonResponse->transaction->url)) {
            return $jsonResponse;
        }

        throw new Exception("Invalid response from Tap Payment: " . $response);
    }

    public function getCharge($chargeId)
    {
        if (!$chargeId) {
            return false;
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.tap.company/v2/charges/$chargeId",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $this->configVars['tap_secret_key']
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            throw new Exception("cURL Error: $err");
        }

        $jsonResponse = json_decode($response);
        
        if (isset($jsonResponse->errors) && count($jsonResponse->errors) > 0) {
            throw new Exception($jsonResponse->errors[0]->description ?? 'Error retrieving charge');
        }

        if (isset($jsonResponse->object) && $jsonResponse->object === "charge") {
            return $jsonResponse;
        }

        throw new Exception("Invalid charge response: " . $response);
    }

    protected function validateChargeData($data)
    {
        $required = ['amount', 'currency', 'description', 'post', 'redirect', 'customer', 'reference'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException("Missing required field: $field");
            }
        }

        if (!isset($data['customer']['first_name']) || !isset($data['customer']['email'])) {
            throw new InvalidArgumentException("Customer first_name and email are required");
        }

        if ($data['amount'] <= 0) {
            throw new InvalidArgumentException("Amount must be greater than 0");
        }
    }
}