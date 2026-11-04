CREATE DATABASE IF NOT EXISTS spice_galery
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE spice_galery;

-- 1. TABELA USUARIO (Tabela independente)
CREATE TABLE Usuario (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senhaHash VARCHAR(255) NOT NULL,
    perfil VARCHAR(50) NOT NULL -- Representando EnumPerfil
);

-- 2. TABELA OBRA (Depende de Usuario)
CREATE TABLE Obra (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    urlImagem VARCHAR(255),
    status VARCHAR(50) NOT NULL, -- Representando EnumStatus
    
    -- Chave Estrangeira
    id_usuario BIGINT NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id) ON DELETE CASCADE
);

-- 3. TABELA LEILAO (Depende de Obra)
CREATE TABLE Leilao (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    dataInicio DATETIME NOT NULL,
    dataFim DATETIME NOT NULL,
    valorInicial DOUBLE NOT NULL,
    status VARCHAR(50) NOT NULL, -- Representando EnumLeilao
    
    -- Chave Estrangeira
    id_obra BIGINT NOT NULL,
    FOREIGN KEY (id_obra) REFERENCES Obra(id) ON DELETE CASCADE
);

-- 4. TABELA AVALIACAO (Depende de Usuario e Obra)
CREATE TABLE Avaliacao (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nota INT NOT NULL CHECK (nota >= 1 AND nota <= 5),
    comentario TEXT,
    dataHora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    -- Chaves Estrangeiras
    id_usuario BIGINT NOT NULL,
    id_obra BIGINT NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id) ON DELETE CASCADE,
    FOREIGN KEY (id_obra) REFERENCES Obra(id) ON DELETE CASCADE
);
-- 5. TABELA LANCE (Depende de Usuario e Leilao)
CREATE TABLE Lance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    valor DECIMAL(10,2) NOT NULL,
    dataHora DATETIME NOT NULL,
    
    -- Chaves Estrangeiras
    id_usuario BIGINT NOT NULL,
    id_leilao BIGINT NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id) ON DELETE CASCADE,
    FOREIGN KEY (id_leilao) REFERENCES Leilao(id) ON DELETE CASCADE
);
