<?php

namespace Workdo\Coingate\Services;

class CoingateService
{
    const VERSION = '3.0.5';
    const USER_AGENT_ORIGIN = 'CoinGate PHP Library';

    private $auth_token;
    private $environment;
    private $user_agent;
    private $curlopt_ssl_verifypeer;

    public function __construct($config = [])
    {
        $this->auth_token = $config['auth_token'] ?? '';
        $this->environment = $config['environment'] ?? 'live';
        $this->user_agent = $config['user_agent'] ?? (self::USER_AGENT_ORIGIN . ' v' . self::VERSION);
        $this->curlopt_ssl_verifypeer = $config['curlopt_ssl_verifypeer'] ?? true;
    }

    public function createPayment($params)
    {
        return $this->makeRequest($params, 'POST');
    }

    public function getPayment($orderId)
    {
        return $this->makeRequest($orderId, 'GET');
    }

    private function makeRequest($params, $method = 'POST')
    {
        $methodUrl = $method == 'GET' ? '/orders/' . $params : '/orders';
        $url = ($this->environment == 'sandbox' ? 'https://api-sandbox.coingate.com/v2/' : 'https://api.coingate.com/v2') . $methodUrl;

        $headers = [
            'Authorization: Token ' . $this->auth_token
        ];

        $curl = curl_init();
        $curl_options = [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_USERAGENT => $this->user_agent,
            CURLOPT_SSL_VERIFYPEER => $this->curlopt_ssl_verifypeer,
            CURLOPT_HTTPHEADER => $headers
        ];

        if ($method == 'POST') {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $curl_options[CURLOPT_POST] = 1;
            $curl_options[CURLOPT_POSTFIELDS] = http_build_query($params);
        }

        curl_setopt_array($curl, $curl_options);

        $raw_response = curl_exec($curl);
        $decoded_response = json_decode($raw_response, true);
        $response = $decoded_response ?: $raw_response;
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        return $method == 'GET' ? $response : ['response' => $response, 'status_code' => $http_status];
    }
}