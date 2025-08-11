<?php
require_once 'config/config.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    // Validações
    if (empty($nome) || empty($email) || empty($senha) || empty($confirmar_senha)) {
        $erro = 'Todos os campos são obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($senha !== $confirmar_senha) {
        $erro = 'As senhas não coincidem.';
    } else {
        try {
            // Verificar se o e-mail já existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $erro = 'Este e-mail já está cadastrado.';
            } else {
                // Criptografar senha e inserir usuário
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
                $stmt->execute([$nome, $email, $senha_hash]);
                
                $sucesso = 'Conta criada com sucesso! Você pode fazer login agora.';
            }
        } catch (PDOException $e) {
            $erro = 'Erro ao criar conta. Tente novamente.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Handly</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
</head>
<body>
    <header class="header">
        <div class="nav-container">
            <a href="index.php" class="logo">🤟 Handly</a>
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php">Início</a></li>
                    <li><a href="login.php">Entrar</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container" style="max-width: 500px; margin-top: 3rem;">
        <div class="card">
            <div class="card-header text-center">
                <h1 class="card-title">Criar Conta</h1>
                <p style="color: var(--medium-gray); margin-top: 0.5rem;">
                    Junte-se à nossa comunidade e comece a aprender LIBRAS hoje mesmo!
                </p>
            </div>

            <?php if ($erro): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 1rem; border-radius: var(--border-radius); margin-bottom: 1.5rem; border: 1px solid #f5c6cb;">
                    <?php echo htmlspecialchars($erro); ?>
                </div>
            <?php endif; ?>

            <?php if ($sucesso): ?>
                <div style="background-color: #d4edda; color: #155724; padding: 1rem; border-radius: var(--border-radius); margin-bottom: 1.5rem; border: 1px solid #c3e6cb;">
                    <?php echo htmlspecialchars($sucesso); ?>
                    <div style="margin-top: 1rem;">
                        <a href="login.php" class="btn btn-primary">Fazer Login</a>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST" action="cadastro.php">
                    <div class="form-group">
                        <label for="nome" class="form-label">Nome Completo</label>
                        <input 
                            type="text" 
                            id="nome" 
                            name="nome" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>"
                            required
                            placeholder="Digite seu nome completo"
                        >
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">E-mail</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            required
                            placeholder="Digite seu e-mail"
                        >
                    </div>

                    <div class="form-group">
                        <label for="senha" class="form-label">Senha</label>
                        <input 
                            type="password" 
                            id="senha" 
                            name="senha" 
                            class="form-input" 
                            required
                            placeholder="Mínimo 6 caracteres"
                            minlength="6"
                        >
                        <small style="color: var(--medium-gray);">A senha deve ter pelo menos 6 caracteres.</small>
                    </div>

                    <div class="form-group">
                        <label for="confirmar_senha" class="form-label">Confirmar Senha</label>
                        <input 
                            type="password" 
                            id="confirmar_senha" 
                            name="confirmar_senha" 
                            class="form-input" 
                            required
                            placeholder="Digite a senha novamente"
                            minlength="6"
                        >
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-large btn-block">
                            Criar Conta
                        </button>
                    </div>

                    <div class="text-center">
                        <p style="color: var(--medium-gray);">
                            Já tem uma conta? 
                            <a href="login.php" style="color: var(--primary-color); text-decoration: none; font-weight: 500;">
                                Faça login aqui
                            </a>
                        </p>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <!-- Benefícios do cadastro -->
        <div class="card" style="margin-top: 2rem;">
            <h3 style="color: var(--primary-color); margin-bottom: 1rem; text-align: center;">
                O que você ganha ao se cadastrar:
            </h3>
            <div style="display: grid; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span style="color: var(--primary-color); font-size: 1.5rem;">📚</span>
                    <span>Acesso completo ao dicionário de sinais</span>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span style="color: var(--primary-color); font-size: 1.5rem;">🎯</span>
                    <span>Trilha de aprendizado personalizada</span>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span style="color: var(--primary-color); font-size: 1.5rem;">🏆</span>
                    <span>Sistema de missões e conquistas</span>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span style="color: var(--primary-color); font-size: 1.5rem;">📊</span>
                    <span>Acompanhamento do seu progresso</span>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container text-center">
            <p>&copy; 2025 Handly - Plataforma de ensino de LIBRAS.</p>
        </div>
    </footer>

    <script>
        // Validação em tempo real das senhas
        const senha = document.getElementById('senha');
        const confirmarSenha = document.getElementById('confirmar_senha');
        
        function validarSenhas() {
            if (confirmarSenha.value && senha.value !== confirmarSenha.value) {
                confirmarSenha.setCustomValidity('As senhas não coincidem');
            } else {
                confirmarSenha.setCustomValidity('');
            }
        }
        
        senha.addEventListener('input', validarSenhas);
        confirmarSenha.addEventListener('input', validarSenhas);

        // Mostrar força da senha
        senha.addEventListener('input', function() {
            const valor = this.value;
            let forca = 0;
            
            if (valor.length >= 6) forca++;
            if (/[A-Z]/.test(valor)) forca++;
            if (/[0-9]/.test(valor)) forca++;
            if (/[^A-Za-z0-9]/.test(valor)) forca++;
            
            let indicador = document.getElementById('forca-senha');
            if (!indicador) {
                indicador = document.createElement('div');
                indicador.id = 'forca-senha';
                indicador.style.marginTop = '0.5rem';
                this.parentNode.appendChild(indicador);
            }
            
            const cores = ['#dc3545', '#ffc107', '#fd7e14', '#28a745'];
            const textos = ['Fraca', 'Regular', 'Boa', 'Forte'];
            
            if (valor.length > 0) {
                indicador.style.color = cores[forca - 1] || cores[0];
                indicador.textContent = `Força da senha: ${textos[forca - 1] || textos[0]}`;
            } else {
                indicador.textContent = '';
            }
        });
    </script>
</body>
</html>
