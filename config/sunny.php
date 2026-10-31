<?php

declare(strict_types=1);

use Sunny\Foundation\Auth\Middleware\AuthenticateJwt;
use Sunny\Foundation\Http\Middleware\AssignRequestId;
use Sunny\Foundation\Http\Middleware\ForceJsonResponse;
use Sunny\Foundation\Http\Middleware\LogApiRequest;

return [

    /*
    |--------------------------------------------------------------------------
    | Global middleware
    |--------------------------------------------------------------------------
    |
    | These middleware classes are pushed onto the HTTP kernel when enabled.
    | JSON forcing and access logs only apply to the configured path patterns,
    | so ordinary web pages keep their original Accept header and stay quiet.
    |
    */

    'middleware' => [
        'enabled' => true,
        'global' => [
            AssignRequestId::class,
            ForceJsonResponse::class,
            LogApiRequest::class,
        ],
        'aliases' => [
            'sunny.request-id' => AssignRequestId::class,
            'sunny.json' => ForceJsonResponse::class,
            'sunny.log' => LogApiRequest::class,
            'sunny.auth' => AuthenticateJwt::class,
        ],
        'json_paths' => ['api/*'],
        'request_id_header' => 'X-Request-Id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'store' => env('SUNNY_CACHE_STORE'),
        'prefix' => env('SUNNY_CACHE_PREFIX', 'sunny'),
        'ttl' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Request logs record the method, path, status, duration, and IP. Bodies
    | and credentials are not written. Keys listed in "redact" are replaced.
    |
    */

    'log' => [
        'channel' => env('SUNNY_LOG_CHANNEL', 'sunny'),
        'level' => env('SUNNY_LOG_LEVEL', 'debug'),
        'days' => 14,
        'paths' => ['api/*'],
        'except' => ['up', 'health'],
        'redact' => [
            'password',
            'password_confirmation',
            'token',
            'access_token',
            'refresh_token',
            'authorization',
            'secret',
            'cookie',
        ],
        'trace' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | JWT
    |--------------------------------------------------------------------------
    |
    | HS256 requires SUNNY_JWT_SECRET (at least 32 characters). RS256 requires
    | a PEM private key for signing and a PEM public key for verification.
    | Only HS256 and RS256 are accepted. The "none" algorithm is rejected.
    |
    */

    'jwt' => [
        'secret' => env('SUNNY_JWT_SECRET'),
        'algo' => env('SUNNY_JWT_ALGO', 'HS256'),
        'private_key' => env('SUNNY_JWT_PRIVATE_KEY'),
        'public_key' => env('SUNNY_JWT_PUBLIC_KEY'),
        'ttl' => 3600,
        'refresh_ttl' => 1209600,
        'ttl_max' => 2592000,
        'leeway' => 0,
        'issuer' => env('SUNNY_JWT_ISSUER'),
        'audience' => env('SUNNY_JWT_AUDIENCE'),
        'user_model' => null,
        'identifier' => 'id',
        'provider' => 'users',
        'guard' => 'sunny',
    ],

];
