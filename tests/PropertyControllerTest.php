<?php

namespace Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Tests\BaseTest\WebTestCase as BaseTestWebTestCase;
class PropertyControllerTest extends BaseTestWebTestCase
{

    public function testCreate(): bool
    {
        $currentRole = 'owner';
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $createData = [
            "address" => "ABCD",
            "streetName" => "EFGH",
            "streetNumber" => "1234",
            "postalCode" => "5678",
            "city" => "IJKL",
            "street" => "MNOP",
            "country" => "QRST",
            "countryCode" => "CH",
            "document" => ["1eeb52bc-7511-6efc-a4cd-5254a2026859"],
            "coverImage" => ["1eed0db6-0e7f-6ea8-977c-5254a2026859"],
            "latitude" => "47.2351512",
            "longitude" => "8.8377217",
            "owner" => "",
            "propertyGroup" => "",
            "administrator" => "",
            "janitor" => ""
        ];
        $client->request('POST', '/api/2.0/property/create', $createData);
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testDetail(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('GET', '/api/2.0/property/details/1eef7e6b-7055-6d4e-91ac-0242ac120004');
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
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $client->request('GET', '/api/2.0/property/list');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testEdit(): bool
    {
        $currentRole = 'owner';
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $editData = [
            "address" => "ABCD 1",
            "streetName" => "EFGH 1",
            "streetNumber" => "1234",
            "postalCode" => "5678",
            "city" => "IJKL 1",
            "street" => "MNOP 1",
            "country" => "QRST 1",
            "countryCode" => "CH",
            "document" => ["1eeb52bc-7511-6efc-a4cd-5254a2026859"],
            "coverImage" => ["1eed0db6-0e7f-6ea8-977c-5254a2026859"],
            "latitude" => "47.2351512",
            "longitude" => "8.8377217",
            "owner" => "",
            "propertyGroup" => "",
            "administrator" => "",
            "janitor" => ""
        ];
        $client->request('POST', '/api/2.0/property/edit/1eef7e6b-7055-6d4e-91ac-0242ac120004', $editData);
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testExpiringList(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('GET', '/api/2.0/property/expiring/list');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    // public function testChangeExpiryDate(): bool
    // {
    //     $currentRole = 'admin';
    //     $token = $this->testAdminLogin();
    //     $client = static::createClient();
    //     $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
    //     $client->setServerParameter('HTTP_currentRole', $currentRole);
    //     $changeExpDateData = [
    //         "planEndDate" => "2024-05-10",
    //         "propertyId" => "1ef02054-c956-608e-b5cd-0242ac120004"
    //     ];
    //     $client->request('POST', '/api/2.0/property/change-expiry-date', $changeExpDateData);
    //     $response = $client->getResponse();
    //     $data = json_decode($response->getContent(), true);
    //     $this->assertTrue($response->getStatusCode() == 200);
    //     $this->assertArrayHasKey('data', $data);
    //     $this->assertTrue(!$data['error']);

    //     return true;
    // }

    public function testListPropertiesWithSubscription(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('GET', '/api/2.0/property/list/subscriptions');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testcompareSubscription(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('GET', '/api/2.0/property/compare/1eef7e6b-7055-6d4e-91ac-0242ac120004');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testGetPlanDetails(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('GET', '/api/2.0/property/1eef7e6b-7055-6d4e-91ac-0242ac120004/plan/1ee7ef34-4cda-6766-9132-5254a2026859');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testGetMorePlanDetails(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('GET', '/api/2.0/property/plan/more/1ee7ef34-4cda-6766-9132-5254a2026859');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testUserDetail(): bool
    {
        $userData = [
            "role" => "janitor",
            "property" => "1eef7e6b-7055-6d4e-91ac-0242ac120004"
        ];
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('POST', '/api/2.0/property/user-details', $userData);
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    // public function testUserDelete(): bool
    // {
        // $token = $this->testAdminLogin();
        // $client = static::createClient();
    //     $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
    //     $client->request('DELETE', '/api/2.0/property/delete/1eef7fdb-ebd9-6f62-abed-0242ac120004');
    //     $response = $client->getResponse();
    //     $data = json_decode($response->getContent(), true);
    //     $this->assertTrue($response->getStatusCode() == 200);
    //     $this->assertArrayHasKey('data', $data);
    //     $this->assertTrue(!$data['error']);

    //     return true;
    // }
}
