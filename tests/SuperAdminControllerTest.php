<?php

namespace Tests\Controller;

use App\Entity\Damage;
use App\Entity\UserIdentity;
use App\Repository\DamageRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\Persistence\ManagerRegistry;
use App\Tests\BaseTest\WebTestCase as BaseTestWebTestCase;
class SuperAdminControllerTest extends BaseTestWebTestCase
{
    
    public function testFeedBackList(): bool
    {
        $token = $this->testAdminLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('GET', '/api/2.0/super-admin/feedback');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testList(): bool
    {
        $currentRole = 'admin';
        $token = $this->testAdminLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $client->request('GET', '/api/2.0/super-admin/property/list');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testPaymentList(): bool
    {
        $currentRole = 'admin';
        $token = $this->testAdminLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $client->request('GET', '/api/2.0/super-admin/payment/list');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testListAllUsers(): bool
    {
        $token = $this->testAdminLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('GET', '/api/2.0/super-admin/user/list');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testObjectResetRequestList(): bool
    {
        $token = $this->testAdminLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('GET', '/api/2.0/super-admin/object-reset/list');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }
}
