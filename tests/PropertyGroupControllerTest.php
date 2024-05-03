<?php

namespace Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Tests\BaseTest\WebTestCase as BaseTestWebTestCase;
class PropertyGroupControllerTest extends BaseTestWebTestCase
{
    
    public function testCreate(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        foreach ($this->getSampleData() as $key => $createData) {
            $client->request('POST', '/api/2.0/property/group/create', $createData);
            $response = $client->getResponse();
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($response->getStatusCode() == 200);
            $this->assertArrayHasKey('data', $data);
            $this->assertTrue(!$data['error']);

            return true;
        }
    }

    public function testEdit(): bool
    {
        $currentRole = 'owner';
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $editData = [
            "name"=> "Test 1",
        ];
        $client->request('PUT', '/api/2.0/property/group/1eef7e3e-ea2a-6fb6-aa0c-0242ac120004', $editData);
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testList(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('GET', '/api/2.0/property/group/list');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testDelete(): bool
    {
        $currentRole = 'owner';
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $client->request('DELETE', '/api/2.0/property/group/delete/1eef7e3e-ea2a-6fb6-aa0c-0242ac120004');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    protected function getSampleData(): array
    {
        $data = [];
        $timestamp = time();
        for ($i = 0; $i < 3; $i++) {
            $data[] = [
                "name"=> "Test_". $timestamp
            ];
        }

        return $data;
    }
    
}
