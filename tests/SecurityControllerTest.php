<?php

namespace Tests\Controller;

use App\Entity\UserIdentity;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\Persistence\ManagerRegistry;
use App\Tests\BaseTest\WebTestCase as BaseTestWebTestCase;
class SecurityControllerTest extends BaseTestWebTestCase
{
    
    // public function testChangePassword(): bool
    // {
        // $token = $this->testLogin();
        // $client = static::createClient();
    //     $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
    //     $data = [
    //         "currentPassword" => "Pass@123",
    //         "newPassword" => "Test@123",
    //         "confirmPassword" => "Test@123"
    //     ];
    //     $client->request('POST', '/api/2.0/secured/change-password', $data);
    //     $response = $client->getResponse();
    //     $data = json_decode($response->getContent(), true);
    //     $this->assertTrue($response->getStatusCode() == 200);
    //     $this->assertArrayHasKey('data', $data);
    //     $this->assertTrue(!$data['error']);

    //     return true;
    // }


    public function testRoles(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('GET', '/api/2.0/secured/user/roles');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testVerifyGuestUser(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('POST', '/api/2.0/secured/verify-guest-user', $this->getSampleVerifyUserData());
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    protected function getSampleVerifyUserData(): array
    {
        $doctrine = static::$kernel->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $userIdentity = $em->getRepository(UserIdentity::class)->findBy(['deleted' => false]);
        foreach ($userIdentity as $key => $user) {
            $data = [
                "token" => $user->getAuthCode(),
                "email" => $user->getUser()->getProperty()
            ];
        }

        return $data;
    }
}
