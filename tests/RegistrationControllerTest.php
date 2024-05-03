<?php

namespace Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Tests\BaseTest\WebTestCase as BaseTestWebTestCase;
class RegistrationControllerTest extends BaseTestWebTestCase
{
    public function testRegister(): bool
    {
        $client = static::createClient();
        foreach ($this->getSampleInput() as $key => $createData) {
            $client->request('POST', '/api/2.0/register/user', $createData);
            $response = $client->getResponse();
            $this->assertSame(200, $response->getStatusCode(), 'Status Code = 200');
            $data = json_decode($response->getContent(), true);
            $this->assertArrayHasKey('data', $data);
            $this->assertArrayHasKey('publicId', $data['data']);
            $this->assertArrayHasKey('firstName', $data['data']);
            $this->assertArrayHasKey('lastName', $data['data']);
            $this->assertArrayHasKey('email', $data['data']);

            return true;
        }
    }

    public function testGuestRegister(): bool
    {
        $client = static::createClient();
        foreach ($this->getSampleGuestUser() as $key => $createData) {
            $client->request('POST', '/api/2.0/register/guest/user', $createData);
            $response = $client->getResponse();
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($response->getStatusCode() == 200);
            $this->assertArrayHasKey('data', $data);
            $this->assertTrue(!$data['error']);

            return true;
        }
    }

    protected function getSampleInput(): array
    {
        $data = [];
        $timestamp = time();
        for ($i = 0; $i < 3; $i++) {
            $email = "testcaseuser_" . $timestamp . "_" . $i . "@yopmail.com";
            $data[] = [
                "email" => $email,
                "password" => "Test@123",
                "confirmPassword" => "Test@123",
                "firstName" => "TestCase",
                "lastName" => "User_$timestamp",
                "dob" => "1995-12-22",
                "street" => "ABCD",
                "city" => "MNOP",
                "zipCode" => "654321",
                "phone" => "0123456789",
                "country" => "INDIA",
                "countryCode" => "IN",
                "role" => "owner"
            ];
        }

        return $data;
    }

    protected function getSampleGuestUser(): array
    {
        $data = [];
        $timestamp = time();
        for ($i = 0; $i < 3; $i++) {
            $email = "testcaseguestuser_" . $timestamp . "_" . $i . "@yopmail.com";
            $data[] = [
                "email" => $email,
                "companyName" => "company name",
                "street" => "street name",
                "streetNumber" => "12345",
                "phone" => "1236547890"
            ];
        }

        return $data;
    }
}
