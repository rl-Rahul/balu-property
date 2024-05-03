<?php

namespace Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\UserIdentity;
use Doctrine\Persistence\ManagerRegistry;
use App\Tests\BaseTest\WebTestCase as BaseTestWebTestCase;
class FloorControllerTest extends BaseTestWebTestCase
{

    public function testAdd(): bool
    {
        $floorData = [
            "floorNumber"=> "A5"
        ];
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('POST', '/api/2.0/floor/add', $floorData);
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testEdit(): bool
    {
        $floorData = [
            "floorNumber"=> "A5.0",
            "sortOrder"=> "1"
        ];
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('PUT', '/api/2.0/floor/edit/1ef01327-f342-6904-82a5-0242ac120004', $floorData);
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testViewFloorDetails(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('GET', '/api/2.0/floor/view/1ef01327-f342-6904-82a5-0242ac120004');
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
        $client->request('DELETE', '/api/2.0/floor/delete/1eefb007-1c10-6298-b3ff-0242ac120004');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

}
