<?php


namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Class DateObjectEventListener
 * @package App\EventListener
 */
class DateObjectEventListener implements EventSubscriberInterface
{
    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SUBMIT => 'onPreSubmit',
        ];
    }

    /**
     * @param FormEvent $event
     * @throws \Exception
     */
    public function onPreSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        if ($form->get('dob')->getData() instanceof \DateTime) {
            $form->get('dob')->setData($form->get('dob')->getData());
        } else {
            $date = $form->get('dob')->getData()->format('Y-m-d');
            $form->get('dob')->setData(new \DateTime($date));
        }
    }
}