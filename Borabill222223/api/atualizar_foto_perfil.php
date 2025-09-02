<?php
session_start();
require_once '../config/config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não logado']);
    exit;
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Obter dados JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['foto_perfil'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Foto de perfil não fornecida']);
    exit;
}

$avatar = $input['foto_perfil'];
$user_id = $_SESSION['user_id'];

try {
    // Atualizar o avatar do usuário
    $stmt = $pdo->prepare("UPDATE usuarios SET avatar = ? WHERE id = ?");
    $result = $stmt->execute([$avatar, $user_id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Avatar atualizado com sucesso']);
    } else {
        throw new Exception('Erro ao atualizar no banco de dados');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor: ' . $e->getMessage()]);
}
?>
