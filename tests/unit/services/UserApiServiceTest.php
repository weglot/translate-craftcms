<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\services;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use weglot\craftweglot\services\UserApiService;

final class UserApiServiceTest extends TestCase
{
    private const V2_KEY = 'sk_abc123';

    private UserApiService $userApiService;

    /** @var list<array{request: RequestInterface}> */
    private array $requests = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->userApiService = new UserApiService();
        \Craft::$app->getCache()->flush();
    }

    protected function tearDown(): void
    {
        \Craft::$app->getCache()->flush();
        parent::tearDown();
    }

    public function testV1KeyHasNoWorkspaceSlug(): void
    {
        self::assertSame('', $this->userApiService->getWorkspaceSlug('wg_abc123'));
    }

    public function testEmptyKeyHasNoWorkspaceSlug(): void
    {
        self::assertSame('', $this->userApiService->getWorkspaceSlug(''));
    }

    public function testCachedSlugIsReturnedWithoutHittingTheApi(): void
    {
        \Craft::$app->getCache()->set(UserApiService::workspaceCacheKey(self::V2_KEY), 'my-workspace');

        self::assertSame('my-workspace', $this->userApiService->getWorkspaceSlug(self::V2_KEY));
    }

    public function testCachedEmptySlugIsHonoured(): void
    {
        \Craft::$app->getCache()->set(UserApiService::workspaceCacheKey(self::V2_KEY), '');

        self::assertSame('', $this->userApiService->getWorkspaceSlug(self::V2_KEY));
    }

    /**
     * A plain settings save never clears this cache, so a slug cached for one key
     * must not be served after the user switches to another project.
     */
    public function testCacheIsScopedToTheApiKey(): void
    {
        self::assertNotSame(
            UserApiService::workspaceCacheKey(self::V2_KEY),
            UserApiService::workspaceCacheKey('sk_other')
        );

        \Craft::$app->getCache()->set(UserApiService::workspaceCacheKey(self::V2_KEY), 'workspace-a');

        $slug = $this->service([new Response(200, [], '{"slug":"workspace-b"}')])->getWorkspaceSlug('sk_other');

        self::assertSame('workspace-b', $slug);
        self::assertCount(1, $this->requests);
        self::assertStringEndsWith('/workspaces/current', $this->requests[0]['request']->getUri()->getPath());
        self::assertSame('Key sk_other', $this->requests[0]['request']->getHeaderLine('Authorization'));
    }

    public function testUnreachableApiYieldsAnEmptyWorkspaceSlugAndThrottlesRetries(): void
    {
        $slug = $this->service([$this->connectionFailure()])->getWorkspaceSlug(self::V2_KEY);

        self::assertSame('', $slug);
        self::assertSame('', \Craft::$app->getCache()->get(UserApiService::workspaceCacheKey(self::V2_KEY)));
    }

    public function testUnreachableApiYieldsAUserInfoErrorWithoutTheKey(): void
    {
        $info = $this->service([$this->connectionFailure()])->getUserInfo('wg_abc123');

        self::assertSame(['error' => true, 'message' => 'API request failed.'], $info);
    }

    public function testHttpErrorKeepsItsStatusInTheUserInfoError(): void
    {
        $info = $this->service([new Response(401, [], '{}')])->getUserInfo('wg_abc123');

        self::assertSame(['error' => true, 'message' => 'API request failed with status 401: Unauthorized'], $info);
    }

    /**
     * DashboardHelper asks once per quick link, so a failing lookup must not repeat
     * its HTTP timeout for every card on the page.
     */
    public function testFetchIsMemoizedForTheRequest(): void
    {
        $svc = new class extends UserApiService {
            public int $fetchCount = 0;

            protected function fetchWorkspaceSlug(string $apiKey): string
            {
                ++$this->fetchCount;

                return '';
            }
        };

        self::assertSame('', $svc->getWorkspaceSlug(self::V2_KEY));
        self::assertSame('', $svc->getWorkspaceSlug(self::V2_KEY));
        self::assertSame('', $svc->getWorkspaceSlug(self::V2_KEY));
        self::assertSame(1, $svc->fetchCount);
    }

    public function testMemoIsDroppedWhenTheApiKeyChanges(): void
    {
        $svc = new class extends UserApiService {
            public int $fetchCount = 0;

            protected function fetchWorkspaceSlug(string $apiKey): string
            {
                ++$this->fetchCount;

                return 'a-workspace';
            }
        };

        $svc->getWorkspaceSlug(self::V2_KEY);
        $svc->getWorkspaceSlug('sk_other');

        self::assertSame(2, $svc->fetchCount);
    }

    /**
     * An unexpected extra call makes MockHandler throw OutOfBoundsException, which is no
     * GuzzleException and so fails the test instead of being swallowed.
     *
     * @param list<Response|ConnectException> $responses
     */
    private function service(array $responses): UserApiService
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->requests));

        return new class(new Client(['handler' => $stack])) extends UserApiService {
            public function __construct(private readonly ClientInterface $client)
            {
                parent::__construct();
            }

            protected function createHttpClient(): ClientInterface
            {
                return $this->client;
            }
        };
    }

    private function connectionFailure(): ConnectException
    {
        return new ConnectException('cURL error 35: TLS connect error', new Request('GET', 'https://api.weglot.test'));
    }
}
