<?php

namespace Epmnzava\Airtel;

class Airtel
{


protected $token;
protected $client_id;
protected $client_secret;
protected $baseurl;

public function __construct($client_id = null, $client_secret = null, $baseurl = null)
   {
      $this->client_id = $client_id;
      $this->client_secret = $client_secret;
      $this->baseurl = $baseurl;
      if ($client_id && $client_secret && $baseurl) {
         $this->token = $this->create_token();
      }
   }

   public function create_token()
   {
      $payload = [
         'client_id' => $this->client_id,
         'client_secret' => $this->client_secret,
         'grant_type' => 'client_credentials',
      ];

      $ch = curl_init($this->baseurl.'/auth/oauth2/token');

      curl_setopt_array($ch, [
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_POST => true,
         CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
         ],
         CURLOPT_POSTFIELDS => json_encode($payload),
      ]);

      $responseBody = curl_exec($ch);

      if ($responseBody === false) {
         $error = curl_error($ch);
         curl_close($ch);

         throw new \RuntimeException('Airtel token request failed: '.$error);
      }

      curl_close($ch);

      $responseData = json_decode($responseBody, true);
      if (!is_array($responseData) || !isset($responseData['access_token'])) {
         throw new \RuntimeException('Airtel token response does not contain access_token');
      }

      return $responseData['access_token'];

   }
  
   
   public function collect($reference,$requestid,$msisdn,$amount,$subscriber_country='TZ',$subscriber_currency='TZS',$transaction_country='TZ',$transaction_currency='TZS')
   {
      if (!is_numeric($amount) || $amount <= 0) {
         throw new \InvalidArgumentException('Amount must be greater than zero');
      }
      if ($amount < 100) {
         throw new \InvalidArgumentException('Amount must be at least 100');
      }
      if ($amount > 7000000) {
         throw new \InvalidArgumentException('Amount must not exceed 7,000,000');
      }
      if (!preg_match('/^\d{10}$/', $msisdn)) {
         throw new \InvalidArgumentException('Invalid MSISDN: must be a 10-digit number');
      }

      $payload = [
         'reference' => $reference,
         'subscriber' => [
            'country' => $subscriber_country,
            'currency' => $subscriber_currency,
            'msisdn' => $msisdn,
         ],
         'transaction' => [
            'amount' => $amount,
            'country' => $transaction_country,
            'currency' => $transaction_currency,
            'id' => $requestid,
         ],
      ];

      $ch = curl_init($this->baseurl);

      curl_setopt_array($ch, [
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_POST => true,
         CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Country' => 'TZ',
            'X-Currency' => 'TZS',
            'Authorization' => 'Bearer ' . $this->token,  
         ],
         CURLOPT_POSTFIELDS => json_encode($payload),
      ]);

      $responseBody = curl_exec($ch);

      if ($responseBody === false) {
         $error = curl_error($ch);
         curl_close($ch);

         throw new \RuntimeException('Airtel collect request failed: '.$error);
      }

      curl_close($ch);

      return $payload;

   }
}
