<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HttpException) {

            $response = new JsonResponse(
                data: [
                        'message' => $exception->getMessage(),
                        'status' => $exception->getStatusCode(),
                    ],
                status: $exception->getStatusCode(),
            );
        } else {
            $response = new JsonResponse(
                data: [
                        'message' => 'Une erreur est survenue',
                        'status' => 500,
                ],
                status: 500
            );
        }

        $event->setResponse($response);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}
