<?php
require_once 'config/config.php';
requireLogin();

$modulo = intval($_GET['modulo'] ?? 1);

// Buscar 5 sinais do módulo
$stmt = $pdo->prepare("SELECT id, palavra, video_url FROM sinais WHERE modulo = ? ORDER BY RAND() LIMIT 5");
$stmt->execute([$modulo]);
$questoes = $stmt->fetchAll();

// buscar distractores (outras palavras) para montar alternativas
$stmt2 = $pdo->prepare("SELECT palavra FROM sinais WHERE modulo != ? ORDER BY RAND() LIMIT 50");
$stmt2->execute([$modulo]);
$others = array_column($stmt2->fetchAll(), 'palavra');

function shuffleOptions($correct, &$pool){
    $opts = [$correct];
    shuffle($pool);
    for($i=0;$i<3 && isset($pool[$i]);$i++) $opts[] = $pool[$i];
    shuffle($opts);
    return $opts;
}

// processar submissão
$score = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $respostas = $_POST['resposta'] ?? [];
    $corretas = 0;
    foreach ($questoes as $i => $q) {
        $correct = $q['palavra'];
        if (isset($respostas[$i]) && $respostas[$i] === $correct) $corretas++;
    }
    $score = $corretas;

    // opcional: marcar sinais como aprendidos quando acertou
    foreach ($questoes as $i => $q) {
        if (isset($respostas[$i]) && $respostas[$i] === $q['palavra']) {
            // marcar progresso se ainda não marcado
            $stmt3 = $pdo->prepare("INSERT INTO progresso_usuario (usuario_id, sinal_id, concluido, data_conclusao, tentativas)
                VALUES (?, ?, 1, NOW(), 1) ON DUPLICATE KEY UPDATE concluido = 1, data_conclusao = NOW(), tentativas = tentativas + 1");
            $stmt3->execute([getUserId(), $q['id']]);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Quiz - Módulo <?php echo $modulo; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="container">
    <div class="card">
        <h2>Quiz — Módulo <?php echo $modulo; ?></h2>
        <?php if ($score === null): ?>
        <form method="POST">
            <?php foreach ($questoes as $i => $q):
                $options = shuffleOptions($q['palavra'], $others);
            ?>
            <div class="card" style="padding:1rem; margin-bottom:1rem;">
                <div style="font-weight:700; margin-bottom:0.5rem;">Pergunta <?php echo $i+1; ?></div>
                <?php if ($q['video_url']): ?>
                    <video controls style="width:100%; max-width:400px; margin-bottom:0.75rem;">
                        <source src="<?php echo $q['video_url']; ?>" type="video/mp4">
                    </video>
                <?php else: ?>
                    <div style="padding:1rem; background:#f3f4f6; margin-bottom:0.75rem;">Vídeo não disponível</div>
                <?php endif; ?>

                <?php foreach ($options as $opt): ?>
                    <label style="display:block; margin:0.25rem 0;">
                        <input type="radio" name="resposta[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($opt); ?>"> <?php echo htmlspecialchars($opt); ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>

            <button class="btn btn-primary" type="submit">Enviar respostas</button>
        </form>
        <?php else: ?>
            <div class="text-center">
                <h3>Você acertou <?php echo $score; ?> / <?php echo count($questoes); ?> perguntas</h3>
                <p>Parabéns pelo progresso! Seus acertos foram salvos como sinais aprendidos.</p>
                <a href="trilha.php?modulo=<?php echo $modulo; ?>" class="btn">Voltar para a trilha</a>
            </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
