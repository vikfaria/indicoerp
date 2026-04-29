<?php

namespace Workdo\Toyyibpay\Services;

use Exception;

class ToyyibpayService
{
    private $userSecretKey;
    private $categoryCode;
    private $baseUrl;

    public function __construct($config = null)
    {
        $this->userSecretKey = $config['user_secret_key'] ?? null;
        $this->categoryCode = $config['category_code'] ?? null;
        $this->baseUrl = 'https://toyyibpay.com';
    }

    public function createBill(array $billData): array
    {
        $payload = array_merge($billData, [
            'userSecretKey' => $this->userSecretKey,
            'categoryCode' => $this->categoryCode,
            'billPriceSetting' => 1,
            'billPayorInfo' => 1,
            'billAmount' => (int) ($billData['billAmount'] * 100),
            'billExternalReferenceNo' => 'AFR341DFI',
            'billSplitPayment' => 0,
            'billSplitPaymentArgs' => '',
            'billPaymentChannel' => '0',
            'billChargeToCustomer' => 1,
            'billExpiryDays' => 3,
            'billExpiryDate' => date('d-m-Y', strtotime('+3 days')),
            'billContentEmail' => __('Thank you for your payment!')
        ]);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_URL, $this->baseUrl . '/index.php/api/createBill');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        $result = curl_exec($curl);
        curl_close($curl);
        
        $obj = json_decode($result);
        
        if ($obj && is_array($obj) && isset($obj[0]->BillCode)) {
            return ['BillCode' => $obj[0]->BillCode];
        }
        
        throw new Exception(__('Invalid response from Toyyibpay API: ') . $result);
    }

    public function getBillTransactions(string $billCode): array
    {
        $payload = [
            'userSecretKey' => $this->userSecretKey,
            'billCode' => $billCode,
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_URL, $this->baseUrl . '/index.php/api/getBillTransactions');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        $result = curl_exec($curl);
        curl_close($curl);
        
        return json_decode($result, true) ?: [];
    }

    public function getPaymentUrl(string $billCode): string
    {
        return $this->baseUrl . '/' . $billCode;
    }
}