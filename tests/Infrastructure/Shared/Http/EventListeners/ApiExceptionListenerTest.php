<?php

namespace App\Tests\Infrastructure\Shared\Http\EventListeners;

use App\Infrastructure\Shared\Http\EventListeners\ApiExceptionListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ApiExceptionListenerTest extends TestCase
{
    public function testRendersAnHttpExceptionWithItsMessage(): void
    {
        $event = $this->event('/api/public/media', new NotFoundHttpException('Company not found.'));

        new ApiExceptionListener()($event);

        $this->assertEnvelope($event, 404, 'Company not found.');
    }

    public function testFallsBackToTheStandardStatusTextWithoutMessage(): void
    {
        $event = $this->event('/api/media/images', new AccessDeniedHttpException());

        new ApiExceptionListener()($event);

        $this->assertEnvelope($event, 403, 'Forbidden');
    }

    public function testKeepsTheExceptionHeaders(): void
    {
        $event = $this->event('/api/public/company', new MethodNotAllowedHttpException(['GET']));

        new ApiExceptionListener()($event);

        $this->assertEnvelope($event, 405, 'Method Not Allowed');
        self::assertSame('GET', $event->getResponse()?->headers->get('Allow'));
    }

    public function testHidesTheDetailsOfAnUnexpectedError(): void
    {
        $event = $this->event('/api', new \RuntimeException('SQLSTATE[HY000] secret details'));

        new ApiExceptionListener()($event);

        $this->assertEnvelope($event, 500, 'Internal server error');
    }

    #[DataProvider('nonApiPathsProvider')]
    public function testIgnoresNonApiPaths(string $path): void
    {
        $event = $this->event($path, new HttpException(404));

        new ApiExceptionListener()($event);

        self::assertNull($event->getResponse());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonApiPathsProvider(): iterable
    {
        yield 'back-office' => ['/admin'];
        yield 'root' => ['/'];
        yield 'api prefix only' => ['/apidoc'];
    }

    private function event(string $path, \Throwable $throwable): ExceptionEvent
    {
        return new ExceptionEvent($this->createStub(HttpKernelInterface::class), Request::create($path), HttpKernelInterface::MAIN_REQUEST, $throwable);
    }

    private function assertEnvelope(ExceptionEvent $event, int $statusCode, string $statusText): void
    {
        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame($statusCode, $response->getStatusCode());
        self::assertSame(['statusCode' => $statusCode, 'statusText' => $statusText], json_decode((string) $response->getContent(), true));
    }
}
