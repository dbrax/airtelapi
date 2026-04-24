<?php

namespace Epmnzava\Airtel;

class Airtel
{


  
   
   public function collect($baseurl,$reference,$subscriber_country,$subscriber_currency,$msisdn,$amount,$transaction_country,$transaction_currency,$requestid)
   {
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

      $ch = curl_init($baseurl);

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

         throw new \RuntimeException('Airtel collect request failed: '.$error);
      }

      $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

      return [
         'status' => $httpStatus,
         'body' => json_decode($responseBody, true),
         'raw' => $responseBody,
         'request' => $payload,
      ];

   }
}
