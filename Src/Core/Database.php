<?php

namespace App\Core;

use Exception;
use PDO;

final class Database{
    private $data = [];
    public static $pdo;

    public function __construct($host, $port, $db_name, $user, $password){
        $data = [
            'host' => $host,
            'port' => $port,
            'db_name' => $db_name,
            'user' => $user,
            'password' => $password
            ,
        ];
    }

    public static function fromEnv(){ 
         $data = [
            'host' => getenv('DB_HOST'),
            'port' => getenv('DB_PORT'),
            'db_name' => getenv('DB_NAME'),
            'user' => getenv('DB_USER'),
            'password' => getenv('DB_PASSWORD'),
        ];

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Выбрасывать исключения при ошибках
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // Ассоциативный массив по умолчанию
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Настоящие подготовленные выражения
        ];

        $dsn = sprintf("mysql:host=%s;dbname=%s;charset=utf8mb4", $data['host'], $data['db_name']);

        try{
            $pdo = new PDO($dsn, $data['user'], $data['password'], $options);
        } catch (\PDOException $e){
            //$e->error_log();
            throw new Exception('uncorrect connect 2 db');
        }
    
        self::$pdo = $pdo;
        return self::$pdo;
    }

}