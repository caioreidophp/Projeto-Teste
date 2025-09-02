<?php
require_once 'config/config.php';
requireLogin();

$categoria_selecionada = $_GET['categoria'] ?? '';
$busca = $_GET['busca'] ?? '';
$dificuldade = $_GET['dificuldade'] ?? '';
$sinal_selecionado = $_GET['sinal'] ?? '';

// Buscar categorias
$stmt = $pdo->prepare("SELECT * FROM categorias ORDER BY nome");
$stmt->execute();
$categorias = $stmt->fetchAll();

// Se uma categoria foi selecionada, buscar sinais dessa categoria
$sinais = [];
$categoria_atual = null;
$sinal_atual = null;

if ($categoria_selecionada) {
    // Buscar dados da categoria
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ?");
    $stmt->execute([$categoria_selecionada]);
    $categoria_atual = $stmt->fetch();
    
    // Buscar sinais da categoria
    $where_conditions = ["s.categoria_id = ?", "s.modulo <= ?"];
    $params = [$categoria_selecionada, $_SESSION['user_modulo'] ?? 3];
    
    if ($busca) {
        $where_conditions[] = "s.palavra LIKE ?";
        $params[] = "%$busca%";
    }
    
    if ($dificuldade) {
        $where_conditions[] = "s.dificuldade = ?";
        $params[] = $dificuldade;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $stmt = $pdo->prepare("
        SELECT s.*, c.nome as categoria_nome,
               CASE 
                   WHEN pu.concluido = 1 THEN 'concluido'
                   WHEN pu.id IS NOT NULL THEN 'iniciado'
                   ELSE 'nao_iniciado'
               END as status_usuario
        FROM sinais s
        JOIN categorias c ON s.categoria_id = c.id
        LEFT JOIN progresso_usuario pu ON s.id = pu.sinal_id AND pu.usuario_id = ?
        WHERE {$where_clause}
        ORDER BY s.palavra
    ");
    $stmt->execute([getUserId(), ...$params]);
    $sinais = $stmt->fetchAll();
    
    // Se um sinal específico foi selecionado
    if ($sinal_selecionado) {
        $stmt = $pdo->prepare("
            SELECT s.*, c.nome as categoria_nome,
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
        $stmt->execute([getUserId(), $sinal_selecionado]);
        $sinal_atual = $stmt->fetch();
    } else if (!empty($sinais)) {
        // Se nenhum sinal foi selecionado, seleciona o primeiro
        $sinal_atual = $sinais[0];
        $sinal_selecionado = $sinal_atual['id'];
    }
}

// Se nenhuma categoria selecionada, mostrar todas as categorias
if (!$categoria_selecionada) {
    // Buscar estatísticas gerais
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_sinais,
               COUNT(pu.sinal_id) as sinais_concluidos
        FROM sinais s
        LEFT JOIN progresso_usuario pu ON s.id = pu.sinal_id AND pu.usuario_id = ? AND pu.concluido = 1
        WHERE s.modulo <= ?
    ");
    $stmt->execute([getUserId(), $_SESSION['user_modulo'] ?? 3]);
    $estatisticas = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dicionário - Handly</title>
    <link rel="stylesheet" href="assets/css/style.css?v=2.1">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <style>
        /* Layout principal do dicionário */
        .dictionary-layout {
            display: flex;
            height: calc(100vh - 120px);
            gap: 2rem;
            margin-bottom: 100px;
        }
        
        /* Área principal do sinal */
        .main-content {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .sinal-display {
            text-align: center;
            color: white;
            z-index: 2;
        }
        
        .sinal-avatar {
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            margin: 0 auto 2rem;
            border: 3px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }
        
        .sinal-palavra {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .sinal-categoria {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }
        
        .sinal-badges {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .sinal-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            backdrop-filter: blur(10px);
        }
        
        /* Barra lateral direita */
        .sidebar {
            width: 350px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .sidebar-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        
        .sidebar-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .search-section {
            padding: 1.5rem;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .search-input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        /* Lista de sinais */
        .sinais-list {
            flex: 1;
            overflow-y: auto;
            padding: 0;
        }
        
        .sinal-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #F3F4F6;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }
        
        .sinal-item:hover {
            background: #F8FAFF;
            transform: translateX(5px);
        }
        
        .sinal-item.active {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-left: 4px solid #667eea;
        }
        
        .sinal-status {
            font-size: 1.5rem;
            width: 30px;
            text-align: center;
        }
        
        .sinal-info {
            flex: 1;
        }
        
        .sinal-nome {
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 0.2rem;
        }
        
        .sinal-dificuldade {
            font-size: 0.8rem;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            color: white;
        }
        
        .dificuldade-facil { background: #10B981; }
        .dificuldade-medio { background: #F59E0B; }
        .dificuldade-dificil { background: #EF4444; }
        
        /* Estado vazio */
        .empty-state {
            text-align: center;
            color: #6B7280;
        }
        
        .empty-avatar {
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 2rem;
            border: 2px dashed rgba(255, 255, 255, 0.5);
        }
        
        /* Botões de ação */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn-modern {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .btn-modern:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .btn-modern.primary {
            background: white;
            color: #667eea;
            border-color: white;
        }
        
        .btn-modern.primary:hover {
            background: #f8fafc;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .dictionary-layout {
                flex-direction: column;
                height: auto;
            }
            
            .sidebar {
                width: 100%;
                order: -1;
                max-height: 300px;
            }
            
            .main-content {
                min-height: 400px;
            }
        }
    </style>
</head>
<body>    <header class="header">
        <div class="nav-container">
            <a href="home.php" class="logo">Handly</a>
        </div>
    </header>

    <main class="container">
        <?php if (!$categoria_selecionada): ?>
        <!-- Grid de categorias quando nenhuma está selecionada -->
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">🔍 Dicionário de LIBRAS</h1>
                <p style="color: var(--medium-gray);">
                    Selecione uma categoria para começar a explorar os sinais
                </p>
            </div>
            
            <!-- Estatísticas -->
            <?php if (isset($estatisticas)): ?>
            <div class="grid grid-3" style="margin-bottom: 2rem;">
                <div class="text-center">
                    <div class="stat-number"><?php echo $estatisticas['total_sinais']; ?></div>
                    <div class="stat-label">Total de Sinais</div>
                </div>
                <div class="text-center">
                    <div class="stat-number"><?php echo $estatisticas['sinais_concluidos']; ?></div>
                    <div class="stat-label">Sinais Aprendidos</div>
                </div>
                <div class="text-center">
                    <div class="stat-number"><?php echo count($categorias); ?></div>
                    <div class="stat-label">Categorias</div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="grid grid-4">
                <?php foreach ($categorias as $categoria): ?>
                    <?php
                    // Contar sinais da categoria
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as total,
                               COUNT(pu.sinal_id) as concluidos
                        FROM sinais s
                        LEFT JOIN progresso_usuario pu ON s.id = pu.sinal_id AND pu.usuario_id = ? AND pu.concluido = 1
                        WHERE s.categoria_id = ? AND s.modulo <= ?
                    ");
                    $stmt->execute([getUserId(), $categoria['id'], $_SESSION['user_modulo'] ?? 3]);
                    $stats_categoria = $stmt->fetch();
                    
                    $percentual = $stats_categoria['total'] > 0 ? 
                        round(($stats_categoria['concluidos'] / $stats_categoria['total']) * 100) : 0;
                    ?>
                    <a href="dicionario.php?categoria=<?php echo $categoria['id']; ?>" 
                       class="card text-center" 
                       style="text-decoration: none; color: inherit; transition: transform 0.3s ease;"
                       onmouseover="this.style.transform='translateY(-5px)'"
                       onmouseout="this.style.transform='translateY(0)'">
                        <div class="category-icon" style="margin-bottom: 1rem;">
                            <?php
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
                            if (isset($icones_categorias[$categoria['icone']])) {
                                echo '<img src="' . $icones_categorias[$categoria['icone']] . '" 
                                      alt="' . htmlspecialchars($categoria['nome']) . '" 
                                      style="width: 60px; height: 60px; object-fit: contain;">';
                            } else {
                                echo '📚';
                            }
                            ?>
                        </div>
                        <h3 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($categoria['nome']); ?></h3>
                        <p style="margin: 0.5rem 0; color: var(--medium-gray); font-size: 0.9rem;">
                            <?php echo $stats_categoria['concluidos']; ?> / <?php echo $stats_categoria['total']; ?> sinais
                        </p>
                        <div class="progress" style="margin: 1rem 0;">
                            <div class="progress-bar" style="width: <?php echo $percentual; ?>%"></div>
                        </div>
                        <small style="color: var(--medium-gray); font-weight: 600;"><?php echo $percentual; ?>% completo</small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Layout do dicionário com categoria selecionada -->
        <div style="display: flex; align-items: center; margin-bottom: 1rem;">
            <a href="dicionario.php" style="color: #667eea; text-decoration: none; font-size: 1rem;">
                ← Voltar às categorias
            </a>
        </div>
        
        <div class="dictionary-layout">
            <!-- Área principal do sinal -->
            <div class="main-content">
                <?php if ($sinal_atual): ?>
                <div class="sinal-display">
                    <div class="sinal-avatar">
                        👤
                    </div>
                    <div class="sinal-palavra"><?php echo htmlspecialchars($sinal_atual['palavra']); ?></div>
                    <div class="sinal-categoria"><?php echo htmlspecialchars($sinal_atual['categoria_nome']); ?></div>
                    
                    <div class="sinal-badges">
                        <span class="sinal-badge">
                            <?php 
                            $dificuldades = ['facil' => 'Fácil', 'medio' => 'Médio', 'dificil' => 'Difícil'];
                            echo $dificuldades[$sinal_atual['dificuldade']] ?? 'N/A';
                            ?>
                        </span>
                        <span class="sinal-badge">Módulo <?php echo $sinal_atual['modulo']; ?></span>
                    </div>
                    
                    <?php if ($sinal_atual['descricao']): ?>
                    <p style="opacity: 0.9; margin-bottom: 2rem; max-width: 400px; margin-left: auto; margin-right: auto;">
                        <?php echo htmlspecialchars($sinal_atual['descricao']); ?>
                    </p>
                    <?php endif; ?>
                    
                    <div class="action-buttons">
                        <?php if ($sinal_atual['video_url']): ?>
                        <a href="#" class="btn-modern primary" onclick="reproduzirVideo('<?php echo $sinal_atual['video_url']; ?>')">
                            🎥 Ver Vídeo
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($sinal_atual['status_usuario'] !== 'concluido'): ?>
                        <button class="btn-modern" onclick="marcarAprendido(<?php echo $sinal_atual['id']; ?>)">
                            ✅ Marcar como Aprendido
                        </button>
                        <?php else: ?>
                        <span class="btn-modern" style="background: rgba(16, 185, 129, 0.3); border-color: rgba(16, 185, 129, 0.5);">
                            ✅ Aprendido
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-avatar">🤷‍♀️</div>
                    <h3>Nenhum sinal encontrado</h3>
                    <p>Tente ajustar sua pesquisa ou explore outras categorias</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Barra lateral direita -->
            <div class="sidebar">
                <div class="sidebar-header">
                    <h2 style="margin: 0; font-size: 1.3rem;"><?php echo htmlspecialchars($categoria_atual['nome']); ?></h2>
                    <p style="margin: 0.5rem 0 0 0; opacity: 0.9; font-size: 0.9rem;">
                        <?php echo count($sinais); ?> sinais disponíveis
                    </p>
                </div>
                
                <div class="sidebar-content">
                    <div class="search-section">
                        <form method="GET" action="dicionario.php">
                            <input type="hidden" name="categoria" value="<?php echo $categoria_selecionada; ?>">
                            <input 
                                type="text" 
                                name="busca" 
                                class="search-input"
                                placeholder="🔍 Buscar sinais..."
                                value="<?php echo htmlspecialchars($busca); ?>"
                            >
                        </form>
                    </div>
                    
                    <div class="sinais-list">
                        <?php if (!empty($sinais)): ?>
                            <?php foreach ($sinais as $sinal): ?>
                                <a href="dicionario.php?categoria=<?php echo $categoria_selecionada; ?>&sinal=<?php echo $sinal['id']; ?><?php echo $busca ? '&busca=' . urlencode($busca) : ''; ?>" 
                                   class="sinal-item <?php echo $sinal['id'] == $sinal_selecionado ? 'active' : ''; ?>">
                                    <div class="sinal-status">
                                        <?php if ($sinal['status_usuario'] === 'concluido'): ?>
                                            ✅
                                        <?php elseif ($sinal['status_usuario'] === 'iniciado'): ?>
                                            🔄
                                        <?php else: ?>
                                            ⭕
                                        <?php endif; ?>
                                    </div>
                                    <div class="sinal-info">
                                        <div class="sinal-nome"><?php echo htmlspecialchars($sinal['palavra']); ?></div>
                                        <span class="sinal-dificuldade dificuldade-<?php echo $sinal['dificuldade']; ?>">
                                            <?php 
                                            $dificuldades = ['facil' => 'Fácil', 'medio' => 'Médio', 'dificil' => 'Difícil'];
                                            echo $dificuldades[$sinal['dificuldade']] ?? 'N/A';
                                            ?>
                                        </span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="padding: 2rem; text-align: center; color: #6B7280;">
                                <p>Nenhum sinal encontrado</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- Modal de vídeo -->
    <div id="videoModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; padding: 2rem; border-radius: 20px; max-width: 600px; width: 90%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="margin: 0;">Vídeo do Sinal</h3>
                <button onclick="fecharVideoModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <video id="videoPlayer" controls style="width: 100%; border-radius: 12px;">
                Seu navegador não suporta vídeos HTML5.
            </video>
        </div>
    </div>

    <!-- Navegação Inferior -->
    <nav class="bottom-nav">
        <a href="home.php" class="bottom-nav-item">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <path d="M3 9L12 2L21 9V20C21 20.5304 20.7893 21.0391 20.4142 21.4142C20.0391 21.7893 19.5304 22 19 22H5C4.46957 22 3.96086 21.7893 3.58579 21.4142C3.21071 21.0391 3 20.5304 3 20V9Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M9 22V12H15V22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Início</span>
        </a>
        <a href="dicionario.php" class="bottom-nav-item active">
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
        <a href="trilha.php" class="bottom-nav-item">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <path d="M3 3V9M21 3V9M3 9H21M5 21H19C20.1046 21 21 20.1046 21 19V7C21 5.89543 20.1046 5 19 5H5C3.89543 5 3 5.89543 3 7V19C3 20.1046 3.89543 21 5 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 17L18 15L16 13M8 13L6 15L8 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Progresso</span>
        </a>
        <a href="perfil.php" class="bottom-nav-item">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                <path d="M19.4 15C19.2669 15.3016 19.2272 15.6362 19.286 15.9606C19.3448 16.285 19.4995 16.5843 19.73 16.82L19.79 16.88C19.976 17.0657 20.1235 17.2863 20.2241 17.5291C20.3248 17.7719 20.3766 18.0322 20.3766 18.295C20.3766 18.5578 20.3248 18.8181 20.2241 19.0609C20.1235 19.3037 19.976 19.5243 19.79 19.71C19.6043 19.896 19.3837 20.0435 19.1409 20.1441C18.8981 20.2448 18.6378 20.2966 18.375 20.2966C18.1122 20.2966 17.8519 20.2448 17.6091 20.1441C17.3663 20.0435 17.1457 19.896 16.96 19.71L16.9 19.65C16.6657 19.4195 16.3663 19.2648 16.0419 19.206C15.7175 19.1472 15.3829 19.1869 15.08 19.32C14.7856 19.4468 14.532 19.6572 14.3488 19.9255C14.1655 20.1938 14.0606 20.5082 14.045 20.835V21C14.045 21.5304 13.8343 22.0391 13.4592 22.4142C13.0841 22.7893 12.5754 23 12.045 23C11.5146 23 11.0059 22.7893 10.6308 22.4142C10.2557 22.0391 10.045 21.5304 10.045 21V20.91C10.0223 20.5679 9.90827 20.2384 9.71838 19.9614C9.52849 19.6844 9.27042 19.4719 8.97 19.345C8.66712 19.2119 8.33253 19.1722 8.00812 19.231C7.68371 19.2898 7.38431 19.4445 7.15 19.675L7.09 19.735C6.90427 19.9207 6.68368 20.0682 6.44088 20.1689C6.19808 20.2696 5.93783 20.3214 5.675 20.3214C5.41217 20.3214 5.15192 20.2696 4.90912 20.1689C4.66632 20.0682 4.44573 19.9207 4.26 19.735C4.07427 19.5493 3.92677 19.3287 3.82606 19.0859C3.72535 18.8431 3.67354 18.5828 3.67354 18.32C3.67354 18.0572 3.72535 17.7969 3.82606 17.5541C3.92677 17.3113 4.07427 17.0907 4.26 16.905L4.32 16.845C4.55052 16.6107 4.70521 16.3113 4.764 15.9869C4.82279 15.6625 4.78307 15.3279 4.65 15.025C4.52321 14.7306 4.31279 14.477 4.04447 14.2938C3.77615 14.1105 3.46179 14.0056 3.135 13.99H3C2.46957 13.99 1.96086 13.7793 1.58579 13.4042C1.21071 13.0291 1 12.5204 1 11.99C1 11.4596 1.21071 10.9509 1.58579 10.5758C1.96086 10.3007 2.46957 9.99 3 9.99H3.09C3.43212 9.96731 3.76159 9.85327 4.03859 9.66338C4.31559 9.47349 4.52807 9.21542 4.655 8.915C4.78807 8.61212 4.82779 8.27753 4.769 7.95312C4.71021 7.62871 4.55552 7.32931 4.325 7.095L4.265 7.035C4.07927 6.84927 3.93177 6.62868 3.83106 6.38588C3.73035 6.14308 3.67854 5.88283 3.67854 5.62C3.67854 5.35717 3.73035 5.09692 3.83106 4.85412C3.93177 4.61132 4.07927 4.39073 4.265 4.205C4.45073 4.01927 4.67132 3.87177 4.91412 3.77106C5.15692 3.67035 5.41717 3.61854 5.68 3.61854C5.94283 3.61854 6.20308 3.67035 6.44588 3.77106C6.68868 3.87177 6.90927 4.01927 7.095 4.205L7.155 4.265C7.38931 4.49552 7.68871 4.65021 8.01312 4.709C8.33753 4.76779 8.67212 4.72807 8.975 4.595H9C9.29444 4.46821 9.54804 4.25779 9.73128 3.98947C9.91452 3.72115 10.0194 3.40679 10.035 3.08V3C10.035 2.46957 10.2457 1.96086 10.6208 1.58579C10.9959 1.21071 11.5046 1 12.035 1C12.5654 1 13.0741 1.21071 13.4492 1.58579C13.8243 1.96086 14.035 2.46957 14.035 3V3.09C14.0506 3.41679 14.1555 3.73115 14.3387 3.99947C14.522 4.26779 14.7756 4.47821 15.07 4.605C15.3729 4.73807 15.7075 4.77779 16.0319 4.719C16.3563 4.66021 16.6557 4.50552 16.89 4.275L16.95 4.215C17.1357 4.02927 17.3563 3.88177 17.5991 3.78106C17.8419 3.68035 18.1022 3.62854 18.365 3.62854C18.6278 3.62854 18.8881 3.68035 19.1309 3.78106C19.3737 3.88177 19.5943 4.02927 19.78 4.215C19.9657 4.40073 20.1132 4.62132 20.2139 4.86412C20.3146 5.10692 20.3665 5.36717 20.3665 5.63C20.3665 5.89283 20.3146 6.15308 20.2139 6.39588C20.1132 6.63868 19.9657 6.85927 19.78 7.045L19.72 7.105C19.4895 7.33931 19.3348 7.63871 19.276 7.96312C19.2172 8.28753 19.2569 8.62212 19.39 8.925V8.95C19.5168 9.24444 19.7272 9.49804 19.9955 9.68128C20.2639 9.86452 20.5782 9.96942 20.905 9.985H21C21.5304 9.985 22.0391 10.1957 22.4142 10.5708C22.7893 10.9459 23 11.4546 23 11.985C23 12.5154 22.7893 13.0241 22.4142 13.3992C22.0391 13.7743 21.5304 13.985 21 13.985H20.91C20.5832 14.0006 20.2685 14.1055 20.0002 14.2887C19.7318 14.472 19.5214 14.7256 19.3946 15.02C19.2616 15.3229 19.2219 15.6575 19.2807 15.9819C19.3395 16.3063 19.4942 16.6057 19.7246 16.84L19.7846 16.9C19.9704 17.0857 20.1179 17.3063 20.2186 17.5491C20.3193 17.7919 20.3711 18.0522 20.3711 18.315C20.3711 18.5778 20.3193 18.8381 20.2186 19.0809C20.1179 19.3237 19.9704 19.5443 19.7846 19.73C19.5989 19.9157 19.3783 20.0632 19.1355 20.1639C18.8927 20.2646 18.6324 20.3165 18.3696 20.3165C18.1068 20.3165 17.8465 20.2646 17.6037 20.1639C17.3609 20.0632 17.1403 19.9157 16.9546 19.73L16.8946 19.67C16.6603 19.4395 16.3609 19.2848 16.0365 19.226C15.7121 19.1672 15.3775 19.2069 15.0746 19.34C14.7802 19.4668 14.5266 19.6772 14.3434 19.9455C14.1601 20.2138 14.0552 20.5282 14.0396 20.855V20.95C14.0396 21.4804 13.8289 21.9891 13.4538 22.3642C13.0787 22.7393 12.57 22.95 12.0396 22.95C11.5092 22.95 11.0005 22.7393 10.6254 22.3642C10.2503 21.9891 10.0396 21.4804 10.0396 20.95V20.86C10.024 20.5332 9.91911 20.2188 9.73587 19.9505C9.55263 19.6822 9.29902 19.4718 9.00458 19.345C8.70171 19.2119 8.36712 19.1722 8.04271 19.231C7.7183 19.2898 7.41889 19.4445 7.18458 19.675L7.12458 19.735C6.93885 19.9207 6.71826 20.0682 6.47546 20.1689C6.23266 20.2696 5.97241 20.3214 5.70958 20.3214C5.44675 20.3214 5.1865 20.2696 4.9437 20.1689C4.7009 20.0682 4.48031 19.9207 4.29458 19.735C4.10885 19.5493 3.96135 19.3287 3.86064 19.0859C3.75993 18.8431 3.70812 18.5828 3.70812 18.32C3.70812 18.0572 3.75993 17.7969 3.86064 17.5541C3.96135 17.3113 4.10885 17.0907 4.29458 16.905L4.35458 16.845C4.58509 16.6107 4.73978 16.3113 4.79857 15.9869C4.85736 15.6625 4.81764 15.3279 4.68458 15.025C4.55778 14.7306 4.34736 14.477 4.07904 14.2938C3.81072 14.1105 3.49636 14.0056 3.16958 13.99H3.07458C2.54415 13.99 2.03544 13.7793 1.66037 13.4042C1.28529 13.0291 1.07458 12.5204 1.07458 11.99C1.07458 11.4596 1.28529 10.9509 1.66037 10.5758C2.03544 10.2007 2.54415 9.99 3.07458 9.99Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Perfil</span>
        </a>
    </nav>

    <script>
        // Função para reproduzir vídeo
        function reproduzirVideo(videoUrl) {
            const modal = document.getElementById('videoModal');
            const player = document.getElementById('videoPlayer');
            
            player.src = videoUrl;
            modal.style.display = 'flex';
        }
        
        // Função para fechar modal de vídeo
        function fecharVideoModal() {
            const modal = document.getElementById('videoModal');
            const player = document.getElementById('videoPlayer');
            
            player.pause();
            player.src = '';
            modal.style.display = 'none';
        }
        
        // Função para marcar sinal como aprendido
        function marcarAprendido(sinalId) {
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
                    alert('Parabéns! Sinal marcado como aprendido!');
                    location.reload();
                } else {
                    alert('Erro ao marcar sinal. Tente novamente.');
                }
            })
            .catch(error => {
                alert('Erro de conexão. Tente novamente.');
            });
        }
        
        // Fechar modal de vídeo ao clicar fora
        document.getElementById('videoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharVideoModal();
            }
        });
        
        // Busca automática
        const searchInput = document.querySelector('input[name="busca"]');
        if (searchInput) {
            let timeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    this.form.submit();
                }, 800);
            });
        }
    </script>
</body>
</html>
