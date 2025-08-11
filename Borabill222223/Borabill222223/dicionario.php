<?php
require_once 'config/config.php';
requireLogin();

$categoria_selecionada = $_GET['categoria'] ?? '';
$busca = $_GET['busca'] ?? '';
$dificuldade = $_GET['dificuldade'] ?? '';

// Buscar categorias
$stmt = $pdo->prepare("SELECT * FROM categorias ORDER BY nome");
$stmt->execute();
$categorias = $stmt->fetchAll();

// Construir query de sinais
$where_conditions = ["s.modulo <= ?"];
$params = [$_SESSION['user_modulo'] ?? 3];

if ($categoria_selecionada) {
    $where_conditions[] = "s.categoria_id = ?";
    $params[] = $categoria_selecionada;
}

if ($busca) {
    $where_conditions[] = "s.palavra LIKE ?";
    $params[] = "%$busca%";
}

if ($dificuldade) {
    $where_conditions[] = "s.dificuldade = ?";
    $params[] = $dificuldade;
}

$where_clause = implode(' AND ', $where_conditions);

// Buscar sinais
$stmt = $pdo->prepare("
    SELECT 
        s.*,
        c.nome as categoria_nome,
        c.icone as categoria_icone,
        CASE 
            WHEN pu.concluido = 1 THEN 'concluido'
            WHEN pu.id IS NOT NULL THEN 'iniciado'
            ELSE 'nao_iniciado'
        END as status_usuario
    FROM sinais s
    JOIN categorias c ON s.categoria_id = c.id
    LEFT JOIN progresso_usuario pu ON s.id = pu.sinal_id AND pu.usuario_id = ?
    WHERE $where_clause
    ORDER BY s.palavra
");
$stmt->execute([getUserId(), ...$params]);
$sinais = $stmt->fetchAll();

// Buscar estatísticas
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_sinais,
        COUNT(pu.sinal_id) as sinais_concluidos
    FROM sinais s
    LEFT JOIN progresso_usuario pu ON s.id = pu.sinal_id AND pu.usuario_id = ? AND pu.concluido = 1
    WHERE s.modulo <= ?
");
$stmt->execute([getUserId(), $_SESSION['user_modulo'] ?? 3]);
$estatisticas = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dicionário - Handly</title>
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
                    <li><a href="dicionario.php" style="background-color: rgba(255,255,255,0.1); border-radius: var(--border-radius);">Dicionário</a></li>
                    <li><a href="trilha.php">Trilha</a></li>
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
                <h1 class="card-title">📚 Dicionário de LIBRAS</h1>
                <p style="color: var(--medium-gray);">
                    Explore nossa coleção de sinais organizados por categorias e níveis de dificuldade
                </p>
            </div>
            
            <!-- Estatísticas -->
            <div class="grid grid-3">
                <div class="text-center">
                    <div class="stat-number"><?php echo count($sinais); ?></div>
                    <div class="stat-label">Sinais Encontrados</div>
                </div>
                <div class="text-center">
                    <div class="stat-number"><?php echo $estatisticas['sinais_concluidos']; ?></div>
                    <div class="stat-label">Sinais Aprendidos</div>
                </div>
                <div class="text-center">
                    <div class="stat-number"><?php echo $estatisticas['total_sinais']; ?></div>
                    <div class="stat-label">Total Disponível</div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card">
            <form method="GET" action="dicionario.php" class="grid grid-4" style="align-items: end;">
                <div class="form-group">
                    <label for="busca" class="form-label">Buscar palavra</label>
                    <input 
                        type="text" 
                        id="busca" 
                        name="busca" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($busca); ?>"
                        placeholder="Digite uma palavra..."
                    >
                </div>
                
                <div class="form-group">
                    <label for="categoria" class="form-label">Categoria</label>
                    <select id="categoria" name="categoria" class="form-input">
                        <option value="">Todas as categorias</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo $categoria['id']; ?>" 
                                    <?php echo $categoria_selecionada == $categoria['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoria['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="dificuldade" class="form-label">Dificuldade</label>
                    <select id="dificuldade" name="dificuldade" class="form-input">
                        <option value="">Todas as dificuldades</option>
                        <option value="facil" <?php echo $dificuldade === 'facil' ? 'selected' : ''; ?>>Fácil</option>
                        <option value="medio" <?php echo $dificuldade === 'medio' ? 'selected' : ''; ?>>Médio</option>
                        <option value="dificil" <?php echo $dificuldade === 'dificil' ? 'selected' : ''; ?>>Difícil</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </div>
            </form>
            
            <?php if ($categoria_selecionada || $busca || $dificuldade): ?>
                <div style="margin-top: 1rem;">
                    <a href="dicionario.php" class="btn btn-outline">Limpar Filtros</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Categorias (se nenhuma estiver selecionada) -->
        <?php if (!$categoria_selecionada && !$busca && !$dificuldade): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Categorias</h2>
            </div>
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
                       style="text-decoration: none; color: inherit;">
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
                        <h3><?php echo htmlspecialchars($categoria['nome']); ?></h3>
                        <p style="margin: 0.5rem 0; color: var(--medium-gray);">
                            <?php echo $stats_categoria['concluidos']; ?> / <?php echo $stats_categoria['total']; ?> sinais
                        </p>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?php echo $percentual; ?>%"></div>
                        </div>
                        <small style="color: var(--medium-gray);"><?php echo $percentual; ?>% completo</small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Lista de sinais -->
        <?php if (!empty($sinais)): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    Sinais
                    <?php if ($categoria_selecionada): ?>
                        <?php
                        $categoria_nome = '';
                        foreach ($categorias as $cat) {
                            if ($cat['id'] == $categoria_selecionada) {
                                $categoria_nome = $cat['nome'];
                                break;
                            }
                        }
                        ?>
                        - <?php echo htmlspecialchars($categoria_nome); ?>
                    <?php endif; ?>
                </h2>
            </div>
            <div class="grid grid-3">
                <?php foreach ($sinais as $sinal): ?>
                    <div class="card sinal-card" 
                         style="cursor: pointer; position: relative;"
                         onclick="abrirModalSinal(<?php echo $sinal['id']; ?>)"
                         data-sinal-id="<?php echo $sinal['id']; ?>">
                        
                        <!-- Status do usuário -->
                        <div style="position: absolute; top: 1rem; right: 1rem; font-size: 1.5rem;">
                            <?php if ($sinal['status_usuario'] === 'concluido'): ?>
                                ✅
                            <?php elseif ($sinal['status_usuario'] === 'iniciado'): ?>
                                🔄
                            <?php else: ?>
                                ⭕
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-center">
                            <h3 style="color: var(--primary-color); margin-bottom: 0.5rem;">
                                <?php echo htmlspecialchars($sinal['palavra']); ?>
                            </h3>
                            
                            <div style="margin-bottom: 1rem;">
                                <span class="badge badge-<?php 
                                    echo $sinal['dificuldade'] === 'facil' ? 'easy' : 
                                         ($sinal['dificuldade'] === 'medio' ? 'medium' : 'hard'); 
                                ?>">
                                    <?php echo ucfirst($sinal['dificuldade']); ?>
                                </span>
                                <span class="badge badge-primary" style="margin-left: 0.5rem;">
                                    Módulo <?php echo $sinal['modulo']; ?>
                                </span>
                            </div>
                            
                            <p style="color: var(--medium-gray); margin-bottom: 1rem;">
                                <?php echo htmlspecialchars($sinal['categoria_nome']); ?>
                            </p>
                            
                            <?php if ($sinal['descricao']): ?>
                                <p style="font-size: 0.9rem; color: var(--medium-gray);">
                                    <?php echo htmlspecialchars($sinal['descricao']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div style="margin-top: 1rem;">
                                <button class="btn btn-primary btn-small" onclick="event.stopPropagation(); abrirModalSinal(<?php echo $sinal['id']; ?>)">
                                    🎥 Ver Sinal
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php elseif ($categoria_selecionada || $busca || $dificuldade): ?>
        <div class="card text-center">
            <h3 style="color: var(--medium-gray);">Nenhum sinal encontrado</h3>
            <p style="color: var(--medium-gray);">Tente ajustar os filtros ou fazer uma nova busca.</p>
            <a href="dicionario.php" class="btn btn-primary">Ver Todos os Sinais</a>
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
            <p>&copy; 2025 Handly - Explore e aprenda novos sinais todos os dias!</p>
        </div>
    </footer>

    <script>
        // Função para abrir modal do sinal
        function abrirModalSinal(sinalId) {
            const modal = document.getElementById('modalSinal');
            const conteudo = document.getElementById('conteudoModalSinal');
            
            // Mostrar loading
            conteudo.innerHTML = '<div class="text-center"><p>Carregando...</p></div>';
            modal.style.display = 'block';
            
            // Buscar dados do sinal via AJAX
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
                                        </div>`
                                    }
                                </div>
                                
                                <div>
                                    <button class="btn btn-primary" onclick="marcarComoAprendido(${sinalId})">
                                        ${data.status_usuario === 'concluido' ? '✅ Aprendido' : '📚 Marcar como Aprendido'}
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
        
        // Função para fechar modal
        function fecharModalSinal() {
            document.getElementById('modalSinal').style.display = 'none';
        }
        
        // Função para marcar sinal como aprendido
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
                    botao.textContent = '✅ Aprendido';
                    botao.disabled = true;
                    
                    // Mostrar mensagem de sucesso
                    alert('Parabéns! Sinal marcado como aprendido.');
                }
            })
            .catch(error => {
                alert('Erro ao marcar sinal. Tente novamente.');
            });
        }
        
        // Fechar modal ao clicar fora dele
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('modalSinal');
            if (event.target === modal) {
                fecharModalSinal();
            }
        });
        
        // Busca em tempo real
        let timeoutBusca;
        document.getElementById('busca').addEventListener('input', function() {
            clearTimeout(timeoutBusca);
            timeoutBusca = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
    </script>
</body>
</html>
