<?php

namespace Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\UserIdentity;
use App\Tests\BaseTest\WebTestCase as BaseTestWebTestCase;

class AdministratorControllerTest extends BaseTestWebTestCase
{
    public function testCreate(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        foreach ($this->getAdministratorCreateData() as $key => $createData) {
            $client->request('POST', '/api/2.0/administrator/create', $createData);
            $response = $client->getResponse();
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($response->getStatusCode() == 200);
            $this->assertArrayHasKey('data', $data);
            $this->assertTrue(!$data['error']);

            return true;
        }
    }

    public function testDetail(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $doctrine = static::$kernel->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $users = $em->getRepository(UserIdentity::class)->findBy(['deleted' => false]);
        foreach ($users as $key => $user) {
            $uuid = $user->getPublicId();
            $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
            $client->request('GET', '/api/2.0/administrator/' . $uuid . '/detail');
            $response = $client->getResponse();
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($response->getStatusCode() == 200);
            $this->assertArrayHasKey('data', $data);
            $this->assertTrue(!$data['error']);

            return true;
        }
    }

    protected function getAdministratorCreateData(): array
    {
        $data = [];
        $timestamp = time();
        for ($i = 0; $i < 3; $i++) {
            $email = "testcaseadministratoruser_" . $timestamp . "_" . $i . "@yopmail.com";
            $data[] = [
                "email" => $email,
                "firstName" => "TestCase",
                "lastName" => "AdministratorUser_" . $timestamp,
                "role" => "propertyAdmin",
                "website" => "http://example.com",
                "administratorName" => "Administrator"
            ];
        }

        return $data;
    }
}
