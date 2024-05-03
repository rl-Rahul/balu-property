<?php

namespace Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Tests\BaseTest\WebTestCase as BaseTestWebTestCase;
class MessageControllerTest extends BaseTestWebTestCase
{
    
    public function testCreate(): bool
    {
        $currentRole = "owner";
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $data = [
            "type"=> "ticket",
            "message"=> "this is an example.",
            "subject"=> "subject example",
            "apartment"=> "1ee8a02c-592c-60b0-9b18-5254a2026859",
            "ticket"=>  "1ee7ad80-48a2-6f36-8423-5254a2026859", 
            "documents"=> []
        ];
        $client->request('POST', '/api/2.0/message/add', $data);
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testList(): bool
    {
        $currentRole = "owner";
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $client->request('GET', '/api/2.0/message/list');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testListLight(): bool
    {
        $currentRole = "owner";
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $client->request('GET', '/api/2.0/message/list-light');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testArchiveMessage(): bool
    {
        $currentRole = "owner";
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $data = [
            "messageId"=>["1eec64b1-436e-68ac-97d7-5254a2026859"],
            "archive"=> true
        ];
        $client->request('PATCH', '/api/2.0/message/archive', $data);
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testDetails(): bool
    {
        $currentRole = "owner";
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $client->request('GET', '/api/2.0/message/details/1eec64b1-436e-68ac-97d7-5254a2026859');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }


    public function testSearchList(): bool
    {
        $currentRole = "owner";
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $data = [
            "limit"=> "10",
            "offset"=> "1",
            "type"=> "ticket",
            "status"=> "open",
            "text"=> "ticket"
        ];
        $client->request('POST', '/api/2.0/message/search', $data);
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }
}
