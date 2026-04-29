<?php

namespace Workdo\Xendit\Services;

use Exception;

class XenditService
{
    private static $apiKey;
    private static $apiBase = 'https://api.xendit.co';

    public static function setApiKey($apiKey)
    {
        self::$apiKey = $apiKey;
    }

    public static function createInvoice($params)
    {
        $requiredParams = ['external_id', 'amount'];
        self::validateParams($params, $requiredParams);

        $url = self::$apiBase . '/v2/invoices';
        return self::makeRequest('POST', $url, $params);
    }

    public static function getInvoice($invoiceId)
    {
        $url = self::$apiBase . '/v2/invoices/' . $invoiceId;
        return self::makeRequest('GET', $url);
    }

    private static function validateParams($params, $required)
    {
        foreach ($required as $param) {
            if (!isset($params[$param])) {
                throw new Exception(__("Missing required parameter:param", ['param' => $param]));
            }
        }
    }

    private static function makeRequest($method, $url, $params = [])
    {
        if (empty(self::$apiKey)) {
            throw new Exception(__("Xendit API key is not set"));
        }
        
        $curl = curl_init();

        $headers = [
            'Authorization: Basic ' . base64_encode(self::$apiKey . ':'),
            'Content-Type: application/json'
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $method !== 'GET' ? json_encode($params) : null,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        
        if ($curlError) {
            throw new Exception(__("Curl Error : error", ['error' => $curlError]));
        }

        if ($httpCode >= 400) {
            $errorData = json_decode($response, true);
            $errorMessage = isset($errorData['message']) ? $errorData['message'] : $response;
            throw new Exception(__("Xendit API Error (HTTP :code) : message", ['code' => $httpCode, 'message' => $errorMessage]));
        }

        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(__("Invalid JSON response from Xendit API"));
        }
        
        return $decodedResponse;
    }
}