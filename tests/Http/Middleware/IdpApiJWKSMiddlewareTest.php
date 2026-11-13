<?php

namespace Zanichelli\IdpExtension\Tests\Http\Middleware;

use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Zanichelli\IdpExtension\Http\Middleware\IdpApiJWKSMiddleware;
use Zanichelli\IdpExtension\Tests\TestCase;

class IdpApiJWKSMiddlewareTest extends TestCase
{
    private const KID = 'test-key';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::store('file')->forget('idp_jwks_public_key');
    }

    public function test_it_decodes_a_token_signed_with_the_jwks_key(): void
    {
        [$privateKey, $jwks] = $this->makeRsaKey();

        $token = JWT::encode([
            'sub' => 1767,
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 60,
            'given_name' => 'Mario',
            'family_name' => 'Rossi',
            'preferred_username' => 'mrossi',
            'email' => 'mrossi@example.com',
            'roles' => [[
                'id' => 1,
                'role_id' => 2,
                'roleName' => 'ADMIN',
                'department_id' => 3,
            ]],
            'attributes' => ['school' => 'XYZ'],
            'myz' => ['enabled' => true],
        ], $privateKey, 'RS256', self::KID);

        $user = $this->middlewareWithJwks($jwks)->decode($token);

        $this->assertSame('Mario', $user['name']);
        $this->assertSame('Rossi', $user['surname']);
        $this->assertSame('mrossi', $user['username']);
        $this->assertSame('mrossi@example.com', $user['email']);
        $this->assertSame([[
            'id' => 1,
            'roleId' => 2,
            'roleName' => 'ADMIN',
            'departmentId' => 3,
            'departmentName' => null,
            'branchCode' => null,
        ]], $user['roles']);
        $this->assertSame([['key' => 'school', 'value' => 'XYZ']], $user['attributes']);
        $this->assertSame(['enabled' => true], $user['myz']);
        foreach (['iat', 'exp', 'nbf', 'sub', 'given_name', 'family_name', 'preferred_username'] as $claim) {
            $this->assertArrayNotHasKey($claim, $user);
        }
    }

    public function test_it_rejects_a_token_signed_with_another_key(): void
    {
        [, $jwks] = $this->makeRsaKey();
        [$otherPrivateKey] = $this->makeRsaKey();

        $token = JWT::encode(['sub' => 1, 'exp' => time() + 60], $otherPrivateKey, 'RS256', self::KID);

        $this->expectException(\Firebase\JWT\SignatureInvalidException::class);

        $this->middlewareWithJwks($jwks)->decode($token);
    }

    public function test_it_rejects_an_expired_token(): void
    {
        [$privateKey, $jwks] = $this->makeRsaKey();

        $token = JWT::encode(['sub' => 1, 'exp' => time() - 120], $privateKey, 'RS256', self::KID);

        $this->expectException(\Firebase\JWT\ExpiredException::class);

        $this->middlewareWithJwks($jwks)->decode($token);
    }

    /**
     * @return array{0: string, 1: array} PEM private key and the matching JWKS document
     */
    private function makeRsaKey(): array
    {
        $resource = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($resource, $privateKey);
        $details = openssl_pkey_get_details($resource);

        $jwks = ['keys' => [[
            'kty' => 'RSA',
            'alg' => 'RS256',
            'use' => 'sig',
            'kid' => self::KID,
            'n' => JWT::urlsafeB64Encode($details['rsa']['n']),
            'e' => JWT::urlsafeB64Encode($details['rsa']['e']),
        ]]];

        return [$privateKey, $jwks];
    }

    private function middlewareWithJwks(array $jwks): TestableIdpApiJWKSMiddleware
    {
        $middleware = new TestableIdpApiJWKSMiddleware();
        $middleware->setClient(new Client([
            'handler' => HandlerStack::create(new MockHandler([
                new Response(200, ['Content-Type' => 'application/json'], json_encode($jwks)),
            ])),
        ]));

        return $middleware;
    }
}

class TestableIdpApiJWKSMiddleware extends IdpApiJWKSMiddleware
{
    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    public function decode(string $token): array
    {
        return $this->getUser($token);
    }
}
