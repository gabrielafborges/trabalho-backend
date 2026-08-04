<?php
/**
 * logout.php
 * Encerrar Sessão e Limpar Redis Session Hash
 */
require_once __DIR__ . '/config.php';

if (isset($_SESSION['session_token'])) {
    $redis->del("user:session:" . $_SESSION['session_token']);
}

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

header("Location: login.php");
exit;
?>
