<?php

namespace Workdo\AuthorizeNet\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

use InvalidArgumentException;

class AuthorizeNetService
{
    private $apiLoginId;
    private $transactionKey;
    private $baseUrl;
    private $environment;

    public function __construct($config = [])
    {
        $this->apiLoginId = $config['api_login_id'] ?? '';
        $this->transactionKey = $config['transaction_key'] ?? '';
        $this->environment = $config['mode'] ?? 'sandbox';

        $this->baseUrl = $this->environment === 'live'
            ? 'https://api.authorize.net/xml/v1/request.api'
            : 'https://apitest.authorize.net/xml/v1/request.api';
    }

    /**
     * Redirect to checkout page with payment data
     * 
     * @param array $paymentData
     * @param object $user
     * @return \Inertia\Response
     */
    public function redirectToCheckout(array $paymentData, $user)
    {
        return Inertia::render('AuthorizeNet/AuthorizeNetCheckout', [
            'payment_data' => $paymentData,
            'user' => $user
        ]);
    }

    /**
     * Create a credit card payment transaction
     * 
     * @param array $paymentData
     * @return object
     * @throws Exception
     */
    public function createPayment(array $paymentData)
    {
        $this->validatePaymentData($paymentData);

        $requestData = [
            'createTransactionRequest' => [
                'merchantAuthentication' => [
                    'name' => $this->apiLoginId,
                    'transactionKey' => $this->transactionKey
                ],
                'refId' => $paymentData['reference_id'] ?? uniqid('auth_'),
                'transactionRequest' => [
                    'transactionType' => 'authCaptureTransaction',
                    'amount' => number_format($paymentData['amount'], 2, '.', ''),
                    'payment' => [
                        'creditCard' => [
                            'cardNumber' => $paymentData['card_number'],
                            'expirationDate' => $paymentData['expiration_date'], // YYYY-MM format
                            'cardCode' => $paymentData['cvv']
                        ]
                    ],
                    'order' => [
                        'invoiceNumber' => $paymentData['invoice_number'] ?? '',
                        'description' => $paymentData['description'] ?? ''
                    ],
                    'billTo' => [
                        'firstName' => $paymentData['billing']['first_name'] ?? '',
                        'lastName' => $paymentData['billing']['last_name'] ?? '',
                        'company' => $paymentData['billing']['company'] ?? '',
                        'address' => $paymentData['billing']['address'] ?? '',
                        'city' => $paymentData['billing']['city'] ?? '',
                        'state' => $paymentData['billing']['state'] ?? '',
                        'zip' => $paymentData['billing']['zip'] ?? '',
                        'country' => $paymentData['billing']['country'] ?? '',
                        'phoneNumber' => $paymentData['billing']['phone'] ?? '',
                        'faxNumber' => $paymentData['billing']['fax'] ?? '',
                        'email' => $paymentData['billing']['email'] ?? ''
                    ],
                    'customerIP' => $paymentData['customer_ip'] ?? $_SERVER['REMOTE_ADDR'] ?? ''
                ]
            ]
        ];

        return $this->makeRequest($requestData);
    }

    /**
     * Make HTTP request to AuthorizeNet API
     * 
     * @param array $requestData
     * @return object
     * @throws Exception
     */
    private function makeRequest(array $requestData)
    {
        $response = Http::timeout(30)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->baseUrl, $requestData);

        if ($response->successful()) {
            $jsonResponse = json_decode(preg_replace('/^\x{FEFF}/u', '', $response->body()));

            if (isset($jsonResponse->messages)) {
                if ($jsonResponse->messages->resultCode === 'Error') {
                    $errorMessage = isset($jsonResponse->messages->message[0]) ? $jsonResponse->messages->message[0]->text : __('Unknown API error.');
                    throw new Exception(__('AuthorizeNet API Error: :message', ['message' => $errorMessage]));
                }
            }
            return $jsonResponse;
        }
        throw new Exception(__('AuthorizeNet API Error: Something went wrong.'));
    }

    /**
     * Validate payment data
     * 
     * @param array $data
     * @throws InvalidArgumentException
     */
    private function validatePaymentData(array $data)
    {
        $required = ['amount', 'card_number', 'expiration_date', 'cvv'];

        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new InvalidArgumentException(__('The required field is missing: :field', ['field' => $field]));
            }
        }

        if ($data['amount'] <= 0) {
            throw new InvalidArgumentException(__('The amount must be greater than 0.'));
        }

        if (!preg_match('/^\d{13,19}$/', $data['card_number'])) {
            throw new InvalidArgumentException(__('The card number format is invalid.'));
        }

        if (!preg_match('/^\d{4}-\d{2}$/', $data['expiration_date'])) {
            throw new InvalidArgumentException(__('The expiration date format is invalid. Use YYYY-MM.'));
        }

        if (!preg_match('/^\d{3,4}$/', $data['cvv'])) {
            throw new InvalidArgumentException(__('The CVV format is invalid.'));
        }
    }
}
