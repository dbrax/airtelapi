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

    // Common test-fixture values.
    private const BASE_URL             = 'https://openapiuat.airtel.co.tz/merchant/v1/payments/';
    private const CLIENT_ID            = 'test_client_id';
    private const CLIENT_SECRET        = 'test_client_secret';
    private const REFERENCE            = 'Testing transaction';
    private const SUBSCRIBER_COUNTRY   = 'TZ';
    private const SUBSCRIBER_CURRENCY  = 'TZS';
    private const MSISDN               = '12****89';
    private const AMOUNT               = 1000;
    private const TRANSACTION_COUNTRY  = 'TZ';
    private const TRANSACTION_CURRENCY = 'TZS';
    private const REQUEST_ID           = 'random-unique-id';

    protected function setUp(): void
    {
        CurlMockState::reset();
        // Prime the mock so the constructor's create_token() call succeeds.
        CurlMockState::$execReturn = json_encode(['access_token' => 'mock_token', 'expires_in' => '180', 'token_type' => 'bearer']);
        $this->airtel = new Airtel(self::CLIENT_ID, self::CLIENT_SECRET, self::BASE_URL);
        // Reset captured state so collect() tests start with a clean slate.
        CurlMockState::reset();
    }

    // ------------------------------------------------------------------
    // Helper
    // ------------------------------------------------------------------

    private function callCollect(): array
    {
        return $this->airtel->collect(
            self::REFERENCE,
            self::SUBSCRIBER_COUNTRY,
            self::SUBSCRIBER_CURRENCY,
            self::MSISDN,
            self::AMOUNT,
            self::TRANSACTION_COUNTRY,
            self::TRANSACTION_CURRENCY,
            self::REQUEST_ID,
        );
    }

    // ------------------------------------------------------------------
    // Payload structure tests
    // ------------------------------------------------------------------

    public function test_collect_builds_correct_payload_structure(): void
    {
        CurlMockState::$execReturn = json_encode(['status' => 'SUCCESS']);

        $result = $this->callCollect();

        $payload = $result['request'];
        $this->assertArrayHasKey('reference',   $payload);
        $this->assertArrayHasKey('subscriber',  $payload);
        $this->assertArrayHasKey('transaction', $payload);
    }

    public function test_collect_sets_correct_reference(): void
    {
        CurlMockState::$execReturn = json_encode(['status' => 'SUCCESS']);

        $result = $this->callCollect();

        $this->assertSame(self::REFERENCE, $result['request']['reference']);
    }

    public function test_collect_sets_correct_subscriber_fields(): void
    {
        CurlMockState::$execReturn = json_encode(['status' => 'SUCCESS']);

        $result     = $this->callCollect();
        $subscriber = $result['request']['subscriber'];

        $this->assertSame(self::SUBSCRIBER_COUNTRY,  $subscriber['country']);
        $this->assertSame(self::SUBSCRIBER_CURRENCY, $subscriber['currency']);
        $this->assertSame(self::MSISDN,              $subscriber['msisdn']);
    }

    public function test_collect_sets_correct_transaction_fields(): void
    {
        CurlMockState::$execReturn = json_encode(['status' => 'SUCCESS']);

        $result      = $this->callCollect();
        $transaction = $result['request']['transaction'];

        $this->assertSame(self::AMOUNT,               $transaction['amount']);
        $this->assertSame(self::TRANSACTION_COUNTRY,  $transaction['country']);
        $this->assertSame(self::TRANSACTION_CURRENCY, $transaction['currency']);
        $this->assertSame(self::REQUEST_ID,           $transaction['id']);
    }

    // ------------------------------------------------------------------
    // HTTP request tests
    // ------------------------------------------------------------------

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

        $result = $this->callCollect();

        $sentJson = CurlMockState::$capturedOptions[CURLOPT_POSTFIELDS];
        $this->assertJson($sentJson);
        $this->assertSame($result['request'], json_decode($sentJson, true));
    }

    // ------------------------------------------------------------------
    // Response handling tests
    // ------------------------------------------------------------------

    public function test_collect_returns_http_status_code(): void
    {
        CurlMockState::$execReturn = json_encode(['status' => 'SUCCESS']);
        CurlMockState::$httpStatus = 200;

        $result = $this->callCollect();

        $this->assertSame(200, $result['status']);
    }

    public function test_collect_returns_decoded_json_body(): void
    {
        $responseData              = ['status' => 'SUCCESS', 'data' => ['transaction_id' => 'abc123']];
        CurlMockState::$execReturn = json_encode($responseData);

        $result = $this->callCollect();

        $this->assertSame($responseData, $result['body']);
    }

    public function test_collect_returns_raw_response_string(): void
    {
        $raw                       = json_encode(['status' => 'SUCCESS']);
        CurlMockState::$execReturn = $raw;

        $result = $this->callCollect();

        $this->assertSame($raw, $result['raw']);
    }

    public function test_collect_returns_non_200_status_codes(): void
    {
        CurlMockState::$execReturn = json_encode(['message' => 'Unauthorized']);
        CurlMockState::$httpStatus = 401;

        $result = $this->callCollect();

        $this->assertSame(401, $result['status']);
        $this->assertSame('Unauthorized', $result['body']['message']);
    }

    // ------------------------------------------------------------------
    // cURL failure test
    // ------------------------------------------------------------------

    public function test_collect_throws_runtime_exception_on_curl_failure(): void
    {
        CurlMockState::$execReturn = false;
        CurlMockState::$error      = 'Could not resolve host';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Airtel collect request failed: Could not resolve host');

        $this->callCollect();
    }
}
