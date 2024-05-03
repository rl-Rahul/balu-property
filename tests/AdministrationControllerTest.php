<?php

namespace Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\UserIdentity;
use Doctrine\Persistence\ManagerRegistry;

class AdministrationControllerTest extends WebTestCase
{
    /**
     * @var ManagerRegistry $doctrine
     */
    protected $doctrine;
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        self::bootKernel();
        $this->doctrine = static::$kernel->getContainer()->get('doctrine');
    }

    public function testLogin(): string
    {
        $this->client->request('POST', '/api/2.0/secured/login', ['username' => 'rahul.rl@pitsolutions.com', 'password' => 'Test@123'], [], ['language' => 'en']);
        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode(), 'Status Code = 200');
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('token_type', $data['data']);
        $this->assertArrayHasKey('expires_in', $data['data']);
        $this->assertArrayHasKey('access_token', $data['data']);
        $this->assertArrayHasKey('refresh_token', $data['data']);
        return $data['data']['access_token'];
    }

    /**
     * @depends testLogin
     * 
     */
    public function testCreate(string $token): bool
    {
        $this->client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        foreach ($this->getAdministratorCreateData() as $key => $createData) {
            $this->client->request('POST', '/api/2.0/administrator/create', $createData);
            $response = $this->client->getResponse();
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($response->getStatusCode() == 200);
            $this->assertArrayHasKey('data', $data);
            $this->assertTrue(!$data['error']);

            return true;
        }
    }

    /**
     * @depends testLogin
     * 
     */
    public function testDetail(string $token): bool
    {
        $em = $this->doctrine->getManager();
        $users = $em->getRepository(UserIdentity::class)->findBy(['deleted' => false]);
        foreach ($users as $key => $user) {
            $uuid = $user->getPublicId();
            $this->client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
            $this->client->request('GET', '/api/2.0/administrator/' . $uuid . '/detail');
            $response = $this->client->getResponse();
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
