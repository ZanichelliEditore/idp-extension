<?php

namespace Zanichelli\IdpExtension\Tests\Http\Middleware;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Zanichelli\IdpExtension\Http\Middleware\IdpMiddleware;
use Zanichelli\IdpExtension\Models\ZUser;
use Zanichelli\IdpExtension\Tests\TestCase;

class IdpMiddlewareSessionFixationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CapturePreLoginSession::$sessionId = null;

        Route::middleware([
            'web',
            CapturePreLoginSession::class,
            FakeIdpMiddleware::class . ':without_permissions',
        ])->get('/_test/idp', function () {
            return [
                'session_id' => session()->getId(),
                'user_id' => auth()->id(),
            ];
        });
    }

    /**
     * The identifier presented at the login callback must not survive the login.
     *
     * @see https://cwe.mitre.org/data/definitions/384.html
     */
    public function test_it_regenerates_the_session_id_when_a_token_authenticates_a_pre_login_session(): void
    {
        $this->fakeIdpUser(1767);

        $response = $this->getJson('/_test/idp?token=an-idp-token');

        $response->assertOk();
        $this->assertNotSame(
            CapturePreLoginSession::$sessionId,
            $response->json('session_id'),
            'The pre-login session identifier was reused for the authenticated session.'
        );
    }

    /**
     * Superseding the identifier is not enough: the record behind the value the
     * attacker knows has to be gone from storage.
     */
    public function test_it_destroys_the_pre_login_session_record(): void
    {
        $this->fakeIdpUser(1767);

        $this->getJson('/_test/idp?token=an-idp-token')->assertOk();

        $this->assertSame(
            '',
            $this->app['session']->getHandler()->read(CapturePreLoginSession::$sessionId),
            'The pre-login session record still exists in storage.'
        );
    }

    /**
     * Rotating the identifier must not drop the identity that was just established.
     */
    public function test_the_authenticated_user_survives_the_regeneration(): void
    {
        $this->fakeIdpUser(1767);

        $response = $this->getJson('/_test/idp?token=an-idp-token');

        $response->assertOk();
        $response->assertJsonPath('user_id', 1767);
    }

    /**
     * A token replayed by the user who already owns the session is not a login
     * transition, so it must not rotate the identifier on every request.
     */
    public function test_it_does_not_regenerate_when_the_same_user_is_already_authenticated(): void
    {
        $this->fakeIdpUser(1767);
        $this->withSession(['user' => $this->zUser(1767)]);

        $response = $this->getJson('/_test/idp?token=an-idp-token');

        $response->assertOk();
        $this->assertSame(CapturePreLoginSession::$sessionId, $response->json('session_id'));
    }

    /**
     * A different identity arriving on an existing session is a login transition.
     */
    public function test_it_regenerates_when_another_user_authenticates_over_the_session(): void
    {
        $this->fakeIdpUser(9999);
        $this->withSession(['user' => $this->zUser(1767)]);

        $response = $this->getJson('/_test/idp?token=another-users-token');

        $response->assertOk();
        $response->assertJsonPath('user_id', 9999);
        $this->assertNotSame(CapturePreLoginSession::$sessionId, $response->json('session_id'));
    }

    /**
     * Queue the payload GET {IDP_BASE_URL}/v1/user answers with.
     */
    private function fakeIdpUser(int $userId): void
    {
        FakeIdpMiddleware::$response = new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'id' => $userId,
            'username' => 'user' . $userId,
            'email' => 'user' . $userId . '@zanichelli.it',
            'is_verified' => true,
            'name' => 'Test',
            'surname' => 'User',
            'is_employee' => true,
            'created_at' => '2026-01-01 00:00:00',
            'roles' => [],
            'attributes' => [],
        ]));
    }

    private function zUser(int $userId): ZUser
    {
        return ZUser::create($userId, 'user' . $userId, 'user' . $userId . '@zanichelli.it', 'a-token', true, 'Test', 'User', true, '2026-01-01 00:00:00');
    }
}

/**
 * Stands in for the anonymous visit that precedes the login: the application
 * issues and persists a session before redirecting the visitor to the identity
 * provider, and that is the identifier an attacker plants. Recording it inside
 * the request is what lets the assertions compare it with the identifier the
 * request ends up authenticated with.
 */
class CapturePreLoginSession
{
    public static ?string $sessionId = null;

    public function handle(Request $request, Closure $next)
    {
        $request->session()->save();

        static::$sessionId = $request->session()->getId();

        return $next($request);
    }
}

/**
 * IdpMiddleware builds its own Guzzle client, so the test overrides that seam
 * instead of reaching the real identity provider.
 */
class FakeIdpMiddleware extends IdpMiddleware
{
    public static ?Response $response = null;

    protected function idpClient()
    {
        return new Client([
            'handler' => HandlerStack::create(new MockHandler([static::$response])),
        ]);
    }
}
