# 🤟 Handly - Plataforma de Ensino de LIBRAS

Uma plataforma educacional profissional e interativa para o aprendizado da Língua Brasileira de Sinais (LIBRAS), desenvolvida em PHP com MySQL.

## 🎯 Características Principais

### 🎨 Design e Interface
- **Paleta de cores profissional**: Turquesa (#20B2AA) como cor primária, com branco e preto como cores secundárias
- **Design responsivo**: Funciona perfeitamente em desktop, tablet e mobile
- **Interface intuitiva**: Navegação clara e experiência do usuário otimizada
- **Animações sutis**: Transições suaves e feedback visual

### 📚 Funcionalidades Educacionais
- **Dicionário interativo**: Mais de 500 sinais organizados por categorias
- **Sistema de trilhas**: 3 módulos progressivos (Básico, Intermediário, Avançado)
- **Vídeos explicativos**: Demonstrações visuais de cada sinal
- **Sistema de dificuldade**: Sinais classificados como Fácil, Médio ou Difícil
- **8 categorias**: Alfabeto, Números, Família, Cores, Animais, Alimentos, Sentimentos, Cumprimentos

### 🏆 Gamificação
- **Sistema de pontos**: Recompensas baseadas na dificuldade dos sinais
- **Missões diversificadas**: Objetivos específicos para manter o engajamento
- **Conquistas**: Sistema de badges e marcos de progresso
- **Acompanhamento detalhado**: Estatísticas completas do progresso do usuário

### 🔐 Sistema de Usuários
- **Cadastro e login seguros**: Autenticação com senhas criptografadas
- **Perfis personalizados**: Informações e estatísticas individuais
- **Progresso salvo**: Continuidade entre sessões
- **Sistema de módulos**: Desbloqueio progressivo de conteúdo

## 🛠️ Tecnologias Utilizadas

- **Backend**: PHP 8.0+
- **Banco de dados**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Arquitetura**: MVC (Model-View-Controller)
- **Segurança**: PDO para prevenção de SQL Injection, senhas hasheadas

## 📋 Pré-requisitos

- **XAMPP** ou ambiente similar (Apache + MySQL + PHP)
- **PHP 8.0** ou superior
- **MySQL 8.0** ou superior
- **Navegador web moderno**

## 🚀 Instalação

### 1. Clone ou baixe o projeto
```bash
# Coloque os arquivos no diretório do XAMPP
C:\xampp\htdocs\Borabill222223\
```

### 2. Configure o banco de dados
1. Inicie o XAMPP (Apache + MySQL)
2. Acesse o phpMyAdmin: `http://localhost/phpmyadmin`
3. Execute o script SQL: `database/handly.sql`

### 3. Configure a conexão
Verifique as configurações em `config/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'handly');
```

### 4. Acesse a plataforma
Abra no navegador: `http://localhost/Borabill222223`

## 📖 Como Usar

### 👤 Para Usuários
1. **Cadastro**: Crie uma conta gratuita na página inicial
2. **Login**: Acesse sua conta pessoal
3. **Home**: Visualize seu progresso e estatísticas
4. **Dicionário**: Explore sinais por categoria ou busca
5. **Trilha**: Siga o aprendizado estruturado por módulos
6. **Missões**: Complete desafios para ganhar pontos
7. **Perfil**: Gerencie suas informações e veja conquistas

### 🎓 Fluxo de Aprendizado
1. **Módulo 1 (Básico)**: Alfabeto, números e sinais fundamentais
2. **Módulo 2 (Intermediário)**: Vocabulário do dia a dia
3. **Módulo 3 (Avançado)**: Sinais complexos e expressões

### 🏆 Sistema de Pontuação
- **Sinais Fáceis**: 10 pontos
- **Sinais Médios**: 20 pontos
- **Sinais Difíceis**: 30 pontos
- **Missões**: Pontos extras variáveis

## 🗂️ Estrutura do Projeto

```
Borabill222223/
├── index.php              # Página inicial (landing page)
├── cadastro.php           # Formulário de cadastro
├── login.php              # Formulário de login
├── logout.php             # Script de logout
├── home.php               # Dashboard principal
├── dicionario.php         # Dicionário de sinais
├── trilha.php             # Trilha de aprendizado
├── missoes.php            # Sistema de missões
├── perfil.php             # Perfil do usuário
├── config/
│   └── config.php         # Configurações e funções
├── assets/
│   └── css/
│       └── style.css      # Estilos principais
├── api/
│   ├── get_sinal.php      # API para buscar sinal
│   └── marcar_sinal_aprendido.php  # API para progresso
└── database/
    └── handly.sql         # Script de criação do banco
```

## 🎨 Paleta de Cores

- **Primária**: `#20B2AA` (Turquesa)
- **Secundária**: `#FFFFFF` (Branco)
- **Terciária**: `#333333` (Preto/Cinza escuro)
- **Accent**: `#17a2b8` (Turquesa escuro)
- **Sucesso**: `#28a745`
- **Alerta**: `#ffc107`
- **Erro**: `#dc3545`

## 📊 Funcionalidades do Banco de Dados

### Tabelas Principais
- **usuarios**: Informações dos usuários
- **categorias**: Categorias dos sinais
- **sinais**: Biblioteca de sinais com vídeos
- **progresso_usuario**: Acompanhamento individual
- **missoes**: Sistema de missões
- **missoes_usuario**: Progresso das missões

### Recursos Avançados
- **Triggers**: Atualização automática de progresso
- **Índices**: Otimização de consultas
- **Relacionamentos**: Integridade referencial
- **Stored Procedures**: Lógica de negócio no banco

## 🔒 Segurança

- **Autenticação**: Sessões PHP seguras
- **Criptografia**: Senhas hasheadas com PASSWORD_DEFAULT
- **Sanitização**: Limpeza de dados de entrada
- **PDO**: Prepared statements contra SQL Injection
- **Validação**: Frontend e backend

## 🌐 Responsividade

- **Mobile-first**: Design otimizado para dispositivos móveis
- **Breakpoints**: Adaptação para tablet e desktop
- **Touch-friendly**: Botões e elementos adequados para touch
- **Performance**: Carregamento otimizado

## 🎮 Gamificação Detalhada

### Tipos de Missões
- **Aprender Sinais**: Completar quantidade específica
- **Categoria Completa**: Finalizar toda uma categoria
- **Sequência de Dias**: Estudar consecutivamente
- **Pontuação**: Acumular pontos específicos

### Conquistas
- **Primeiro Passo**: Primeiro sinal aprendido
- **Iniciante**: 10 sinais
- **Estudioso**: 50 sinais
- **Expert**: 100 sinais
- **Missionário**: 5 missões completas
- **Pontuador**: 500 pontos
- **Persistente**: 7 dias de estudo
- **Veterano**: 30 dias de estudo

## 🔧 Customização

### Adicionar Novos Sinais
1. Acesse a tabela `sinais` no banco de dados
2. Insira: palavra, descrição, categoria, dificuldade, módulo, video_url
3. O sistema atualizará automaticamente as estatísticas

### Criar Novas Missões
1. Insira na tabela `missoes`
2. Defina tipo, objetivo, recompensa e módulo requerido
3. O sistema as disponibilizará automaticamente

### Personalizar Design
- Edite `assets/css/style.css` para alterar aparência
- Modifique variáveis CSS em `:root` para cores
- Ajuste responsividade nos media queries

## 📱 Compatibilidade

- **Navegadores**: Chrome, Firefox, Safari, Edge (últimas versões)
- **Dispositivos**: Desktop, Tablet, Smartphone
- **Sistemas**: Windows, macOS, Linux, iOS, Android

## 🚀 Performance

- **Otimizações**: Consultas SQL otimizadas
- **Caching**: Headers de cache para assets estáticos
- **Compressão**: CSS minificado em produção
- **Lazy loading**: Carregamento sob demanda

## 🔄 Atualizações Futuras

### Funcionalidades Planejadas
- **Sistema de chat**: Comunicação entre usuários
- **Avaliações**: Sistema de rating para sinais
- **Modo offline**: Funcionalidade PWA
- **Reconhecimento de gestos**: IA para validação
- **Multiplayer**: Competições entre usuários

### Melhorias Técnicas
- **API REST**: Endpoints padronizados
- **Framework CSS**: Migração para Bootstrap/Tailwind
- **TypeScript**: Tipagem para JavaScript
- **Docker**: Containerização da aplicação

## 🤝 Contribuição

Para contribuir com o projeto:
1. Faça um fork do repositório
2. Crie uma branch para sua feature
3. Implemente as mudanças
4. Teste thoroughly
5. Envie um pull request

## 📄 Licença

Este projeto foi desenvolvido para fins educacionais como parte do sistema Handly de ensino de LIBRAS.

## 👥 Créditos

- **Desenvolvimento**: Sistema Handly
- **Design**: Interface responsiva e acessível
- **Conteúdo**: Sinais de LIBRAS educacionais
- **Inspiração**: Comunidade surda brasileira

## 📞 Suporte

Para dúvidas ou problemas:
- Verifique este README
- Consulte os comentários no código
- Teste em ambiente de desenvolvimento primeiro

---

**🤟 Handly - Conectando mundos através da LIBRAS**

*Desenvolvido com ❤️ para promover a inclusão e acessibilidade através da educação.*
