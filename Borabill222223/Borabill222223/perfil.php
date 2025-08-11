<?php
require_once 'config/config.php';
requireLogin();

$erro = '';
$sucesso = '';

// Processar atualização do perfil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    if (empty($nome) || empty($email)) {
        $erro = 'Nome e e-mail são obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } else {
        try {
            // Verificar se o e-mail já existe (para outro usuário)
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$email, getUserId()]);
            
            if ($stmt->fetch()) {
                $erro = 'Este e-mail já está sendo usado por outro usuário.';
            } else {
                // Atualizar dados básicos
                $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ? WHERE id = ?");
                $stmt->execute([$nome, $email, getUserId()]);
                
                // Atualizar senha se fornecida
                if (!empty($nova_senha)) {
                    if (empty($senha_atual)) {
                        $erro = 'Senha atual é obrigatória para alterar a senha.';
                    } elseif (strlen($nova_senha) < 6) {
                        $erro = 'A nova senha deve ter pelo menos 6 caracteres.';
                    } elseif ($nova_senha !== $confirmar_senha) {
                        $erro = 'As senhas não coincidem.';
                    } else {
                        // Verificar senha atual
                        $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
                        $stmt->execute([getUserId()]);
                        $usuario_atual = $stmt->fetch();
                        
                        if (password_verify($senha_atual, $usuario_atual['senha'])) {
                            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                            $stmt->execute([$senha_hash, getUserId()]);
                            
                            if (empty($erro)) {
                                $sucesso = 'Perfil atualizado com sucesso!';
                                $_SESSION['user_name'] = $nome;
                            }
                        } else {
                            $erro = 'Senha atual incorreta.';
                        }
                    }
                } else {
                    $sucesso = 'Perfil atualizado com sucesso!';
                    $_SESSION['user_name'] = $nome;
                }
            }
        } catch (PDOException $e) {
            $erro = 'Erro ao atualizar perfil. Tente novamente.';
        }
    }
}

