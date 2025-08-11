<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$sinal_id = $_GET['id'] ?? '';

if (!$sinal_id) {
    echo json_encode(['success' => false, 'message' => 'ID do sinal não fornecido']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            s.*,
            c.nome as categoria_nome,
            CASE 
                WHEN pu.concluido = 1 THEN 'concluido'
                WHEN pu.id IS NOT NULL THEN 'iniciado'
                ELSE 'nao_iniciado'
            END as status_usuario
        FROM sinais s
        JOIN categorias c ON s.categoria_id = c.id
        LEFT JOIN progresso_usuario pu ON s.id = pu.sinal_id AND pu.usuario_id = ?
        WHERE s.id = ?
    ");
    $stmt->execute([getUserId(), $sinal_id]);
    $sinal = $stmt->fetch();
    
    if ($sinal) {
        echo json_encode([
            'success' => true,
            'sinal' => $sinal,
            'status_usuario' => $sinal['status_usuario']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Sinal não encontrado']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro do servidor']);
}
?>
