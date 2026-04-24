<?php

/**
 * Namespace-level overrides for cURL functions used by Airtel::collect().
 *
 * Because the calls inside Airtel are unqualified (e.g. curl_init()),
 * PHP resolves them first inside the Epmnzava\Airtel namespace before
 * falling back to global. Defining them here intercepts every call made
 * from that namespace without touching the class itself.
 *
 * Tests manipulate state through CurlMockState.
 */

namespace Epmnzava\Airtel;

class CurlMockState
{
    /** Value returned by curl_exec() — false simulates a cURL failure. */
    public static mixed $execReturn = '';

    /** HTTP status code returned by curl_getinfo(). */
    public static int $httpStatus = 200;

    /** Error string returned by curl_error() on failure. */
    public static string $error = '';

    /** Captures the options array passed to curl_setopt_array(). */
    public static array $capturedOptions = [];

    /** Captures the URL passed to curl_init(). */
    public static string $capturedUrl = '';

    public static function reset(): void
    {
        self::$execReturn   = '';
        self::$httpStatus   = 200;
        self::$error        = '';
        self::$capturedOptions = [];
        self::$capturedUrl  = '';
    }
}

// ---------------------------------------------------------------------------
// cURL stubs
// ---------------------------------------------------------------------------

function curl_init(string $url = ''): mixed
{
    CurlMockState::$capturedUrl = $url;
    return 'mock_handle';
}

function curl_setopt_array(mixed $ch, array $options): bool
{
    CurlMockState::$capturedOptions = $options;
    return true;
}

function curl_exec(mixed $ch): string|false
{
    return CurlMockState::$execReturn;
}

function curl_getinfo(mixed $ch, int $option = 0): mixed
{
    return CurlMockState::$httpStatus;
}

function curl_error(mixed $ch): string
{
    return CurlMockState::$error;
}

function curl_close(mixed $ch): void
{
    // no-op
}
