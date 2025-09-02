<?php
require_once '../config/config.php';

header('Content-Type: application/json');

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

// Ler dados JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['pontos']) || !isset($input['acao'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}

$pontos = intval($input['pontos']);
$acao = $input['acao'];
$usuario_id = $_SESSION['user_id'];

if ($pontos <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Pontos devem ser positivos']);
    exit;
}

try {
    // Iniciar transação
    $pdo->beginTransaction();
    
    // Atualizar pontuação do usuário
    $stmt = $pdo->prepare("UPDATE usuarios SET pontuacao_total = pontuacao_total + ? WHERE id = ?");
    $stmt->execute([$pontos, $usuario_id]);
    
    // Registrar histórico de pontuação (se a tabela existir)
    try {
        $stmt = $pdo->prepare("INSERT INTO historico_pontuacao (usuario_id, pontos, acao, data_criacao) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$usuario_id, $pontos, $acao]);
    } catch (PDOException $e) {
        // Tabela pode não existir, continuar sem erro
    }
    
    // Commit da transação
    $pdo->commit();
    
    // Buscar pontuação atualizada
    $stmt = $pdo->prepare("SELECT pontuacao_total FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $nova_pontuacao = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'pontos_adicionados' => $pontos,
        'pontuacao_total' => $nova_pontuacao,
        'acao' => $acao
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno do servidor']);
}
?>
