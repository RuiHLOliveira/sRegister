<?php

namespace App\Tests;

use App\Tests\DefaultTestCase;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;

class MyTest extends DefaultTestCase //extends TestCase
{
    public function testCannotLogin()
    {
        $this->expectException(ClientException::class);
        $response = self::post('/auth/login', self::json, [
            'email' => 'test1@test.com',
            'password' => '123456'
        ]);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testCanLogin()
    {
        $response = self::post('/auth/login', self::json, [
            'email' => 'test@test.com',
            'password' => '123456'
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $responseBody = $response->getBody();
        $responseData = json_decode((string) $responseBody);
        $this->assertEquals($responseData->message, "success!");
    }

    public function testCannotListNotebooks()
    {
        $this->expectException(ClientException::class);
        $response = self::get('/api/notebooks');
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testCanListNotebooks()
    {
        self::needsLogin();
        $response = self::get('/api/notebooks');
        $this->assertEquals(200, $response->getStatusCode());
    }
}