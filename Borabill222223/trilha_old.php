<?php
require_once 'config/config.php';
requireLogin();

$modulo_selecionado = $_GET['modulo'] ?? 1;

// Buscar informações do usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([getUserId()]);
$usuario = $stmt->fetch();

// Buscar progresso geral por módulo
$stmt = $pdo->prepare("
    SELECT 
        s.modulo,
        COUNT(*) as total_sinais,
        COUNT(pu.sinal_id) as sinais_concluidos,
        ROUND((COUNT(pu.sinal_id) / COUNT(*)) * 100) as percentual
    FROM sinais s
    LEFT JOIN progresso_usuario pu ON s.id = pu.sinal_id AND pu.usuario_id = ? AND pu.concluido = 1
    GROUP BY s.modulo
    ORDER BY s.modulo
");
$stmt->execute([getUserId()]);
$modulos = $stmt->fetchAll();

// Buscar sinais do módulo selecionado agrupados por categoria
$stmt = $pdo->prepare("
    SELECT 
        c.id as categoria_id,
        c.nome as categoria_nome,
        c.icone as categoria_icone,
        s.*,
        CASE 
            WHEN pu.concluido = 1 THEN 'concluido'
            WHEN pu.id IS NOT NULL THEN 'iniciado'
            ELSE 'nao_iniciado'
        END as status_usuario
    FROM categorias c
    JOIN sinais s ON c.id = s.categoria_id
    LEFT JOIN progresso_usuario pu ON s.id = pu.sinal_id AND pu.usuario_id = ?
    WHERE s.modulo = ?
    ORDER BY c.nome, s.palavra
");
$stmt->execute([getUserId(), $modulo_selecionado]);
$sinais_modulo = $stmt->fetchAll();

// Agrupar sinais por categoria
$sinais_por_categoria = [];
foreach ($sinais_modulo as $sinal) {
    $categoria_id = $sinal['categoria_id'];
    if (!isset($sinais_por_categoria[$categoria_id])) {
        $sinais_por_categoria[$categoria_id] = [
            'categoria' => [
                'id' => $categoria_id,
                'nome' => $sinal['categoria_nome'],
                'icone' => $sinal['categoria_icone']
            ],
            'sinais' => []
        ];
    }
    $sinais_por_categoria[$categoria_id]['sinais'][] = $sinal;
}

// Agrupar sinais do módulo em lições (trail style)
$lesson_size = 4; // 4 sinais por lição (mude para 5 se preferir)
$sinais_flat = $sinais_modulo; // já contém status_usuario por sinal
$lessons = array_values(array_chunk($sinais_flat, $lesson_size));

$lesson_statuses = [];
foreach ($lessons as $i => $lesson) {
    $total = count($lesson);
    $concluidos = 0;
    foreach ($lesson as $s) {
        if (($s['status_usuario'] ?? '') === 'concluido') $concluidos++;
    }
    $lesson_statuses[$i] = [
        'total' => $total,
        'concluidos' => $concluidos,
        'completo' => ($total > 0 && $concluidos === $total)
    ];
}

// Flag: módulo completo quando todas as lições estiverem completas
$modulo_completo = !empty($lessons) && array_reduce($lesson_statuses, function($carry, $l){ return $carry && $l['completo']; }, true);

// Verificar se o módulo está liberado
$modulo_liberado = $modulo_selecionado <= $usuario['progresso_modulo'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trilha de Aprendizado - Handly</title>
    <link rel="stylesheet" href="assets/css/style.css?v=2.0">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <style>
        /* CSS Forçado para Navegação Inferior */
        .bottom-nav {
            position: fixed !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            background: white !important;
            border-top: 1px solid #e0e0e0 !important;
            display: flex !important;
            justify-content: space-around !important;
            padding: 0.5rem 0 !important;
            z-index: 1000 !important;
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.1) !important;
        }

        .bottom-nav-item {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            text-decoration: none !important;
            color: #666666 !important;
            transition: all 0.3s ease !important;
            padding: 0.5rem 0.25rem !important;
            min-width: 60px !important;
            border-radius: 12px !important;
        }

        .bottom-nav-item:hover {
            color: #22c55e !important;
            background-color: rgba(34, 197, 94, 0.1) !important;
            transform: translateY(-2px) !important;
        }

        .bottom-nav-item.active {
            color: #22c55e !important;
            background-color: rgba(34, 197, 94, 0.15) !important;
        }

        .bottom-nav-item svg {
            width: 22px !important;
            height: 22px !important;
            margin-bottom: 0.25rem !important;
            stroke-width: 2 !important;
            transition: all 0.3s ease !important;
        }

        .bottom-nav-item span {
            font-size: 0.7rem !important;
            font-weight: 500 !important;
            text-align: center !important;
            line-height: 1 !important;
            margin-top: 0.1rem !important;
        }
    </style>
</head>
<body>    <header class="header">
        <div class="nav-container">
            <a href="home.php" class="logo">Handly</a>
        </div>
    </header>

    <main class="duolingo-layout" style="padding-bottom: 100px;">
        <!-- Coluna principal (trilha) -->
        <div class="main-column">
            <!-- Barra do módulo atual -->
            <div style="margin-bottom:2rem;">
                <a href="trilha_modulos.php" class="module-current-bar" style="display:block; background:linear-gradient(135deg, #10B981, #059669); color:white; padding:1rem 1.5rem; border-radius:12px; text-decoration:none; box-shadow:var(--shadow); transition:transform 0.2s;">
                    <div style="display:flex; align-items:center; gap:1rem;">
                        <div style="font-size:1.5rem;">🎯</div>
                        <div>
                            <div style="font-weight:bold; font-size:1.1rem;">Módulo <?php echo $modulo_selecionado; ?></div>
                            <div style="opacity:0.9; font-size:0.9rem;">
                                <?php 
                                $modulo_atual = array_filter($modulos, fn($m) => $m['modulo'] == $modulo_selecionado);
                                $modulo_atual = reset($modulo_atual);
                                echo $modulo_atual['percentual'] ?? 0; 
                                ?>% • <?php echo $modulo_atual['sinais_concluidos'] ?? 0; ?>/<?php echo $modulo_atual['total_sinais'] ?? 0; ?> sinais
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <?php if ($modulo_liberado): ?>
                <!-- Trilha vertical de lições -->
                <div class="vertical-trail">
                    <div class="trail-path">
                        <?php foreach ($lessons as $idx => $lesson): 
                            $status = $lesson_statuses[$idx];
                            $cls = $status['completo'] ? 'lesson-complete' : ($status['concluidos'] > 0 ? 'lesson-progress' : 'lesson-locked');
                            $isFirst = $idx === 0;
                            $canStart = $idx === 0 || $lesson_statuses[$idx-1]['completo'];
                        ?>
                            <div class="trail-lesson <?php echo $cls; ?> <?php echo $canStart ? 'can-start' : ''; ?>" onclick="<?php echo $canStart ? "abrirLicao($idx)" : 'alert(\'Complete a lição anterior primeiro\')'; ?>">
                                <div class="lesson-connector" <?php echo $isFirst ? 'style="display:none;"' : ''; ?>></div>
                                <div class="lesson-circle">
                                    <div class="lesson-number"><?php echo $idx + 1; ?></div>
                                    <?php if ($status['completo']): ?>
                                        <div class="lesson-check">✓</div>
                                    <?php endif; ?>
                                </div>
                                <div class="lesson-info">
                                    <div class="lesson-title">Lição <?php echo $idx + 1; ?></div>
                                    <div class="lesson-progress"><?php echo $status['concluidos']; ?>/<?php echo $status['total']; ?> sinais</div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Quiz final -->
                        <div class="trail-lesson quiz-lesson <?php echo $modulo_completo ? 'quiz-ready' : 'quiz-locked'; ?>" onclick="<?php echo $modulo_completo ? "location.href='trilha_quiz.php?modulo={$modulo_selecionado}'" : 'alert(\'Complete todas as lições primeiro\')'; ?>">
                            <div class="lesson-connector"></div>
                            <div class="lesson-circle quiz-circle">
                                <div class="lesson-number">👑</div>
                            </div>
                            <div class="lesson-info">
                                <div class="lesson-title">Quiz Final</div>
                                <div class="lesson-progress">Teste seus conhecimentos</div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="locked-module">
                    <h3>🔒 Módulo Bloqueado</h3>
                    <p>Complete o módulo anterior para acessar este conteúdo.</p>
                    <a href="trilha.php?modulo=<?php echo $usuario['progresso_modulo']; ?>" class="btn btn-primary">Ir para Módulo Atual</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar direita -->
        <div class="sidebar">
            <!-- Stats do usuário -->
            <div class="sidebar-card">
                <div class="user-stats">
                    <div class="stat-item">
                        <div class="stat-icon">🔥</div>
                        <div class="stat-info">
                            <div class="stat-number"><?php echo $usuario['pontuacao_total'] ?? 0; ?></div>
                            <div class="stat-label">XP Total</div>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon">⚡</div>
                        <div class="stat-info">
                            <div class="stat-number"><?php echo $usuario['sequencia_dias'] ?? 0; ?></div>
                            <div class="stat-label">Dias seguidos</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Missão do dia -->
            <?php
            // Buscar uma missão ativa
            $stmt = $pdo->prepare("SELECT m.*, COALESCE(mu.progresso_atual, 0) as progresso_atual FROM missoes m LEFT JOIN missoes_usuario mu ON m.id = mu.missao_id AND mu.usuario_id = ? WHERE m.ativa = 1 AND (mu.concluida = 0 OR mu.concluida IS NULL) AND m.modulo_requerido <= ? ORDER BY m.recompensa_pontos DESC LIMIT 1");
            $stmt->execute([getUserId(), $usuario['progresso_modulo']]);
            $missao_ativa = $stmt->fetch();
            ?>
            
            <?php if ($missao_ativa): ?>
            <div class="sidebar-card">
                <div class="mission-header">
                    <h4>Missão do Dia</h4>
                    <a href="missoes.php" style="color:var(--primary-color); font-size:0.9rem;">Ver todas</a>
                </div>
                <div class="mission-content">
                    <div class="mission-icon">⚡</div>
                    <div class="mission-info">
                        <div class="mission-title"><?php echo htmlspecialchars($missao_ativa['titulo']); ?></div>
                        <div class="mission-description"><?php echo htmlspecialchars($missao_ativa['descricao']); ?></div>
                        <div class="mission-progress">
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill" style="width: <?php echo min(100, ($missao_ativa['progresso_atual'] / $missao_ativa['objetivo']) * 100); ?>%"></div>
                            </div>
                            <div class="progress-text"><?php echo $missao_ativa['progresso_atual']; ?>/<?php echo $missao_ativa['objetivo']; ?></div>
                        </div>
                        <div class="mission-reward">+<?php echo $missao_ativa['recompensa_pontos']; ?> XP</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Progresso geral -->
            <div class="sidebar-card">
                <h4>Seu Progresso</h4>
                <div class="progress-overview">
                    <?php foreach ($modulos as $mod): ?>
                    <div class="module-progress-item">
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <div class="module-mini-icon"><?php echo $mod['modulo'] == 1 ? '🌱' : ($mod['modulo'] == 2 ? '🌿' : '🌳'); ?></div>
                            <span>Módulo <?php echo $mod['modulo']; ?></span>
                        </div>
                        <div class="module-mini-progress">
                            <div style="width:<?php echo $mod['percentual']; ?>%"></div>
                        </div>
                        <span class="module-mini-percent"><?php echo $mod['percentual']; ?>%</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

        <?php if ($modulo_liberado): ?>
    <!-- Conteúdo do módulo selecionado -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    Módulo <?php echo $modulo_selecionado; ?> - 
                    <?php 
                    echo $modulo_selecionado == 1 ? 'Básico' : 
                         ($modulo_selecionado == 2 ? 'Intermediário' : 'Avançado'); 
                    ?>
                </h2>
                <p style="color: var(--medium-gray);">
                    <?php
                    switch ($modulo_selecionado) {
                        case 1:
                            echo "Aprenda os fundamentos da LIBRAS: alfabeto, números e sinais básicos.";
                            break;
                        case 2:
                            echo "Expanda seu vocabulário com palavras do dia a dia e estruturas mais complexas.";
                            break;
                        case 3:
                            echo "Domine sinais avançados e expressões para comunicação fluente.";
                            break;
                    }
                    ?>
                </p>
            </div>

            <?php if (!empty($sinais_por_categoria)): ?>
                <!-- Trilha estilo Duolingo: bolinhas (lições) e quiz -->
                <div class="card" style="padding-bottom:0.25rem;">
                    <div style="padding:0.75rem 1.25rem; background:#fff; border-radius:8px; margin-bottom:1rem;">
                        <h3 style="margin:0; color:var(--primary-color);">Trilha de Aprendizado</h3>
                        <p style="margin:0.25rem 0 0 0; color:var(--medium-gray);">Progresso do módulo em lições e quiz final</p>
                    </div>

                    <div style="padding:1rem;">
                        <div class="trail">
                            <div class="lessons-row">
                                <?php foreach ($lessons as $idx => $lesson): 
                                    $status = $lesson_statuses[$idx];
                                    $cls = $status['completo'] ? 'bubble-complete' : ($status['concluidos'] > 0 ? 'bubble-inprogress' : 'bubble-locked');
                                ?>
                                    <div class="lesson-bubble <?php echo $cls; ?>" data-lesson="<?php echo $idx; ?>" onclick="abrirLicao(<?php echo $idx; ?>)">
                                        <div class="bubble-index"><?php echo $idx + 1; ?></div>
                                        <?php if ($status['completo']): ?>
                                            <div class="bubble-check">✅</div>
                                        <?php else: ?>
                                            <small class="bubble-sub"><?php echo $status['concluidos']; ?>/<?php echo $status['total']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>

                                <!-- Quiz bubble -->
                                <div class="lesson-bubble quiz-bubble <?php echo $modulo_completo ? 'bubble-ready' : 'bubble-locked'; ?>" 
                                     onclick="<?php echo $modulo_completo ? "location.href='trilha_quiz.php?modulo={$modulo_selecionado}'" : 'alert(\'Complete todas as lições antes de fazer o quiz\')'; ?>">
                                    <div class="bubble-index">Q</div>
                                    <small class="bubble-sub">Quiz</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php foreach ($sinais_por_categoria as $categoria_data): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">                                <?php
                                $icones_categorias = [
                                    'alphabet.png' => 'https://cdn-icons-png.flaticon.com/512/5333/5333073.png',
                                    'numbers.png' => 'https://cdn-icons-png.flaticon.com/512/5090/5090276.png',
                                    'family.png' => 'https://cdn-icons-png.flaticon.com/512/3021/3021797.png',
                                    'colors.png' => 'https://cdn-icons-png.flaticon.com/512/3162/3162558.png',
                                    'animals.png' => 'https://cdn-icons-png.flaticon.com/512/8334/8334302.png',
                                    'food.png' => 'https://cdn-icons-png.flaticon.com/512/9267/9267535.png',
                                    'emotions.png' => 'https://cdn-icons-png.flaticon.com/512/4299/4299530.png',
                                    'greetings.png' => 'https://cdn-icons-png.flaticon.com/512/3790/3790110.png'
                                ];
                                if (isset($icones_categorias[$categoria_data['categoria']['icone']])) {
                                    echo '<img src="' . $icones_categorias[$categoria_data['categoria']['icone']] . '" 
                                          alt="' . htmlspecialchars($categoria_data['categoria']['nome']) . '" 
                                          class="inline-categoria-icon"
                                          style="width: 24px; height: 24px; object-fit: contain;">';
                                }
                                echo htmlspecialchars($categoria_data['categoria']['nome']);
                                ?>
                            </h3>
                            
                            <?php
                            $total_categoria = count($categoria_data['sinais']);
                            $concluidos_categoria = count(array_filter($categoria_data['sinais'], 
                                function($s) { return $s['status_usuario'] === 'concluido'; }));
                            $percentual_categoria = $total_categoria > 0 ? 
                                round(($concluidos_categoria / $total_categoria) * 100) : 0;
                            ?>
                            
                            <div style="margin-top: 0.5rem;">
                                <small style="color: var(--medium-gray);">
                                    <?php echo $concluidos_categoria; ?> / <?php echo $total_categoria; ?> sinais 
                                    (<?php echo $percentual_categoria; ?>%)
                                </small>
                                <div class="progress" style="margin-top: 0.5rem;">
                                    <div class="progress-bar" style="width: <?php echo $percentual_categoria; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-4">
                            <?php foreach ($categoria_data['sinais'] as $sinal): ?>
                                <div class="card sinal-card" 
                                     style="cursor: pointer; position: relative;"
                                     onclick="abrirModalSinal(<?php echo $sinal['id']; ?>)"
                                     data-sinal-id="<?php echo $sinal['id']; ?>">
                                    
                                    <!-- Status -->
                                    <div style="position: absolute; top: 0.5rem; right: 0.5rem; font-size: 1.2rem;">
                                        <?php if ($sinal['status_usuario'] === 'concluido'): ?>
                                            ✅
                                        <?php elseif ($sinal['status_usuario'] === 'iniciado'): ?>
                                            🔄
                                        <?php else: ?>
                                            ⭕
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="text-center">
                                        <h4 style="color: var(--primary-color); margin-bottom: 0.5rem;">
                                            <?php echo htmlspecialchars($sinal['palavra']); ?>
                                        </h4>
                                        
                                        <span class="badge badge-<?php 
                                            echo $sinal['dificuldade'] === 'facil' ? 'easy' : 
                                                 ($sinal['dificuldade'] === 'medio' ? 'medium' : 'hard'); 
                                        ?>" style="font-size: 0.75rem;">
                                            <?php echo ucfirst($sinal['dificuldade']); ?>
                                        </span>
                                        
                                        <?php if ($sinal['descricao']): ?>
                                            <p style="font-size: 0.8rem; color: var(--medium-gray); margin-top: 0.5rem;">
                                                <?php echo htmlspecialchars(substr($sinal['descricao'], 0, 50)); ?>
                                                <?php echo strlen($sinal['descricao']) > 50 ? '...' : ''; ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <button class="btn btn-primary btn-small" 
                                                style="margin-top: 0.5rem; font-size: 0.8rem;"
                                                onclick="event.stopPropagation(); abrirModalSinal(<?php echo $sinal['id']; ?>)">
                                            🎥 Aprender
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center" style="padding: 2rem;">
                    <h3 style="color: var(--medium-gray);">Nenhum sinal encontrado para este módulo</h3>
                    <p style="color: var(--medium-gray);">Os sinais serão adicionados em breve.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php else: ?>
        <!-- Módulo bloqueado -->
        <div class="card text-center">
            <h3 style="color: var(--medium-gray); margin-bottom: 1rem;">🔒 Módulo Bloqueado</h3>
            <p style="color: var(--medium-gray); margin-bottom: 2rem;">
                Complete o módulo anterior para desbloquear este conteúdo.
            </p>
            <a href="trilha.php?modulo=<?php echo $usuario['progresso_modulo']; ?>" class="btn btn-primary">
                Ir para Módulo Atual
            </a>
        </div>
        <?php endif; ?>
    </main>

    <!-- Modal para exibir vídeo do sinal -->
    <div id="modalSinal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="fecharModalSinal()">&times;</button>
            <div id="conteudoModalSinal">
                <!-- Conteúdo será carregado via JavaScript -->
            </div>
        </div>
    </div>

    <!-- Modal de Lição (trail) -->
    <div id="licaoModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="fecharLicaoModal()">&times;</button>
            <div id="licaoConteudo">Carregando...</div>
        </div>
    </div>

    <footer class="footer">
        <div class="container text-center">
            <p>&copy; 2025 Handly - Siga sua trilha e evolua constantemente!</p>
        </div>
    </footer>

    <script>
        // Reutilizar funções do dicionário
        function abrirModalSinal(sinalId) {
            const modal = document.getElementById('modalSinal');
            const conteudo = document.getElementById('conteudoModalSinal');
            
            conteudo.innerHTML = '<div class="text-center"><p>Carregando...</p></div>';
            modal.style.display = 'block';
            
            fetch(`api/get_sinal.php?id=${sinalId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        conteudo.innerHTML = `
                            <div class="text-center">
                                <h2 style="color: var(--primary-color); margin-bottom: 1rem;">
                                    ${data.sinal.palavra}
                                </h2>
                                
                                <div style="margin-bottom: 1rem;">
                                    <span class="badge badge-${data.sinal.dificuldade === 'facil' ? 'easy' : 
                                                                 data.sinal.dificuldade === 'medio' ? 'medium' : 'hard'}">
                                        ${data.sinal.dificuldade.charAt(0).toUpperCase() + data.sinal.dificuldade.slice(1)}
                                    </span>
                                    <span class="badge badge-primary" style="margin-left: 0.5rem;">
                                        ${data.sinal.categoria_nome}
                                    </span>
                                </div>
                                
                                ${data.sinal.descricao ? `<p style="margin-bottom: 1.5rem;">${data.sinal.descricao}</p>` : ''}
                                
                                <div class="video-container" style="margin-bottom: 1.5rem;">
                                    ${data.sinal.video_url ? 
                                        `<video controls autoplay style="width: 100%; max-width: 400px;">
                                            <source src="${data.sinal.video_url}" type="video/mp4">
                                            Seu navegador não suporta vídeos HTML5.
                                        </video>` :                                        `<div style="padding: 2rem; background-color: var(--light-gray); border-radius: var(--border-radius);">
                                            <p>Vídeo em breve...</p>
                                            <p style="font-size: 2rem; margin: 1rem 0;">LIBRAS</p>
                                            <p style="color: var(--medium-gray);">Imagine o sinal para "${data.sinal.palavra}"</p>
                                        </div>`
                                    }
                                </div>
                                
                                <div>
                                    <button class="btn btn-primary" onclick="marcarComoAprendido(${sinalId})"
                                            ${data.status_usuario === 'concluido' ? 'disabled' : ''}>
                                        ${data.status_usuario === 'concluido' ? '✅ Já Aprendido' : '📚 Marcar como Aprendido'}
                                    </button>
                                </div>
                            </div>
                        `;
                    } else {
                        conteudo.innerHTML = '<div class="text-center"><p style="color: var(--danger-color);">Erro ao carregar sinal.</p></div>';
                    }
                })
                .catch(error => {
                    conteudo.innerHTML = '<div class="text-center"><p style="color: var(--danger-color);">Erro de conexão.</p></div>';
                });
        }
        
        function fecharModalSinal() {
            document.getElementById('modalSinal').style.display = 'none';
        }
        
        function marcarComoAprendido(sinalId) {
            fetch('api/marcar_sinal_aprendido.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({sinal_id: sinalId})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Atualizar interface
                    const card = document.querySelector(`[data-sinal-id="${sinalId}"]`);
                    if (card) {
                        const statusIcon = card.querySelector('div[style*="position: absolute"]');
                        if (statusIcon) {
                            statusIcon.textContent = '✅';
                        }
                    }
                    
                    // Atualizar botão no modal
                    const botao = event.target;
                    botao.textContent = '✅ Já Aprendido';
                    botao.disabled = true;
                    
                    // Mostrar mensagem de sucesso
                    alert(`Parabéns! Você ganhou ${data.pontos_ganhos} pontos!`);
                    
                    // Recarregar página após 2 segundos para atualizar progresso
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                }
            })
            .catch(error => {
                alert('Erro ao marcar sinal. Tente novamente.');
            });
        }
        
        // Fechar modal ao clicar fora
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('modalSinal');
            if (event.target === modal) {
                fecharModalSinal();
            }
        });
        
        // Animação dos cards de progresso
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-bar');
            
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                
                setTimeout(() => {
                    bar.style.transition = 'width 1s ease-out';
                    bar.style.width = width;
                }, 100);
            });        });
    </script>

    <script>
        // Dados das lições para uso no cliente
        const TRILHA_LESSONS = <?php echo json_encode(array_map(function($l){ return array_map(function($s){ return [
            'id'=>$s['id'],'palavra'=>$s['palavra'],'video_url'=>$s['video_url'],'status'=>$s['status_usuario']
        ];}, $l);}, $lessons)); ?>;

        function abrirLicao(idx) {
            const lesson = TRILHA_LESSONS[idx];
            const container = document.getElementById('licaoConteudo');
            let html = `<h3>Lição ${idx + 1}</h3><p>Aprenda estes sinais (${lesson.length})</p><div class="grid grid-3">`;
            lesson.forEach(s => {
                html += `<div class="card text-center" style="padding:0.8rem;">
                            <div style="font-size:2rem; margin-bottom:0.6rem;">🎬</div>
                            <div style="font-weight:700;">${s.palavra}</div>
                            <div style="margin-top:0.6rem;">
                                ${s.video_url ? `<button class="btn btn-primary btn-small" onclick="reproduzirVideo('${s.video_url}')">Ver vídeo</button>` : ''}
                                ${s.status !== 'concluido' ? `<button class="btn btn-secondary btn-small" onclick="marcarAprendido(${s.id}, this)">Marcar Aprendido</button>` : `<span style="color:var(--success-color);font-weight:700;">Aprendido</span>`}
                            </div>
                         </div>`;
            });
            html += `</div>`;
            container.innerHTML = html;
            document.getElementById('licaoModal').style.display = 'block';
        }

        function fecharLicaoModal(){ document.getElementById('licaoModal').style.display='none'; }

        function reproduzirVideo(url){
            // Se já existir função global, chama; senão usa modal simples
            try {
                abrirModalVideo && abrirModalVideo(url);
            } catch (e) {
                alert('Reproduzir vídeo: ' + url);
            }
        }

        function marcarAprendido(sinalId, btn) {
            btn.disabled = true;
            fetch('api/marcar_sinal_aprendido.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({sinal_id: sinalId})
            }).then(r=>r.json()).then(data=>{
                if(data.success){
                    btn.outerHTML = '<span style="color:var(--success-color);font-weight:700;">Aprendido</span>';
                    setTimeout(()=>location.reload(),800);
                } else {
                    alert('Erro ao marcar');
                    btn.disabled = false;
                }
            }).catch(()=>{ alert('Erro de conexão'); btn.disabled=false; });
        }
    </script>

    <style>
    /* Trilhas - bolinhas e quiz */
    .trail { padding: 1rem 0; }
    .lessons-row { display:flex; gap:1rem; align-items:center; overflow:auto; padding:1rem 0; }
    .lesson-bubble { width:80px; height:80px; border-radius:50%; background:#fff; display:flex; flex-direction:column; align-items:center; justify-content:center; cursor:pointer; box-shadow:var(--shadow); transition:transform .15s; }
    .lesson-bubble:hover{ transform:translateY(-6px); }
    .bubble-complete{ background:linear-gradient(90deg,#10B981,#059669); color:#fff; }
    .bubble-inprogress{ background:#fef3c7; color:#92400e; }
    .bubble-locked{ background:#f3f4f6; color:#9ca3af; }
    .quiz-bubble{ width:100px; height:100px; border-radius:12px; }
    .quiz-bubble.bubble-ready{ background:linear-gradient(90deg,#8b5cf6,#a855f7); color:#fff; }
    .bubble-index{ font-weight:800; font-size:1.25rem; }
    .bubble-sub{ font-size:0.8rem; color:rgba(0,0,0,0.6); }
    </style>

    <!-- Navegação Inferior -->
    <nav class="bottom-nav">
        <a href="home.php" class="bottom-nav-item">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <path d="M3 9L12 2L21 9V20C21 20.5304 20.7893 21.0391 20.4142 21.4142C20.0391 21.7893 19.5304 22 19 22H5C4.46957 22 3.96086 21.7893 3.58579 21.4142C3.21071 21.0391 3 20.5304 3 20V9Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M9 22V12H15V22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Início</span>
        </a>
        <a href="dicionario.php" class="bottom-nav-item">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <path d="M4 19.5C4 18.837 4.26339 18.2011 4.73223 17.7322C5.20107 17.2634 5.83696 17 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M6.5 2H20V22H6.5C5.83696 22 5.20107 21.7366 4.73223 21.2678C4.26339 20.7989 4 20.163 4 19.5V4.5C4 3.83696 4.26339 3.20107 4.73223 2.73223C5.20107 2.26339 5.83696 2 6.5 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Aprender</span>
        </a>
        <a href="missoes.php" class="bottom-nav-item">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                <path d="M9 12L11 14L15 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Missões</span>
        </a>
        <a href="trilha.php" class="bottom-nav-item active">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <path d="M3 3V9M21 3V9M3 9H21M5 21H19C20.1046 21 21 20.1046 21 19V7C21 5.89543 20.1046 5 19 5H5C3.89543 5 3 5.89543 3 7V19C3 20.1046 3.89543 21 5 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 17L18 15L16 13M8 13L6 15L8 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Progresso</span>
        </a>
        <a href="perfil.php" class="bottom-nav-item">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                <path d="M19.4 15C19.2669 15.3016 19.2272 15.6362 19.286 15.9606C19.3448 16.285 19.4995 16.5843 19.73 16.82L19.79 16.88C19.976 17.0657 20.1235 17.2863 20.2241 17.5291C20.3248 17.7719 20.3766 18.0322 20.3766 18.295C20.3766 18.5578 20.3248 18.8181 20.2241 19.0609C20.1235 19.3037 19.976 19.5243 19.79 19.71C19.6043 19.896 19.3837 20.0435 19.1409 20.1441C18.8981 20.2448 18.6378 20.2966 18.375 20.2966C18.1122 20.2966 17.8519 20.2448 17.6091 20.1441C17.3663 20.0435 17.1457 19.896 16.96 19.71L16.9 19.65C16.6643 19.4195 16.365 19.2648 16.0406 19.206C15.7162 19.1472 15.3816 19.1869 15.08 19.32C14.7842 19.4468 14.532 19.6572 14.3543 19.9255C14.1766 20.1938 14.0813 20.5082 14.08 20.83V21C14.08 21.5304 13.8693 22.0391 13.4942 22.4142C13.1191 22.7893 12.6104 23 12.08 23C11.5496 23 11.0409 22.7893 10.6658 22.4142C10.2907 22.0391 10.08 21.5304 10.08 21V20.91C10.0723 20.579 9.96512 20.2573 9.77251 19.9887C9.5799 19.7201 9.31074 19.5176 9 19.41C8.69838 19.2769 8.36381 19.2372 8.03941 19.296C7.71502 19.3548 7.41568 19.5095 7.18 19.74L7.12 19.8C6.93425 19.986 6.71368 20.1335 6.47088 20.2341C6.22808 20.3348 5.96783 20.3866 5.705 20.3866C5.44217 20.3866 5.18192 20.3348 4.93912 20.2341C4.69632 20.1335 4.47575 19.986 4.29 19.8C4.10405 19.6143 3.95653 19.3937 3.85588 19.1509C3.75523 18.9081 3.70343 18.6478 3.70343 18.385C3.70343 18.1222 3.75523 17.8619 3.85588 17.6191C3.95653 17.3763 4.10405 17.1557 4.29 16.97L4.35 16.91C4.58054 16.6743 4.73519 16.375 4.794 16.0506C4.85282 15.7262 4.81312 15.3916 4.68 15.09C4.55324 14.7942 4.34276 14.542 4.07447 14.3643C3.80618 14.1866 3.49179 14.0913 3.17 14.09H3C2.46957 14.09 1.96086 13.8793 1.58579 13.5042C1.21071 13.1291 1 12.6204 1 12.09C1 11.5596 1.21071 11.0509 1.58579 10.6758C1.96086 10.3007 2.46957 10.09 3 10.09H3.09C3.42099 10.0823 3.742 9.97512 4.01062 9.78251C4.27925 9.5899 4.48167 9.32074 4.59 9.01C4.72312 8.70838 4.76282 8.37381 4.704 8.04941C4.64519 7.72502 4.49054 7.42568 4.26 7.19L4.2 7.13C4.01405 6.94425 3.86653 6.72368 3.76588 6.48088C3.66523 6.23808 3.61343 5.97783 3.61343 5.715C3.61343 5.45217 3.66523 5.19192 3.76588 4.94912C3.86653 4.70632 4.01405 4.48575 4.2 4.3C4.38575 4.11405 4.60632 3.96653 4.84912 3.86588C5.09192 3.76523 5.35217 3.71343 5.615 3.71343C5.87783 3.71343 6.13808 3.76523 6.38088 3.86588C6.62368 3.96653 6.84425 4.11405 7.03 4.3L7.09 4.36C7.32568 4.59054 7.62502 4.74519 7.94941 4.804C8.27381 4.86282 8.60838 4.82312 8.91 4.69H9C9.29577 4.56324 9.54802 4.35276 9.72569 4.08447C9.90337 3.81618 9.99872 3.50179 10 3.18V3C10 2.46957 10.2107 1.96086 10.5858 1.58579C10.9609 1.21071 11.4696 1 12 1C12.5304 1 13.0391 1.21071 13.4142 1.58579C13.7893 1.96086 14 2.46957 14 3V3.09C14.0013 3.41179 14.0966 3.72618 14.2743 3.99447C14.452 4.26276 14.7042 4.47324 15 4.6C15.3016 4.73312 15.6362 4.77282 15.9606 4.714C16.285 4.65519 16.5843 4.50054 16.82 4.27L16.88 4.21C17.0657 4.02405 17.2863 3.87653 17.5291 3.77588C17.7719 3.67523 18.0322 3.62343 18.295 3.62343C18.5578 3.62343 18.8181 3.67523 19.0609 3.77588C19.3037 3.87653 19.5243 4.02405 19.71 4.21C19.896 4.39575 20.0435 4.61632 20.1441 4.85912C20.2448 5.10192 20.2966 5.36217 20.2966 5.625C20.2966 5.88783 20.2448 6.14808 20.1441 6.39088C20.0435 6.63368 19.896 6.85425 19.71 7.04L19.65 7.1C19.4195 7.33568 19.2648 7.63502 19.206 7.95941C19.1472 8.28381 19.1869 8.61838 19.32 8.92V9C19.4468 9.29577 19.6572 9.54802 19.9255 9.72569C20.1938 9.90337 20.5082 9.99872 20.83 10H21C21.5304 10 22.0391 10.2107 22.4142 10.5858C22.7893 10.9609 23 11.4696 23 12C23 12.5304 22.7893 13.0391 22.4142 13.4142C22.0391 13.7893 21.5304 14 21 14H20.91C20.5882 14.0013 20.2738 14.0966 20.0055 14.2743C19.7372 14.452 19.5268 14.7042 19.4 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Perfil</span>
        </a>
    </nav>
</body>
</html>
