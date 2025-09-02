<?php
require_once 'config/config.php';
requireLogin();

$modulo = intval($_GET['modulo'] ?? 1);
$licao = intval($_GET['licao'] ?? 0);
$corretas = intval($_GET['corretas'] ?? 0);
$total = intval($_GET['total'] ?? 0);

$percentual = $total > 0 ? round(($corretas / $total) * 100) : 0;
$passou = $percentual >= 70; // 70% para passar
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado - Lição <?php echo $licao + 1; ?> - Handly</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: #ffffff;
            min-height: 100vh;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 1rem;
        }
        
        .result-container {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 16px;
            padding: 3rem 2rem;
            text-align: center;
            border: 2px solid #e5e7eb;
        }
        
        .result-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            animation: bounce 0.6s ease-in-out;
        }
        
        .result-title {
            font-size: 32px;
            font-weight: 700;
            color: #3c3c3c;
            margin-bottom: 1rem;
        }
        
        .result-subtitle {
            font-size: 18px;
            color: #afafaf;
            margin-bottom: 2rem;
            font-weight: 400;
        }
        
        .score-display {
            background: #f7f7f7;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid #e5e7eb;
        }
        
        .score-number {
            font-size: 48px;
            font-weight: 700;
            color: <?php echo $passou ? '#58cc02' : '#ff4b4b'; ?>;
            margin-bottom: 0.5rem;
        }
        
        .score-text {
            font-size: 16px;
            color: #afafaf;
            font-weight: 400;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-item {
            background: #f7f7f7;
            border-radius: 8px;
            padding: 1rem;
            border: 1px solid #e5e7eb;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #3c3c3c;
        }
        
        .stat-label {
            font-size: 14px;
            color: #afafaf;
            font-weight: 400;
        }
        
        .actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .btn-primary {
            background: #58cc02;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 16px 24px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.2s;
            text-decoration: none;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-primary:hover {
            background: #4fb300;
        }
        
        .btn-secondary {
            background: white;
            color: #1cb0f6;
            border: 2px solid #1cb0f6;
            border-radius: 12px;
            padding: 14px 24px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-secondary:hover {
            background: #1cb0f6;
            color: white;
        }
        
        .celebration {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
            font-size: 2rem;
            animation: celebrate 2s ease-in-out infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 53%, 80%, 100% {
                transform: translate3d(0,0,0);
            }
            40%, 43% {
                transform: translate3d(0,-20px,0);
            }
            70% {
                transform: translate3d(0,-10px,0);
            }
            90% {
                transform: translate3d(0,-4px,0);
            }
        }
        
        @keyframes celebrate {
            0%, 100% { opacity: 0; transform: scale(0.5) rotate(0deg); }
            50% { opacity: 1; transform: scale(1) rotate(180deg); }
        }
    </style>
</head>
<body>
    <div class="result-container">
        <div class="result-icon">
            <?php echo $passou ? '🎉' : '😔'; ?>
        </div>
        
        <h1 class="result-title">
            <?php echo $passou ? 'Parabéns!' : 'Quase lá!'; ?>
        </h1>
        
        <p class="result-subtitle">
            <?php if ($passou): ?>
                Você completou a Lição <?php echo $licao + 1; ?> com sucesso!
            <?php else: ?>
                Continue praticando para melhorar seu desempenho.
            <?php endif; ?>
        </p>
        
        <div class="score-display">
            <div class="score-number"><?php echo $percentual; ?>%</div>
            <div class="score-text">Pontuação</div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number"><?php echo $corretas; ?></div>
                <div class="stat-label">Corretas</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $total - $corretas; ?></div>
                <div class="stat-label">Erradas</div>
            </div>
        </div>
        
        <div class="actions">
            <?php if ($passou): ?>
                <a href="trilha.php?modulo=<?php echo $modulo; ?>" class="btn-primary">
                    Continuar Trilha
                </a>
                <a href="trilha_licao_quiz.php?modulo=<?php echo $modulo; ?>&licao=<?php echo $licao; ?>" class="btn-secondary">
                    Refazer Lição
                </a>
            <?php else: ?>
                <a href="trilha_licao_quiz.php?modulo=<?php echo $modulo; ?>&licao=<?php echo $licao; ?>" class="btn-primary">
                    Tentar Novamente
                </a>
                <a href="trilha.php?modulo=<?php echo $modulo; ?>" class="btn-secondary">
                    Voltar à Trilha
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($passou): ?>
        <div class="celebration">✨🎊🎈</div>
    <?php endif; ?>
    
    <script>
        // Adicionar pontuação ao usuário se passou
        <?php if ($passou): ?>
        fetch('api/adicionar_pontos.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                pontos: <?php echo $corretas * 10; ?>,
                acao: 'licao_concluida'
            })
        }).catch(console.error);
        <?php endif; ?>
        
        // Som de sucesso/falha (se disponível)
        <?php if ($passou): ?>
            // Som de sucesso
            try {
                const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+D0u2kgBSuBzvLZiTQIFmm98OScTgwOVKjl87lpHgU2jdX1unAhBSl+yO/eizEKHWjE7+OVSwwUarrm65RPEAw6k9ny0YU2CAhwrfVrU1oTHYOFZJKDh4eHeJ2VjomLF3h9nJaKjYaCgIeNfIaBhXqTmlGOgpiSe4yBg4SEhHeJk3uLeZOJjImIhAyDhJKKjYqJhJOLiYmClgmKhJaKhIyUQwwHgIKQhIqOQwwHgIKQhIqO');
                audio.play();
            } catch(e) {}
        <?php else: ?>
            // Som de falha
            try {
                const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+D0u2kgBSuBzvLZiTQIFmm98OScTgwOVKjl87lpHgU2jdX1unAhBSl+yO/eizEKHWjE7+OVSwwUarrm65RPEAw6k9ny0YU2CAhwrfVrU1oTHYOFZJKDh4eHeJ2VjomLF3h9nJaKjYaCgIeNfIaBhXqTmlGOgpiSe4yBg4SEhHeJk3uLeZOJjImIhAyDhJKKjYqJhJOLiYmClgmKhJaKhIyUQwwHgIKQhIqOQwwHgIKQhIqO');
                audio.play();
            } catch(e) {}
        <?php endif; ?>
    </script>
</body>
</html>
