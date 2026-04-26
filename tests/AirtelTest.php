<?php

namespace Epmnzava\Airtel\Tests;

use Epmnzava\Airtel\Airtel;
use Epmnzava\Airtel\CurlMockState;
use PHPUnit\Framework\TestCase;

// Load the namespace-level cURL stubs before any test runs.
require_once __DIR__ . '/CurlMock.php';

class AirtelTest extends TestCase
{
    private Airtel $airtel;

    private const BASE_URL             = 'https://openapiuat.airtel.co.tz/merchant/v1/payments/';
    private const CLIENT_ID            = 'test_client_id';
    private const CLIENT_SECRET        = 'test_client_secret';
    private const REFERENCE            = 'Testing transaction';
    private const SUBSCRIBER_COUNTRY   = 'TZ';
    private const SUBSCRIBER_CURRENCY  = 'TZS';
    private const MSISDN               = '0689259497';
    private const AMOUNT               = 1000;
    private const TRANSACTION_COUNTRY  = 'TZ';
    private const TRANSACTION_CURRENCY = 'TZS';
    private const REQUEST_ID           = 'random-unique-id';

    protected function setUp(): void
    {
        CurlMockState::reset();
        CurlMockState::$execReturn = json_encode(['access_token' => 'mock_token', 'expires_in' => '180', 'token_type' => 'bearer']);
        $this->airtel = new Airtel(self::CLIENT_ID, self::CLIENT_SECRET, self::BASE_URL);
        CurlMockState::reset();
    }

    private function callCollect(): array
    {
        return $this->airtel->collect(
            self::REFERENCE,
            self::REQUEST_ID,
            self::MSISDN,
            self::AMOUNT,
            self::SUBSCRIBER_COUNTRY,
            self::SUBSCRIBER_CURRENCY,
            self::TRANSACTION_COUNTRY,
            self::TRANSACTION_CURRENCY,
        );
    }

    // Payload structure

    public function test_collect_builds_correct_payload_structure(): void
    {
        CurlMockState::$execReturn = json_encode(['status' => 'SUCCESS']);
        $result = $this->callCollect();
        $this->assertArrayHasKey('reference',   $result);
        $this->assertArrayHasKey('subscriber',  $result);
        $this->assertArrayHasKey('transaction', $result);
    }

    public function test_collect_sets_correct_reference(): void
    {
        CurlMockState::$execReturn = json_encode(['status' => 'SUCCESS']);
        $result = $this->callCollect();
        $this->assertSame(self::REFERENCE, $result['reference']);
    }

    public function test_collect_sets_correct_subscriber_fields(): void
    {
        CurlMockState::$execReturn = json_encode(['status' => 'SUCCESS']);
        $result     = $this->callCollect();
        $subscriber = $result['subscriber'];
        $this->assertSame(self::SUBSCRIBER_COUNTRY,  $subscriber['country']);
        $this->assertSame(self::SUBSCRIBER_CURRENCY, $subscriber['currency']);
        $this->assertSame(self::MSISDN,              $subscriber['msisdn']);
    }

    public function test_collect_sets_correct_transaction_fields(): void
    {
        CurlMockState::$execReturn = json_encode(['status' => 'SUCCESS']);
        $result      = $this->callCollect();
        $transaction = $result['transaction'];
        $this->assertSame(self::AMOUNT,               $transaction['amount']);
        $this->assertSame(self::TRANSACTION_COUNTRY,  $transaction['country']);
        $this->assertSame(self::TRANSACTION_CURRENCY, $transaction['currency']);
        $this->assertSame(self::REQUEST_ID,           $transaction['id']);
    }

    // HTTP request tests

    public function test_collect_sends_to_correct_url(): void
    {
        CurlMockState::$execReturn = json_encode(['status' => 'SUCCESS']);
        $this->callCollect();
        $this->assertSame(self::BASE_URL, CurlMockState::$capturedUrl);
    }

