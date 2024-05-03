<?php

namespace Tests\Controller;

use App\Entity\Damage;
use App\Entity\UserIdentity;
use App\Repository\DamageRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\Persistence\ManagerRegistry;
use App\Tests\BaseTest\WebTestCase as BaseTestWebTestCase;
class UserControllerTest extends BaseTestWebTestCase
{
    
    public function testGetProfile(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $doctrine = static::$kernel->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $users = $em->getRepository(UserIdentity::class)->findBy(['deleted' => false]);
        foreach ($users as $key => $user) {
            $uuid = $user->getPublicId();
            $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
            $client->request('GET', '/api/2.0/user/get-profile/' . $uuid);
            $response = $client->getResponse();
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($response->getStatusCode() == 200);
            $this->assertArrayHasKey('data', $data);
            $this->assertTrue(!$data['error']);

            return true;
        }
    }

    // public function testUpdateProfile(): bool
    // {
    //     $data = [
    //         "email"=> "testcaseuser9.0@yopmail.com",
    //         "firstName"=> "TestCase",
    //         "lastName"=> "User9",
    //         "dob"=> "1995-12-22",
    //         "street"=> "ABCD",
    //         "city"=> "MNOP",
    //         "zipCode"=> "654321",
    //         "phone"=> "0123456789",
    //         "country"=> "INDIA",
    //         "countryCode"=> "IN",
    //         "role"=> "owner"
    //     ];
        // $token = $this->testLogin();
        // $client = static::createClient();
    //     $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
    //     $client->request('POST', '/api/2.0/user/update-profile', $data);
    //     $response = $client->getResponse();
    //     $data = json_decode($response->getContent(), true);
    //     $this->assertTrue($response->getStatusCode() == 200);
    //     $this->assertArrayHasKey('data', $data);
    //     $this->assertTrue(!$data['error']);

    //     return true;
    // }

    public function testDashboard(): bool
    {
        $currentRole = 'property_admin';
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $client->request('GET', '/api/2.0/user/dashboard?type=dashboard&filter[status]=open');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testUpdateUserLanguage(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('POST', '/api/2.0/user/update-language');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testSubscriptionHistories(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('GET', '/api/2.0/user/subscription/history');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testCheckEmailExists(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('POST', '/api/2.0/user/check/email-exists', $this->getUserEmailData());
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    protected function getUserEmailData(): array
    {
        $data = [];
        $doctrine = static::$kernel->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $users = $em->getRepository(UserIdentity::class)->findBy(['deleted' => false]);
        foreach ($users as $key => $user) {
            $data = [
                "email" => $user->getUser()->getProperty(),
                "isRegisterUser" => true
            ];
            return $data;
        }
    }

    // protected function getUserUpdateData(): array
    // {
    //     $data = [];
    //     $doctrine = static::$kernel->getContainer()->get('doctrine');
        // $em = $doctrine->getManager();
    //     $users = $em->getRepository(UserIdentity::class)->findBy(['deleted' => false]);
    //     foreach ($users as $key => $user) {
    //         foreach ($user->getRole() as $key => $roleObj) {
    //             $damage = $em->getRepository(Damage::class)->findBy([$user->getUser(), $roleObj->getRoleKey()]);
    //         }
    //         // $damages = $this->damageRepository->getDamages($user->getUser(), $currentRole);
    //         $data = [
    //             "email" => $user->getUser()->getProperty(),
    //             "isRegisterUser" => true
    //         ];
    //         return $data;
    //     }
    // }
}
