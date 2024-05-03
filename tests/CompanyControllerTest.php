<?php

namespace Tests\Controller;

use App\Entity\CompanySubscriptionPlan;
use App\Entity\Damage;
use App\Entity\DamageOffer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\UserIdentity;
use Doctrine\Persistence\ManagerRegistry;
use App\Tests\BaseTest\WebTestCase as BaseTestWebTestCase;

class CompanyControllerTest extends BaseTestWebTestCase
{
    public function testGetCategories(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('GET', '/api/2.0/company/category-list');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testCreate(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        foreach ($this->getCompanyCreateSampleData() as $key => $createData) {
            $client->request('POST', '/api/2.0/company/create', $createData);
            $response = $client->getResponse();
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($response->getStatusCode() == 200);
            $this->assertArrayHasKey('data', $data);
            $this->assertTrue(!$data['error']);

            return true;
        }
    }

    public function testSearchCompany(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('GET', '/api/2.0/company/search');
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
        $doctrine = static::$kernel->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $users = $em->getRepository(UserIdentity::class)->findBy(['deleted' => false]);
        foreach ($users as $key => $user) {
            $uuid = $user->getPublicId();
            $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
            $client->request('GET', '/api/2.0/company/' . $uuid . '/detail');
            $response = $client->getResponse();
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($response->getStatusCode() == 200);
            $this->assertArrayHasKey('data', $data);
            $this->assertTrue(!$data['error']);

            return true;
        }
    }

    public function testDetails(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $doctrine = static::$kernel->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $offerDeatils = $em->getRepository(DamageOffer::class)->findBy(['deleted' => false, 'active' => true]);
        foreach ($offerDeatils as $key => $offers) {
            $offerId = $offers->getPublicId();
            $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
            $client->request('GET', '/api/2.0/company/offer/' . $offerId);
            $response = $client->getResponse();
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($response->getStatusCode() == 200);
            $this->assertArrayHasKey('data', $data);
            $this->assertTrue(!$data['error']);

            return true;
        }
    }

    public function testCreateCompanyUser(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        foreach ($this->getCompanyUserCreateSampleData() as $key => $createData) {
            $client->request('POST', '/api/2.0/company/add/user', $createData);
            $response = $client->getResponse();
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($response->getStatusCode() == 200);
            $this->assertArrayHasKey('data', $data);
            $this->assertTrue(!$data['error']);

            return true;
        }
    }

    public function testcompanyUserDetail(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $doctrine = static::$kernel->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $users = $em->getRepository(UserIdentity::class)->findBy(['deleted' => false]);
        foreach ($users as $key => $user) {
            $uuid = $user->getPublicId();
            $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
            $client->request('GET', '/api/2.0/company/user/' . $uuid);
            $response = $client->getResponse();
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($response->getStatusCode() == 200);
            $this->assertArrayHasKey('data', $data);
            $this->assertTrue(!$data['error']);

            return true;
        }
    }

    public function testListCompanyUsers(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('GET', '/api/2.0/company/user');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testEditCompanyUser(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $doctrine = static::$kernel->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $uuid = "1ef008a7-8591-6d40-99e2-0242ac120004";
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $users = $em->getRepository(UserIdentity::class)->findBy(['deleted' => false]);
        foreach ($users as $key => $user) {
            if($user->getPublicId() == $uuid){
                foreach ($this->getCompanyUserEditSampleData() as $key => $createData) {
                    $client->request('PATCH', '/api/2.0/company/user/' . $uuid, $createData);
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

    // public function testDeleteCompanyUser(): bool
    // {
        // $token = $this->testLogin();
        // $client = static::createClient();
    //     $uuid = "1eedb8fa-079b-6c00-88e3-0242ac120004";
    //     $client->setServerParameter('HTTP_Authorization','Bearer ' . $token);
    //     $client->request('DELETE', '/api/2.0/company/user/'.$uuid);
    //     $response = $client->getResponse();
    //     $data = json_decode($response->getContent(), true);
    //     $this->assertTrue($response->getStatusCode()==200);
    //     $this->assertArrayHasKey('data', $data);
    //     $this->assertTrue(!$data['error']);

    //     return true;
    // }

    public function testGetCompanySubscriptionPlans(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('GET', '/api/2.0/company/subscription');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testCompareSubscription(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('GET', '/api/2.0/company/compare');
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
        $doctrine = static::$kernel->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $plans = $em->getRepository(CompanySubscriptionPlan::class)->findBy(['deleted' => false]);
        foreach ($plans as $key => $plan) {
            $planId = $plan->getPublicId();
            $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
            $client->request('GET', '/api/2.0/company/plan/' . $planId);
            $response = $client->getResponse();
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($response->getStatusCode() == 200);
            $this->assertArrayHasKey('data', $data);
            $this->assertTrue(!$data['error']);

            return true;
        }
    }

    public function testGetMorePlanDetails(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $doctrine = static::$kernel->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $plans = $em->getRepository(CompanySubscriptionPlan::class)->findBy(['deleted' => false]);
        foreach ($plans as $key => $plan) {
            $planId = $plan->getPublicId();
            $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
            $client->request('GET', '/api/2.0/company/plan/more/' . $planId);
            $response = $client->getResponse();
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($response->getStatusCode() == 200);
            $this->assertArrayHasKey('data', $data);
            $this->assertTrue(!$data['error']);

            return true;
        }
    }

    public function testActivateCompanyUsers(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $doctrine = static::$kernel->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $uuid = "1ef00650-ee09-6016-b115-0242ac120004";
        $users = $em->getRepository(UserIdentity::class)->findBy(['deleted' => false]);
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        foreach ($users as $key => $user) {
            if($user->getPublicId() == $uuid){
                $data = ["users"=> [$uuid]];
                $client->request('POST', '/api/2.0/company/activate/users', $data);
                $response = $client->getResponse();
                $data = json_decode($response->getContent(), true);
                $this->assertTrue($response->getStatusCode() == 200);
                $this->assertArrayHasKey('data', $data);
                $this->assertTrue(!$data['error']);

                return true;
            }            
        }
    }

    public function testResendInvitation(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $doctrine = static::$kernel->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $users = $em->getRepository(UserIdentity::class)->findBy(['deleted' => false]);
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        foreach ($users as $key => $user) {
            $client->request('POST', '/api/2.0/company/resend/'. $user->getPublicId());
            $response = $client->getResponse();
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($response->getStatusCode() == 200);
            $this->assertArrayHasKey('data', $data);
            $this->assertTrue(!$data['error']);

            return true;
        }
    }

    public function testGetCompaniesByCategoryBasedOnFilter(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $doctrine = static::$kernel->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $damages = $em->getRepository(Damage::class)->findBy(['deleted' => false]);
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        foreach ($damages as $key => $damage) {
            $data = [
                "searchKey" => "test",
                "damage" => $damage->getPublicId()
            ];
            $client->request('POST', '/api/2.0/company/category-company-list-filter', $data);
            $response = $client->getResponse();
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($response->getStatusCode() == 200);
            $this->assertArrayHasKey('data', $data);
            $this->assertTrue(!$data['error']);

            return true;
        }
    }

    public function testGetCompaniesByCategory(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $doctrine = static::$kernel->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        // $damages = $em->getRepository(Damage::class)->findBy(['deleted' => false]);
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        // foreach ($damages as $key => $damage) {
            // $data = [
            //     "searchKey" => "test",
            //     "damage" => $damage->getPublicId()
            // ];
            $client->request('GEt', '/api/2.0/company/category-company-list/1ed3ef57-9403-62be-ab61-5254a2026859/1eed0b5c-023b-6644-8301-5254a2026859');
            $response = $client->getResponse();   
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($response->getStatusCode() == 200);
            $this->assertArrayHasKey('data', $data);
            $this->assertTrue(!$data['error']);

            return true;
        // }
    }

    public function testGeneratePublicLinkForDamage(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $doctrine = static::$kernel->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $damages = $em->getRepository(Damage::class)->findBy(['deleted' => false]);
        foreach ($damages as $key => $value) {
            $damage = $value->getPublicId();
            $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
            $client->request('GET', '/api/2.0/company/generate-sharable/' . $damage);
            $response = $client->getResponse();
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($response->getStatusCode() == 200);
            $this->assertArrayHasKey('data', $data);
            $this->assertTrue(!$data['error']);

            return true;
        }
    }

    protected function getCompanyCreateSampleData(): array
    {
        $data = [];
        $timestamp = time();
        for ($i = 0; $i < 3; $i++) {
            $email = "testcasecompany_" . $timestamp . "_" . $i . "@yopmail.com";
            $data[] = [
                "email" => $email,
                "firstName" => "TestCase",
                "lastName" => "CompanyUser_$timestamp",
                "streetNumber" => "ABCD",
                "phone" => "0123456789",
                "country" => "ABCD",
                "countryCode" => "CH",
                "isPolicyAccepted" => "false",
                "jobTitle" => "ABCD",
                "role" => "company",
                "category" => ["1ec7c784-4b9c-66ee-9248-0242ac120003"],
                "landLine" => "876543210",
                "companyName" => "CompanyTestCase_$timestamp",
                "latitude" => "8.5576076",
                "longitude" => "76.8731328",
                "street" => "ABCD",
                "zipCode" => "123456",
                "city" => "ABCD",
                "sendInvite" => "false",
                "dob" => "2002-11-04",
                "language" => "en",
                "website" => "http://example.com",
                "document" => "1ee7b1d7-f5f7-61f4-9268-5254a2026859",
                "damage" => "1ee7b2d0-c6e7-669e-b81f-5254a2026859"
            ];
        }

        return $data;
    }

    protected function getCompanyUserCreateSampleData(): array
    {
        $data = [];
        $timestamp = time();
        for ($i = 0; $i < 3; $i++) {
            $email = "testcasecompanyuser_" . $timestamp . "_" . $i . "@yopmail.com";
            $data[] = [
                "email" => $email,
                "firstName" => "TestCase",
                "lastName" => "User_$timestamp",
                "phone" => "9876543210",
                "jobTitle" => "JobTitle_$timestamp",
                "permission" => ["VIEW_DAMAGE"],
                "dob" => "2002-11-04",
                "role" => "owner",
                "companyName" => "companyName_$timestamp"
            ];
        }

        return $data;
    }

    protected function getCompanyUserEditSampleData(): array
    {
        $data = [];
        $timestamp = time();
        for ($i = 0; $i < 3; $i++) {
            $email = "testcasecompanyuser_" . $timestamp . "_" . $i . "@yopmail.com";
            $data[] = [
                "email" => $email,
                "firstName" => "TestCase",
                "lastName" => "User_$timestamp",
                "phone" => "9876543210",
                "jobTitle" => "JobTitle_$timestamp",
                "permission" => ["VIEW_DAMAGE"],
                "dob" => "2002-11-04",
                "role" => "owner",
                "companyName" => "companyName_$timestamp"
            ];
        }

        return $data;
    }
}
