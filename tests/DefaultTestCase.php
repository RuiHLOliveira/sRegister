<?php

namespace App\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;

abstract class DefaultTestCase extends TestCase
{
    const json = 'json';
    const form = 'form';

    protected static $host = 'localhost';
    protected static $user = 'postgres';
    protected static $password = '123456';
    protected static $name = 'sregister';
    protected static $port = '5432';

    protected static $needsLogin = false;
    protected static $httpClient;
    protected static $dbConnection;
    protected static $headers;

    public static function getConnection() {
        if (self::$dbConnection === null) {
            self::$dbConnection = new \PDO('pgsql:host='.self::$host.';port='.self::$port.';dbname='.self::$name.';user='.self::$user.';password='.self::$password);
            self::$dbConnection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
        return self::$dbConnection;
    }

    public static function createTestUser(){
        $user = self::fetchTestUser();
        if(isset($user['id'])) self::deleteTestUser();
        $sql = 'INSERT INTO public."user" (email,roles,"password") VALUES (\'test@test.com\',\'[]\',\'$argon2id$v=19$m=65536,t=4,p=1$b2R4dUdtS0FRQ3l0d2RWbQ$u+7x1rf4uD2frRSXJPZxbRXjunbYgFf4j+e2CCXIjXA\');';
        $stmt = self::$dbConnection->prepare($sql);
        $stmt->execute();
    }

    public static function fetchTestUser(){
        $sql = 'select * from public."user" where email = \'test@test.com\'';
        $stmt = self::$dbConnection->prepare($sql);
        $stmt->execute();
        $user = $stmt->fetch();
        return $user;
    }

    public static function deleteTestUser(){
        $user = self::fetchTestUser();

        $sql = 'DELETE FROM public.user_access where user_id = \''.$user['id'].'\'';
        $stmt = self::$dbConnection->prepare($sql);
        $stmt->execute();

        $sql = 'DELETE FROM public."user" where email = \'test@test.com\'';
        $stmt = self::$dbConnection->prepare($sql);
        $stmt->execute();
    }

    public static function needsLogin(){
        $data = [
            'email' => 'test@test.com',
            'password' => '123456'
        ];
        $response = self::$httpClient->post('/auth/login', ['body'=>json_encode($data)]);
        
        $body = $response->getBody();
        $responseData = json_decode((string)$body);

        self::$needsLogin = true;
        self::$headers = ['Authorization' => $responseData->token];
    }

    public static function get($url){
        $headers = [];
        if(self::$needsLogin == true) $headers = self::$headers;
        $request = new Request('GET', $url, $headers);
        $response = self::$httpClient->send($request);
        return $response;
    }

    public static function post($url, $type, $body){
        $headers = [];

        switch ($type) {
            case 'json':
                $body = json_encode($body);
                break;
            case 'form':
                throw new \Exception("Não implementado", 1);
                break;
            default:
                throw new \Exception("Não implementado", 1);
                break;
        }
        
        if(self::$needsLogin == true) $headers = self::$headers;
        $request = new Request('POST', $url, $headers, $body);
        $response = self::$httpClient->send($request);
        return $response;
    }

    public static function setUpBeforeClass(): void
    {
        self::getConnection();
        self::$httpClient = new Client(['base_uri' => 'http://localhost:8000']);
        self::createTestUser();
    }
    
    protected function tearDown(): void
    {
        self::$needsLogin = false;
    }

    public static function tearDownAfterClass(): void
    {
        self::deleteTestUser();
        self::$dbConnection = null;
        self::$httpClient = null;
    }
}
