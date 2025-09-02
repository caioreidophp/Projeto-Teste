-- Banco de dados Handly - Sistema de ensino de LIBRAS
-- Data: 12 de Agosto de 2025
-- Script completo para criação e população do banco

-- Criar e usar o banco de dados
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

-- ========== INSERÇÃO DE DADOS ==========

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

-- Inserir todos os sinais (36 sinais no total)
INSERT INTO sinais (palavra, descricao, categoria_id, dificuldade, modulo, video_url) VALUES
-- Alfabeto (6 letras - fácil - módulo 1)
('A', 'Letra A do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/a.mp4'),
('B', 'Letra B do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/b.mp4'),
('C', 'Letra C do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/c.mp4'),
('D', 'Letra D do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/d.mp4'),
('E', 'Letra E do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/e.mp4'),
('F', 'Letra F do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/f.mp4'),

-- Números (6 números - fácil/médio - módulo 1/2)
('1', 'Número um em LIBRAS', 2, 'facil', 1, 'videos/numeros/1.mp4'),
('2', 'Número dois em LIBRAS', 2, 'facil', 1, 'videos/numeros/2.mp4'),
('3', 'Número três em LIBRAS', 2, 'facil', 1, 'videos/numeros/3.mp4'),
('4', 'Número quatro em LIBRAS', 2, 'facil', 1, 'videos/numeros/4.mp4'),
('5', 'Número cinco em LIBRAS', 2, 'facil', 1, 'videos/numeros/5.mp4'),
('10', 'Número dez em LIBRAS', 2, 'medio', 2, 'videos/numeros/10.mp4'),

-- Família (6 sinais - médio - módulo 2)
('MÃE', 'Sinal para mãe', 3, 'medio', 2, 'videos/familia/mae.mp4'),
('PAI', 'Sinal para pai', 3, 'medio', 2, 'videos/familia/pai.mp4'),
('IRMÃO', 'Sinal para irmão', 3, 'medio', 2, 'videos/familia/irmao.mp4'),
('IRMÃ', 'Sinal para irmã', 3, 'medio', 2, 'videos/familia/irma.mp4'),
('AVÔ', 'Sinal para avô', 3, 'medio', 2, 'videos/familia/avo.mp4'),
('AVÓ', 'Sinal para avó', 3, 'medio', 2, 'videos/familia/avo.mp4'),

-- Cores (3 sinais - fácil/médio - módulo 1/2)
('AZUL', 'Sinal para a cor azul', 4, 'facil', 1, 'videos/cores/azul.mp4'),
('VERMELHO', 'Sinal para a cor vermelha', 4, 'facil', 1, 'videos/cores/vermelho.mp4'),
('AMARELO', 'Sinal para a cor amarela', 4, 'medio', 2, 'videos/cores/amarelo.mp4'),

-- Animais (3 sinais - médio - módulo 2)
('CACHORRO', 'Sinal para cachorro', 5, 'medio', 2, 'videos/animais/cachorro.mp4'),
('GATO', 'Sinal para gato', 5, 'medio', 2, 'videos/animais/gato.mp4'),
('PÁSSARO', 'Sinal para pássaro', 5, 'medio', 2, 'videos/animais/passaro.mp4'),

-- Alimentos (3 sinais - médio/difícil - módulo 2/3)
('ÁGUA', 'Sinal para água', 6, 'medio', 2, 'videos/alimentos/agua.mp4'),
('COMIDA', 'Sinal para comida', 6, 'medio', 2, 'videos/alimentos/comida.mp4'),
('CHOCOLATE', 'Sinal para chocolate', 6, 'dificil', 3, 'videos/alimentos/chocolate.mp4'),

-- Sentimentos (6 sinais - difícil - módulo 3)
('FELIZ', 'Sinal para felicidade', 7, 'dificil', 3, 'videos/sentimentos/feliz.mp4'),
('TRISTE', 'Sinal para tristeza', 7, 'dificil', 3, 'videos/sentimentos/triste.mp4'),
('PREOCUPADO', 'Sinal para preocupação', 7, 'dificil', 3, 'videos/sentimentos/preocupado.mp4'),
('BRAVO', 'Sinal para raiva/bravo', 7, 'dificil', 3, 'videos/sentimentos/bravo.mp4'),
('SURPRESO', 'Sinal para surpresa', 7, 'dificil', 3, 'videos/sentimentos/surpreso.mp4'),
('NERVOSO', 'Sinal para nervosismo', 7, 'dificil', 3, 'videos/sentimentos/nervoso.mp4'),

-- Cumprimentos (3 sinais - fácil/médio - módulo 1/2)
('OLÁ', 'Sinal de cumprimento olá', 8, 'facil', 1, 'videos/cumprimentos/ola.mp4'),
('TCHAU', 'Sinal de despedida tchau', 8, 'facil', 1, 'videos/cumprimentos/tchau.mp4'),
('OBRIGADO', 'Sinal para agradecer', 8, 'medio', 2, 'videos/cumprimentos/obrigado.mp4');

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

-- ========== CONSULTAS DE VERIFICAÇÃO ==========

-- Mostrar todas as categorias
SELECT * FROM categorias;

-- Mostrar todos os sinais organizados por categoria
SELECT 
    s.id,
    s.palavra,
    s.descricao,
    c.nome as categoria,
    s.dificuldade,
    s.modulo,
    s.video_url
FROM sinais s
JOIN categorias c ON s.categoria_id = c.id
ORDER BY c.id, s.palavra;

-- Mostrar todas as missões
SELECT * FROM missoes;

-- Estatísticas do banco
SELECT 
    'Categorias' as tabela, 
    COUNT(*) as total 
FROM categorias
UNION ALL
SELECT 
    'Sinais' as tabela, 
    COUNT(*) as total 
FROM sinais
UNION ALL
SELECT 
    'Missões' as tabela, 
    COUNT(*) as total 
FROM missoes;

-- Contagem de sinais por dificuldade
SELECT 
    dificuldade,
    COUNT(*) as quantidade
FROM sinais
GROUP BY dificuldade
ORDER BY FIELD(dificuldade, 'facil', 'medio', 'dificil');

-- Contagem de sinais por categoria
SELECT 
    c.nome as categoria,
    COUNT(s.id) as quantidade_sinais
FROM categorias c
LEFT JOIN sinais s ON c.id = s.categoria_id
GROUP BY c.id, c.nome
ORDER BY c.nome;

-- Inserir letras G até Z no alfabeto
INSERT INTO sinais (palavra, descricao, categoria_id, dificuldade, modulo, video_url) VALUES
('G', 'Letra G do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/g.mp4'),
('H', 'Letra H do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/h.mp4'),
('I', 'Letra I do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/i.mp4'),
('J', 'Letra J do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/j.mp4'),
('K', 'Letra K do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/k.mp4'),
('L', 'Letra L do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/l.mp4'),
('M', 'Letra M do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/m.mp4'),
('N', 'Letra N do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/n.mp4'),
('O', 'Letra O do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/o.mp4'),
('P', 'Letra P do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/p.mp4'),
('Q', 'Letra Q do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/q.mp4'),
('R', 'Letra R do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/r.mp4'),
('S', 'Letra S do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/s.mp4'),
('T', 'Letra T do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/t.mp4'),
('U', 'Letra U do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/u.mp4'),
('V', 'Letra V do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/v.mp4'),
('W', 'Letra W do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/w.mp4'),
('X', 'Letra X do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/x.mp4'),
('Y', 'Letra Y do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/y.mp4'),
('Z', 'Letra Z do alfabeto em LIBRAS', 1, 'facil', 1, 'videos/alfabeto/z.mp4');
