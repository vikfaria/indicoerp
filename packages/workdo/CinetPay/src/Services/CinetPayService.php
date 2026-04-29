<?php

namespace Workdo\CinetPay\Services;

use Exception;
use InvalidArgumentException;

class CinetPayService
{
    private $apiKey;
    private $siteId;
    private $baseUrl;

    protected $requiredConfigVars = ['cinetpay_api_key' => true, 'cinetpay_site_id' => true];
    protected $configVars = ['cinetpay_api_key' => null, 'cinetpay_site_id' => null];

    public function __construct($config = [])
    {
        foreach ($this->requiredConfigVars as $param => $required) {
            if (array_key_exists($param, $config)) {
                $this->configVars[$param] = $config[$param];
            } elseif ($required) {
                throw new InvalidArgumentException(__('Missing required parameter: :param', ['param' => $param]));
            }
        }

        $this->apiKey = $this->configVars['cinetpay_api_key'];
        $this->siteId = $this->configVars['cinetpay_site_id'];
        $this->baseUrl = 'https://api-checkout.cinetpay.com';
    }

    /**
     * Initialize payment checkout
     */
    public function checkout(array $paymentData)
    {
        $paymentData = array_merge($paymentData, $this->prepareCustomerData($paymentData));
        $this->validateCheckoutData($paymentData);

        $payload = [
            'apikey'                 => $this->apiKey,
            'site_id'                => $this->siteId,
            'transaction_id'         => $paymentData['transaction_id'],
            'amount'                 => (int) $paymentData['amount'],
            'currency'               => $paymentData['currency'],
            'description'            => $paymentData['description'],
            'return_url'             => $paymentData['return_url'],
            'notify_url'             => $paymentData['notify_url'],
            'channels'               => $paymentData['channels'] ?? 'ALL',

            'customer_name'          => $paymentData['customer_name'],
            'customer_surname'       => $paymentData['customer_surname'],
            'customer_email'         => $paymentData['customer_email'],
            'customer_phone_number'  => $paymentData['customer_phone_number'],
            'customer_address'       => $paymentData['customer_address'],
            'customer_city'          => $paymentData['customer_city'],
            'customer_country'       => $paymentData['customer_country'],
            'customer_state'         => $paymentData['customer_state'],
            'customer_zip_code'      => $paymentData['customer_zip_code'],
        ];

        return $this->makeRequest('/v2/payment', $payload);
    }

    /**
     * Check payment status
     */
    public function checkPaymentStatus(array $paymentData)
    {
        $this->validateStatusCheckData($paymentData);

        $payload = [
            'apikey'         => $this->apiKey,
            'site_id'        => $this->siteId,
            'transaction_id' => $paymentData['transaction_id']
        ];

        return $this->makeRequest('/v2/payment/check', $payload);
    }

    /**
     * Get transaction details
     */
    public function getTransaction(string $transactionId)
    {
        if (empty($transactionId)) {
            throw new InvalidArgumentException(__('Transaction ID is required'));
        }

        $payload = [
            'apikey'         => $this->apiKey,
            'site_id'        => $this->siteId,
            'transaction_id' => $transactionId
        ];

        return $this->makeRequest('/v2/payment/check', $payload);
    }

    /**
     * Verify payment notification (webhook)
     */
    public function verifyNotification(array $notificationData)
    {
        if (!isset($notificationData['cpm_trans_id'])) {
            throw new InvalidArgumentException(__('Missing required notification parameters'));
        }

        return $this->checkPaymentStatus([
            'transaction_id' => $notificationData['cpm_trans_id']
        ]);
    }

    /**
     * Make HTTP request to CinetPay API
     */
    private function makeRequest(string $endpoint, array $payload)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => $this->baseUrl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error    = curl_error($curl);
        curl_close($curl);

        if ($error) {
            throw new Exception(__('CinetPay API connection error: :error', ['error' => $error]));
        }

        $decodedResponse = json_decode($response);

        if (!$decodedResponse) {
            throw new Exception(__('Invalid response from CinetPay API'));
        }

        if (!in_array($httpCode, [200, 201])) {
            throw new Exception($decodedResponse->description ?? __('CinetPay API error: HTTP :code', ['code' => $httpCode]));
        }

