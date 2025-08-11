<?php
require_once 'config/config.php';
requireLogin();

// Buscar informações do usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([getUserId()]);
$usuario = $stmt->fetch();

// Buscar todas as missões do usuário
$stmt = $pdo->prepare("
    SELECT 
        m.*,
        COALESCE(mu.progresso_atual, 0) as progresso_atual,
        COALESCE(mu.concluida, 0) as concluida,
        mu.data_conclusao
    FROM missoes m
    LEFT JOIN missoes_usuario mu ON m.id = mu.missao_id AND mu.usuario_id = ?
    WHERE m.ativa = 1
    ORDER BY 
        CASE WHEN mu.concluida = 1 THEN 1 ELSE 0 END,
        m.modulo_requerido,
        m.recompensa_pontos DESC
");
$stmt->execute([getUserId()]);
$todas_missoes = $stmt->fetchAll();

// Separar missões por categoria
$missoes_ativas = [];
$missoes_concluidas = [];
$missoes_bloqueadas = [];

foreach ($todas_missoes as $missao) {
    if ($missao['concluida']) {
        $missoes_concluidas[] = $missao;
    } elseif ($missao['modulo_requerido'] <= $usuario['progresso_modulo']) {
        $missoes_ativas[] = $missao;
    } else {
        $missoes_bloqueadas[] = $missao;
    }
}

// Calcular estatísticas
$total_missoes = count($todas_missoes);
$missoes_completas = count($missoes_concluidas);
$pontos_ganhos_missoes = array_sum(array_column($missoes_concluidas, 'recompensa_pontos'));
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Missões - Handly</title>
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
                    <li><a href="missoes.php" style="background-color: rgba(255,255,255,0.1); border-radius: var(--border-radius);">Missões</a></li>
                    <li><a href="perfil.php">Perfil</a></li>
                    <li><a href="logout.php" class="btn btn-outline">Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <!-- Cabeçalho -->
        <div class="card" style="background: linear-gradient(135deg, var(--primary-color), var(--accent-color)); color: white;">
            <h1 style="color: white; margin-bottom: 1rem;">🏆 Centro de Missões</h1>
            <p style="opacity: 0.9; font-size: 1.1rem; margin-bottom: 2rem;">
                Complete missões para ganhar pontos e desbloquear conquistas especiais!
            </p>
            
            <!-- Estatísticas -->
            <div class="grid grid-4">
                <div class="text-center">
                    <div class="stat-number" style="color: white;"><?php echo $missoes_completas; ?></div>
                    <div class="stat-label" style="color: rgba(255,255,255,0.8);">Missões Completas</div>
                </div>
                <div class="text-center">
                    <div class="stat-number" style="color: white;"><?php echo count($missoes_ativas); ?></div>
                    <div class="stat-label" style="color: rgba(255,255,255,0.8);">Missões Ativas</div>
                </div>
                <div class="text-center">
                    <div class="stat-number" style="color: white;"><?php echo $pontos_ganhos_missoes; ?></div>
                    <div class="stat-label" style="color: rgba(255,255,255,0.8);">Pontos por Missões</div>
                </div>
                <div class="text-center">
                    <div class="stat-number" style="color: white;"><?php echo $usuario['pontuacao_total']; ?></div>
                    <div class="stat-label" style="color: rgba(255,255,255,0.8);">Pontos Totais</div>
                </div>
            </div>
        </div>

        <!-- Missões Ativas -->
        <?php if (!empty($missoes_ativas)): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">🎯 Missões Ativas</h2>
                <p style="color: var(--medium-gray);">
                    Complete estas missões para ganhar pontos e avançar na sua jornada
                </p>
            </div>
            
            <div class="grid grid-2">
                <?php foreach ($missoes_ativas as $missao): ?>
                    <?php 
                    $percentual = $missao['objetivo'] > 0 ? 
                        min(round(($missao['progresso_atual'] / $missao['objetivo']) * 100), 100) : 0;
                    ?>
                    <div class="card">
                        <div class="flex-between" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                            <h3 style="color: var(--primary-color); margin: 0;">
                                <?php echo htmlspecialchars($missao['titulo']); ?>
                            </h3>
                            <span class="badge badge-primary">
                                +<?php echo $missao['recompensa_pontos']; ?> pts
                            </span>
                        </div>
                        
                        <p style="color: var(--medium-gray); margin-bottom: 1rem;">
                            <?php echo htmlspecialchars($missao['descricao']); ?>
                        </p>
                        
                        <div style="margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span style="font-size: 0.9rem;">
                                    Progresso: <strong><?php echo $missao['progresso_atual']; ?> / <?php echo $missao['objetivo']; ?></strong>
                                </span>
                                <span style="font-weight: bold; color: var(--primary-color);">
                                    <?php echo $percentual; ?>%
                                </span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" style="width: <?php echo $percentual; ?>%"></div>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="badge badge-<?php 
                                echo $missao['modulo_requerido'] == 1 ? 'easy' : 
                                     ($missao['modulo_requerido'] == 2 ? 'medium' : 'hard'); 
                            ?>">
                                Módulo <?php echo $missao['modulo_requerido']; ?>
                            </span>
                            
                            <?php if ($percentual >= 100): ?>
                                <span style="color: var(--success-color); font-weight: bold;">
                                    ✅ Pronto para coleta!
                                </span>
                            <?php else: ?>
                                <span style="color: var(--medium-gray); font-size: 0.875rem;">
                                    Tipo: <?php echo ucfirst(str_replace('_', ' ', $missao['tipo'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Missões Concluídas -->
        <?php if (!empty($missoes_concluidas)): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">✅ Missões Concluídas</h2>
                <p style="color: var(--medium-gray);">
                    Parabéns pelas missões completadas! Aqui estão suas conquistas
                </p>
            </div>
            
            <div class="grid grid-3">
                <?php foreach ($missoes_concluidas as $missao): ?>
                    <div class="card" style="background-color: var(--light-gray); border-left: 4px solid var(--success-color);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                            <h4 style="color: var(--success-color); margin: 0;">
                                <?php echo htmlspecialchars($missao['titulo']); ?>
                            </h4>
                            <span style="font-size: 1.5rem;">✅</span>
                        </div>
                        
                        <p style="color: var(--medium-gray); margin-bottom: 1rem; font-size: 0.9rem;">
                            <?php echo htmlspecialchars($missao['descricao']); ?>
                        </p>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="badge badge-primary">
                                +<?php echo $missao['recompensa_pontos']; ?> pts
                            </span>
                            <small style="color: var(--medium-gray);">
                                <?php echo formatDate($missao['data_conclusao']); ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Missões Bloqueadas -->
        <?php if (!empty($missoes_bloqueadas)): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">🔒 Missões Futuras</h2>
                <p style="color: var(--medium-gray);">
                    Estas missões serão desbloqueadas conforme você progride nos módulos
                </p>
            </div>
            
            <div class="grid grid-2">
                <?php foreach ($missoes_bloqueadas as $missao): ?>
                    <div class="card" style="opacity: 0.7; border: 2px dashed var(--border-color);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                            <h3 style="color: var(--medium-gray); margin: 0;">
                                🔒 <?php echo htmlspecialchars($missao['titulo']); ?>
                            </h3>
                            <span class="badge" style="background-color: var(--medium-gray); color: white;">
                                +<?php echo $missao['recompensa_pontos']; ?> pts
                            </span>
                        </div>
                        
                        <p style="color: var(--medium-gray); margin-bottom: 1rem;">
                            <?php echo htmlspecialchars($missao['descricao']); ?>
                        </p>
                        
                        <div style="background-color: var(--warning-color); color: var(--tertiary-color); padding: 0.75rem; border-radius: var(--border-radius); text-align: center;">
                            <strong>Requisito:</strong> Módulo <?php echo $missao['modulo_requerido']; ?>
                            <br>
                            <small>Complete o módulo <?php echo $missao['modulo_requerido'] - 1; ?> para desbloquear</small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tipos de Missões -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">📋 Tipos de Missões</h2>
            </div>
            
            <div class="grid grid-2">
                <div class="card">
                    <h4 style="color: var(--primary-color); margin-bottom: 1rem;">🎯 Missões de Aprendizado</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 0.5rem;">📚 <strong>Aprender Sinais:</strong> Complete um número específico de sinais</li>
                        <li style="margin-bottom: 0.5rem;">📖 <strong>Categoria Completa:</strong> Termine todas as palavras de uma categoria</li>
                        <li style="margin-bottom: 0.5rem;">🎓 <strong>Módulo Completo:</strong> Finalize um módulo inteiro</li>
                    </ul>
                </div>
                
                <div class="card">
                    <h4 style="color: var(--primary-color); margin-bottom: 1rem;">⚡ Missões de Engajamento</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 0.5rem;">📅 <strong>Sequência de Dias:</strong> Estude por vários dias consecutivos</li>
                        <li style="margin-bottom: 0.5rem;">🏆 <strong>Pontuação:</strong> Acumule uma quantidade específica de pontos</li>
                        <li style="margin-bottom: 0.5rem;">💪 <strong>Dedicação:</strong> Complete atividades diárias</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Sistema de Pontos -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">💎 Sistema de Pontos</h2>
            </div>
            
            <div class="grid grid-3">
                <div class="text-center">
                    <div style="font-size: 3rem; margin-bottom: 0.5rem;">📚</div>
                    <h4>Sinais Aprendidos</h4>
                    <p style="color: var(--medium-gray);">
                        <strong>Fácil:</strong> 10 pontos<br>
                        <strong>Médio:</strong> 20 pontos<br>
                        <strong>Difícil:</strong> 30 pontos
                    </p>
                </div>
                
                <div class="text-center">
                    <div style="font-size: 3rem; margin-bottom: 0.5rem;">🏆</div>
                    <h4>Missões</h4>
                    <p style="color: var(--medium-gray);">
                        Ganhe pontos extras ao completar missões especiais e desafios
                    </p>
                </div>
                
                <div class="text-center">
                    <div style="font-size: 3rem; margin-bottom: 0.5rem;">🎯</div>
                    <h4>Conquistas</h4>
                    <p style="color: var(--medium-gray);">
                        Desbloqueie recompensas especiais e novos conteúdos
                    </p>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="card text-center" style="background: linear-gradient(135deg, var(--primary-color), var(--accent-color)); color: white;">
            <h2 style="color: white; margin-bottom: 1rem;">Pronto para mais missões?</h2>
            <p style="opacity: 0.9; margin-bottom: 2rem;">
                Continue aprendendo para desbloquear novas missões e ganhar mais pontos!
            </p>
            <div>
                <a href="trilha.php" class="btn btn-large btn-secondary" style="margin-right: 1rem;">
                    🎯 Continuar Trilha
                </a>
                <a href="dicionario.php" class="btn btn-large btn-outline" style="border-color: white; color: white;">
                    📚 Explorar Dicionário
                </a>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container text-center">
            <p>&copy; 2025 Handly - Complete missões e torne-se um expert em LIBRAS!</p>
        </div>
    </footer>

    <script>
        // Animação das barras de progresso
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-bar');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const bar = entry.target;
                        const width = bar.style.width;
                        bar.style.width = '0%';
                        
                        setTimeout(() => {
                            bar.style.transition = 'width 1.5s ease-out';
                            bar.style.width = width;
                        }, 200);
                        
                        observer.unobserve(bar);
                    }
                });
            });
            
            progressBars.forEach(bar => {
                observer.observe(bar);
            });
        });

        // Animação dos números de estatísticas
        function animarEstatisticas() {
            const statNumbers = document.querySelectorAll('.stat-number');
            
            statNumbers.forEach(stat => {
                const finalValue = parseInt(stat.textContent);
                if (isNaN(finalValue)) return;
                
                let currentValue = 0;
                const increment = Math.ceil(finalValue / 30);
                
                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        currentValue = finalValue;
                        clearInterval(timer);
                    }
                    stat.textContent = currentValue;
                }, 50);
            });
        }

        // Executar animações quando a página carregar
        document.addEventListener('DOMContentLoaded', animarEstatisticas);

        // Efeito hover nos cards
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                if (!this.style.opacity || this.style.opacity === '1') {
                    this.style.transform = 'translateY(-3px)';
                    this.style.transition = 'transform 0.3s ease';
                }
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>
