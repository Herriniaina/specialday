<?php

namespace App\Tests\Infrastructure\Shared\Http\EventListeners;

use App\Infrastructure\Shared\Http\EventListeners\ValidationExceptionListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class ValidationExceptionListenerTest extends TestCase
{
    private HttpKernelInterface $kernel;
    private Request $request;
    private ValidationExceptionListener $listener;

    protected function setUp(): void
    {
        $this->kernel = $this->createStub(HttpKernelInterface::class);
        $this->request = Request::create('/');
        $this->listener = new ValidationExceptionListener();
    }

    private function dispatch(\Throwable $throwable): ExceptionEvent
    {
        $event = new ExceptionEvent($this->kernel, $this->request, HttpKernelInterface::MAIN_REQUEST, $throwable);
        ($this->listener)($event);

        return $event;
    }

    public function testInvokeMapsValidationFailedExceptionToBadRequestEnvelope(): void
    {
        $violation = new ConstraintViolation(
            message: 'This value should not be blank.',
            messageTemplate: null,
            parameters: [],
            root: null,
            propertyPath: 'name',
            invalidValue: '',
        );
        $validationFailedException = new ValidationFailedException('', new ConstraintViolationList([$violation]));
        $throwable = new \RuntimeException('wrapped', 0, $validationFailedException);

        $event = $this->dispatch($throwable);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true);
        self::assertSame('Invalid parameter', $body['statusText']);
        self::assertSame(['name' => 'This value should not be blank.'], $body['payload']);
    }

    public function testInvokeMapsSerializerExceptionToInvalidJsonEnvelope(): void
    {
        $throwable = new \RuntimeException('wrapped', 0, new NotEncodableValueException('bad json'));

        $event = $this->dispatch($throwable);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true);
        self::assertSame('Invalid json format.', $body['statusText']);
        self::assertArrayNotHasKey('payload', $body);
    }

    public function testInvokeLeavesResponseUnsetForUnrelatedExceptions(): void
    {
        $event = $this->dispatch(new \RuntimeException('unrelated'));

        self::assertNull($event->getResponse());
    }
}