    public function test_collect_sends_json_content_type_header(): void
    {
        CurlMockState::$execReturn = json_encode(['status' => 'SUCCESS']);
        $this->callCollect();
        $headers = CurlMockState::$capturedOptions[CURLOPT_HTTPHEADER];
        $this->assertContains('Content-Type: application/json', $headers);
    }

    public function test_collect_sends_accept_json_header(): void
    {
        CurlMockState::$execReturn = json_encode(['status' => 'SUCCESS']);
        $this->callCollect();
        $headers = CurlMockState::$capturedOptions[CURLOPT_HTTPHEADER];
        $this->assertContains('Accept: application/json', $headers);
    }

    public function test_collect_sends_payload_as_json_encoded_post_fields(): void
    {
        CurlMockState::$execReturn = json_encode(['status' => 'SUCCESS']);
        $result   = $this->callCollect();
        $sentJson = CurlMockState::$capturedOptions[CURLOPT_POSTFIELDS];
        $this->assertJson($sentJson);
        $this->assertSame($result, json_decode($sentJson, true));
    }

    // Response handling

    public function test_collect_returns_payload_structure(): void
    {
        CurlMockState::$execReturn = json_encode(['status' => 'SUCCESS']);
        $result = $this->callCollect();
        $this->assertArrayHasKey('reference',   $result);
        $this->assertArrayHasKey('subscriber',  $result);
        $this->assertArrayHasKey('transaction', $result);
    }

    // cURL failure

    public function test_collect_throws_runtime_exception_on_curl_failure(): void
    {
        CurlMockState::$execReturn = false;
        CurlMockState::$error      = 'Could not resolve host';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Airtel collect request failed: Could not resolve host');
        $this->callCollect();
    }

    // Amount validation

    public function test_collect_throws_exception_for_zero_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be greater than zero');
        $this->airtel->collect(uniqid('ref_', true), uniqid('req_', true), '0689259497', 0);
    }

    public function test_collect_throws_exception_for_negative_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be greater than zero');
        $this->airtel->collect(uniqid('ref_', true), uniqid('req_', true), '0689259497', -500);
    }

    public function test_collect_throws_exception_for_amount_below_minimum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be at least 100');
        $this->airtel->collect(uniqid('ref_', true), uniqid('req_', true), '0689259497', 99);
    }

    public function test_collect_throws_exception_for_amount_above_maximum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must not exceed 7,000,000');
        $this->airtel->collect(uniqid('ref_', true), uniqid('req_', true), '0689259497', 7000001);
    }

    // MSISDN validation

    public function test_collect_accepts_valid_msisdn_0689259497(): void
    {
        CurlMockState::$execReturn = json_encode(['status' => 'SUCCESS']);
        $result = $this->airtel->collect(uniqid('ref_', true), uniqid('req_', true), '0689259497', 500);
        $this->assertSame('0689259497', $result['subscriber']['msisdn']);
    }

    public function test_collect_accepts_subscriber_msisdn_0679079774(): void
    {
        CurlMockState::$execReturn = json_encode(['status' => 'SUCCESS']);
        $result = $this->airtel->collect(uniqid('ref_', true), uniqid('req_', true), '0679079774', 500);
        $this->assertSame('0679079774', $result['subscriber']['msisdn']);
    }

    // Happy path

    public function test_collect_succeeds_with_valid_amount_and_msisdn(): void
    {
        CurlMockState::$execReturn = json_encode(['status' => 'SUCCESS']);
        $reference = uniqid('ref_', true);
        $requestId = uniqid('req_', true);
        $result = $this->airtel->collect($reference, $requestId, '0689259497', 1000);
        $this->assertSame($reference,   $result['reference']);
        $this->assertSame($requestId,   $result['transaction']['id']);
        $this->assertSame(1000,         $result['transaction']['amount']);
        $this->assertSame('0689259497', $result['subscriber']['msisdn']);
    }
}
