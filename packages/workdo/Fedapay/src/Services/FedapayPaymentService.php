<?php

namespace Workdo\Fedapay\Services;

use Exception;
use InvalidArgumentException;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;

class FedapayPaymentService
{
    protected $config;
    protected $requiredConfigVars = ['fedapay_public_key' => true, 'fedapay_secret_key' => true];
    protected $configVars = ['fedapay_public_key' => null, 'fedapay_secret_key' => null, 'fedapay_mode' => 'sandbox'];

    public function __construct($config = [])
    {
        $this->config = $config;
        foreach ($this->requiredConfigVars as $param => $required) {
            if (array_key_exists($param, $config)) {
                $this->configVars[$param] = $config[$param];
            } elseif ($required) {
                throw new InvalidArgumentException(__('Missing required parameter: :param', ['param' => $param]));
            }
        }
        
        if (isset($config['fedapay_mode'])) {
            $this->configVars['fedapay_mode'] = $config['fedapay_mode'];
        }
    }

    protected function getUrl($endpoint = "")
    {
        $mode = $this->configVars['fedapay_mode'] ?? 'sandbox';
        $url = ($mode == 'live') ? "https://api.fedapay.com/v1" : "https://sandbox-api.fedapay.com/v1";
        return $url . $endpoint;
    }

    /**
     * Initialize a FedaPay transaction
     *
     * @param array<string, mixed> $data
     * @return object{success: bool, url: string|null, reference?: string, message?: string}
     */
    public function initializeTransaction($data)
    {
        try {
            if (empty($data['currency'])) {
                throw new Exception(__('Currency is required.'));
            }

            // FedaPay requires amount as integer in minor currency units (cents)
            $amountInCents = (int)round($data['price'] * 100);

            // Build callback URL with order_id parameter
            $callbackUrl = $data['url'];
            $separator = strpos($callbackUrl, '?') !== false ? '&' : '?';
            $callbackUrl .= $separator . 'order_id=' . $data['order_id'];

            $payload = [
                'description' => $data['product'] ?? __('Payment'),
                'amount' => $amountInCents,
                'currency' => [
                    'iso' => strtoupper($data['currency'])
                ],
                'callback_url' => $callbackUrl,
                'cancel_url' => $callbackUrl,
            ];

            try {
                /** @var \Illuminate\Http\Client\Response $response */
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->configVars['fedapay_secret_key'],
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])->post($this->getUrl('/transactions'), $payload);
            } catch (\Exception $httpException) {
                throw new Exception(__('Failed to connect to FedaPay API: ') . $httpException->getMessage());
            }

            /** @var array<string, mixed>|null $responseData */
            $responseData = $response->json();

            if (!$response->successful()) {
                $errorMessage = __('Transaction creation failed');
                
                // Try to extract error message from various possible response structures
                if (isset($responseData['message']) && !empty($responseData['message'])) {
                    $errorMessage = $responseData['message'];
                }
                
                // Handle validation errors array
                if (isset($responseData['errors']) && is_array($responseData['errors'])) {
                    $errors = [];
                    foreach ($responseData['errors'] as $field => $messages) {
                        if (is_array($messages)) {
                            foreach ($messages as $msg) {
                                $errors[] = is_string($field) ? "$field: $msg" : $msg;
                            }
                        } else {
                            $errors[] = is_string($messages) ? $messages : json_encode($messages);
                        }
                    }
                    if (!empty($errors)) {
                        $errorMessage = implode('. ', $errors);
                    }
                }
                
                // Handle error object
                if (isset($responseData['error'])) {
                    if (is_string($responseData['error'])) {
                        $errorMessage = $responseData['error'];
                    } elseif (is_array($responseData['error'])) {
                        if (isset($responseData['error']['message'])) {
                            $errorMessage = $responseData['error']['message'];
                        } elseif (isset($responseData['error']['description'])) {
                            $errorMessage = $responseData['error']['description'];
                        }
                    }
                }
                
                // Handle reason field
                if (isset($responseData['reason']) && !empty($responseData['reason'])) {
                    $errorMessage = $responseData['reason'];
                }
                
                throw new Exception($errorMessage);
            }

            /** @var array<string, mixed> $result */
            $result = $response->json();
            $transaction = $result['v1/transaction'] ?? null;

            if (!$transaction) {
                throw new Exception(__('Invalid transaction response'));
            }
            
            // Use payment_url directly from transaction response if available
            $paymentUrl = $transaction['payment_url'] ?? null;

            Session::put($data['order_id'], [
                'payment_reference' => $transaction['reference'] ?? $data['order_id'],
                'transaction_id' => $transaction['id'],
                'other' => $data['session'] ?? []
            ]);

            if (!$paymentUrl) {
                throw new Exception(__('Payment URL not found in transaction response'));
            }

            return (object) [
                'success' => true,
                'url' => $paymentUrl,
                'reference' => $transaction['reference'] ?? $data['order_id']
            ];

        } catch (\Exception $e) {
            return (object) [
                'success' => false,
                'message' => $e->getMessage(),
                'url' => null
            ];
        }
    }

    /**
     * Verify a FedaPay transaction
     *
     * @param string|int $transactionId
     * @return array<string, mixed>
     */
    public function verifyTransaction($transactionId)
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->configVars['fedapay_secret_key'],
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->get($this->getUrl('/transactions/' . $transactionId));

            if ($response->successful()) {
                /** @var array<string, mixed> $result */
                $result = $response->json();
                $transaction = $result['v1/transaction'] ?? [];
                
                return [
                    'requestSuccessful' => true,
                    'status' => $transaction['status'] ?? 'pending',
                    'reference' => $transaction['reference'] ?? null,
                    'amount' => $transaction['amount'] ?? 0
                ];
            }
            
            return [
                'requestSuccessful' => false,
                'responseMessage' => __('Transaction verification failed.')
            ];
        } catch (\Exception $e) {
            return [
                'requestSuccessful' => false,
                'responseMessage' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if payment was successful
     *
     * @param array<string, mixed> $result
     * @return bool
     */
    public function isPaymentSuccessful($result): bool
    {
        return isset($result['requestSuccessful']) && 
               $result['requestSuccessful'] && 
               isset($result['status']) && 
               in_array($result['status'], ['approved', 'transferred']);
    }

    /**
     * Create a FedaPay service instance
     *
     * @param array<string, mixed> $config
     * @return self
     */
    public static function createInstance($config)
    {
        return new self([
            'fedapay_public_key' => $config['fedapay_public_key'] ?? '',
            'fedapay_secret_key' => $config['fedapay_secret_key'] ?? '',
            'fedapay_mode' => $config['fedapay_mode'] ?? 'sandbox'
        ]);
    }

    /**
     * Create a FedaPay service instance from settings
     *
     * @param int|null $userId
     * @return self
     */
    public static function createFromSettings($userId = null)
    {
        if ($userId) {
            return self::createInstance([
                'fedapay_public_key' => company_setting('fedapay_public_key', $userId),
                'fedapay_secret_key' => company_setting('fedapay_secret_key', $userId),
                'fedapay_mode' => company_setting('fedapay_mode', $userId)
            ]);
        }
        
        $adminSettings = getAdminAllSetting();
        return self::createInstance($adminSettings);
    }
}
