<?php 
namespace Framework;

use InvalidArgumentException;
use RuntimeException;
use Safepay\SafepayClient;

class SafePay
{
    private array $config;
    private \Safepay\SafepayClient $safepay;
    private TripDb $db;

    public function __construct()
    {
        $this->config = require basePath('config/safepay.php');
        
        $this->safepay = new \Safepay\SafepayClient([
            'api_key' => 'sec_15f18f37-9a17-4653-b476-23fd0d972c6f',
            'api_base' => 'https://sandbox.api.getsafepay.com'
        ]);

        $this->db = new TripDb();
    }

    public function fetchTrackerInfo(string $trackerToken)
    {
        $url = 'https://sandbox.api.getsafepay.com/reporter/api/v1/payments/'. rawurlencode($trackerToken);

        $apiToken = $this->config['secret_key'] ?? '';

        if (empty($apiToken)) {
            throw new \RuntimeException('Safepay API token is missing.');
        }

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-SFPY-MERCHANT-SECRET: 69711833efb75ac39b4d2cd04a9c4be4bb7c244c57f44ccabe1b86ea00351084'
            ],
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);

            throw new \RuntimeException($error);
        }

        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode !== 200) {
            throw new \RuntimeException(
                "Safepay API returned HTTP $httpCode: " . $response
            );
        }

        return $result['data']['state'] ?? null;
    }


    // public function fetchTrackerInfo(string $trackerToken)
    // {

    //     try {
    //         $reponse = $this->safepay->reporter->payments->fetch($trackerToken);

    //         $result = $reponse->toArray();

    //         $state = $result['tracker']['state'];
            
    //         return $state;


    //     } catch (\UnexpectedValueException $e) {
            
    //         http_response_code(400);
    //         throw $e;
    //     }
    // }

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

        //create session
        $sessionToken = $this->createSession($basketId, $amount);

        $paymentId = $this->storePayment($basketId, $amount, $sessionToken);

        //get token 
        $authToken = $this->getAuthToken();
        //checkout url
        $checkoutUrl = $this->createCheckoutUrl($sessionToken, $authToken);

        return $checkoutUrl;
    }

    

    private function createSession(mixed $basketId, float $amount){

        $data = [
            "merchant_api_key" => 'sec_15f18f37-9a17-4653-b476-23fd0d972c6f',
            "intent"           => "CYBERSOURCE",
            "mode"             => "payment",
            "entry_mode"       => "raw",
            "currency"         => $this->config['currency_code'],
            "amount"           => $amount,
            "metadata"         => [
                "order_id" => (string) $basketId
            ],
            "include_fees"     => false
        ];

        try {
              $session = $this->safepay->order->setup($data);
            //var_dump($session);

            // echo '<pre>';
            //     print_r($session->toArray());
            // echo '</pre>';

            $response = $session->toArray();

            $token = $response['tracker']['token'];
            
            return $token;
             
 
        } catch(\UnexpectedValueException $e) {
                // Invalid payload
                http_response_code(400);
                exit();
        }
        
    }

    private function getAuthToken()
    {

        $url = 'https://sandbox.api.getsafepay.com/client/passport/v1/token';

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'X-SFPY-MERCHANT-SECRET: 69711833efb75ac39b4d2cd04a9c4be4bb7c244c57f44ccabe1b86ea00351084'
            ],
        ]);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            die('cURL Error: ' . curl_error($curl));
        }

        $result = json_decode($response, true);

        curl_close($curl);

        $result = json_decode($response, true);

        return $result['data'] ?? false;
        
    }

    // private function getAuthToken(){

    //     try {
    //         $response = $this->safepay->passport->create();
    //         echo '<pre>';
    //             print_r($response->toArray());
    //         echo '</pre>';
    //         var_dump($response);
    //         return $response->data;
    //     } catch (\UnexpectedValueException $e) {
    //         // Invalid payload
    //         http_response_code(400);
    //         exit();
    //     }
        
    // }

    private function createCheckoutUrl(string $token, string $authToken)
    {
        try {
            $checkoutURL = \Safepay\Checkout::constructURL([
                // either production or sandbox
                "environment" => "sandbox",
                // this the tracker.token from the payment session response
                "tracker" => $token,
                // the authentication token
                "tbt" => $authToken,
                // attach a source, available options are: hosted, popup, mobile, woocommerce, and shopify
                "source" => "hosted",
                // OPTIONAL attach the customer.token from the create customer response to prefill the payment form
                "user_id" => "1234567890",
                // the success URL to redirect your customer to after payment complete
                "redirect_url" => "https://1f62-39-46-10-36.ngrok-free.app/success",
                // the cancel URL to redirect your customer to after the customer cancels the checkout session
                "cancel_url" => "https://1f62-39-46-10-36.ngrok-free.app/cancel"
            ]);

            return $checkoutURL;

        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            http_response_code(400);
            exit();
        }
    }


    private function storePayment(int $id, int $fare, string $token){
        $stmt = 
            $this->db->getConn()->prepare("
                INSERT INTO payment (booking_id, gateway, tracker, amount, status)
                VALUES (?, 'safepay', ?, ?, 'pending')
            ");

            $stmt->execute([
                $id,
                $token,
                $fare
            ]);

        $paymentId = (int) $this->db->getConn()->lastInsertId();

        return $paymentId;
    }



}
