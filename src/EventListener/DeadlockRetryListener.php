<?php

namespace App\EventListener;

use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DeadlockRetryListener implements EventSubscriberInterface
{
    private $retryCount;

    public function __construct(?int $retryCount = null)
    {
        $this->retryCount = $retryCount;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        // Check if the exception is a deadlock or optimistic lock exception
        if ($exception instanceof DeadlockException || $exception instanceof OptimisticLockException) {
            // Retry the database operation
            $this->retryDatabaseOperation($event);
        }
    }

    private function retryDatabaseOperation(ExceptionEvent $event)
    {
        // Implement your retry logic here

        // For example, you might retry the operation a certain number of times
        for ($i = 0; $i < $this->retryCount; ++$i) {
            usleep(500000); // 500ms delay (adjust as needed)

            // Dispatch the original request again
            $this->retryRequest($event);
        }
    }

    private function retryRequest(ExceptionEvent $event)
    {
        $kernel = $event->getKernel();
        $request = $event->getRequest();

        $response = $kernel->handle($request);

        // Set the new response on the event
        $event->setResponse($response);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}
