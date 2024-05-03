<?php

namespace Tests\Controller;

use App\Entity\Damage;
use App\Entity\Property;
use App\Entity\UserIdentity;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\Persistence\ManagerRegistry;
use App\Tests\BaseTest\WebTestCase as BaseTestWebTestCase;

class DamageControllerTest extends BaseTestWebTestCase
{

    public function testCreate(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $currentRole = 'property_admin';
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        foreach ($this->getSampleCreate() as $key => $createData) {
            $client->request('POST', '/api/2.0/ticket/add', $createData);
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
        $token = $this->testLogin();
        $client = static::createClient();
        $doctrine = static::$kernel->getContainer()->get('doctrine');
        $ticketId = "1ef01259-ec3f-6d2e-a7f1-0242ac120004";
        $currentRole = 'owner';
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $em = $doctrine->getManager();
        $damages = $em->getRepository(Damage::class)->findBy(['deleted' => false]);
        foreach ($damages as $key => $value) {
            $damage = $value->getPublicId();
            if($damage == $ticketId){
                foreach ($this->getSampleEdit() as $key => $createData) {
                    $client->request('PUT', '/api/2.0/ticket/edit/' . $ticketId, $createData);
                    $response = $client->getResponse();
                    $data = json_decode($response->getContent(), true);
                    $this->assertTrue($response->getStatusCode() == 200);
                    $this->assertArrayHasKey('data', $data);
                    $this->assertTrue(!$data['error']);
    
                    return true;
                }
            }
        }
    }

    public function testDetails(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $doctrine = static::$kernel->getContainer()->get('doctrine');
        $currentRole = 'owner';
        $ticketId = "1ef01259-ec3f-6d2e-a7f1-0242ac120004";
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $em = $doctrine->getManager();
        $damages = $em->getRepository(Damage::class)->findBy(['deleted' => false]);
        foreach ($damages as $key => $damage) {
            $damage = $damage->getPublicId();
            if($damage == $ticketId){
                $client->request('GET', '/api/2.0/ticket/details/'. $ticketId);
                $response = $client->getResponse();
                $data = json_decode($response->getContent(), true);
                $this->assertTrue($response->getStatusCode() == 200);
                $this->assertArrayHasKey('data', $data);
                $this->assertTrue(!$data['error']);

                return true;
            }
        }
    }

    public function testList(): bool
    {
        $currentRole = 'owner';
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $client->request('GET', '/api/2.0/ticket/list?filter[status]=open&offset=0&limit=20');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    // public function testUpdate(): bool
    // {
    //     $currentRole = 'property_admin';
        // $token = $this->testLogin();
        // $client = static::createClient();
    //     $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
    //     $client->setServerParameter('HTTP_currentRole', $currentRole);
    //     $updateData = [
    //         "ticket"=>  "1ef01f84-cd73-6098-bdab-0242ac120004",
    //         "status" => "REPAIR_CONFIRMED",
    //         "currentStatus" => "PROPERTY_ADMIN_CREATE_DAMAGE" ,
    //         "withSignature"=> true,
    //         "signature"=> "1ed12522-5bf3-683a-92a4-0242ac130003"
    //     ];
    //     $client->request('PATCH', '/api/2.0/ticket/update', $updateData);
    //     $response = $this->client->getResponse();
    //     $data = json_decode($response->getContent(), true);
    //     $this->assertTrue($response->getStatusCode() == 200);
    //     $this->assertArrayHasKey('data', $data);
    //     $this->assertTrue(!$data['error']);

    //     return true;
    // }

    public function testUsers(): bool
    {
        $ticketId = "1ef01259-ec3f-6d2e-a7f1-0242ac120004";
        $token = $this->testLogin();
        $client = static::createClient();
        $doctrine = static::$kernel->getContainer()->get('doctrine');
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $em = $doctrine->getManager();
        $damages = $em->getRepository(Damage::class)->findBy(['deleted' => false]);
        foreach ($damages as $key => $damage) {
            $damage = $damage->getPublicId();
            if($damage == $ticketId){
                $client->request('GET', '/api/2.0/ticket/users/'. $ticketId);
                $response = $client->getResponse();
                $data = json_decode($response->getContent(), true);
                $this->assertTrue($response->getStatusCode() == 200);
                $this->assertArrayHasKey('data', $data);
                $this->assertTrue(!$data['error']);

                return true;
            }
        }
    }

    // public function testDelete(): bool
    // {
    //     $currentRole = 'property_admin';
        // $token = $this->testLogin();
        // $client = static::createClient();
    //     $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
    //     $client->setServerParameter('HTTP_currentRole', $currentRole);
    //     $client->request('DELETE', '/api/2.0/ticket/delete/1ef01f84-cd73-6098-bdab-0242ac120004');
    //     $response = $client->getResponse();
    //     $data = json_decode($response->getContent(), true);
    //     $this->assertTrue($response->getStatusCode() == 200);
    //     $this->assertArrayHasKey('data', $data);
    //     $this->assertTrue(!$data['error']);

    //     return true;
    // }

    public function testSearchList(): bool
    {
        $currentRole = 'owner';
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $client->request('POST', '/api/2.0/ticket/search', $this->getSearchList());
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testGetLocationImage(): bool
    {
        $currentRole = 'owner';
        $token = $this->testLogin();
        $client = static::createClient();
        $doctrine = static::$kernel->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $damages = $em->getRepository(Damage::class)->findBy(['deleted' => false]);
        foreach ($damages as $key => $damage) {
            $ticketId = $damage->getPublicId();
            $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
            $client->setServerParameter('HTTP_currentRole', $currentRole);
            $client->request('GET', '/api/2.0/ticket/location-image/' . $ticketId);
            $response = $client->getResponse();
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($response->getStatusCode() == 200);
            $this->assertArrayHasKey('data', $data);
            $this->assertTrue(!$data['error']);

            return true;
        }
    }

    public function testGetOfferDetailListByCompany(): bool
    {
        $currentRole = 'company';
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $client->request('GET', '/api/2.0/ticket/offer-details');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testSendDamageOfferRequestEmailToNonRegisteredCompanies(): bool
    {
        $currentRole = 'owner';
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $data = [
            "email" => "rahul.rl@pitsolutions.com",
            "subject"=> "Ticket allocation request",
            "damage"=> "1eea560e-e4b6-636c-bf45-5254a2026859",
            "status"=> "OWNER_SEND_TO_COMPANY_WITHOUT_OFFER"
        ];
        $client->request('POST', '/api/2.0/ticket/offer-request/non-registered-companies', $data);
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testInfo(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('GET', '/api/2.0/ticket/info/1eea4bdf-43a2-6626-91e1-5254a2026859/1ee9e4a4-6dee-615c-b708-5254a2026859');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testLoopJanitor(): bool
    {
        $currentRole = 'owner';
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $data = [
            "isJanitorEnabled" => "true"
        ];
        $client->request('PUT', '/api/2.0/ticket/loop-janitor/1eea560e-e4b6-636c-bf45-5254a2026859', $data);
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    protected function getSampleCreate(): array
    {
        $data = [];
        $timestamp = time();
        for ($i = 0; $i < 3; $i++) {
            $data[] = [
                "title" => "Title_$timestamp",
                "description" => "Description_$timestamp",
                "apartment" => "1eeb5cb5-2980-68dc-9aee-5254a2026859"
            ];
        }

        return $data;
    }

    protected function getSampleEdit(): array
    {
        $data = [];
        $timestamp = time();
        for ($i = 0; $i < 3; $i++) {
            $data[] = [
                "title" => "Title_$timestamp",
                "description" => "Description_$timestamp",
                "apartment" => "1eeb5cb5-2980-68dc-9aee-5254a2026859"
            ];
        }

        return $data;
    }

    protected function getSearchList(): array
    {
        $doctrine = static::$kernel->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $properties = $em->getRepository(Property::class)->findBy(['deleted' => false, 'active' => true]);
        foreach ($properties as $key => $property) {
            $data = [
                "property" => $property->getPublicId(),
                "limit" =>  "10",
                "offset" =>  "1",
                "text" =>  "test",
                "status" =>  "open"
            ];

            return $data;
        }
    }

    // protected function getSendDamageOfferRequestEmailData(): array
    // {
    //     $doctrine = static::$kernel->getContainer()->get('doctrine');
    //     $em = $doctrine->getManager();
    //     $users = $em->getRepository(UserIdentity::class)->findBy(['deleted' => false]);
    //     $properties = $em->getRepository(Property::class)->findBy(['deleted' => false, 'active' => true]);
    //     foreach ($users as $key => $user) {
    //         foreach ($properties as $key => $property) {
    //             $data = [
    //                 "email" => $user->getUser()->getProperty(),
    //                 "subject"=> "Ticket allocation request",
    //                 "damage"=> $property->getPublicId(),
    //                 "status"=> "OWNER_SEND_TO_COMPANY_WITHOUT_OFFER"
    //             ];

    //             return $data;
    //         }
    //     }
    // }
}
