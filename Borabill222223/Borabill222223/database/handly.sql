-- Banco de dados Handly - Sistema de ensino de LIBRAS
-- Data: 11 de Agosto de 2025

CREATE DATABASE IF NOT EXISTS handly CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE handly;

-- Tabela de usuários
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    progresso_modulo INT DEFAULT 1,
    pontuacao_total INT DEFAULT 0,
    avatar VARCHAR(255) DEFAULT NULL
);

-- Tabela de categorias dos sinais
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    descricao TEXT,
    icone VARCHAR(100),
    cor VARCHAR(7) DEFAULT '#20B2AA'
);

-- Tabela de sinais
CREATE TABLE sinais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    palavra VARCHAR(100) NOT NULL,
    descricao TEXT,
    categoria_id INT,
    dificuldade ENUM('facil', 'medio', 'dificil') NOT NULL,
    modulo INT NOT NULL, -- 1, 2 ou 3 baseado na dificuldade
    video_url VARCHAR(255),
    imagem_url VARCHAR(255),
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

-- Tabela de progresso do usuário
CREATE TABLE progresso_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    sinal_id INT,
    concluido BOOLEAN DEFAULT FALSE,
    data_conclusao TIMESTAMP NULL,
    tentativas INT DEFAULT 0,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (sinal_id) REFERENCES sinais(id),
    UNIQUE KEY unique_progresso (usuario_id, sinal_id)
);

-- Tabela de missões
CREATE TABLE missoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    descricao TEXT,
    tipo ENUM('aprender_sinais', 'categoria_completa', 'sequencia_dias', 'pontuacao') NOT NULL,
    objetivo INT NOT NULL, -- quantidade necessária para completar
    recompensa_pontos INT DEFAULT 10,
    modulo_requerido INT DEFAULT 1,
    ativa BOOLEAN DEFAULT TRUE
);

-- Tabela de missões do usuário
CREATE TABLE missoes_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    missao_id INT,
    progresso_atual INT DEFAULT 0,
    concluida BOOLEAN DEFAULT FALSE,
    data_conclusao TIMESTAMP NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (missao_id) REFERENCES missoes(id),
    UNIQUE KEY unique_missao_usuario (usuario_id, missao_id)
);

-- Inserir categorias iniciais
INSERT INTO categorias (nome, descricao, icone) VALUES
('Alfabeto', 'Sinais do alfabeto em LIBRAS', 'alphabet.png'),
('Números', 'Números de 0 a 100 em LIBRAS', 'numbers.png'),
('Família', 'Sinais relacionados à família', 'family.png'),
('Cores', 'Sinais das cores básicas', 'colors.png'),
('Animais', 'Sinais de animais diversos', 'animals.png'),
('Alimentos', 'Sinais de comidas e bebidas', 'food.png'),
('Sentimentos', 'Sinais de emoções e sentimentos', 'emotions.png'),
('Cumprimentos', 'Sinais de saudações e cortesia', 'greetings.png');

-- Inserir alguns sinais de exemplo
INSERT INTO sinais (palavra, descricao, categoria_id, dificuldade, modulo, video_url) VALUES
-- Alfabeto (fácil - módulo 1)
('A', 'Letra A do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/a.mp4'),
('B', 'Letra B do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/b.mp4'),
('C', 'Letra C do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/c.mp4'),

-- Números (fácil - módulo 1)
('1', 'Número um em LIBRAS', 2, 'facil', 1, 'videos/numeros/1.mp4'),
('2', 'Número dois em LIBRAS', 2, 'facil', 1, 'videos/numeros/2.mp4'),
('3', 'Número três em LIBRAS', 2, 'facil', 1, 'videos/numeros/3.mp4'),

-- Família (médio - módulo 2)
('MÃE', 'Sinal para mãe', 3, 'medio', 2, 'videos/familia/mae.mp4'),
('PAI', 'Sinal para pai', 3, 'medio', 2, 'videos/familia/pai.mp4'),
('IRMÃO', 'Sinal para irmão', 3, 'medio', 2, 'videos/familia/irmao.mp4'),

-- Sentimentos (difícil - módulo 3)
('FELIZ', 'Sinal para felicidade', 7, 'dificil', 3, 'videos/sentimentos/feliz.mp4'),
('TRISTE', 'Sinal para tristeza', 7, 'dificil', 3, 'videos/sentimentos/triste.mp4'),
('PREOCUPADO', 'Sinal para preocupação', 7, 'dificil', 3, 'videos/sentimentos/preocupado.mp4');

-- Inserir missões iniciais
INSERT INTO missoes (titulo, descricao, tipo, objetivo, recompensa_pontos, modulo_requerido) VALUES
('Primeiro Passo', 'Aprenda seus primeiros 5 sinais', 'aprender_sinais', 5, 50, 1),
('Mestre do Alfabeto', 'Complete toda a categoria Alfabeto', 'categoria_completa', 1, 100, 1),
('Dedicação', 'Estude por 3 dias consecutivos', 'sequencia_dias', 3, 75, 1),
('Explorador', 'Aprenda 20 sinais diferentes', 'aprender_sinais', 20, 200, 2),
('Família Unida', 'Complete toda a categoria Família', 'categoria_completa', 3, 150, 2),
('Persistente', 'Acumule 500 pontos', 'pontuacao', 500, 100, 2),
('Expert em LIBRAS', 'Aprenda 50 sinais diferentes', 'aprender_sinais', 50, 500, 3),
('Mestre dos Sentimentos', 'Complete toda a categoria Sentimentos', 'categoria_completa', 7, 300, 3);
