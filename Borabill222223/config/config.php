<?php
// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'handly');

// Configurações gerais do site
define('SITE_URL', 'http://localhost:8080/Borabill222223');
define('SITE_NAME', 'Handly');
define('SITE_DESCRIPTION', 'Aprenda LIBRAS de forma interativa e divertida');

// Configurações de sessão
ini_set('session.gc_maxlifetime', 3600); // 1 hora
session_start();

// Conexão com o banco de dados
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Funções auxiliares
function redirect($url) {
    header("Location: " . SITE_URL . "/" . $url);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getUserName() {
    return $_SESSION['user_name'] ?? '';
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}
?>
