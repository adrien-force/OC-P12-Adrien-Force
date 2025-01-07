<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\ConstraintViolationInterface;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $response = match (true) {
            $exception instanceof ValidationFailedException => $this->handleValidationException($exception),
            $exception instanceof HttpException => $this->handleHttpException($exception),
            default => $this->handleGenericException($exception),
        };

        $event->setResponse($response);
    }

    private function handleValidationException(ValidationFailedException $exception): JsonResponse
    {
        $violations = $exception->getViolations();
        $errors = [];

        /** @var ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $errors[] = [
                'propriété' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return new JsonResponse(
            data: [
                'message' => 'Validation des données échouée',
                'statut' => 400,
                'erreurs' => $errors,
            ],
            status: 400
        );
    }

    private function handleHttpException(HttpException $exception): JsonResponse
    {
        return new JsonResponse(
            data: [
                'message' => $exception->getMessage(),
                'status' => $exception->getStatusCode(),
            ],
            status: $exception->getStatusCode()
        );
    }

    private function handleGenericException($exception): JsonResponse
    {
        return new JsonResponse(
            data: [
                'message' => $exception ? $exception->getMessage() : 'Une erreur inconnue est survenue',
                'status' => 500,
            ],
            status: 500
        );
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}
