<?php

namespace Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Tests\BaseTest\WebTestCase as BaseTestWebTestCase;

class AmenityControllerTest extends BaseTestWebTestCase
{
    public function testGetAmenities(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('GET', '/api/2.0/amenity/list');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }
}
