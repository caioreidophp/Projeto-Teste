<?php
require_once 'config/config.php';
requireLogin();

$modulo_selecionado = intval($_GET['modulo'] ?? 1);

// Buscar informações do usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([getUserId()]);
$usuario = $stmt->fetch();

// Buscar progresso geral por módulo
$stmt = $pdo->prepare("SELECT s.modulo, COUNT(*) as total_sinais, COUNT(pu.sinal_id) as sinais_concluidos, ROUND((COUNT(pu.sinal_id) / COUNT(*)) * 100) as percentual FROM sinais s LEFT JOIN progresso_usuario pu ON s.id = pu.sinal_id AND pu.usuario_id = ? AND pu.concluido = 1 GROUP BY s.modulo ORDER BY s.modulo");
$stmt->execute([getUserId()]);
$modulos = $stmt->fetchAll();

$cores_modulo = [1 => ['bg' => '#10B981', 'light' => '#D1FAE5'], 2 => ['bg' => '#F59E0B', 'light' => '#FEF3C7'], 3 => ['bg' => '#EF4444', 'light' => '#FEE2E2']];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Módulos - Trilha</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <main class="container">
        <div class="card">
            <h2>Todos os Módulos</h2>
            <p style="color:var(--medium-gray);">Veja os módulos disponíveis e quais estão bloqueados.</p>
        </div>

        <div class="grid grid-3">
            <?php foreach ($modulos as $modulo):
                $liberado = $modulo['modulo'] <= $usuario['progresso_modulo'];
                $cor = $cores_modulo[$modulo['modulo']];
            ?>
            <div class="card" style="border-radius:12px; <?php echo !$liberado ? 'opacity:0.6;' : ''; ?>">
                <div class="text-center" style="padding:1rem;">
                    <div style="font-size:3rem; margin-bottom:1rem;">
                        <?php if (!$liberado): ?>
                            🔒
                        <?php elseif ($modulo['percentual'] == 100): ?>
                            🏆
                        <?php else: ?>
                            <?php echo $modulo['modulo'] == 1 ? '🌱' : ($modulo['modulo'] == 2 ? '🌿' : '🌳'); ?>
                        <?php endif; ?>
                    </div>

                    <h3 style="color: <?php echo $cor['bg']; ?>;">Módulo <?php echo $modulo['modulo']; ?></h3>
                    <div style="margin:0.5rem 0;">
                        <span class="badge" style="background: <?php echo $cor['light']; ?>; color: <?php echo $cor['bg']; ?>; padding:0.3rem 0.8rem; border-radius:20px;">
                            <?php echo $modulo['modulo'] == 1 ? '😊 Básico' : ($modulo['modulo'] == 2 ? '🤔 Intermediário' : '💪 Avançado'); ?>
                        </span>
                    </div>

                    <p style="color:var(--medium-gray);">📚 <?php echo $modulo['sinais_concluidos']; ?> / <?php echo $modulo['total_sinais']; ?> sinais</p>
                    <div class="progress" style="height:8px; border-radius:8px; margin:0.5rem 0; overflow:hidden;">
                        <div style="width:<?php echo $modulo['percentual']; ?>%; height:100%; background:<?php echo $cor['bg']; ?>;"></div>
                    </div>

                    <?php if ($liberado): ?>
                        <a href="trilha.php?modulo=<?php echo $modulo['modulo']; ?>" class="btn btn-primary" style="margin-top:0.75rem;">Abrir Módulo</a>
                    <?php else: ?>
                        <div style="margin-top:0.75rem; color:var(--medium-gray);">Complete o módulo anterior para desbloquear</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>
