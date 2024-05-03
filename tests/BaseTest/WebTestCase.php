<?php

/**
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\BaseTest;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase AS BaseTestCase;

/**
 * WebTestCase is the base class for functional tests.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class WebTestCase extends BaseTestCase
{

    public function testLogin(): string
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('POST', '/api/2.0/secured/login', ['username' => 'rahul.rl@pitsolutions.com', 'password' => 'Test@123'], [], ['language' => 'en']);
        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode(), 'Status Code = 200');
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('token_type', $data['data']);
        $this->assertArrayHasKey('expires_in', $data['data']);
        $this->assertArrayHasKey('access_token', $data['data']);
        $this->assertArrayHasKey('refresh_token', $data['data']);
        static::ensureKernelShutdown();
        return $data['data']['access_token'];
    }

    public function testAdminLogin(): string
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        // $client->request('POST', '/api/2.0/secured/login', ['username' => 'office@balu.property', 'password' => 'Test@123'], [], ['language' => 'en']);
        $client->request('POST', '/api/2.0/secured/login', ['username' => 'testcaseuser_1714370538_0@yopmail.com', 'password' => 'Test@123'], [], ['language' => 'en']);
        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode(), 'Status Code = 200');
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('token_type', $data['data']);
        $this->assertArrayHasKey('expires_in', $data['data']);
        $this->assertArrayHasKey('access_token', $data['data']);
        $this->assertArrayHasKey('refresh_token', $data['data']);
        static::ensureKernelShutdown();
        return $data['data']['access_token'];
    } 
}
