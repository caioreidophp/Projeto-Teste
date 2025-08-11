<?php
require_once 'config/config.php';
requireLogin();

// Buscar informações do usuário
$stmt = $pdo->prepare("
    SELECT 
        u.*,
        COUNT(DISTINCT pu.sinal_id) as sinais_aprendidos,
        COUNT(DISTINCT mu.missao_id) as missoes_concluidas
    FROM usuarios u
    LEFT JOIN progresso_usuario pu ON u.id = pu.usuario_id AND pu.concluido = 1
    LEFT JOIN missoes_usuario mu ON u.id = mu.usuario_id AND mu.concluida = 1
    WHERE u.id = ?
    GROUP BY u.id
");
$stmt->execute([getUserId()]);
$usuario = $stmt->fetch();

// Buscar progresso por módulo
$stmt = $pdo->prepare("
    SELECT 
        s.modulo,
        COUNT(*) as total_sinais,
        COUNT(pu.sinal_id) as sinais_concluidos
    FROM sinais s
    LEFT JOIN progresso_usuario pu ON s.id = pu.sinal_id AND pu.usuario_id = ? AND pu.concluido = 1
    GROUP BY s.modulo
    ORDER BY s.modulo
");
$stmt->execute([getUserId()]);
$progresso_modulos = $stmt->fetchAll();

// Buscar missões ativas do usuário
$stmt = $pdo->prepare("
    SELECT 
        m.*,
        COALESCE(mu.progresso_atual, 0) as progresso_atual,
        mu.concluida
    FROM missoes m
    LEFT JOIN missoes_usuario mu ON m.id = mu.missao_id AND mu.usuario_id = ?
    WHERE m.ativa = 1 AND m.modulo_requerido <= ?
    ORDER BY mu.concluida ASC, m.recompensa_pontos DESC
    LIMIT 6
");
$stmt->execute([getUserId(), $usuario['progresso_modulo']]);
$missoes = $stmt->fetchAll();

// Buscar sinais recentes por categoria
$stmt = $pdo->prepare("
    SELECT 
        c.id, c.nome, c.icone,
        COUNT(s.id) as total_sinais,
        COUNT(pu.sinal_id) as sinais_concluidos
    FROM categorias c
    LEFT JOIN sinais s ON c.id = s.categoria_id AND s.modulo <= ?
    LEFT JOIN progresso_usuario pu ON s.id = pu.sinal_id AND pu.usuario_id = ? AND pu.concluido = 1
    GROUP BY c.id
    ORDER BY c.nome
");
$stmt->execute([$usuario['progresso_modulo'], getUserId()]);
$categorias = $stmt->fetchAll();

// Calcular estatísticas
$total_sinais_disponiveis = 0;
$total_sinais_concluidos = 0;
foreach ($progresso_modulos as $modulo) {
    $total_sinais_disponiveis += $modulo['total_sinais'];
    $total_sinais_concluidos += $modulo['sinais_concluidos'];
}

$percentual_geral = $total_sinais_disponiveis > 0 ? round(($total_sinais_concluidos / $total_sinais_disponiveis) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Handly</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
</head>
<body>
    <header class="header">
        <div class="nav-container">
            <a href="home.php" class="logo">🤟 Handly</a>
            <nav>
                <ul class="nav-menu">
                    <li><a href="home.php">Home</a></li>
                    <li><a href="dicionario.php">Dicionário</a></li>
                    <li><a href="trilha.php">Trilha</a></li>
                    <li><a href="missoes.php">Missões</a></li>
                    <li><a href="perfil.php">Perfil</a></li>
                    <li><a href="logout.php" class="btn btn-outline">Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <!-- Boas-vindas -->
        <div class="card" style="background: linear-gradient(135deg, var(--primary-color), var(--accent-color)); color: white;">
            <h1 style="color: white; margin-bottom: 0.5rem;">
                Olá, <?php echo htmlspecialchars($usuario['nome']); ?>! 👋
            </h1>
            <p style="opacity: 0.9; font-size: 1.1rem;">
                Bem-vindo de volta! Continue sua jornada de aprendizado em LIBRAS.
            </p>
        </div>

        <!-- Estatísticas gerais -->
        <div class="grid grid-4">
            <div class="card text-center">
                <div class="stats">
                    <span class="stat-number"><?php echo $total_sinais_concluidos; ?></span>
                    <span class="stat-label">Sinais Aprendidos</span>
                </div>
            </div>
            <div class="card text-center">
                <div class="stats">
                    <span class="stat-number"><?php echo $percentual_geral; ?>%</span>
                    <span class="stat-label">Progresso Geral</span>
                </div>
            </div>
            <div class="card text-center">
                <div class="stats">
                    <span class="stat-number"><?php echo $usuario['progresso_modulo']; ?></span>
                    <span class="stat-label">Módulo Atual</span>
                </div>
            </div>
            <div class="card text-center">
                <div class="stats">
                    <span class="stat-number"><?php echo $usuario['pontuacao_total']; ?></span>
                    <span class="stat-label">Pontos</span>
                </div>
            </div>
        </div>

        <!-- Progresso por módulo -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Seu Progresso por Módulo</h2>
            </div>
            <div class="grid grid-3">
                <?php foreach ($progresso_modulos as $modulo): ?>
                    <?php 
                    $percentual_modulo = $modulo['total_sinais'] > 0 ? 
                        round(($modulo['sinais_concluidos'] / $modulo['total_sinais']) * 100) : 0;
                    $status = $modulo['modulo'] <= $usuario['progresso_modulo'] ? 'disponivel' : 'bloqueado';
                    ?>
                    <div class="card" style="<?php echo $status === 'bloqueado' ? 'opacity: 0.6;' : ''; ?>">
                        <div class="text-center">
                            <h3 style="color: var(--primary-color);">
                                Módulo <?php echo $modulo['modulo']; ?>
                                <?php if ($status === 'bloqueado'): ?>
                                    🔒
                                <?php elseif ($percentual_modulo === 100): ?>
                                    ✅
                                <?php endif; ?>
                            </h3>
                            <p style="margin: 1rem 0;">
                                <?php echo $modulo['sinais_concluidos']; ?> / <?php echo $modulo['total_sinais']; ?> sinais
                            </p>
                            <div class="progress">
                                <div class="progress-bar" style="width: <?php echo $percentual_modulo; ?>%"></div>
                            </div>
                            <p style="margin-top: 0.5rem; font-weight: bold;">
                                <?php echo $percentual_modulo; ?>%
                            </p>
                            <?php if ($status === 'disponivel'): ?>
                                <a href="trilha.php?modulo=<?php echo $modulo['modulo']; ?>" class="btn btn-primary" style="margin-top: 1rem;">
                                    <?php echo $percentual_modulo > 0 ? 'Continuar' : 'Começar'; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Missões ativas -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Missões Ativas</h2>
                <a href="missoes.php" style="color: var(--primary-color); text-decoration: none;">Ver todas →</a>
            </div>
            <div class="grid grid-2">
                <?php foreach (array_slice($missoes, 0, 4) as $missao): ?>
                    <?php 
                    $percentual_missao = round(($missao['progresso_atual'] / $missao['objetivo']) * 100);
                    $percentual_missao = min($percentual_missao, 100);
                    ?>
                    <div class="card" style="<?php echo $missao['concluida'] ? 'background-color: var(--light-gray);' : ''; ?>">
                        <h4 style="color: var(--primary-color); margin-bottom: 0.5rem;">
                            <?php echo htmlspecialchars($missao['titulo']); ?>
                            <?php if ($missao['concluida']): ?>
                                ✅
                            <?php endif; ?>
                        </h4>
                        <p style="margin-bottom: 1rem; color: var(--medium-gray);">
                            <?php echo htmlspecialchars($missao['descricao']); ?>
                        </p>
                        <div style="margin-bottom: 0.5rem;">
                            <small>Progresso: <?php echo $missao['progresso_atual']; ?> / <?php echo $missao['objetivo']; ?></small>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?php echo $percentual_missao; ?>%"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem;">
                            <span style="font-size: 0.875rem; color: var(--medium-gray);">
                                <?php echo $percentual_missao; ?>%
                            </span>
                            <span class="badge badge-primary">
                                +<?php echo $missao['recompensa_pontos']; ?> pts
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Categorias do dicionário -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Explore o Dicionário</h2>
                <a href="dicionario.php" style="color: var(--primary-color); text-decoration: none;">Ver todos →</a>
            </div>
            <div class="grid grid-4">
                <?php foreach (array_slice($categorias, 0, 8) as $categoria): ?>
                    <?php 
                    $percentual_categoria = $categoria['total_sinais'] > 0 ? 
                        round(($categoria['sinais_concluidos'] / $categoria['total_sinais']) * 100) : 0;
                    ?>
                    <a href="dicionario.php?categoria=<?php echo $categoria['id']; ?>" 
                       class="card text-center" 
                       style="text-decoration: none; color: inherit; transition: var(--transition);">
                        <div class="category-icon">
                            <?php
                            $icones = [
                                'alphabet.png' => '🔤',
                                'numbers.png' => '🔢',
                                'family.png' => '👨‍👩‍👧‍👦',
                                'colors.png' => '🎨',
                                'animals.png' => '🐾',
                                'food.png' => '🍎',
                                'emotions.png' => '😊',
                                'greetings.png' => '👋'
                            ];
                            echo $icones[$categoria['icone']] ?? '📖';
                            ?>
                        </div>
                        <h4><?php echo htmlspecialchars($categoria['nome']); ?></h4>
                        <p style="margin: 0.5rem 0; color: var(--medium-gray);">
                            <?php echo $categoria['sinais_concluidos']; ?> / <?php echo $categoria['total_sinais']; ?> sinais
                        </p>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?php echo $percentual_categoria; ?>%"></div>
                        </div>
                        <small style="color: var(--medium-gray);"><?php echo $percentual_categoria; ?>%</small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Ações Rápidas</h2>
            </div>
            <div class="grid grid-3">
                <a href="trilha.php" class="btn btn-primary btn-large">
                    🎯 Continuar Trilha
                </a>
                <a href="dicionario.php" class="btn btn-outline btn-large">
                    📚 Explorar Dicionário
                </a>
                <a href="missoes.php" class="btn btn-secondary btn-large">
                    🏆 Ver Missões
                </a>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container text-center">
            <p>&copy; 2025 Handly - Continue aprendendo LIBRAS todos os dias!</p>
        </div>
    </footer>

    <script>
        // Atualizar progresso em tempo real
        function atualizarProgresso() {
            // Aqui você pode adicionar AJAX para atualizar dados em tempo real
            // Por enquanto, apenas recarrega a página a cada 5 minutos
            setTimeout(() => {
                location.reload();
            }, 300000); // 5 minutos
        }

        // Animações para os cards de estatísticas
        document.addEventListener('DOMContentLoaded', function() {
            const statNumbers = document.querySelectorAll('.stat-number');
            
            statNumbers.forEach(stat => {
                const finalValue = parseInt(stat.textContent);
                let currentValue = 0;
                const increment = Math.ceil(finalValue / 20);
                
                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        currentValue = finalValue;
                        clearInterval(timer);
                    }
                    stat.textContent = currentValue + (stat.textContent.includes('%') ? '%' : '');
                }, 50);
            });
        });

        // Hover effect nos cards de categoria
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>
