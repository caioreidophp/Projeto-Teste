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
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.1);
                opacity: 0.8;
            }
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
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

        @keyframes glow {
            0%, 100% {
                box-shadow: 0 0 5px rgba(139, 92, 246, 0.3);
            }
            50% {
                box-shadow: 0 0 20px rgba(139, 92, 246, 0.6);
            }
        }

        /* Aplicar animações */
        .card {
            animation: slideInUp 0.6s ease-out;
        }

        .card:nth-child(2) {
            animation-delay: 0.1s;
        }

        .card:nth-child(3) {
            animation-delay: 0.2s;
        }
    </style>
</head>
<body>    <header class="header">
        <div class="nav-container">
            <a href="home.php" class="logo">Handly</a>
        </div>
    </header>

    <main class="container" style="padding-bottom: 100px;">
        <!-- Cabeçalho Gamificado -->
        <div class="card" style="background: linear-gradient(135deg, #8B5CF6, #A855F7); color: white; border-radius: 20px; text-align: center; padding: 2rem; margin-bottom: 2rem;">
            <h1 style="color: white; margin-bottom: 0.5rem; font-size: 2rem; font-weight: bold;">🎉 Boas-vindas amigo,</h1>
            <p style="opacity: 0.9; font-size: 1.1rem; margin-bottom: 1.5rem;">
                Complete as missões para ganhar pontos,<br>
                e assim subir de nível!
            </p>
            
            <!-- Card de Missões do Dia -->
            <div style="background: rgba(255, 255, 255, 0.2); border-radius: 15px; padding: 1.5rem; margin-top: 1.5rem; backdrop-filter: blur(10px);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="color: white; margin: 0; font-size: 1.2rem;">Missões do dia</h3>
                    <span style="background: rgba(255, 255, 255, 0.3); padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.9rem;">
                        Total de pontos: <?php echo $usuario['pontuacao_total']; ?>
                    </span>
                </div>
                
                <?php if (!empty($missoes_ativas)): ?>
                    <?php $primeira_missao = $missoes_ativas[0]; ?>
                    <?php $percentual = $primeira_missao['objetivo'] > 0 ? 
                        min(round(($primeira_missao['progresso_atual'] / $primeira_missao['objetivo']) * 100), 100) : 0; ?>
                    
                    <div style="background: rgba(255, 255, 255, 0.95); border-radius: 12px; padding: 1rem; color: #333; margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.8rem;">
                            <span style="font-weight: bold; color: #8B5CF6;"><?php echo htmlspecialchars($primeira_missao['titulo']); ?></span>
                            <span style="background: #22C55E; color: white; padding: 0.2rem 0.6rem; border-radius: 50px; font-size: 0.8rem; display: flex; align-items: center;">
                                <?php if ($percentual >= 100): ?>
                                    ✅
                                <?php else: ?>
                                    🎯
                                <?php endif; ?>
                            </span>
                        </div>
                        <div style="background: #E5E7EB; height: 8px; border-radius: 50px; overflow: hidden; margin-bottom: 0.5rem;">
                            <div style="background: linear-gradient(90deg, #22C55E, #16A34A); height: 100%; width: <?php echo $percentual; ?>%; transition: width 0.3s ease;"></div>
                        </div>
                        <div style="font-size: 0.85rem; color: #6B7280;">
                            Progresso: <?php echo $primeira_missao['progresso_atual']; ?> / <?php echo $primeira_missao['objetivo']; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div style="opacity: 0.8; font-size: 0.9rem; display: flex; align-items: center; justify-content: center;">
                    🔒 Mais missões em breve
                </div>
            </div>
            
            <!-- Botão de Loading Animado -->
            <div style="margin-top: 2rem;">
                <div style="width: 60px; height: 60px; margin: 0 auto; background: rgba(255, 255, 255, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; animation: pulse 2s infinite;">
                    <div style="width: 20px; height: 20px; background: white; border-radius: 50%; animation: bounce 1s infinite;"></div>
                </div>
            </div>
        </div>

        <!-- Missões Disponíveis -->
        <?php if (!empty($missoes_ativas)): ?>
        <div class="card" style="border-radius: 20px; padding: 1.5rem;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h2 style="color: #8B5CF6; margin-bottom: 0.5rem; font-size: 1.8rem;">🎯 Missões Disponíveis</h2>
                <p style="color: #6B7280; font-size: 1rem;">
                    Escolha uma missão e comece a ganhar pontos!
                </p>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                <?php foreach ($missoes_ativas as $index => $missao): ?>
                    <?php 
                    $percentual = $missao['objetivo'] > 0 ? 
                        min(round(($missao['progresso_atual'] / $missao['objetivo']) * 100), 100) : 0;
                    
                    // Cores diferentes para cada missão
                    $cores = [
                        ['bg' => '#22C55E', 'light' => '#DCFCE7', 'emoji' => '🎯'],
                        ['bg' => '#3B82F6', 'light' => '#DBEAFE', 'emoji' => '📚'], 
                        ['bg' => '#F59E0B', 'light' => '#FEF3C7', 'emoji' => '⭐'],
                        ['bg' => '#EF4444', 'light' => '#FEE2E2', 'emoji' => '🔥']
                    ];
                    $cor = $cores[$index % 4];
                    ?>
                    
                    <div style="background: <?php echo $cor['light']; ?>; border: 2px solid <?php echo $cor['bg']; ?>; border-radius: 16px; padding: 1.5rem; transition: transform 0.3s ease, box-shadow 0.3s ease; cursor: pointer;" 
                         onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 25px rgba(0,0,0,0.1)'"
                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                        
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.8rem;">
                                <div style="font-size: 2rem;"><?php echo $cor['emoji']; ?></div>
                                <h3 style="color: <?php echo $cor['bg']; ?>; margin: 0; font-size: 1.2rem; font-weight: bold;">
                                    <?php echo htmlspecialchars($missao['titulo']); ?>
                                </h3>
                            </div>
                            <div style="background: <?php echo $cor['bg']; ?>; color: white; padding: 0.4rem 0.8rem; border-radius: 20px; font-weight: bold; font-size: 0.85rem;">
                                +<?php echo $missao['recompensa_pontos']; ?> pts
                            </div>
                        </div>
                        
                        <p style="color: #374151; margin-bottom: 1.5rem; line-height: 1.5;">
                            <?php echo htmlspecialchars($missao['descricao']); ?>
                        </p>
                        
                        <div style="margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.8rem;">
                                <span style="font-size: 0.9rem; color: #374151;">
                                    <strong><?php echo $missao['progresso_atual']; ?></strong> / <?php echo $missao['objetivo']; ?>
                                </span>
                                <span style="font-weight: bold; color: <?php echo $cor['bg']; ?>; font-size: 1.1rem;">
                                    <?php echo $percentual; ?>%
                                </span>
                            </div>
                            <div style="background: #E5E7EB; height: 12px; border-radius: 50px; overflow: hidden;">
                                <div style="background: linear-gradient(90deg, <?php echo $cor['bg']; ?>, <?php echo $cor['bg']; ?>AA); height: 100%; width: <?php echo $percentual; ?>%; border-radius: 50px; transition: width 0.5s ease;"></div>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="background: <?php echo $cor['bg']; ?>22; color: <?php echo $cor['bg']; ?>; padding: 0.3rem 0.8rem; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">
                                Módulo <?php echo $missao['modulo_requerido']; ?>
                            </div>
                            
                            <?php if ($percentual >= 100): ?>
                                <div style="background: #22C55E; color: white; padding: 0.5rem 1rem; border-radius: 25px; font-weight: bold; font-size: 0.9rem; display: flex; align-items: center; gap: 0.3rem;">
                                    ✅ Colete agora!
                                </div>
                            <?php else: ?>
                                <div style="color: #6B7280; font-size: 0.85rem;">
                                    🎮 Em progresso
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Conquistas e Estatísticas Gamificadas -->
        <?php if (!empty($missoes_concluidas)): ?>
        <div class="card" style="background: linear-gradient(135deg, #10B981, #059669); color: white; border-radius: 20px; padding: 2rem; text-align: center;">
            <h2 style="color: white; margin-bottom: 1rem; font-size: 1.5rem;">🏆 Suas Conquistas</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                <div style="background: rgba(255, 255, 255, 0.2); padding: 1rem; border-radius: 15px; backdrop-filter: blur(10px);">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">✅</div>
                    <div style="font-size: 1.5rem; font-weight: bold; margin-bottom: 0.2rem;"><?php echo count($missoes_concluidas); ?></div>
                    <div style="font-size: 0.8rem; opacity: 0.9;">Concluídas</div>
                </div>
                <div style="background: rgba(255, 255, 255, 0.2); padding: 1rem; border-radius: 15px; backdrop-filter: blur(10px);">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">⭐</div>
                    <div style="font-size: 1.5rem; font-weight: bold; margin-bottom: 0.2rem;"><?php echo $pontos_ganhos_missoes; ?></div>
                    <div style="font-size: 0.8rem; opacity: 0.9;">Pontos Ganhos</div>
                </div>
                <div style="background: rgba(255, 255, 255, 0.2); padding: 1rem; border-radius: 15px; backdrop-filter: blur(10px);">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">🎯</div>
                    <div style="font-size: 1.5rem; font-weight: bold; margin-bottom: 0.2rem;"><?php echo count($missoes_ativas); ?></div>
                    <div style="font-size: 0.8rem; opacity: 0.9;">Ativas</div>
                </div>
            </div>
            <p style="opacity: 0.9; font-size: 1rem;">Continue completando missões para subir de nível! 🚀</p>
        </div>
        <?php endif; ?>

        <!-- Dicas Gamificadas -->
        <div class="card" style="border-radius: 20px; background: linear-gradient(135deg, #F59E0B, #D97706); color: white; text-align: center; padding: 2rem;">
            <h3 style="color: white; margin-bottom: 1rem; font-size: 1.3rem;">💡 Dica do Dia</h3>
            <p style="opacity: 0.95; font-size: 1rem; line-height: 1.6; margin-bottom: 1.5rem;">
                Complete suas missões diárias para ganhar bonus de pontos extras e desbloquear conquistas especiais! 
                Cada missão concluída te aproxima do próximo nível.
            </p>
            <div style="background: rgba(255, 255, 255, 0.2); padding: 1rem; border-radius: 12px; backdrop-filter: blur(10px);">
                <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🎮</div>
                <div style="font-size: 0.9rem; opacity: 0.9;">Próximas missões desbloqueiam em breve!</div>
            </div>
        </div>
                
                <div class="card">
    </main>

    <footer class="footer">
        <div class="container text-center">
            <p>&copy; 2025 Handly - Complete missões e torne-se um expert em LIBRAS! 🎮</p>
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
            });        });
    </script>

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
        <a href="missoes.php" class="bottom-nav-item active">
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
</body>
</html>
