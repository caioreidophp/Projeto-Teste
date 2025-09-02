<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handly - Aprenda LIBRAS de forma interativa</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
</head>
<body>    <header class="header">
        <div class="nav-container">
            <a href="index.php" class="logo">Handly</a>
            <nav>
                <ul class="nav-menu">
                    <li><a href="#sobre">Sobre</a></li>
                    <li><a href="#recursos">Recursos</a></li>
                    <li><a href="login.php">Entrar</a></li>
                    <li><a href="cadastro.php" class="btn btn-outline">Criar Conta</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <h1 class="fade-in">Bem-vindo ao Handly</h1>
                <p class="fade-in">Aprenda LIBRAS (Língua Brasileira de Sinais) de forma interativa, divertida e profissional</p>
                <div style="margin-top: 2rem;">
                    <a href="cadastro.php" class="btn btn-large btn-secondary" style="margin-right: 1rem;">Começar Agora</a>
                    <a href="login.php" class="btn btn-large btn-outline">Já tenho conta</a>
                </div>
            </div>
        </section>

        <!-- Sobre o Projeto -->
        <section id="sobre" class="container">
            <div class="card text-center">
                <div class="card-header">
                    <h2 class="card-title">Sobre o Handly</h2>
                </div>
                <p style="font-size: 1.1rem; line-height: 1.8;">
                    O <strong>Handly</strong> é uma plataforma educacional dedicada ao ensino da <strong>LIBRAS</strong> 
                    (Língua Brasileira de Sinais). Nossa missão é tornar o aprendizado da língua de sinais 
                    acessível, interativo e eficaz para todos.
                </p>
                <p style="margin-top: 1.5rem; font-size: 1.1rem; line-height: 1.8;">
                    Através de vídeos explicativos, sistema de progressão gamificado e trilhas de aprendizado 
                    estruturadas, oferecemos uma experiência completa para quem deseja se comunicar em LIBRAS.
                </p>
            </div>
        </section>

        <!-- Sobre LIBRAS -->
        <section class="container">
            <div class="grid grid-2">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">O que é LIBRAS?</h3>
                    </div>
                    <p>
                        A <strong>Língua Brasileira de Sinais (LIBRAS)</strong> é a língua oficial da comunidade 
                        surda brasileira. É uma língua visual-espacial completa, com gramática própria e 
                        reconhecida oficialmente pela Lei 10.436/2002.
                    </p>
                    <p style="margin-top: 1rem;">
                        LIBRAS não é uma versão sinalizada do português, mas sim uma língua independente, 
                        com estrutura e características únicas que refletem a cultura surda brasileira.
                    </p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Por que aprender LIBRAS?</h3>
                    </div>                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 0.5rem;"><strong>Inclusão social:</strong> Comunicar-se com a comunidade surda</li>
                        <li style="margin-bottom: 0.5rem;"><strong>Oportunidades profissionais:</strong> Mercado em crescimento</li>
                        <li style="margin-bottom: 0.5rem;"><strong>Desenvolvimento cognitivo:</strong> Melhora coordenação e memória</li>
                        <li style="margin-bottom: 0.5rem;"><strong>Responsabilidade social:</strong> Contribuir para uma sociedade mais inclusiva</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Recursos da Plataforma -->
        <section id="recursos" class="container">
            <div class="card text-center">
                <div class="card-header">
                    <h2 class="card-title">Recursos da Plataforma</h2>
                </div>
            </div>

            <div class="grid grid-3">                <div class="card text-center">
                    <div class="category-icon"><img src="https://cdn-icons-png.flaticon.com/512/2847/2847502.png" alt="Dicionário" style="width: 60px; height: 60px; object-fit: contain;"></div>
                    <h4>Dicionário Interativo</h4>
                    <p>Mais de 100 sinais organizados por categorias, com vídeos explicativos em alta qualidade e descrições detalhadas.</p>
                </div>                <div class="card text-center">
                    <div class="category-icon"><img src="https://cdn-icons-png.flaticon.com/512/610/610064.png" alt="Trilha" style="width: 60px; height: 60px; object-fit: contain;"></div>
                    <h4>Trilha de Aprendizado</h4>
                    <p>Sistema progressivo dividido em 3 módulos (Básico, Intermediário, Avançado) com dificuldade crescente.</p>
                </div>                <div class="card text-center">
                    <div class="category-icon">🎮</div>
                    <h4>Sistema de Missões</h4>
                    <p>Gamificação com missões diárias, conquistas e sistema de pontuação para manter a motivação.</p>
                </div>                <div class="card text-center">
                    <div class="category-icon">📊</div>
                    <h4>Acompanhamento</h4>
                    <p>Monitore seu progresso em tempo real, veja estatísticas detalhadas e identifique áreas de melhoria.</p>
                </div>                <div class="card text-center">
                    <div class="category-icon">🎥</div>
                    <h4>Vídeos de Qualidade</h4>
                    <p>Todos os sinais são demonstrados por instrutores qualificados com ângulos múltiplos e velocidades variáveis.</p>
                </div>                <div class="card text-center">
                    <div class="category-icon">📱</div>
                    <h4>Acesso Multiplataforma</h4>
                    <p>Estude em qualquer dispositivo - computador, tablet ou smartphone, com sincronização automática.</p>
                </div>
            </div>
        </section>

        <!-- Estatísticas -->
        <section class="container">
            <div class="card">
                <div class="card-header text-center">
                    <h2 class="card-title">Nossa Comunidade</h2>
                </div>
                <div class="grid grid-4 text-center">
                    <div class="stats">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Sinais Cadastrados</span>
                    </div>
                    <div class="stats">
                        <span class="stat-number">8</span>
                        <span class="stat-label">Categorias</span>
                    </div>
                    <div class="stats">
                        <span class="stat-number">3</span>
                        <span class="stat-label">Módulos</span>
                    </div>
                    <div class="stats">
                        <span class="stat-number">24/7</span>
                        <span class="stat-label">Disponível</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Call to Action -->
        <section class="container">
            <div class="card text-center" style="background: linear-gradient(135deg, var(--primary-color), var(--accent-color)); color: white;">
                <h2 style="color: white; margin-bottom: 1rem;">Pronto para começar?</h2>
                <p style="font-size: 1.1rem; margin-bottom: 2rem; opacity: 0.9;">
                    Junte-se a centenas de pessoas que já estão aprendendo LIBRAS conosco. 
                    É gratuito e você pode começar agora mesmo!
                </p>
                <div>
                    <a href="cadastro.php" class="btn btn-large btn-secondary" style="margin-right: 1rem;">Criar Conta Grátis</a>
                    <a href="login.php" class="btn btn-large btn-outline" style="border-color: white; color: white;">Fazer Login</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Handly - Plataforma de ensino de LIBRAS. Todos os direitos reservados.</p>            <p style="margin-top: 0.5rem; opacity: 0.8;">
                Desenvolvido com amor para promover a inclusão e acessibilidade através da educação.
            </p>
        </div>
    </footer>

    <script>
        // Smooth scroll para âncoras
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Animação fade-in nos elementos visíveis
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        }, observerOptions);

        // Observar todos os cards
        document.querySelectorAll('.card').forEach(card => {
            observer.observe(card);
        });
    </script>
</body>
</html>
