<?php

namespace Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\PushNotification;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Tests\BaseTest\WebTestCase as BaseTestWebTestCase;
class PushNotificationControllerTest extends BaseTestWebTestCase
{
    
    public function testGetUserNotifications(): bool
    {
        $currentRole = 'company';
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $client->request('GET', '/api/2.0/notification/list');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testChangeNotificationReadStatus(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        // $data = [
        //     "notificationId" => ["1eec64c4-c3f7-677c-ade0-5254a2026859", "1eec64ab-ccbb-62aa-ae01-5254a2026859"],
        //     "isRead" => true,
        // ];
        $client->request('PATCH', '/api/2.0/notification/read-status', $this->getNotificationSampleData());
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    protected function getNotificationSampleData(): array
    {
        $data = [
            "notificationId" => ["1eec64c4-c3f7-677c-ade0-5254a2026859", "1eec64ab-ccbb-62aa-ae01-5254a2026859"],
            "isRead" => false,
        ];
        return $data;
    }
}