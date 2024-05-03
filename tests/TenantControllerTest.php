<?php

namespace Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Tests\BaseTest\WebTestCase as BaseTestWebTestCase;
class TenantControllerTest extends BaseTestWebTestCase
{
    
    // public function testCreate(): bool
    // {
    //     $currentRole = 'owner';
        // $token = $this->testLogin();
        // $client = static::createClient();
    //     $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
    //     $client->setServerParameter('HTTP_currentRole', $currentRole);
    //     $createData = [
    //         "startDate"=> "2024-04-15",
    //         "endDate"=> "2024-04-16",
    //         "ownerVote"=> false,
    //         "contractPeriodType"=> "1ee7a831-7232-6512-a1fd-5254a2026859",
    //         "additionalComment"=> "test",
    //         "object"=> "1ee7ad68-19a3-6a18-a031-5254a2026859",
    //         "property"=> "1ee7ad68-18c1-6348-9842-5254a2026859",
    //         "noticePeriod"=> "1ee7a831-71e8-63ea-81af-5254a2026859",
    //         "role"=> "owner",
    //         "tenants"=> [
    //             ["id"=> "1ee7b0d4-5505-627c-b512-5254a2026859","role"=> "object_owner"]
    //         ],
    //         "documents"=> ["1ecb66da-d42e-6784-bad1-0242ac120003", "1ec79eba-3ad0-6be6-b1b4-0242ac120004"]
    //     ];
    //     $client->request('POST', '/api/2.0/tenant/new', $createData);
    //     $response = $client->getResponse();
    //     $data = json_decode($response->getContent(), true);
    //     $this->assertTrue($response->getStatusCode() == 200);
    //     $this->assertArrayHasKey('data', $data);
    //     $this->assertTrue(!$data['error']);

    //     return true;
    // }


    // public function testEdit(): bool
    // {
    //     $currentRole = 'owner';
        // $token = $this->testLogin();
        // $client = static::createClient();
    //     $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
    //     $client->setServerParameter('HTTP_currentRole', $currentRole);
    //     $editData = [
    //         "startDate"=> "2024-04-15",
    //         "endDate"=> "2024-04-16",
    //         "ownerVote"=> false,
    //         "contractPeriodType"=> "1ee7a831-7232-6512-a1fd-5254a2026859",
    //         "additionalComment"=> "test comment",
    //         "object"=> "1ee7ad68-19a3-6a18-a031-5254a2026859",
    //         "property"=> "1ee7ad68-18c1-6348-9842-5254a2026859",
    //         "noticePeriod"=> "1ee7a831-71e8-63ea-81af-5254a2026859",
    //         "role"=> "owner",
    //         "tenants"=> [
    //             ["id"=> "1ee7b0d4-5505-627c-b512-5254a2026859","role"=> "object_owner"]
    //         ],
    //         "documents"=> ["1ecb66da-d42e-6784-bad1-0242ac120003", "1ec79eba-3ad0-6be6-b1b4-0242ac120004"]
    //     ];
    //     $client->request('POST', '/api/2.0/tenant/edit/1eef7c25-b123-60bc-a9f8-0242ac120004', $editData);
    //     $response = $client->getResponse();
    //     $data = json_decode($response->getContent(), true);
    //     $this->assertTrue($response->getStatusCode() == 200);
    //     $this->assertArrayHasKey('data', $data);
    //     $this->assertTrue(!$data['error']);

    //     return true;
    // }

    // public function testTerminate(): bool
    // {
            // $token = $this->testLogin();
            // $client = static::createClient();
    //     $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
    //     $terminationData = [
    //         "noticeReceiptDate"=> "2024-04-11",
    //         "terminationDate"=> "2024-04-12",
    //         "property"=> "1ee7ad68-18c1-6348-9842-5254a2026859",
    //         "object"=> "1ee7ad68-19a3-6a18-a031-5254a2026859"
    //     ];
    //     $client->request('POST', '/api/2.0/tenant/terminate/1eef7c25-b123-60bc-a9f8-0242ac120004', $terminationData);
    //     $response = $client->getResponse();
    //     $data = json_decode($response->getContent(), true);
    //     $this->assertTrue($response->getStatusCode() == 200);
    //     $this->assertArrayHasKey('data', $data);
    //     $this->assertTrue(!$data['error']);

    //     return true;
    // }

    // public function testCheckNoticePeriod(): bool
    // {
        // $token = $this->testLogin();
        // $client = static::createClient();
    //     $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
    //     $editData = [
    //         "startDate"=> "2024-04-15",
    //         "endDate"=> "2024-04-16",
    //         "ownerVote"=> false,
    //         "contractPeriodType"=> "1ee7a831-7232-6512-a1fd-5254a2026859",
    //         "additionalComment"=> "test comment",
    //         "object"=> "1ee7ad68-19a3-6a18-a031-5254a2026859",
    //         "property"=> "1ee7ad68-18c1-6348-9842-5254a2026859",
    //         "noticePeriod"=> "1ee7a831-71e8-63ea-81af-5254a2026859",
    //         "role"=> "owner",
    //         "tenants"=> [
    //             ["id"=> "1ee7b0d4-5505-627c-b512-5254a2026859","role"=> "object_owner"]
    //         ],
    //         "documents"=> ["1ecb66da-d42e-6784-bad1-0242ac120003", "1ec79eba-3ad0-6be6-b1b4-0242ac120004"]
    //     ];
    //     $client->request('POST', '/api/2.0/tenant/edit/1eef7c25-b123-60bc-a9f8-0242ac120004', $editData);
    //     $response = $client->getResponse();
    //     $data = json_decode($response->getContent(), true);
    //     $this->assertTrue($response->getStatusCode() == 200);
    //     $this->assertArrayHasKey('data', $data);
    //     $this->assertTrue(!$data['error']);

    //     return true;
    // }

    // public function testRevoke(): bool
    // {
        // $token = $this->testLogin();
        // $client = static::createClient();
    //     $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
    //     $revokeData = [
    //         "property"=> "1ee7ad68-18c1-6348-9842-5254a2026859",
    //         "object"=> "1ee7ad68-19a3-6a18-a031-5254a2026859"
    //     ];
    //     $client->request('POST', '/api/2.0/tenant/revoke/1eef7c25-b123-60bc-a9f8-0242ac120004', $revokeData);
    //     $response = $client->getResponse();
    //     $data = json_decode($response->getContent(), true);
    //     $this->assertTrue($response->getStatusCode() == 200);
    //     $this->assertArrayHasKey('data', $data);
    //     $this->assertTrue(!$data['error']);

    //     return true;
    // }

    public function testCheckStartDate(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $checkDateData = [
            "property"=> "1ee7ad68-18c1-6348-9842-5254a2026859",
            "object"=> "1ee7ad68-19a3-6a18-a031-5254a2026859",
            "startDate"=> "2024-04-14",
        ];
        $client->request('POST', '/api/2.0/tenant/check-date', $checkDateData);
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }
}
