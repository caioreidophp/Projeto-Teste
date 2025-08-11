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

// Verificar se o módulo está liberado
$modulo_liberado = $modulo_selecionado <= $usuario['progresso_modulo'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trilha de Aprendizado - Handly</title>
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
                    <li><a href="trilha.php" style="background-color: rgba(255,255,255,0.1); border-radius: var(--border-radius);">Trilha</a></li>
                    <li><a href="missoes.php">Missões</a></li>
                    <li><a href="perfil.php">Perfil</a></li>
                    <li><a href="logout.php" class="btn btn-outline">Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <!-- Cabeçalho -->
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">🎯 Trilha de Aprendizado</h1>
                <p style="color: var(--medium-gray);">
                    Siga nossa trilha estruturada para aprender LIBRAS de forma progressiva
                </p>
            </div>
        </div>

        <!-- Seletor de módulos -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Módulos de Aprendizado</h2>
            </div>
            
            <div class="grid grid-3">
                <?php foreach ($modulos as $modulo): ?>
                    <?php 
                    $liberado = $modulo['modulo'] <= $usuario['progresso_modulo'];
                    $ativo = $modulo['modulo'] == $modulo_selecionado;
                    ?>
                    <div class="card <?php echo $ativo ? 'border-primary' : ''; ?>" 
                         style="<?php echo !$liberado ? 'opacity: 0.6;' : ''; ?> 
                                <?php echo $ativo ? 'border: 2px solid var(--primary-color);' : ''; ?>">
                        <div class="text-center">
                            <h3 style="color: var(--primary-color); margin-bottom: 1rem;">
                                Módulo <?php echo $modulo['modulo']; ?>
                                <?php if (!$liberado): ?>
                                    🔒
                                <?php elseif ($modulo['percentual'] == 100): ?>
                                    ✅
                                <?php elseif ($ativo): ?>
                                    🎯
                                <?php endif; ?>
                            </h3>
                            
                            <div style="margin-bottom: 1rem;">
                                <span class="badge badge-<?php 
                                    echo $modulo['modulo'] == 1 ? 'easy' : 
                                         ($modulo['modulo'] == 2 ? 'medium' : 'hard'); 
                                ?>">
                                    <?php 
                                    echo $modulo['modulo'] == 1 ? 'Básico' : 
                                         ($modulo['modulo'] == 2 ? 'Intermediário' : 'Avançado'); 
                                    ?>
                                </span>
                            </div>
                            
                            <p style="margin-bottom: 1rem; color: var(--medium-gray);">
                                <?php echo $modulo['sinais_concluidos']; ?> / <?php echo $modulo['total_sinais']; ?> sinais
                            </p>
                            
                            <div class="progress">
                                <div class="progress-bar" style="width: <?php echo $modulo['percentual']; ?>%"></div>
                            </div>
                            
                            <p style="margin: 0.5rem 0; font-weight: bold;">
                                <?php echo $modulo['percentual']; ?>%
                            </p>
                            
                            <?php if ($liberado): ?>
                                <a href="trilha.php?modulo=<?php echo $modulo['modulo']; ?>" 
                                   class="btn <?php echo $ativo ? 'btn-primary' : 'btn-outline'; ?>">
                                    <?php echo $ativo ? 'Módulo Atual' : 
                                               ($modulo['percentual'] > 0 ? 'Continuar' : 'Começar'); ?>
                                </a>
                            <?php else: ?>
                                <p style="color: var(--medium-gray); font-size: 0.875rem;">
                                    Complete o módulo anterior para desbloquear
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

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
                <?php foreach ($sinais_por_categoria as $categoria_data): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
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
                                echo ($icones[$categoria_data['categoria']['icone']] ?? '📖') . ' ';
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
                                        </video>` :
                                        `<div style="padding: 2rem; background-color: var(--light-gray); border-radius: var(--border-radius);">
                                            <p>Vídeo em breve...</p>
                                            <p style="font-size: 2rem; margin: 1rem 0;">👋</p>
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
            });
        });
    </script>
</body>
</html>
