<?php
require_once 'config/config.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $erro = 'E-mail e senha são obrigatórios.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, nome, senha FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();
            
            if ($usuario && password_verify($senha, $usuario['senha'])) {
                $_SESSION['user_id'] = $usuario['id'];
                $_SESSION['user_name'] = $usuario['nome'];
                
                // Redirecionar para o home
                redirect('home.php');
            } else {
                $erro = 'E-mail ou senha incorretos.';
            }
        } catch (PDOException $e) {
            $erro = 'Erro ao fazer login. Tente novamente.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Handly</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
</head>
<body>    <header class="header">
        <div class="nav-container">
            <a href="index.php" class="logo">Handly</a>
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php">Início</a></li>
                    <li><a href="cadastro.php">Criar Conta</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container" style="max-width: 500px; margin-top: 3rem;">
        <div class="card">
            <div class="card-header text-center">
                <h1 class="card-title">Entrar</h1>
                <p style="color: var(--medium-gray); margin-top: 0.5rem;">
                    Faça login para continuar aprendendo LIBRAS
                </p>
            </div>

            <?php if ($erro): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 1rem; border-radius: var(--border-radius); margin-bottom: 1.5rem; border: 1px solid #f5c6cb;">
                    <?php echo htmlspecialchars($erro); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
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
                        autofocus
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
                        placeholder="Digite sua senha"
                    >
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-large btn-block">
                        Entrar
                    </button>
                </div>

                <div class="text-center">
                    <p style="color: var(--medium-gray);">
                        Não tem uma conta? 
                        <a href="cadastro.php" style="color: var(--primary-color); text-decoration: none; font-weight: 500;">
                            Cadastre-se aqui
                        </a>
                    </p>
                </div>
            </form>
        </div>

        <!-- Informações adicionais -->
        <div class="card" style="margin-top: 2rem; text-align: center;">
            <h3 style="color: var(--primary-color); margin-bottom: 1rem;">
                Primeiro acesso?
            </h3>
            <p style="margin-bottom: 1.5rem; color: var(--medium-gray);">
                Crie sua conta gratuita e tenha acesso a todo o conteúdo da plataforma Handly.
            </p>
            <a href="cadastro.php" class="btn btn-outline btn-large">
                Criar Conta Grátis
            </a>
        </div>

        <!-- Benefícios -->
        <div class="card" style="margin-top: 2rem;">
            <h3 style="color: var(--primary-color); margin-bottom: 1rem; text-align: center;">
                Continue de onde parou:
            </h3>            <div style="display: grid; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span style="color: var(--primary-color); font-size: 1rem;">💾</span>
                    <span>Seu progresso é salvo automaticamente</span>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span style="color: var(--primary-color); font-size: 1rem;">🎯</span>
                    <span>Missões personalizadas baseadas no seu nível</span>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span style="color: var(--primary-color); font-size: 1rem;">📱</span>
                    <span>Acesse de qualquer dispositivo</span>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span style="color: var(--primary-color); font-size: 1rem;">📊</span>
                    <span>Acompanhe suas conquistas e estatísticas</span>
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
        // Foco automático no campo de e-mail
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            if (emailInput && !emailInput.value) {
                emailInput.focus();
            }
        });

        // Salvar e-mail no localStorage para conveniência (se o usuário desejar)
        const emailInput = document.getElementById('email');
        const formLogin = document.querySelector('form');

        // Carregar e-mail salvo
        const emailSalvo = localStorage.getItem('handly_email');
        if (emailSalvo && !emailInput.value) {
            emailInput.value = emailSalvo;
        }

        // Salvar e-mail ao submeter (apenas se login for bem-sucedido)
        formLogin.addEventListener('submit', function() {
            if (emailInput.value) {
                localStorage.setItem('handly_email', emailInput.value);
            }
        });

        // Enter para submeter o formulário
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                formLogin.submit();
            }
        });
    </script>
</body>
</html>
