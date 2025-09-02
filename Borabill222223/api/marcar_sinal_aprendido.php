<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$sinal_id = $input['sinal_id'] ?? '';

if (!$sinal_id) {
    echo json_encode(['success' => false, 'message' => 'ID do sinal não fornecido']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Inserir ou atualizar progresso
    $stmt = $pdo->prepare("
        INSERT INTO progresso_usuario (usuario_id, sinal_id, concluido, data_conclusao, tentativas)
        VALUES (?, ?, 1, NOW(), 1)
        ON DUPLICATE KEY UPDATE
        concluido = 1,
        data_conclusao = NOW(),
        tentativas = tentativas + 1
    ");
    $stmt->execute([getUserId(), $sinal_id]);
    
    // Buscar informações do sinal para calcular pontos
    $stmt = $pdo->prepare("SELECT dificuldade FROM sinais WHERE id = ?");
    $stmt->execute([$sinal_id]);
    $sinal = $stmt->fetch();
    
    if ($sinal) {
        // Calcular pontos baseado na dificuldade
        $pontos = match($sinal['dificuldade']) {
            'facil' => 10,
            'medio' => 20,
            'dificil' => 30,
            default => 10
        };
        
        // Atualizar pontuação do usuário
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET pontuacao_total = pontuacao_total + ? 
            WHERE id = ?
        ");
        $stmt->execute([$pontos, getUserId()]);
        
        // Verificar se completou alguma missão
        atualizarMissoes(getUserId(), $pdo);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Sinal marcado como aprendido!',
        'pontos_ganhos' => $pontos ?? 0
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar progresso']);
}

function atualizarMissoes($usuario_id, $pdo) {
    // Buscar missões ativas do usuário
    $stmt = $pdo->prepare("
        SELECT m.*, COALESCE(mu.progresso_atual, 0) as progresso_atual
        FROM missoes m
        LEFT JOIN missoes_usuario mu ON m.id = mu.missao_id AND mu.usuario_id = ?
        WHERE m.ativa = 1 AND (mu.concluida = 0 OR mu.concluida IS NULL)
    ");
    $stmt->execute([$usuario_id]);
    $missoes = $stmt->fetchAll();
    
    foreach ($missoes as $missao) {
        $novo_progresso = $missao['progresso_atual'];
        
        switch ($missao['tipo']) {
            case 'aprender_sinais':
                // Contar sinais aprendidos
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM progresso_usuario 
                    WHERE usuario_id = ? AND concluido = 1
                ");
                $stmt->execute([$usuario_id]);
                $novo_progresso = $stmt->fetchColumn();
                break;
                
            case 'categoria_completa':
                // Verificar se completou alguma categoria
                $stmt = $pdo->prepare("
                    SELECT COUNT(DISTINCT s.categoria_id) 
                    FROM sinais s
                    JOIN progresso_usuario pu ON s.id = pu.sinal_id
                    WHERE pu.usuario_id = ? AND pu.concluido = 1
                    AND s.categoria_id NOT IN (
                        SELECT DISTINCT s2.categoria_id
                        FROM sinais s2
                        LEFT JOIN progresso_usuario pu2 ON s2.id = pu2.sinal_id AND pu2.usuario_id = ?
                        WHERE pu2.concluido IS NULL OR pu2.concluido = 0
                    )
                ");
                $stmt->execute([$usuario_id, $usuario_id]);
                $novo_progresso = $stmt->fetchColumn();
                break;
        }
        
        // Atualizar progresso da missão
        $stmt = $pdo->prepare("
            INSERT INTO missoes_usuario (usuario_id, missao_id, progresso_atual, concluida)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            progresso_atual = VALUES(progresso_atual),
            concluida = VALUES(concluida),
            data_conclusao = CASE WHEN VALUES(concluida) = 1 THEN NOW() ELSE data_conclusao END
        ");
        
        $concluida = $novo_progresso >= $missao['objetivo'];
        $stmt->execute([$usuario_id, $missao['id'], $novo_progresso, $concluida]);
        
        // Se completou a missão, dar pontos extras
        if ($concluida && $novo_progresso != $missao['progresso_atual']) {
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET pontuacao_total = pontuacao_total + ? 
                WHERE id = ?
            ");
            $stmt->execute([$missao['recompensa_pontos'], $usuario_id]);
        }
    }
}
?>
