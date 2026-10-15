<?php

const DB_HOST  = '127.0.0.1';
const DB_PORTA = '3306';
const DB_NOME  = 'gerenciamento_chaves';
const DB_USER  = 'root';
const DB_SENHA = '';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORTA . ';dbname=' . DB_NOME . ';charset=utf8mb4',
        DB_USER,
        DB_SENHA,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('Falha na conexão com o banco: ' . $e->getMessage());
    http_response_code(500);
    exit('Erro ao conectar ao banco de dados.');
}