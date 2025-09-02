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
            <a href="index.php" class="logo">Handly</a>
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php">Início</a></li>
                    <li><a href="login.php">Entrar</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container" style="max-width: 500px; margin-top: 3rem;">
        <div class="card" style="border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
            <div style="background: linear-gradient(135deg, #10B981, #059669); color: white; padding: 2rem; text-align: center;">
                <h1 style="color: white; margin-bottom: 0.5rem; font-size: 2rem; font-weight: bold;">
                    🌟 Começe Sua Jornada!
                </h1>
                <p style="opacity: 0.9; margin: 0; font-size: 1.1rem;">
                    Junte-se à aventura LIBRAS e desbloqueie um novo mundo! 🚀
                </p>
            </div>
            
            <div style="padding: 2rem;">

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
                        <button type="submit" class="btn btn-large btn-block" style="background: linear-gradient(135deg, #10B981, #059669); border: none; color: white; padding: 1rem; font-size: 1.1rem; font-weight: bold; border-radius: 12px; transition: all 0.3s ease;">
                            ✨ Criar Minha Conta
                        </button>
                    </div>

                    <div class="text-center">
                        <p style="color: var(--medium-gray);">
                            Já tem uma conta? 
                            <a href="login.php" style="color: #10B981; text-decoration: none; font-weight: 600;">
                                🔑 Faça login aqui
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
            <?php endif; ?>
        </div>

        <!-- Benefícios Gamificados -->
        <div class="card" style="background: linear-gradient(135deg, #06B6D4, #0891B2); color: white; border-radius: 20px; padding: 2rem; margin-top: 2rem;">
            <h3 style="color: white; margin-bottom: 1.5rem; text-align: center; font-size: 1.6rem;">
                🎉 Junte-se à Aventura LIBRAS!
            </h3>
            <p style="text-align: center; opacity: 0.9; margin-bottom: 2rem; font-size: 1rem;">
                Cadastre-se agora e desbloqueie um mundo de possibilidades! 🚀
            </p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <div style="background: rgba(255, 255, 255, 0.15); padding: 1.5rem; border-radius: 15px; text-align: center; backdrop-filter: blur(10px);">
                    <div style="margin-bottom: 1rem;"><img src="https://cdn-icons-png.flaticon.com/512/2847/2847502.png" alt="Dicionário" style="width: 60px; height: 60px; object-fit: contain;"></div>
                    <h4 style="color: white; margin: 0 0 0.5rem 0; font-weight: bold;">Dicionário Completo</h4>
                    <p style="opacity: 0.9; font-size: 0.9rem; margin: 0;">Mais de 500 sinais organizados</p>
                </div>
                
                <div style="background: rgba(255, 255, 255, 0.15); padding: 1.5rem; border-radius: 15px; text-align: center; backdrop-filter: blur(10px);">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🎮</div>
                    <h4 style="color: white; margin: 0 0 0.5rem 0; font-weight: bold;">Sistema Gamificado</h4>
                    <p style="opacity: 0.9; font-size: 0.9rem; margin: 0;">Missões, pontos e conquistas</p>
                </div>
                
                <div style="background: rgba(255, 255, 255, 0.15); padding: 1.5rem; border-radius: 15px; text-align: center; backdrop-filter: blur(10px);">
                    <div style="margin-bottom: 1rem;"><img src="https://cdn-icons-png.flaticon.com/512/1356/1356479.png" alt="Foguete" style="width: 60px; height: 60px; object-fit: contain;"></div>
                    <h4 style="color: white; margin: 0 0 0.5rem 0; font-weight: bold;">Trilha Personalizada</h4>
                    <p style="opacity: 0.9; font-size: 0.9rem; margin: 0;">Aprenda no seu ritmo</p>
                </div>
                
                <div style="background: rgba(255, 255, 255, 0.15); padding: 1.5rem; border-radius: 15px; text-align: center; backdrop-filter: blur(10px);">
                    <div style="margin-bottom: 1rem;"><img src="https://cdn-icons-png.flaticon.com/512/7408/7408613.png" alt="Estrela" style="width: 60px; height: 60px; object-fit: contain;"></div>
                    <h4 style="color: white; margin: 0 0 0.5rem 0; font-weight: bold;">Progresso Detalhado</h4>
                    <p style="opacity: 0.9; font-size: 0.9rem; margin: 0;">Acompanhe seu crescimento</p>
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
