<?php
require_once 'config/config.php';
requireLogin();

$modulo = intval($_GET['modulo'] ?? 1);
$licao = intval($_GET['licao'] ?? 0);

// Buscar informações do usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([getUserId()]);
$usuario = $stmt->fetch();

// Buscar sinais do módulo selecionado
$stmt = $pdo->prepare("
    SELECT 
        s.*,
        CASE 
            WHEN pu.concluido = 1 THEN 'concluido'
            WHEN pu.id IS NOT NULL THEN 'iniciado'
            ELSE 'nao_iniciado'
        END as status_usuario
    FROM sinais s
    LEFT JOIN progresso_usuario pu ON s.id = pu.sinal_id AND pu.usuario_id = ?
    WHERE s.modulo = ?
    ORDER BY s.palavra
");
$stmt->execute([getUserId(), $modulo]);
$sinais_modulo = $stmt->fetchAll();

// Agrupar sinais em lições
$lesson_size = 4;
$lessons = array_values(array_chunk($sinais_modulo, $lesson_size));

// Verificar se a lição existe
if (!isset($lessons[$licao])) {
    header('Location: trilha.php?modulo=' . $modulo);
    exit;
}

$current_lesson = $lessons[$licao];

// Buscar distractores (outras palavras) para as alternativas
$stmt = $pdo->prepare("SELECT palavra FROM sinais WHERE modulo <= ? AND id NOT IN (" . str_repeat('?,', count($current_lesson)-1) . "?) ORDER BY RAND() LIMIT 20");
$params = [$modulo];
foreach ($current_lesson as $sinal) {
    $params[] = $sinal['id'];
}
$stmt->execute($params);
$distractores = array_column($stmt->fetchAll(), 'palavra');

function criarAlternativas($palavra_correta, $distractores) {
    $alternativas = [$palavra_correta];
    
    // Adicionar 3 distractores
    $distractores_shuffled = $distractores;
    shuffle($distractores_shuffled);
    
    for ($i = 0; $i < 3 && $i < count($distractores_shuffled); $i++) {
        if ($distractores_shuffled[$i] !== $palavra_correta) {
            $alternativas[] = $distractores_shuffled[$i];
        }
    }
    
    // Garantir que temos 4 alternativas
    while (count($alternativas) < 4) {
        $alternativas[] = "Opção " . count($alternativas);
    }
    
    shuffle($alternativas);
    return $alternativas;
}

// Processar submissão do quiz
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $respostas = $_POST['resposta'] ?? [];
    $corretas = 0;
    $resultados = [];
    
    foreach ($current_lesson as $i => $sinal) {
        $correta = isset($respostas[$i]) && $respostas[$i] === $sinal['palavra'];
        $resultados[$i] = $correta;
        if ($correta) {
            $corretas++;
            // Marcar sinal como aprendido
            $stmt = $pdo->prepare("INSERT INTO progresso_usuario (usuario_id, sinal_id, concluido, data_conclusao, tentativas)
                VALUES (?, ?, 1, NOW(), 1) ON DUPLICATE KEY UPDATE concluido = 1, data_conclusao = NOW(), tentativas = tentativas + 1");
            $stmt->execute([getUserId(), $sinal['id']]);
        }
    }
    
    // Redirecionar para resultado
    $query_params = http_build_query([
        'modulo' => $modulo,
        'licao' => $licao,
        'corretas' => $corretas,
        'total' => count($current_lesson)
    ]);
    header('Location: trilha_licao_resultado.php?' . $query_params);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lição <?php echo $licao + 1; ?> - Módulo <?php echo $modulo; ?> - Handly</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: #ffffff;
            min-height: 100vh;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .quiz-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 1rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .quiz-header {
            background: #ffffff;
            padding: 1rem 0;
            margin-bottom: 2rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .quiz-progress {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
        }
        
        .progress-bar {
            flex: 1;
            height: 12px;
            background: #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: #58cc02;
            transition: width 0.3s ease;
        }
        
        .quiz-counter {
            font-weight: 700;
            color: #3c3c3c;
            font-size: 15px;
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #afafaf;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: background-color 0.2s;
        }
        
        .close-btn:hover {
            background: #f7f7f7;
        }
        
        .quiz-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .question-card {
            background: #ffffff;
            padding: 2rem 1rem;
            margin-bottom: 2rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .question-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .question-title {
            font-size: 24px;
            font-weight: 700;
            color: #3c3c3c;
            margin-bottom: 8px;
        }
        
        .question-subtitle {
            color: #afafaf;
            font-size: 16px;
            font-weight: 400;
        }
        
        .video-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .video-player {
            max-width: 400px;
            width: 100%;
            border-radius: 15px;
            border: 2px solid #e5e7eb;
        }
        
        .video-placeholder {
            width: 300px;
            height: 200px;
            background: #f7f7f7;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            color: #afafaf;
            font-size: 3rem;
            border: 2px solid #e5e7eb;
        }
        
        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .option-btn {
            background: #ffffff;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px 20px;
            font-size: 16px;
            font-weight: 600;
            color: #3c3c3c;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .option-btn:hover {
            border-color: #b4b4b4;
            background: #f7f7f7;
        }
        
        .option-btn.selected {
            border-color: #1cb0f6;
            background: #ddf4ff;
            color: #1cb0f6;
        }
        
        .option-btn.correct {
            border-color: #58cc02;
            background: #d7ffb8;
            color: #58cc02;
        }
        
        .option-btn.incorrect {
            border-color: #ff4b4b;
            background: #ffd7d7;
            color: #ff4b4b;
        }
        
        .option-btn.disabled {
            pointer-events: none;
            opacity: 0.6;
        }
        
        .quiz-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 600px;
            margin: 0 auto;
            width: 100%;
        }
        
        .skip-btn {
            background: none;
            border: none;
            color: #afafaf;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            padding: 0.5rem 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .skip-btn:hover {
            color: #1cb0f6;
        }
        
        .continue-btn {
            background: #e5e5e5;
            color: #afafaf;
            border: none;
            border-radius: 12px;
            padding: 16px 24px;
            font-size: 15px;
            font-weight: 700;
            cursor: not-allowed;
            transition: all 0.2s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            min-width: 120px;
        }
        
        .continue-btn.enabled {
            background: #58cc02;
            color: white;
            cursor: pointer;
        }
        
        .continue-btn:hover.enabled {
            background: #4fb300;
        }
        
        .question-navigation {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 2rem;
        }
        
        .nav-dot {
            width: 16px;
            height: 4px;
            border-radius: 2px;
            background: #e5e7eb;
            transition: background-color 0.2s;
        }
        
        .nav-dot.current {
            background: #1cb0f6;
        }
        
        .nav-dot.completed {
            background: #58cc02;
        }
        
        .feedback-overlay {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            color: white;
            font-weight: 700;
            font-size: 18px;
            text-align: center;
            transform: translateY(100%);
            transition: transform 0.3s ease;
            z-index: 1000;
        }
        
        .feedback-overlay.correct {
            background: #58cc02;
        }
        
        .feedback-overlay.incorrect {
            background: #ff4b4b;
        }
        
        .feedback-overlay.show {
            transform: translateY(0);
        }
        
        .question-card.answered .option-btn {
            pointer-events: none;
        }
        
        @media (max-width: 768px) {
            .quiz-container {
                padding: 1rem;
            }
            
            .question-card {
                padding: 1.5rem 1rem;
            }
            
            .options-grid {
                grid-template-columns: 1fr;
                max-width: 100%;
            }
            
            .quiz-actions {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="quiz-container">
        <!-- Header do Quiz -->
        <div class="quiz-header">
            <div class="quiz-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 0%" id="progressBar"></div>
                </div>
                <div class="quiz-counter">
                    <span id="currentQuestion">1</span> / <?php echo count($current_lesson); ?>
                </div>
            </div>
            <button class="close-btn" onclick="confirmarSaida()">×</button>
        </div>
        
        <!-- Navegação por pontos -->
        <div class="question-navigation">
            <?php for ($i = 0; $i < count($current_lesson); $i++): ?>
                <div class="nav-dot <?php echo $i === 0 ? 'current' : ''; ?>" id="dot-<?php echo $i; ?>"></div>
            <?php endfor; ?>
        </div>
        
        <!-- Conteúdo do Quiz -->
        <div class="quiz-content">
            <form method="POST" id="quizForm">
                <?php foreach ($current_lesson as $index => $sinal): 
                    $alternativas = criarAlternativas($sinal['palavra'], $distractores);
                ?>
                    <div class="question-card" id="question-<?php echo $index; ?>" style="<?php echo $index === 0 ? '' : 'display: none;'; ?>">
                        <div class="question-header">
                            <h2 class="question-title">Qual é o sinal mostrado no vídeo?</h2>
                            <p class="question-subtitle">Selecione a palavra correta</p>
                        </div>
                        
                        <div class="video-container">
                            <?php if ($sinal['video_url']): ?>
                                <video class="video-player" controls loop muted>
                                    <source src="<?php echo htmlspecialchars($sinal['video_url']); ?>" type="video/mp4">
                                    Seu navegador não suporta vídeos.
                                </video>
                            <?php else: ?>
                                <div class="video-placeholder">
                                    🎬
                                </div>
                                <p style="color: #6b7280; margin-top: 1rem;">Vídeo não disponível</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="options-grid">
                            <?php foreach ($alternativas as $opcao): ?>
                                <button type="button" class="option-btn" 
                                        onclick="selecionarOpcao(<?php echo $index; ?>, '<?php echo htmlspecialchars($opcao, ENT_QUOTES); ?>', this)">
                                    <?php echo htmlspecialchars($opcao); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        
                        <input type="hidden" name="resposta[<?php echo $index; ?>]" id="resposta-<?php echo $index; ?>">
                        
                        <div class="quiz-actions">
                            <button type="button" class="skip-btn" onclick="pularQuestao()">Pular</button>
                            <button type="button" class="continue-btn" id="continue-<?php echo $index; ?>" 
                                    onclick="<?php echo $index === count($current_lesson) - 1 ? 'finalizarQuiz()' : 'proximaQuestao()'; ?>">
                                <?php echo $index === count($current_lesson) - 1 ? 'Finalizar' : 'Continuar'; ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </form>
        </div>
    </div>
    
    <!-- Feedback Overlay -->
    <div id="feedbackOverlay" class="feedback-overlay">
        <div id="feedbackMessage"></div>
    </div>
    
    <script>
        let currentQuestionIndex = 0;
        const totalQuestions = <?php echo count($current_lesson); ?>;
        const respostas = {};
        const respostasCorretas = <?php echo json_encode(array_column($current_lesson, 'palavra')); ?>;
        const feedbackResults = []; // Para armazenar resultados para o resumo final
        let autoAdvanceTimer = null; // Para controlar o timer de avanço automático
        
        function atualizarProgresso() {
            const progress = ((currentQuestionIndex + 1) / totalQuestions) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
            document.getElementById('currentQuestion').textContent = currentQuestionIndex + 1;
            
            // Atualizar navegação por pontos
            document.querySelectorAll('.nav-dot').forEach((dot, index) => {
                dot.classList.remove('current', 'completed');
                if (index < currentQuestionIndex) {
                    dot.classList.add('completed');
                } else if (index === currentQuestionIndex) {
                    dot.classList.add('current');
                }
            });
        }
        
        function mostrarFeedback(correto, respostaCorreta = '') {
            const overlay = document.getElementById('feedbackOverlay');
            const message = document.getElementById('feedbackMessage');
            
            if (correto) {
                overlay.className = 'feedback-overlay correct';
                message.textContent = 'Correto!';
            } else {
                overlay.className = 'feedback-overlay incorrect';
                message.textContent = `Incorreto! A resposta correta é: ${respostaCorreta}`;
            }
            
            overlay.classList.add('show');
            
            // Esconder depois de 2 segundos
            setTimeout(() => {
                overlay.classList.remove('show');
            }, 2000);
        }
        
        function selecionarOpcao(questionIndex, opcao, button) {
            // Debug - remover depois
            console.log(`Selecionando opção na questão ${questionIndex}, questão atual: ${currentQuestionIndex}`);
            
            // Não permitir seleção se já respondeu ou se não é a questão atual
            const questionCard = document.getElementById(`question-${questionIndex}`);
            if (questionCard.classList.contains('answered') || questionIndex !== currentQuestionIndex) {
                console.log('Seleção bloqueada - já respondida ou não é questão atual');
                return;
            }
            
            // Remover seleção anterior
            questionCard.querySelectorAll('.option-btn').forEach(btn => {
                btn.classList.remove('selected');
            });
            
            // Selecionar nova opção
            button.classList.add('selected');
            respostas[questionIndex] = opcao;
            
            // Verificar se está correto
            const respostaCorreta = respostasCorretas[questionIndex];
            const correto = opcao === respostaCorreta;
            
            // Armazenar resultado para o resumo final
            feedbackResults[questionIndex] = {
                correto: correto,
                resposta: opcao,
                respostaCorreta: respostaCorreta
            };
            
            // Mostrar feedback visual nas opções
            questionCard.querySelectorAll('.option-btn').forEach(btn => {
                btn.classList.add('disabled');
                const btnText = btn.textContent.trim();
                if (btnText === respostaCorreta) {
                    btn.classList.add('correct');
                } else if (btnText === opcao && !correto) {
                    btn.classList.add('incorrect');
                }
            });
            
            // Marcar questão como respondida
            questionCard.classList.add('answered');
            
            // Mostrar feedback
            mostrarFeedback(correto, respostaCorreta);
            
            // Atualizar input hidden
            document.getElementById(`resposta-${questionIndex}`).value = opcao;
            
            // Habilitar botão continuar
            const continueBtn = document.getElementById(`continue-${questionIndex}`);
            continueBtn.classList.add('enabled');
            
            // Limpar timer anterior se existir
            if (autoAdvanceTimer) {
                clearTimeout(autoAdvanceTimer);
            }
            
            // Auto-avançar após 2.5 segundos (somente se é a questão atual)
            if (questionIndex === currentQuestionIndex) {
                autoAdvanceTimer = setTimeout(() => {
                    if (currentQuestionIndex < totalQuestions - 1) {
                        proximaQuestao();
                    }
                    autoAdvanceTimer = null;
                }, 2500);
            }
        }
        
        function proximaQuestao() {
            // Limpar timer de avanço automático
            if (autoAdvanceTimer) {
                clearTimeout(autoAdvanceTimer);
                autoAdvanceTimer = null;
            }
            
            if (currentQuestionIndex < totalQuestions - 1) {
                // Esconder questão atual
                document.getElementById(`question-${currentQuestionIndex}`).style.display = 'none';
                
                // Mostrar próxima questão
                currentQuestionIndex++;
                const nextQuestion = document.getElementById(`question-${currentQuestionIndex}`);
                nextQuestion.style.display = 'block';
                
                // Atualizar progresso
                atualizarProgresso();
                
                // Iniciar vídeo da próxima questão (se existir e se não foi respondida ainda)
                if (!nextQuestion.classList.contains('answered')) {
                    const nextVideo = nextQuestion.querySelector('video');
                    if (nextVideo) {
                        nextVideo.currentTime = 0;
                        nextVideo.play().catch(() => {
                            // Ignorar erro de autoplay se o navegador bloquear
                        });
                    }
                }
            }
        }
        
        function pularQuestao() {
            if (currentQuestionIndex < totalQuestions - 1) {
                proximaQuestao();
            } else {
                finalizarQuiz();
            }
        }
        
        function finalizarQuiz() {
            document.getElementById('quizForm').submit();
        }
        
        function confirmarSaida() {
            if (confirm('Tem certeza que deseja sair? Seu progresso será perdido.')) {
                window.location.href = `trilha.php?modulo=<?php echo $modulo; ?>`;
            }
        }
        
        // Inicializar
        atualizarProgresso();
        
        // Auto-play do primeiro vídeo
        document.addEventListener('DOMContentLoaded', function() {
            const firstVideo = document.querySelector('#question-0 video');
            if (firstVideo) {
                firstVideo.play().catch(() => {
                    // Ignorar erro de autoplay se o navegador bloquear
                });
            }
        });
    </script>
</body>
</html>
