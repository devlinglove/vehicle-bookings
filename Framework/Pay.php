<?php 
namespace Framework;

use InvalidArgumentException;
use RuntimeException;

class Pay
{
    private array $config;

    public function __construct()
    {
        $this->config = require basePath('config/payfast.php');
    }

    public function createPayment(mixed $order): string
    {
        $amount = $order->amount;

        if ($amount <= 0) {
            throw new InvalidArgumentException('Invalid payment amount.');
        }

        $basketId = $order->booking_id ?? '';

        if ($basketId === '') {
            throw new InvalidArgumentException('Order ID is required.');
        }

        $currency = $this->config['currency_code'] ?? 'PKR';

        // Request the access token from PayFast.
        $token = $this->getAccessToken(
            $basketId,
            $amount,
            $currency
        );

        var_dump($token);

        // Checkout form parameters.
        $fields = [
            'CURRENCY_CODE' => $currency,
            'MERCHANT_ID' => $this->config['merchant_id'],
            'MERCHANT_NAME' => $this->config['merchant_name'],
            'TOKEN' => $token,
            'BASKET_ID' => $basketId,
            'TXNAMT' => $this->formatAmount($amount),
            'ORDER_DATE' => date('Y-m-d H:i:s'),
            'SUCCESS_URL' => $this->config['success_url'],
            'FAILURE_URL' => $this->config['failure_url'],
            'CHECKOUT_URL' => '',
            'CUSTOMER_EMAIL_ADDRESS' => $order['customer_email'],
            'CUSTOMER_MOBILE_NO' => $order['customer_phone'],
            'PROCCODE' => $this->config['proccode'] ?? '00',
        ];

        return $this->generateCheckoutForm($fields);
    }

    /**
     * Fetch access token from PayFast.
     */
    private function getAccessToken(
        string $basketId,
        float $amount,
        string $currency
    ): string {
        $payload = [
            'MERCHANT_ID' => $this->config['merchant_id'],
            'SECURED_KEY' => $this->config['merchant_key'],
            'BASKET_ID' => $basketId,
            'TXNAMT' => $this->formatAmount($amount),
            'CURRENCY_CODE' => $currency,
        ];

        $ch = curl_init($this->config['token_url']);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ],
            CURLOPT_USERAGENT => 'PHP PayFast Integration',
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);

            throw new RuntimeException(
                'PayFast connection failed: ' . $error
            );
        }

        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpStatus < 200 || $httpStatus >= 300) {
            throw new RuntimeException(
                'PayFast token API returned HTTP ' . $httpStatus
            );
        }

        $result = json_decode($response, true);

        if (!is_array($result)) {
            throw new RuntimeException(
                'Invalid JSON response from PayFast.'
            );
        }

        if (empty($result['ACCESS_TOKEN'])) {
            throw new RuntimeException(
                'PayFast did not return an access token: ' . $response
            );
        }

        return $result['ACCESS_TOKEN'];
    }

    /**
     * Generate an auto-submitting HTML checkout form.
     */
    private function generateCheckoutForm(array $fields): string
    {
        $checkoutUrl = $this->config['checkout_url'];

        $html = '<!DOCTYPE html>';
        $html .= '<html><head><meta charset="UTF-8">';
        $html .= '<title>Redirecting to PayFast</title>';
        $html .= '</head><body>';
        $html .= '<p>Redirecting to payment gateway...</p>';

        $html .= '<form id="payfast-form" method="POST" action="'
            . htmlspecialchars($checkoutUrl, ENT_QUOTES, 'UTF-8')
            . '">';

        foreach ($fields as $key => $value) {
            $html .= '<input type="hidden" name="'
                . htmlspecialchars($key, ENT_QUOTES, 'UTF-8')
                . '" value="'
                . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8')
                . '">';
        }

        $html .= '</form>';

        $html .= '<script>';
        $html .= 'document.getElementById("payfast-form").submit();';
        $html .= '</script>';

        $html .= '</body></html>';

        return $html;
    }

    /**
     * Verify PayFast transaction response.
     *
     * $payment must contain the original:
     * basket_id and amount stored in your database.
     */
    public function verifyPayment(
        array $data,
        array $payment
    ): bool {
        $requiredFields = [
            'err_code',
            'Response_Key',
            'basket_id',
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }

        // Compare returned basket ID against our stored basket ID.
        if ((string)$data['basket_id'] !==
            (string)$payment['basket_id']) {
            return false;
        }

        $errCode = (string)$data['err_code'];

        // PayFast success codes found in the integration reference.
        if (!in_array($errCode, ['000', '00'], true)) {
            return false;
        }

        $merchantId = (string)$this->config['merchant_id'];
        $basketId = (string)$payment['basket_id'];
        $secretWord = (string)($this->config['secret_word'] ?? '');

        // Use the original amount from your database.
        $amount = $this->formatAmount(
            (float)$payment['amount']
        );

        $responseString =
            $merchantId .
            $basketId .
            $secretWord .
            $amount .
            $errCode;

        $expectedHash = md5($responseString);

        return hash_equals(
            strtolower($expectedHash),
            strtolower((string)$data['Response_Key'])
        );
    }

    /**
     * Format amount consistently.
     */
    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
