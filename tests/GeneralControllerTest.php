<?php

namespace Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Tests\BaseTest\WebTestCase as BaseTestWebTestCase;
class GeneralControllerTest extends BaseTestWebTestCase
{
    public function testSend(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        foreach ($this->getSampleSend() as $key => $createData) {
            $client->request('POST', '/api/2.0/general/send', $createData);
            $response = $client->getResponse();
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($response->getStatusCode() == 200);
            $this->assertArrayHasKey('data', $data);
            $this->assertTrue(!$data['error']);

            return true;
        }
    }

    public function testMore(): bool
    {
        $token = $this->testLogin();
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $token);
        $client->request('GET', '/api/2.0/general/more');
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($response->getStatusCode() == 200);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue(!$data['error']);

        return true;
    }

    protected function getSampleSend(): array
    {
        $data = [];
        $timestamp = time();
        for ($i = 0; $i < 3; $i++) {
            $data[] = [
                "subject" => "Subject $timestamp",
                "message" => "Message $timestamp"
            ];
        }

        return $data;
    }
}
