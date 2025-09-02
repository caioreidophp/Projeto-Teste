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

// Definir avatar padrão se não existir
if (empty($usuario['avatar'])) {
    $usuario['avatar'] = '🎮';
}

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

        /* Animações Gamificadas */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.9; }
        }

        @keyframes slideInUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .card {
            animation: slideInUp 0.6s ease-out;
        }

        .card:nth-child(2) { animation-delay: 0.1s; }
        .card:nth-child(3) { animation-delay: 0.2s; }
        .card:nth-child(4) { animation-delay: 0.3s; }
    </style>
</head>
<body>    <header class="header">
        <div class="nav-container">
            <a href="home.php" class="logo">Handly</a>
        </div>
    </header>

    <main class="container" style="padding-bottom: 100px;">
        <!-- Boas-vindas Gamificadas -->
        <div class="card" style="background: linear-gradient(135deg, #06B6D4, #0891B2); color: white; border-radius: 20px; text-align: center; padding: 2rem; margin-bottom: 2rem;">
            <!-- Foto de Perfil com Opção de Edição -->
            <div style="position: relative; display: inline-block; margin-bottom: 1rem;">
                <div id="profile-photo" style="width: 120px; height: 120px; border-radius: 50%; background: rgba(255, 255, 255, 0.2); display: flex; align-items: center; justify-content: center; font-size: 3rem; margin: 0 auto; border: 3px solid rgba(255, 255, 255, 0.3);">
                    <?php echo htmlspecialchars($usuario['avatar']); ?>
                </div>
                <button id="edit-photo-btn" style="position: absolute; top: 5px; right: 5px; width: 40px; height: 40px; border-radius: 50%; background: #ffffff; border: 3px solid #06B6D4; color: #06B6D4; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: bold; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 10;" onmouseover="this.style.background='#06B6D4'; this.style.color='white'; this.style.transform='scale(1.1)'" onmouseout="this.style.background='#ffffff'; this.style.color='#06B6D4'; this.style.transform='scale(1)'">
                    ✏️
                </button>
            </div>
            
            <h1 style="color: white; margin-bottom: 0.5rem; font-size: 2rem; font-weight: bold;">
                Olá, <?php echo htmlspecialchars($usuario['nome']); ?>
            </h1>
            <p style="opacity: 0.9; font-size: 1.2rem; margin-bottom: 1.5rem;">
                Continue sua jornada de aprendizado em LIBRAS
            </p>
            
            <!-- Estatísticas Gamificadas -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 1rem; margin-top: 1.5rem;">
                <div style="background: rgba(255, 255, 255, 0.2); padding: 1.5rem 1rem; border-radius: 15px; backdrop-filter: blur(10px);">
                    <div style="margin-bottom: 0.5rem; text-align: center;">
                        <img src="https://cdn-icons-png.flaticon.com/512/2847/2847502.png" alt="Sinais Aprendidos" style="width: 40px; height: 40px; object-fit: contain;">
                    </div>
                    <div style="font-size: 2rem; font-weight: bold; margin-bottom: 0.3rem;"><?php echo $total_sinais_concluidos; ?></div>
                    <div style="font-size: 0.85rem; opacity: 0.9;">Sinais Aprendidos</div>
                </div>
                <div style="background: rgba(255, 255, 255, 0.2); padding: 1.5rem 1rem; border-radius: 15px; backdrop-filter: blur(10px);">
                    <div style="margin-bottom: 0.5rem; text-align: center;">
                        <img src="https://cdn-icons-png.flaticon.com/512/610/610064.png" alt="Missões" style="width: 40px; height: 40px; object-fit: contain;">
                    </div>
                    <div style="font-size: 2rem; font-weight: bold; margin-bottom: 0.3rem;"><?php echo $usuario['missoes_concluidas']; ?></div>
                    <div style="font-size: 0.85rem; opacity: 0.9;">Missões</div>
                </div>
                <div style="background: rgba(255, 255, 255, 0.2); padding: 1.5rem 1rem; border-radius: 15px; backdrop-filter: blur(10px);">
                    <div style="margin-bottom: 0.5rem; text-align: center;">
                        <img src="https://cdn-icons-png.flaticon.com/512/7408/7408613.png" alt="Pontos" style="width: 40px; height: 40px; object-fit: contain;">
                    </div>
                    <div style="font-size: 2rem; font-weight: bold; margin-bottom: 0.3rem;"><?php echo $usuario['pontuacao_total']; ?></div>
                    <div style="font-size: 0.85rem; opacity: 0.9;">Pontos</div>
                </div>
                <div style="background: rgba(255, 255, 255, 0.2); padding: 1.5rem 1rem; border-radius: 15px; backdrop-filter: blur(10px);">
                    <div style="margin-bottom: 0.5rem; text-align: center;">
                        <img src="https://cdn-icons-png.flaticon.com/512/8989/8989449.png" alt="Progresso" style="width: 40px; height: 40px; object-fit: contain;">
                    </div>
                    <div style="font-size: 2rem; font-weight: bold; margin-bottom: 0.3rem;"><?php echo $percentual_geral; ?>%</div>
                    <div style="font-size: 0.85rem; opacity: 0.9;">Progresso</div>
                </div>
            </div>
        </div>
            <p style="opacity: 0.9; font-size: 1.1rem;">
                Bem-vindo de volta! Continue sua jornada de aprendizado em LIBRAS.
            </p>
        </div>

        <!-- Seu Progresso nos Módulos -->
        <div class="card" style="border-radius: 20px; padding: 2rem;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h2 style="color: #10B981; margin-bottom: 0.5rem; font-size: 1.8rem;">🎮 Sua Jornada LIBRAS</h2>
                <p style="color: #6B7280; font-size: 1rem;">
                    Continue desbloqueando novos níveis
                </p>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                <?php foreach ($progresso_modulos as $index => $modulo): ?>
                    <?php 
                    $percentual_modulo = $modulo['total_sinais'] > 0 ? 
                        round(($modulo['sinais_concluidos'] / $modulo['total_sinais']) * 100) : 0;
                    
                    $status = 'bloqueado';
                    if ($modulo['modulo'] <= $usuario['progresso_modulo']) {
                        $status = 'disponivel';
                    }
                    if ($modulo['modulo'] < $usuario['progresso_modulo']) {
                        $status = 'concluido';
                    }
                    
                    // Cores diferentes para cada módulo
                    $cores_modulos = [
                        ['bg' => '#10B981', 'light' => '#D1FAE5'], // Básico - Verde
                        ['bg' => '#3B82F6', 'light' => '#DBEAFE'], // Intermediário - Azul
                        ['bg' => '#8B5CF6', 'light' => '#EDE9FE']  // Avançado - Roxo
                    ];
                    $cor = $cores_modulos[$index % 3];
                    ?>
                    
                    <div style="background: <?php echo $cor['light']; ?>; border: 2px solid <?php echo $cor['bg']; ?>; border-radius: 16px; padding: 1.5rem; transition: transform 0.3s ease, box-shadow 0.3s ease; <?php echo $status === 'bloqueado' ? 'opacity: 0.6; filter: grayscale(50%);' : ''; ?>"
                         onmouseover="<?php if ($status !== 'bloqueado') echo "this.style.transform='translateY(-8px)'; this.style.boxShadow='0 15px 30px rgba(0,0,0,0.15)'"; ?>"
                         onmouseout="<?php if ($status !== 'bloqueado') echo "this.style.transform='translateY(0)'; this.style.boxShadow='none'"; ?>">
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                    
                                <div>
                                    <h3 style="color: <?php echo $cor['bg']; ?>; margin: 0; font-size: 1.3rem; font-weight: bold;">
                                        Módulo <?php echo $modulo['modulo']; ?>
                                    </h3>
                                    <div style="font-size: 0.9rem; color: #6B7280; margin-top: 0.2rem;">
                                        <?php 
                                        $nomes = ['Iniciante', 'Aventureiro', 'Expert'];
                                        echo $nomes[$index % 3];
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($status === 'concluido'): ?>
                                <div style="background: #10B981; color: white; padding: 0.5rem 1rem; border-radius: 25px; font-weight: bold; font-size: 0.85rem; display: flex; align-items: center; gap: 0.3rem;">
                                    ✅ Completo!
                                </div>
                            <?php elseif ($status === 'bloqueado'): ?>
                                <div style="background: #6B7280; color: white; padding: 0.5rem 1rem; border-radius: 25px; font-weight: bold; font-size: 0.85rem; display: flex; align-items: center; gap: 0.3rem;">
                                    Bloqueado
                                </div>
                            <?php else: ?>
                                <div style="background: <?php echo $cor['bg']; ?>; color: white; padding: 0.5rem 1rem; border-radius: 25px; font-weight: bold; font-size: 0.85rem;">
                                    Ativo
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <p style="color: #374151; margin-bottom: 1.5rem; line-height: 1.5;">
                            <?php echo $modulo['sinais_concluidos']; ?> de <?php echo $modulo['total_sinais']; ?> sinais dominados
                        </p>
                        
                        <div style="margin-bottom: 1.5rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.8rem;">
                                <span style="font-size: 0.9rem; color: #374151; font-weight: 600;">
                                    Progresso
                                </span>
                                <span style="font-weight: bold; color: <?php echo $cor['bg']; ?>; font-size: 1.2rem;">
                                    <?php echo $percentual_modulo; ?>%
                                </span>
                            </div>
                            <div style="background: #E5E7EB; height: 14px; border-radius: 50px; overflow: hidden;">
                                <div style="background: linear-gradient(90deg, <?php echo $cor['bg']; ?>, <?php echo $cor['bg']; ?>AA); height: 100%; width: <?php echo $percentual_modulo; ?>%; border-radius: 50px; transition: width 0.8s ease;"></div>
                            </div>
                        </div>
                        
                        <?php if ($status === 'disponivel'): ?>
                            <a href="trilha.php?modulo=<?php echo $modulo['modulo']; ?>" 
                               style="display: block; background: <?php echo $cor['bg']; ?>; color: white; padding: 1rem; text-align: center; border-radius: 12px; text-decoration: none; font-weight: bold; transition: all 0.3s ease;"
                               onmouseover="this.style.transform='scale(1.05)'"
                               onmouseout="this.style.transform='scale(1)'">
                                <?php echo $percentual_modulo > 0 ? 'Continuar' : 'Começar'; ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Missões Gamificadas -->
        <div class="card" style="background: linear-gradient(135deg, #F59E0B, #D97706); color: white; border-radius: 20px; padding: 2rem; text-align: center;">
            <h2 style="color: white; margin-bottom: 0.5rem; font-size: 1.8rem;">⚡ Desafios do Dia</h2>
            <p style="opacity: 0.9; font-size: 1rem; margin-bottom: 1.5rem;">
                Complete esses desafios e ganhe pontos extras!
            </p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; text-align: left;">
                <?php foreach (array_slice($missoes, 0, 2) as $index => $missao): ?>
                    <?php 
                    $percentual_missao = round(($missao['progresso_atual'] / $missao['objetivo']) * 100);
                    $percentual_missao = min($percentual_missao, 100);
                    ?>
                    
                    <div style="background: rgba(255, 255, 255, 0.95); border-radius: 16px; padding: 1.5rem; color: #333; transition: transform 0.3s ease;"
                         onmouseover="this.style.transform='scale(1.02)'"
                         onmouseout="this.style.transform='scale(1)'">
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.8rem;">
                               
                                <h4 style="color: #F59E0B; margin: 0; font-weight: bold; font-size: 1.1rem;">
                                    <?php echo htmlspecialchars($missao['titulo']); ?>
                                </h4>
                            </div>
                            <div style="background: #10B981; color: white; padding: 0.3rem 0.8rem; border-radius: 20px; font-weight: bold; font-size: 0.8rem;">
                                +<?php echo $missao['recompensa_pontos']; ?>pts
                            </div>
                        </div>
                        
                        <p style="color: #6B7280; margin-bottom: 1.2rem; font-size: 0.9rem; line-height: 1.4;">
                            <?php echo htmlspecialchars($missao['descricao']); ?>
                        </p>
                        
                        <div style="margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem;">
                                <span style="font-size: 0.85rem; color: #374151;">
                                    <strong><?php echo $missao['progresso_atual']; ?></strong> / <?php echo $missao['objetivo']; ?>
                                </span>
                                <span style="font-weight: bold; color: #F59E0B; font-size: 1rem;">
                                    <?php echo $percentual_missao; ?>%
                                </span>
                            </div>
                            <div style="background: #E5E7EB; height: 8px; border-radius: 50px; overflow: hidden;">
                                <div style="background: linear-gradient(90deg, #10B981, #059669); height: 100%; width: <?php echo $percentual_missao; ?>%; border-radius: 50px; transition: width 0.5s ease;"></div>
                            </div>
                        </div>
                        
                        <?php if ($percentual_missao >= 100): ?>
                            <div style="background: #10B981; color: white; padding: 0.8rem; text-align: center; border-radius: 12px; font-weight: bold;">
                                ✅ Missão Completa! Colete sua recompensa
                            </div>
                        <?php else: ?>
                            <div style="background: #F3F4F6; color: #6B7280; padding: 0.8rem; text-align: center; border-radius: 12px; font-size: 0.9rem;">
                                🎮 Continue jogando para completar!
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="margin-top: 2rem;">
                <a href="missoes.php" style="background: rgba(255, 255, 255, 0.2); color: white; padding: 1rem 2rem; border-radius: 25px; text-decoration: none; font-weight: bold; backdrop-filter: blur(10px); transition: all 0.3s ease;"
                   onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'"
                   onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'">
                    Ver Todas as Missões
                </a>
            </div>
        </div>

        <!-- Explore Categorias Gamificadas -->
        <div class="card" style="border-radius: 20px; padding: 2rem;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h2 style="color: #8B5CF6; margin-bottom: 0.5rem; font-size: 1.8rem;">🌟 Mundo dos Sinais</h2>
                <p style="color: #6B7280; font-size: 1rem; margin-bottom: 1rem;">
                    Descubra novos sinais em cada categoria!
                </p>
                <a href="dicionario.php" style="color: #8B5CF6; text-decoration: none; font-weight: bold; font-size: 0.9rem;">
                    🔍 Explorar Tudo →
                </a>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1.2rem;">
                <?php foreach (array_slice($categorias, 0, 6) as $index => $categoria): ?>
                    <?php 
                    $percentual_categoria = $categoria['total_sinais'] > 0 ? 
                        round(($categoria['sinais_concluidos'] / $categoria['total_sinais']) * 100) : 0;
                    
                    // Cores vibrantes para cada categoria
                    $cores_cat = [
                        ['bg' => '#EF4444', 'light' => '#FEE2E2'], // Vermelho
                        ['bg' => '#F59E0B', 'light' => '#FEF3C7'], // Amarelo
                        ['bg' => '#10B981', 'light' => '#D1FAE5'], // Verde
                        ['bg' => '#3B82F6', 'light' => '#DBEAFE'], // Azul
                        ['bg' => '#8B5CF6', 'light' => '#EDE9FE'], // Roxo
                        ['bg' => '#EC4899', 'light' => '#FCE7F3']  // Rosa
                    ];
                    $cor_cat = $cores_cat[$index % 6];
                    ?>
                    
                    <a href="dicionario.php?categoria=<?php echo $categoria['id']; ?>" 
                       style="background: <?php echo $cor_cat['light']; ?>; border: 2px solid <?php echo $cor_cat['bg']; ?>; border-radius: 16px; padding: 1.5rem; text-decoration: none; color: inherit; transition: all 0.3s ease; text-align: center; display: block;"
                       onmouseover="this.style.transform='translateY(-8px) scale(1.02)'; this.style.boxShadow='0 12px 24px rgba(0,0,0,0.15)'"
                       onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='none'">
                        
                        <div style="font-size: 2.5rem; margin-bottom: 1rem;">
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
                                echo '<img src="https://cdn-icons-png.flaticon.com/512/2847/2847502.png" 
                                      alt="Categoria" 
                                      style="width: 60px; height: 60px; object-fit: contain;">';
                            }
                            ?>
                        </div>
                        
                        <h4 style="color: <?php echo $cor_cat['bg']; ?>; margin: 0 0 0.8rem 0; font-weight: bold; font-size: 1rem;">
                            <?php echo htmlspecialchars($categoria['nome']); ?>
                        </h4>
                        
                        <p style="margin: 0.8rem 0; color: #6B7280; font-size: 0.85rem;">
                            <strong><?php echo $categoria['sinais_concluidos']; ?></strong> / <?php echo $categoria['total_sinais']; ?> sinais
                        </p>
                        
                        <div style="background: #E5E7EB; height: 6px; border-radius: 50px; overflow: hidden; margin: 0.8rem 0;">
                            <div style="background: <?php echo $cor_cat['bg']; ?>; height: 100%; width: <?php echo $percentual_categoria; ?>%; border-radius: 50px; transition: width 0.5s ease;"></div>
                        </div>
                        
                        <div style="font-weight: bold; color: <?php echo $cor_cat['bg']; ?>; font-size: 0.9rem;">
                            <?php echo $percentual_categoria; ?>% completo
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Ações Rápidas Gamificadas -->
        <div class="card" style="background: linear-gradient(135deg, #10B981, #059669); color: white; border-radius: 20px; padding: 2rem; text-align: center;">
            <h2 style="color: white; margin-bottom: 1rem; font-size: 1.6rem;">Próximos Passos</h2>
            <p style="opacity: 0.9; margin-bottom: 2rem; font-size: 1rem;">
                Escolha seu próximo passo na jornada LIBRAS
            </p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <a href="trilha.php" 
                   style="background: rgba(255, 255, 255, 0.2); color: white; padding: 1.5rem; border-radius: 16px; text-decoration: none; backdrop-filter: blur(10px); transition: all 0.3s ease;"
                   onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'; this.style.transform='scale(1.05)'"
                   onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'; this.style.transform='scale(1)'">
                    <div style="margin-bottom: 0.8rem;"><img src="https://cdn-icons-png.flaticon.com/512/610/610064.png" alt="Trilha" style="width: 50px; height: 50px; object-fit: contain;"></div>
                    <div style="font-weight: bold; font-size: 1.1rem;">Continuar Trilha</div>
                </a>
                
                <a href="dicionario.php" 
                   style="background: rgba(255, 255, 255, 0.2); color: white; padding: 1.5rem; border-radius: 16px; text-decoration: none; backdrop-filter: blur(10px); transition: all 0.3s ease;"
                   onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'; this.style.transform='scale(1.05)'"
                   onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'; this.style.transform='scale(1)'">
                    <div style="margin-bottom: 0.8rem;"><img src="https://cdn-icons-png.flaticon.com/512/2847/2847502.png" alt="Dicionário" style="width: 50px; height: 50px; object-fit: contain;"></div>
                    <div style="font-weight: bold; font-size: 1.1rem;">Explorar Sinais</div>
                </a>
                
                <a href="missoes.php" 
                   style="background: rgba(255, 255, 255, 0.2); color: white; padding: 1.5rem; border-radius: 16px; text-decoration: none; backdrop-filter: blur(10px); transition: all 0.3s ease;"
                   onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'; this.style.transform='scale(1.05)'"
                   onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'; this.style.transform='scale(1)'">
                    <div style="font-size: 2.5rem; margin-bottom: 0.8rem;">⚡</div>
                    <div style="font-weight: bold; font-size: 1.1rem;">Ver Desafios</div>
                </a>
            </div>
        </div>
    </main>

    <!-- Navegação Inferior -->
    <nav class="bottom-nav">
        <a href="home.php" class="bottom-nav-item active">
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
                <path d="M19.4 15C19.2669 15.3016 19.2272 15.6362 19.286 15.9606C19.3448 16.285 19.4995 16.5843 19.73 16.82L19.79 16.88C19.976 17.0657 20.1235 17.2863 20.2241 17.5291C20.3248 17.7719 20.3766 18.0322 20.3766 18.295C20.3766 18.5578 20.3248 18.8181 20.2241 19.0609C20.1235 19.3037 19.976 19.5243 19.79 19.71C19.6043 19.896 19.3837 20.0435 19.1409 20.1441C18.8981 20.2448 18.6378 20.2966 18.375 20.2966C18.1122 20.2966 17.8519 20.2448 17.6091 20.1441C17.3663 20.0435 17.1457 19.896 16.96 19.71L16.9 19.65C16.6643 19.4195 16.365 19.2648 16.0406 19.206C15.7162 19.1472 15.3816 19.1869 15.08 19.32C14.7842 19.4468 14.532 19.6572 14.3543 19.9255C14.1766 20.1938 14.0813 20.5082 14.08 20.83V21C14.08 21.5304 13.8693 22.0391 13.4942 22.4142C13.1191 22.7893 12.6104 23 12.08 23C11.5496 23 11.0409 22.7893 10.6658 22.4142C10.2907 22.0391 10.08 21.5304 10.08 21V20.91C10.0723 20.579 9.96512 20.2573 9.77251 19.9887C9.5799 19.7201 9.31074 19.5176 9 19.41C8.69838 19.2769 8.36381 19.2372 8.03941 19.296C7.71502 19.3548 7.41568 19.5095 7.18 19.74L7.12 19.8C6.93425 19.986 6.71368 20.1335 6.47088 20.2341C6.22808 20.3348 5.96783 20.3866 5.705 20.3866C5.44217 20.3866 5.18192 20.3348 4.93912 20.2341C4.69632 20.1335 4.47575 19.986 4.29 19.8C4.10405 19.6143 3.95653 19.3937 3.85588 19.1509C3.75523 18.9081 3.70343 18.6478 3.70343 18.385C3.70343 18.1222 3.75523 17.8619 3.85588 17.6191C3.95653 17.3763 4.10405 17.1557 4.29 16.97L4.35 16.91C4.58054 16.6743 4.73519 16.375 4.794 16.0506C4.85282 15.7262 4.81312 15.3916 4.68 15.09C4.55324 14.7942 4.34276 14.542 4.07447 14.3643C3.80618 14.1866 3.49179 14.0913 3.17 14.09H3C2.46957 14.09 1.96086 13.8793 1.58579 13.5042C1.21071 13.1291 1 12.6204 1 12.09C1 11.5596 1.21071 11.0509 1.58579 10.6758C1.96086 10.3007 2.46957 10.09 3 10.09H3.09C3.42099 10.0823 3.742 9.97512 4.01062 9.78251C4.27925 9.5899 4.48167 9.32074 4.59 9.01C4.72312 8.70838 4.76282 8.37381 4.704 8.04941C4.64519 7.72502 4.49054 7.42568 4.26 7.19L4.2 7.13C4.01405 6.94425 3.86653 6.72368 3.76588 6.48088C3.66523 6.23808 3.61343 5.97783 3.61343 5.715C3.61343 5.45217 3.66523 5.19192 3.76588 4.94912C3.86653 4.70632 4.01405 4.48575 4.2 4.3C4.38575 4.11405 4.60632 3.96653 4.84912 3.86588C5.09192 3.76523 5.35217 3.71343 5.615 3.71343C5.87783 3.71343 6.13808 3.76523 6.38088 3.86588C6.62368 3.96653 6.84425 4.11405 7.03 4.3L7.09 4.36C7.32568 4.59054 7.62502 4.74519 7.94941 4.804C8.27381 4.86282 8.60838 4.82312 8.91 4.69H9C9.29577 4.56324 9.54802 4.35276 9.72569 4.08447C9.90337 3.81618 9.99872 3.50179 10 3.18V3C10 2.46957 10.2107 1.96086 10.5858 1.58579C10.9609 1.21071 11.4696 1 12 1C12.5304 1 13.0391 1.21071 13.4142 1.58579C13.7893 1.96086 14 2.46957 14 3V3.09C14.0013 3.41179 14.0966 3.72618 14.2743 3.99447C14.452 4.26276 14.7042 4.47324 15 4.6C15.3016 4.73312 15.6362 4.77282 15.9606 4.714C16.285 4.65519 16.5843 4.50054 16.82 4.27L16.88 4.21C17.0657 4.02405 17.2863 3.87653 17.5291 3.77588C17.7719 3.67523 18.0322 3.62343 18.295 3.62343C18.5578 3.62343 18.8181 3.67523 19.0609 3.77588C19.3037 3.87653 19.5243 4.02405 19.71 4.21C19.896 4.39575 20.0435 4.61632 20.1441 4.85912C20.2448 5.10192 20.2966 5.36217 20.2966 5.625C20.2966 5.88783 20.2448 6.14808 20.1441 6.39088C20.0435 6.63368 19.896 6.85425 19.71 7.04L19.65 7.1C19.4195 7.33568 19.2648 7.63502 19.206 7.95941C19.1472 8.28381 19.1869 8.61838 19.32 8.92V9C19.4468 9.29577 19.6572 9.54802 19.9255 9.72569C20.1938 9.90337 20.5082 9.99872 20.83 10H21C21.5304 10 22.0391 10.2107 22.4142 10.5858C22.7893 10.9609 23 11.4696 23 12C23 12.5304 22.7893 13.0391 22.4142 13.4142C22.0391 13.7893 21.5304 14 21 14H20.91C20.5882 14.0013 20.2738 14.0966 20.0055 14.2743C19.7372 14.452 19.5268 14.7042 19.4 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Perfil</span>
        </a>
    </nav>

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

        // Sistema de edição de foto de perfil
        const editPhotoBtn = document.getElementById('edit-photo-btn');
        const profilePhoto = document.getElementById('profile-photo');
        
        console.log('Botão de edição encontrado:', editPhotoBtn);
        console.log('Foto de perfil encontrada:', profilePhoto);
        
        if (editPhotoBtn) {
            editPhotoBtn.addEventListener('click', function() {
                console.log('Clique no botão de edição detectado');
                showEmojiModal();
            });
        } else {
            console.error('Botão de edição não encontrado!');
        }
        
        function showEmojiModal() {
            // Emojis relacionados ao LIBRAS e aprendizado
            const librasEmojis = [
                '🤟', '👋', '🙌', '👏', '🤝', '✌️', '👍', '👎', 
                '🤞', '🤘', '📚', '🎓', '🧠', '💡', '⭐', '🏆', 
                '🎯', '🚀', '💪', '👑', '😊', '😄', '🤗', '😎', 
                '🥳', '😇', '🤓', '😉', '🙂', '😍', '🎮', '🏅',
                '💫', '🌟', '✨', '🔥', '🎨', '🎭', '🎪', '🎊'
            ];
            
            // Criar modal com animação
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1000;
                backdrop-filter: blur(8px);
                animation: fadeIn 0.3s ease-out;
            `;
            
            const modalContent = document.createElement('div');
            modalContent.style.cssText = `
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 25px;
                padding: 1.5rem;
                max-width: 450px;
                width: 90%;
                max-height: 90vh;
                box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
                text-align: center;
                position: relative;
                overflow: hidden;
                animation: slideUp 0.4s ease-out;
                box-sizing: border-box;
            `;
            
            // Adicionar CSS de animação
            if (!document.getElementById('modal-animations')) {
                const style = document.createElement('style');
                style.id = 'modal-animations';
                style.textContent = `
                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }
                    @keyframes slideUp {
                        from { transform: translateY(50px); opacity: 0; }
                        to { transform: translateY(0); opacity: 1; }
                    }
                    .emoji-btn:hover {
                        transform: scale(1.1) !important;
                        box-shadow: 0 6px 15px rgba(6, 182, 212, 0.3) !important;
                        border-color: rgba(255, 255, 255, 0.6) !important;
                    }
                    .emoji-btn:active {
                        transform: scale(0.95) !important;
                    }
                    #emoji-grid {
                        justify-content: center !important;
                    }
                    @media (max-width: 480px) {
                        #emoji-grid {
                            grid-template-columns: repeat(6, minmax(0, 1fr)) !important;
                        }
                        .emoji-btn {
                            width: 40px !important;
                            height: 40px !important;
                            min-width: 40px !important;
                            min-height: 40px !important;
                            max-width: 40px !important;
                            max-height: 40px !important;
                            font-size: 1.2rem !important;
                        }
                    }
                `;
                document.head.appendChild(style);
            }
            
            modalContent.innerHTML = `
                <div style="
                    position: absolute;
                    top: -50px;
                    right: -50px;
                    width: 100px;
                    height: 100px;
                    background: rgba(255, 255, 255, 0.1);
                    border-radius: 50%;
                "></div>
                <div style="
                    position: absolute;
                    bottom: -30px;
                    left: -30px;
                    width: 60px;
                    height: 60px;
                    background: rgba(255, 255, 255, 0.08);
                    border-radius: 50%;
                "></div>
                
                <div style="margin-bottom: 2rem;">
                    <div style="
                        width: 80px;
                        height: 80px;
                        background: rgba(255, 255, 255, 0.2);
                        border-radius: 50%;
                        margin: 0 auto 1rem;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 2.5rem;
                        border: 3px solid rgba(255, 255, 255, 0.3);
                    ">🎮</div>
                    <h3 style="
                        color: white; 
                        margin: 0; 
                        font-size: 1.8rem; 
                        font-weight: bold;
                        text-shadow: 0 2px 4px rgba(0,0,0,0.3);
                    ">Escolha seu Avatar</h3>
                    <p style="
                        color: rgba(255, 255, 255, 0.9); 
                        margin: 0.5rem 0 0 0; 
                        font-size: 1rem;
                    ">Selecione um emoji que te representa!</p>
                </div>
                
                <div style="
                    background: rgba(255, 255, 255, 0.15);
                    border-radius: 20px;
                    padding: 1rem;
                    margin-bottom: 2rem;
                    backdrop-filter: blur(10px);
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    overflow: hidden;
                ">
                    <div id="emoji-grid" style="
                        display: grid; 
                        grid-template-columns: repeat(8, minmax(0, 1fr)); 
                        gap: 6px;
                        justify-items: center;
                        align-items: center;
                        width: 100%;
                        box-sizing: border-box;
                    ">
                        ${librasEmojis.map(emoji => `
                            <button class="emoji-btn" style="
                                width: 45px;
                                height: 45px;
                                min-width: 45px;
                                min-height: 45px;
                                max-width: 45px;
                                max-height: 45px;
                                border: 2px solid rgba(255, 255, 255, 0.2);
                                border-radius: 10px;
                                font-size: 1.4rem;
                                cursor: pointer;
                                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                                background: rgba(255, 255, 255, 0.1);
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                backdrop-filter: blur(5px);
                                position: relative;
                                overflow: hidden;
                                box-sizing: border-box;
                                line-height: 1;
                                padding: 0;
                                margin: 0;
                            " onclick="selectEmoji('${emoji}')">${emoji}</button>
                        `).join('')}
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: center;">
                    <button onclick="closeEmojiModal()" style="
                        background: rgba(239, 68, 68, 0.9);
                        color: white;
                        border: none;
                        padding: 14px 28px;
                        border-radius: 12px;
                        cursor: pointer;
                        font-size: 1rem;
                        font-weight: 600;
                        transition: all 0.3s ease;
                        backdrop-filter: blur(10px);
                        border: 1px solid rgba(255, 255, 255, 0.2);
                        text-shadow: 0 1px 2px rgba(0,0,0,0.3);
                    " onmouseover="this.style.background='rgba(220, 38, 38, 0.9)'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(239, 68, 68, 0.4)'" 
                       onmouseout="this.style.background='rgba(239, 68, 68, 0.9)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'"
                       onmousedown="this.style.transform='translateY(0)'"
                       onmouseup="this.style.transform='translateY(-2px)'">
                        ✖️ Cancelar
                    </button>
                </div>
            `;
            
            modal.appendChild(modalContent);
            document.body.appendChild(modal);
            
            // Fechar modal clicando fora
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeEmojiModal();
                }
            });
            
            window.currentModal = modal;
        }
        
        function selectEmoji(emoji) {
            profilePhoto.innerHTML = emoji;
            
            // Salvar no banco de dados via AJAX
            fetch('api/atualizar_foto_perfil.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ foto_perfil: emoji })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Avatar atualizado com sucesso!');
                } else {
                    console.error('Erro ao atualizar avatar');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
            });
            
            closeEmojiModal();
        }
        
        function closeEmojiModal() {
            if (window.currentModal) {
                document.body.removeChild(window.currentModal);
                window.currentModal = null;
            }
        }
    </script>
</body>
</html>
