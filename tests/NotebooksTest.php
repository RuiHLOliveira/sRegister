<?php

namespace App\Tests;

use PDO;
use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use App\Tests\DefaultTestCase;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\VarDumper\VarDumper;

use function PHPUnit\Framework\assertEquals;

class NotebooksTest extends DefaultTestCase //extends TestCase
{

    private function getUser () {
        $user = self::fetchTestUser();
        return $user;
    }

    private function getUserId () {
        $user = $this->getUser();
        return $user['id'];
    }

    private function find($table,$array) {
        $where = '';
        foreach ($array as $key => $value) {
            $where .= "$key = :$key";
        }

        $sql = "SELECT * FROM $table WHERE ($where);";
        $stmt = self::$dbConnection->prepare($sql);

        foreach ($array as $key => $value) {
            $stmt->bindValue(":$key",$value);
        }
        $stmt->execute();
    }

    private function createInDatabase ($table,$array) {
        $fields = array_keys($array);

        $values = $fields;
        $values = array_map(function ($element) {
            return ":".$element;
        },$values);

        $fields = implode(',',$fields);
        $values = implode(',',$values);

        $sql = "INSERT INTO $table ($fields) VALUES ($values);";
        $stmt = self::$dbConnection->prepare($sql);

        foreach ($array as $key => $value) {
            $stmt->bindValue(":$key",$value);
        }

        $stmt->execute();
        
        return self::$dbConnection->lastInsertId();
    }

    private function removeFromDatabase($table, $array) {
        $where = '';
        foreach ($array as $key => $value) {
            $where .= "$key = :$key";
        }

        $sql = "DELETE FROM $table WHERE ($where);";
        $stmt = self::$dbConnection->prepare($sql);

        foreach ($array as $key => $value) {
            $stmt->bindValue(":$key",$value);
        }
        $stmt->execute();
    }

    private function createNotebook() {
        $now = new DateTime();
        $notebook = [
            'user_id' => $this->getUserId(),
            'name' => 'teste',
            'created_at' => $now->format('Y-m-d H:i:s'),
            'updated_at' => $now->format('Y-m-d H:i:s'),
        ];
        $id = $this->createInDatabase('notebook',$notebook);

        $notebook['id'] = $id;
        return $notebook;
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
        $notebooks[] = $this->createNotebook();
        $notebooks[] = $this->createNotebook();
        $response = self::get('/api/notebooks');
        $responseBody = $response->getBody();
        $responseData = json_decode((string) $responseBody, true);
        $this->assertEquals(count($notebooks), count($responseData['notebooks']));
        $this->assertEquals(200, $response->getStatusCode());
        $this->removeFromDatabase('notebook',['user_id' => $notebooks[0]['user_id']]);
    }

    public function testCanGetNotebook()
    {
        self::needsLogin();
        $notebook = $this->createNotebook();
        $notebooks[] = $this->createNotebook();
        $response = self::get('/api/notebooks/'.$notebook['id']);
        $responseBody = $response->getBody();
        $responseData = json_decode((string) $responseBody, true);
        $this->assertEquals($responseData['notebook']['id'], $notebook['id']);
        $this->assertEquals(200, $response->getStatusCode());
        $this->removeFromDatabase('notebook',['user_id' => $notebook['user_id']]);
        $this->removeFromDatabase('notebook',['user_id' => $notebooks[0]['user_id']]);
    }
}