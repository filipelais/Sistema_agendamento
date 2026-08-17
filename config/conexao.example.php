<?php
// Copie este arquivo para "conexao.php" e preencha com seus dados de acesso.

$host = 'localhost';
$dbname = 'nome_do_banco';
$usuario = 'seu_usuario';
$senha = 'sua_senha';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $usuario,
        $senha,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados.");
}