        return $decodedResponse;
    }

    /**
     * Validate checkout data
     */
    private function validateCheckoutData(array $data)
    {
        $required = [
            'transaction_id',
            'amount',
            'currency',
            'description',
            'return_url',
            'notify_url',
            'customer_name',
            'customer_surname',
            'customer_email',
            'customer_phone_number',
            'customer_address',
            'customer_city',
            'customer_country',
            'customer_state',
            'customer_zip_code'
        ];

        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new InvalidArgumentException(__('Missing required field: :field', ['field' => $field]));
            }
        }

        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new InvalidArgumentException(__('Amount must be a positive number'));
        }

        if (!filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(__('Invalid customer email format'));
        }

        if (!filter_var($data['return_url'], FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException(__('Invalid return URL format'));
        }

        if (!filter_var($data['notify_url'], FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException(__('Invalid notify URL format'));
        }

        if (strlen($data['transaction_id']) > 100) {
            throw new InvalidArgumentException(__('Transaction ID must not exceed 100 characters'));
        }

        if (strlen($data['customer_country']) !== 2) {
            throw new InvalidArgumentException(__('Customer country must be a 2-character ISO code'));
        }

        if (!empty($data['customer_state']) && strlen($data['customer_state']) !== 2) {
            throw new InvalidArgumentException(__('Customer state must be a 2-character code'));
        }

        if (strlen($data['customer_zip_code']) < 1 || strlen($data['customer_zip_code']) > 10) {
            throw new InvalidArgumentException(__('Customer zip code must be between 1 and 10 characters'));
        }
    }

    /**
     * Validate status check data
     */
    private function validateStatusCheckData(array $data)
    {
        if (!isset($data['transaction_id']) || empty($data['transaction_id'])) {
            throw new InvalidArgumentException(__('Transaction ID is required for status check'));
        }
    }

    /**
     * Format amount for CinetPay
     */
    public function formatAmount(float $amount, string $currency)
    {
        if (in_array(strtoupper($currency), ['USD', 'EUR'])) {
            return (int) ($amount * 100);
        }

        return (int) $amount;
    }

    /**
     * Generate unique transaction ID
     */
    public function generateTransactionId()
    {
        return (string) (time() . '_' . uniqid());
    }

    /**
     * Build normalized customer fields for CinetPay.
     *
     * Accepts either a customer-only array (legacy keys and/or customer_* keys) or a full checkout
     * payload (when transaction_id is present, only customer-related keys are read from it).
     * Missing, null, or blank values use defaults.
     */
    public function prepareCustomerData(array $data)
    {
        if (array_key_exists('transaction_id', $data)) {
            $slice = [];
            foreach ([
                'customer_name',
                'customer_surname',
                'customer_email',
                'customer_phone_number',
                'customer_address',
                'customer_city',
                'customer_country',
                'customer_state',
                'customer_zip_code',
            ] as $key) {
                if (array_key_exists($key, $data)) {
                    $slice[$key] = $data[$key];
                }
            }
            foreach (['name', 'email', 'phone', 'address', 'city', 'country', 'state', 'zip_code'] as $key) {
                if (array_key_exists($key, $data)) {
                    $slice[$key] = $data[$key];
                }
            }
            $data = $slice;
        }

        $customer = $data;

        $blank = static function ($value): bool {
            if ($value === null) {
                return true;
            }
            if (is_string($value) && trim($value) === '') {
                return true;
            }

            return false;
        };

        $firstName = null;
        $lastName  = null;

        if (!$blank($customer['customer_name'] ?? null)) {
            $firstName = trim((string) $customer['customer_name']);
        }
        if (!$blank($customer['customer_surname'] ?? null)) {
            $lastName = trim((string) $customer['customer_surname']);
        }

        if ($firstName === null) {
            $customerName = $blank($customer['name'] ?? null)
                ? 'Customer User'
                : trim((string) $customer['name']);
            $nameParts = explode(' ', $customerName, 2);
            $firstName = trim($nameParts[0] ?? '') !== '' ? $nameParts[0] : 'Customer';
            if ($lastName === null) {
                $lastName = isset($nameParts[1]) && trim($nameParts[1]) !== '' ? trim($nameParts[1]) : 'User';
            }
        }

        if ($lastName === null || $lastName === '') {
            $lastName = 'User';
        }

        $email = 'customer@example.com';
        if (!$blank($customer['customer_email'] ?? null)) {
            $email = trim((string) $customer['customer_email']);
        } elseif (!$blank($customer['email'] ?? null)) {
            $email = trim((string) $customer['email']);
        }

        $phone = '+2250000000';
        if (!$blank($customer['customer_phone_number'] ?? null)) {
            $phone = (string) $customer['customer_phone_number'];
        } elseif (!$blank($customer['phone'] ?? null)) {
            $phone = (string) $customer['phone'];
        }
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . ltrim($phone, '0');
        }

        $address = 'Default Address';
        if (!$blank($customer['customer_address'] ?? null)) {
            $address = trim((string) $customer['customer_address']);
        } elseif (!$blank($customer['address'] ?? null)) {
            $address = trim((string) $customer['address']);
        }

        $city = 'Abidjan';
        if (!$blank($customer['customer_city'] ?? null)) {
            $city = trim((string) $customer['customer_city']);
        } elseif (!$blank($customer['city'] ?? null)) {
            $city = trim((string) $customer['city']);
        }

        $country = 'CI';
        if (!$blank($customer['customer_country'] ?? null)) {
            $country = strtoupper(trim((string) $customer['customer_country']));
        } elseif (!$blank($customer['country'] ?? null)) {
            $country = strtoupper(trim((string) $customer['country']));
        }
        if (strlen($country) !== 2) {
            $country = 'CI';
        }

        $state = 'AB';
        if (!$blank($customer['customer_state'] ?? null)) {
            $state = strtoupper(trim((string) $customer['customer_state']));
        } elseif (!$blank($customer['state'] ?? null)) {
            $state = strtoupper(trim((string) $customer['state']));
        }
        if (strlen($state) !== 2) {
            $state = 'AB';
        }

        $zipCode = '00000';
        if (!$blank($customer['customer_zip_code'] ?? null)) {
            $zipCode = (string) $customer['customer_zip_code'];
        } elseif (!$blank($customer['zip_code'] ?? null)) {
            $zipCode = (string) $customer['zip_code'];
        }
        $zipCode = substr(preg_replace('/\s+/', '', $zipCode), 0, 10);
        if ($zipCode === '') {
            $zipCode = '00000';
        }
        if (strlen($zipCode) < 5) {
            $zipCode = str_pad(substr($zipCode, 0, 5), 5, '0', STR_PAD_LEFT);
        }

        return [
            'customer_name'         => $firstName,
            'customer_surname'      => $lastName,
            'customer_email'        => $email,
            'customer_phone_number' => $phone,
            'customer_address'      => $address,
            'customer_city'         => $city,
            'customer_country'      => $country,
            'customer_state'        => $state,
            'customer_zip_code'     => $zipCode,
        ];
    }
}
