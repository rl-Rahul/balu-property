<?php

namespace App\Command\Traits;

use App\Entity\MailQueue;
 
trait CommandTrait
{   
    /**
     * @var string $mailType
     */
    private ?string $mailType = null;

    /**
    * saveMailQue
    *
    * @param string $mailSubject
    * @param string $template 
    * @param string $toMail
    *
    * @return void
    */
    private function saveMailQueue(string $mailSubject, string $template, string $toMail): void
    { 
        $em = $this->doctrine->getManager();
        $mailQueue = new MailQueue();
        $mailQueue->setMailType($this->mailType);
        $mailQueue->setSubject($mailSubject);
        $mailQueue->setBodyText($template);
        $mailQueue->setToMail($toMail);
        $mailQueue->setCreatedAt(new \DateTime());
        $em->persist($mailQueue);
        $em->flush(); 
        
        return;
    }

}
