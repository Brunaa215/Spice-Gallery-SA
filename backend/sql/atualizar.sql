-- Para quem JÁ importou "banco de dados SA.sql": acrescenta as colunas novas.
-- Rode UMA vez: mysql -u root -p < sql/atualizar.sql
SET NAMES utf8mb4;
USE spice_galery;

ALTER TABLE Usuario
    ADD COLUMN ativo          TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN aceiteTermosEm DATETIME   NULL,
    ADD COLUMN criadoEm       DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE Obra
    ADD COLUMN categoria VARCHAR(50) NOT NULL DEFAULT 'pintura',
    ADD COLUMN criadoEm  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD KEY idx_obra_categoria (categoria);

ALTER TABLE Leilao
    ADD COLUMN incremento DECIMAL(10,2) NOT NULL DEFAULT 1.00;

-- Curadoria inicial: admin@spice.com / Admin@12345!
INSERT INTO Usuario (nome, email, senhaHash, perfil, aceiteTermosEm)
VALUES ('Curadoria Spice', 'admin@spice.com', '$2y$10$sPZuRpNwm/YnuSH1l85ZnuNOfYO7poKiw.7bCCSvStY3Fw6suo7Ye', 'ADMIN', NOW())
ON DUPLICATE KEY UPDATE id = id;
