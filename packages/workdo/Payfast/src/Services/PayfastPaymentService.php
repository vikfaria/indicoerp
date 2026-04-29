<?php

namespace Workdo\Payfast\Services;

use Illuminate\Http\Request;
use Exception;
use InvalidArgumentException;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\TransferStats;

class PayfastPaymentService
{
    protected $config;
    protected $requiredConfigVars = ['payfast_merchant_id' => true, 'payfast_merchant_key' => true];
    protected $configVars = ['payfast_merchant_id' => null, 'payfast_merchant_key' => null, 'payfast_salt_passphrase' => null];

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
        
        if (array_key_exists('payfast_salt_passphrase', $config)) {
            $this->configVars['payfast_salt_passphrase'] = $config['payfast_salt_passphrase'];
        }
    }

    protected function getUrl($endpoint = "")
    {
        $mode = $this->config['payfast_mode'] ?? 'sandbox';
        $url = ($mode == 'live') ? "https://www.payfast.co.za" : "https://sandbox.payfast.co.za";
        return $url . $endpoint;
    }

    public function checkout($pay)
    {
        // Form Data
        $FormData = array(
            'merchant_id'   => $this->configVars['payfast_merchant_id'] ?? '',
            'merchant_key'  => $this->configVars['payfast_merchant_key'] ?? '',
            'return_url'    => $pay['url'] . '?status=success',
            'cancel_url'    => $pay['url'] . '?status=cancel',
            'notify_url'    => $pay['url'] . '?status=notify',
            'name_first'    => $pay['name'],
            'email_address' => $pay['email'],
            'm_payment_id'  => $pay['order_id'],
            'amount'        => number_format(sprintf('%.2f', $pay['price']), 2, '.', ''),
            'item_name'     => $pay['product'] ?? 'item',
        );
        
        // Generate Signature
        $FormData['signature'] = $this->generateSignature($FormData, $this->configVars['payfast_salt_passphrase']);

        $effectiveUrl = null;
        $response = Http::asForm()->withOptions([
            'on_stats' => function (TransferStats $stats) use (&$effectiveUrl) {
                $effectiveUrl = $stats->getHandlerStats() ?? null;
            }
        ])->post($this->getUrl('/eng/process'), $FormData);

        if ($response->successful()) {
            if ($effectiveUrl) {
                Session::put($pay['order_id'], [
                    'FormData' => array_diff_key($FormData, array_flip(['return_url', 'cancel_url', 'notify_url'])),
                    'other' => $pay['session']
                ]);
                $Url = $effectiveUrl['redirect_url'] == '' ? $effectiveUrl['url'] : $effectiveUrl['redirect_url'];
                return (object) ['success' => true, 'url' => $Url];
            }
        }
        return (object) ['success' => false, 'message' => __('Something went wrong, Please try again.'), 'url' => null];
    }

    protected function generateSignature($data, $passPhrase = null)
    {
        // Create parameter string
        $pfOutput = '';
        foreach ($data as $key => $val) {
            if ($val !== '') {
                $pfOutput .= $key . '=' . urlencode(trim($val)) . '&';
            }
        }

        // Remove last ampersand
        $getString = substr($pfOutput, 0, -1);
        if ($passPhrase !== null) {
            $getString .= '&passphrase=' . urlencode(trim($passPhrase));
        }
        return md5($getString);
    }
}