// Buscar informações completas do usuário
$stmt = $pdo->prepare("
    SELECT 
        u.*,
        COUNT(DISTINCT pu.sinal_id) as sinais_aprendidos,
        COUNT(DISTINCT mu.missao_id) as missoes_concluidas,
        DATEDIFF(CURRENT_DATE, u.data_cadastro) as dias_cadastrado
    FROM usuarios u
    LEFT JOIN progresso_usuario pu ON u.id = pu.usuario_id AND pu.concluido = 1
    LEFT JOIN missoes_usuario mu ON u.id = mu.usuario_id AND mu.concluida = 1
    WHERE u.id = ?
    GROUP BY u.id
");
$stmt->execute([getUserId()]);
$usuario = $stmt->fetch();

// Buscar estatísticas detalhadas
$stmt = $pdo->prepare("
    SELECT 
        s.dificuldade,
        COUNT(*) as quantidade
    FROM progresso_usuario pu
    JOIN sinais s ON pu.sinal_id = s.id
    WHERE pu.usuario_id = ? AND pu.concluido = 1
    GROUP BY s.dificuldade
");
$stmt->execute([getUserId()]);
$stats_dificuldade = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Buscar progresso por categoria
$stmt = $pdo->prepare("
    SELECT 
        c.nome as categoria,
        COUNT(s.id) as total_sinais,
        COUNT(pu.sinal_id) as sinais_concluidos,
        ROUND((COUNT(pu.sinal_id) / COUNT(s.id)) * 100) as percentual
    FROM categorias c
    LEFT JOIN sinais s ON c.id = s.categoria_id
    LEFT JOIN progresso_usuario pu ON s.id = pu.sinal_id AND pu.usuario_id = ? AND pu.concluido = 1
    GROUP BY c.id, c.nome
    ORDER BY percentual DESC
");
$stmt->execute([getUserId()]);
$stats_categorias = $stmt->fetchAll();

// Buscar atividade recente
$stmt = $pdo->prepare("
    SELECT 
        s.palavra,
        c.nome as categoria,
        pu.data_conclusao
    FROM progresso_usuario pu
    JOIN sinais s ON pu.sinal_id = s.id
    JOIN categorias c ON s.categoria_id = c.id
    WHERE pu.usuario_id = ? AND pu.concluido = 1
    ORDER BY pu.data_conclusao DESC
    LIMIT 10
");
$stmt->execute([getUserId()]);
$atividade_recente = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - Handly</title>
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
                    <li><a href="perfil.php" style="background-color: rgba(255,255,255,0.1); border-radius: var(--border-radius);">Perfil</a></li>
                    <li><a href="logout.php" class="btn btn-outline">Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <!-- Cabeçalho do Perfil -->
        <div class="card" style="background: linear-gradient(135deg, var(--primary-color), var(--accent-color)); color: white;">
            <div class="text-center">
                <div style="width: 100px; height: 100px; background: rgba(255,255,255,0.2); border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; font-size: 3rem;">
                    👤
                </div>
                <h1 style="color: white; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($usuario['nome']); ?></h1>
                <p style="opacity: 0.9;">Membro desde <?php echo formatDate($usuario['data_cadastro']); ?></p>
                <p style="opacity: 0.9;">Estudando há <?php echo $usuario['dias_cadastrado']; ?> dias</p>
            </div>
        </div>

        <!-- Estatísticas Gerais -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">📊 Suas Estatísticas</h2>
            </div>
            
            <div class="grid grid-4">
                <div class="text-center">
                    <div class="stat-number"><?php echo $usuario['sinais_aprendidos']; ?></div>
                    <div class="stat-label">Sinais Aprendidos</div>
                </div>
                <div class="text-center">
                    <div class="stat-number"><?php echo $usuario['pontuacao_total']; ?></div>
                    <div class="stat-label">Pontos Totais</div>
                </div>
                <div class="text-center">
                    <div class="stat-number"><?php echo $usuario['missoes_concluidas']; ?></div>
                    <div class="stat-label">Missões Completas</div>
                </div>
                <div class="text-center">
                    <div class="stat-number"><?php echo $usuario['progresso_modulo']; ?></div>
                    <div class="stat-label">Módulo Atual</div>
                </div>
            </div>
        </div>

        <div class="grid grid-2">
            <!-- Formulário de Edição -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">✏️ Editar Perfil</h2>
                </div>

                <?php if ($erro): ?>
                    <div style="background-color: #f8d7da; color: #721c24; padding: 1rem; border-radius: var(--border-radius); margin-bottom: 1.5rem; border: 1px solid #f5c6cb;">
                        <?php echo htmlspecialchars($erro); ?>
                    </div>
                <?php endif; ?>

                <?php if ($sucesso): ?>
                    <div style="background-color: #d4edda; color: #155724; padding: 1rem; border-radius: var(--border-radius); margin-bottom: 1.5rem; border: 1px solid #c3e6cb;">
                        <?php echo htmlspecialchars($sucesso); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="perfil.php">
                    <div class="form-group">
                        <label for="nome" class="form-label">Nome Completo</label>
                        <input 
                            type="text" 
                            id="nome" 
                            name="nome" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($usuario['nome']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">E-mail</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($usuario['email']); ?>"
                            required
                        >
                    </div>

                    <hr style="margin: 2rem 0; border: 1px solid var(--border-color);">
                    
                    <h4 style="color: var(--primary-color); margin-bottom: 1rem;">Alterar Senha (opcional)</h4>

                    <div class="form-group">
                        <label for="senha_atual" class="form-label">Senha Atual</label>
                        <input 
                            type="password" 
                            id="senha_atual" 
                            name="senha_atual" 
                            class="form-input" 
                            placeholder="Digite sua senha atual"
                        >
                    </div>

                    <div class="form-group">
                        <label for="nova_senha" class="form-label">Nova Senha</label>
                        <input 
                            type="password" 
                            id="nova_senha" 
                            name="nova_senha" 
                            class="form-input" 
                            placeholder="Mínimo 6 caracteres"
                            minlength="6"
                        >
                    </div>

                    <div class="form-group">
                        <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                        <input 
                            type="password" 
                            id="confirmar_senha" 
                            name="confirmar_senha" 
                            class="form-input" 
                            placeholder="Digite a nova senha novamente"
                        >
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-large btn-block">
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>

            <!-- Estatísticas Detalhadas -->
            <div>
                <!-- Progresso por Dificuldade -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">🎯 Sinais por Dificuldade</h3>
                    </div>
                    
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span>Fácil</span>
                            <span class="badge badge-easy"><?php echo $stats_dificuldade['facil'] ?? 0; ?> sinais</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span>Médio</span>
                            <span class="badge badge-medium"><?php echo $stats_dificuldade['medio'] ?? 0; ?> sinais</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span>Difícil</span>
                            <span class="badge badge-hard"><?php echo $stats_dificuldade['dificil'] ?? 0; ?> sinais</span>
                        </div>
                    </div>
                </div>

                <!-- Progresso por Categoria -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">📚 Progresso por Categoria</h3>
                    </div>
                    
                    <?php foreach (array_slice($stats_categorias, 0, 5) as $categoria): ?>
                        <div style="margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span style="font-weight: 500;"><?php echo htmlspecialchars($categoria['categoria']); ?></span>
                                <span style="font-size: 0.875rem; color: var(--medium-gray);">
                                    <?php echo $categoria['sinais_concluidos']; ?>/<?php echo $categoria['total_sinais']; ?>
                                </span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" style="width: <?php echo $categoria['percentual']; ?>%"></div>
                            </div>
                            <small style="color: var(--medium-gray);"><?php echo $categoria['percentual']; ?>%</small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Atividade Recente -->
        <?php if (!empty($atividade_recente)): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">🕒 Atividade Recente</h2>
            </div>
            
            <div class="grid grid-2">
                <?php foreach ($atividade_recente as $atividade): ?>
                    <div style="display: flex; align-items: center; padding: 1rem; background-color: var(--light-gray); border-radius: var(--border-radius); margin-bottom: 0.5rem;">
                        <div style="background-color: var(--primary-color); color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1rem; font-size: 1.2rem;">
                            ✅
                        </div>
                        <div style="flex: 1;">
                            <h4 style="margin: 0; color: var(--primary-color);">
                                <?php echo htmlspecialchars($atividade['palavra']); ?>
                            </h4>
                            <p style="margin: 0; font-size: 0.875rem; color: var(--medium-gray);">
                                <?php echo htmlspecialchars($atividade['categoria']); ?> • 
                                <?php echo formatDate($atividade['data_conclusao']); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Conquistas -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">🏆 Conquistas</h2>
            </div>
            
            <div class="grid grid-4">
                <?php
                $conquistas = [
                    ['titulo' => 'Primeiro Passo', 'descricao' => 'Aprendeu o primeiro sinal', 'icone' => '🎯', 'desbloqueada' => $usuario['sinais_aprendidos'] >= 1],
                    ['titulo' => 'Iniciante', 'descricao' => 'Aprendeu 10 sinais', 'icone' => '📚', 'desbloqueada' => $usuario['sinais_aprendidos'] >= 10],
                    ['titulo' => 'Estudioso', 'descricao' => 'Aprendeu 50 sinais', 'icone' => '🎓', 'desbloqueada' => $usuario['sinais_aprendidos'] >= 50],
                    ['titulo' => 'Expert', 'descricao' => 'Aprendeu 100 sinais', 'icone' => '🏆', 'desbloqueada' => $usuario['sinais_aprendidos'] >= 100],
                    ['titulo' => 'Missionário', 'descricao' => 'Completou 5 missões', 'icone' => '🎯', 'desbloqueada' => $usuario['missoes_concluidas'] >= 5],
                    ['titulo' => 'Pontuador', 'descricao' => 'Ganhou 500 pontos', 'icone' => '💎', 'desbloqueada' => $usuario['pontuacao_total'] >= 500],
                    ['titulo' => 'Persistente', 'descricao' => 'Estuda há 7 dias', 'icone' => '📅', 'desbloqueada' => $usuario['dias_cadastrado'] >= 7],
                    ['titulo' => 'Veterano', 'descricao' => 'Estuda há 30 dias', 'icone' => '⭐', 'desbloqueada' => $usuario['dias_cadastrado'] >= 30]
                ];
                ?>
                
                <?php foreach ($conquistas as $conquista): ?>
                    <div class="card text-center" style="<?php echo !$conquista['desbloqueada'] ? 'opacity: 0.5;' : ''; ?>">
                        <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">
                            <?php echo $conquista['desbloqueada'] ? $conquista['icone'] : '🔒'; ?>
                        </div>
                        <h4 style="color: <?php echo $conquista['desbloqueada'] ? 'var(--primary-color)' : 'var(--medium-gray)'; ?>; margin-bottom: 0.5rem;">
                            <?php echo htmlspecialchars($conquista['titulo']); ?>
                        </h4>
                        <p style="color: var(--medium-gray); font-size: 0.875rem;">
                            <?php echo htmlspecialchars($conquista['descricao']); ?>
                        </p>
                        <?php if ($conquista['desbloqueada']): ?>
                            <span class="badge badge-primary" style="margin-top: 0.5rem;">Desbloqueada</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container text-center">
            <p>&copy; 2025 Handly - Seu progresso é nossa inspiração!</p>
        </div>
    </footer>

    <script>
        // Validação da nova senha
        const novaSenha = document.getElementById('nova_senha');
        const confirmarSenha = document.getElementById('confirmar_senha');
        
        function validarSenhas() {
            if (confirmarSenha.value && novaSenha.value !== confirmarSenha.value) {
                confirmarSenha.setCustomValidity('As senhas não coincidem');
            } else {
                confirmarSenha.setCustomValidity('');
            }
        }
        
        novaSenha.addEventListener('input', validarSenhas);
        confirmarSenha.addEventListener('input', validarSenhas);

        // Animação das estatísticas
        document.addEventListener('DOMContentLoaded', function() {
            const statNumbers = document.querySelectorAll('.stat-number');
            
            statNumbers.forEach(stat => {
                const finalValue = parseInt(stat.textContent);
                if (isNaN(finalValue)) return;
                
                let currentValue = 0;
                const increment = Math.ceil(finalValue / 25);
                
                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        currentValue = finalValue;
                        clearInterval(timer);
                    }
                    stat.textContent = currentValue;
                }, 60);
            });
            
            // Animação das barras de progresso
            const progressBars = document.querySelectorAll('.progress-bar');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                
                setTimeout(() => {
                    bar.style.transition = 'width 1s ease-out';
                    bar.style.width = width;
                }, 200);
            });
        });
    </script>
</body>
</html>
