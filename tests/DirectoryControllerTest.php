<?php

namespace Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Tests\BaseTest\WebTestCase as BaseTestWebTestCase;

class DirectoryControllerTest extends BaseTestWebTestCase
{

    public function testIndex(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('GET', '/api/2.0/directory/individual');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testAdd(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        foreach ($this->getSampleData() as $key => $data) {
            $client->request('POST', '/api/2.0/directory/add', $data);
            $response = $client->getResponse();
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($response->getStatusCode() == 200);
            $this->assertArrayHasKey('data', $data);
            $this->assertTrue(!$data['error']);

            return true;
        }
    }

    public function testSearch(): bool
    {
        $currentRole = "owner";
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $data = [
            "search"=> "test"
        ];
        $client->request('POST', '/api/2.0/directory/search/individual', $data);
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testEdit(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $data = [
            "email"=> "testcasecompanyuser_1713783678_0123@yopmail.com",
            "property"=> "1ef009fb-8111-68b0-8607-0242ac120004",
            "firstName"=> "CH",
            "lastName"=> "Langenthal",
            "dob"=> "1995-12-22",
            "publicId"=> "1ef012f1-b7e9-6780-9a94-0242ac120004"
        ];
        $client->request('POST', '/api/2.0/directory/edit', $data);
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testDetail(): bool
    {
        $currentRole = "owner";
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $client->request('GET', '/api/2.0/directory/1ef012f1-b7e9-6780-9a94-0242ac120004/individual/detail');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    // public function testResendInvitation(): bool
    // {
    //     $token = $this->testLogin();
        // $client = static::createClient();
    //     $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
    //     $data = [
    //         "role"=> "individual"
    //     ];
    //     $client->request('POST', '/api/2.0/directory/resend/1ef012f1-b7e9-6780-9a94-0242ac120004', $data);
    //     $response = $client->getResponse();
    //     $data = json_decode($response->getContent(), true);
    //     $this->assertTrue($response->getStatusCode() == 200);
    //     $this->assertArrayHasKey('data', $data);
    //     $this->assertTrue(!$data['error']);

    //     return true;
    // }

    public function testDelete(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('DELETE', '/api/2.0/directory/delete/1ef012f1-b7e9-6780-9a94-0242ac120004');
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
            $email = "testcaseadddirectoryuser_" . $timestamp . "_" . $i . "@yopmail.com";
            $data[] = [
                "email"=> $email,
                "property"=> "1ef009fb-8111-68b0-8607-0242ac120004",
                "firstName" => "Test Case",
                "lastName" => "Add Directory User". $timestamp,
                "dob"=> "1995-12-22",
                "sendInvite"=> 1
            ];
        }

        return $data;
    }
}
