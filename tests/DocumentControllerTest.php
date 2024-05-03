<?php

namespace Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\UserIdentity;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Tests\BaseTest\WebTestCase as BaseTestWebTestCase;
class DocumentControllerTest extends BaseTestWebTestCase
{

    public function testSearch(): bool
    {
        $currentRole = 'owner';
        $searchData = [
            "search"=> "",
            "folder"=> "1eefbafe-6dfc-65fa-a14e-0242ac120004"
        ];
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $client->request('POST', '/api/2.0/document/search', $searchData);
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testCreateFolder(): bool
    {
        $createFolderData = [
            "name"=> "TestCaseFolder1",
            "isPrivate"=> "private",
            "parent"=> "1eef7fd6-8916-63bc-84ab-0242ac120004",
            "isManual"=> true
        ];
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('POST', '/api/2.0/document/create-folder', $createFolderData);
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testList(): bool
    {
        $currentRole = 'owner';
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $client->request('GET', '/api/2.0/document/list/1eef7fd6-8916-63bc-84ab-0242ac120004');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testEditFolder(): bool
    {
        $editFolderData = [
            "name"=> "Test Case Folder1",
            "isPrivate"=> "public/private"
        ];
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('PUT', '/api/2.0/document/edit-folder/1eefbafe-6dfc-65fa-a14e-0242ac120004', $editFolderData);
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testEditDocument(): bool
    {
        $editDocumentData = [
            "fileName"=> "Test Case File1",
            "isPrivate"=> "public/private",
            "type"=> "property"
        ];
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('PUT', '/api/2.0/document/edit-document/1eefbb2e-b23b-6632-83f9-0242ac120004', $editDocumentData);
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    public function testDelete(): bool
    {
        $currentRole = 'owner';
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->setServerParameter('HTTP_currentRole', $currentRole);
        $client->request('DELETE', '/api/2.0/document/delete/folder/1eefbe85-88f0-61ec-a77e-0242ac120004');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }
}
