<?php
require_once 'config/config.php';

// Destruir a sessão
session_destroy();

// Redirecionar para a página inicial
redirect('index.php');
?>
