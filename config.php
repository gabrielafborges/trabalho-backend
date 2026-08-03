<?php
// ======================================================
// Arquivo de Configuração e Conexão com Banco de Dados
// ======================================================

// Inicializar a sessão caso ainda não esteja ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------
// CONFIGURAÇÃO DO BANCO DE DADOS
// ------------------------------------------------------
$driver = 'mysql'; // Configurado para MySQL (XAMPP / WAMP / MySQL Server)

// Configurações do MySQL no XAMPP (padrão: host=localhost, user=root, sem senha)
$db_host = 'localhost';
$db_name = 'sistema_login';
$db_user = 'root';
$db_pass = '';

try {
    if ($driver === 'mysql') {
        // Conexão inicial sem especificar o banco de dados (para permitir a criação automática se não existir)
        $pdo = new PDO("mysql:host={$db_host};charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Criar o banco de dados se ainda não existir
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$db_name}`");

        // Criar a tabela de usuários se ainda não existir
        $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            senha VARCHAR(255) NOT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    } else {
        // Conexão via SQLite (fallback)
        $db_path = __DIR__ . '/banco.db';
        $pdo = new PDO("sqlite:" . $db_path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            senha TEXT NOT NULL,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }

    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Tratar erro de conexão de forma amigável com orientações para o XAMPP
    die("<div style='font-family: sans-serif; padding: 24px; color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 12px; max-width: 600px; margin: 40px auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>
            <h3 style='margin-top:0;'>Erro de Conexão com o MySQL (XAMPP)!</h3>
            <p><strong>Detalhes do erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <hr style='border:0; border-top: 1px solid #f5c6cb; margin: 15px 0;'>
            <p style='font-size: 14px; margin-bottom: 0;'>
                📌 <strong>Dica XAMPP:</strong> Certifique-se de que o módulo <strong>MySQL</strong> está iniciado no <strong>XAMPP Control Panel</strong>.
            </p>
         </div>");
}
?>
