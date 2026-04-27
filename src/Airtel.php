<?php

namespace Epmnzava\Airtel;

use Illuminate\Support\Facades\Log;

class Airtel
{


protected $client_id;
protected $client_secret;
protected $baseurl;

public function __construct($client_id = null, $client_secret = null, $baseurl = null)
   {
      $this->client_id = $client_id;
      $this->client_secret = $client_secret;
      $this->baseurl = $baseurl;
 
   }

   public function create_token()
   {
      $payload = [
         'client_id' => $this->client_id,
         'client_secret' => $this->client_secret,
         'grant_type' => 'client_credentials',
      ];

      $ch = curl_init();

   $curl = curl_init();

   $url=$this->baseurl.'/auth/oauth2/token';

curl_setopt_array($curl, array(
  CURLOPT_URL =>$url,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => json_encode($payload),
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
  ),
));

$response = curl_exec($curl);

      if ($response === false) {
         $error = curl_error($ch);
         curl_close($ch);

         throw new \RuntimeException('Airtel token request failed: '.$error);
      }

      curl_close($ch);

      $responseData = json_decode($response, true);
      if (!is_array($responseData) || !isset($responseData['access_token'])) {
         throw new \RuntimeException('Airtel token response does not contain access_token');
      }



      return $responseData['access_token'];

   }
  
   
   public function collect($reference,$requestid,$msisdn,$amount,$subscriber_country='TZ',$subscriber_currency='TZS',$transaction_country='TZ',$transaction_currency='TZS')
   {
 
   $token= $this->create_token();


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

      $url=$this->baseurl."/merchant/v1/payments";
      $ch = curl_init($url);

      curl_setopt_array($ch, [
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_POST => true,
         CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Country' => 'TZ',
            'X-Currency' => 'TZS',
            'Authorization' => 'Bearer ' . $token,  
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

   
      $responseData = json_decode($responseBody, true);

      return $responseData;
   }
}
