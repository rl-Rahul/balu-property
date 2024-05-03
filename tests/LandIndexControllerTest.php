<?php

namespace Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\UserIdentity;
use Doctrine\Persistence\ManagerRegistry;
use App\Tests\BaseTest\WebTestCase as BaseTestWebTestCase;
class LandIndexControllerTest extends BaseTestWebTestCase
{

    public function testAdd(): bool
    {
        $landIndexData = [
            "name"=> "test123",
            "nameDe"=> "test de123",
            "sortOrder"=> 11,
            "active"=> true
        ];
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('POST', '/api/2.0/land-index/add', $landIndexData);
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testEdit(): bool
    {
        $landIndexData = [
            "name"=> "test321",
            "nameDe"=> "test de321",
            "sortOrder"=> 11,
            "active"=> true
        ];
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('PUT', '/api/2.0/land-index/edit/1ef01333-3c87-6302-bfe2-0242ac120004', $landIndexData);
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testViewLandIndexDetails(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('GET', '/api/2.0/land-index/view/1ef01333-3c87-6302-bfe2-0242ac120004');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testDelete(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('DELETE', '/api/2.0/land-index/delete/1ef0132d-abc2-6506-a532-0242ac120004');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

}
