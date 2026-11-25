-- Perfil do artista (apelido, especialidade, estilos, foto com moldura, papel de parede, redes sociais):
-- atualiza um banco criado antes disso. Rode UMA vez, depois do atualizar_leilao_minutos.sql:
--   mysql -u root -p < sql/atualizar_perfil.sql
-- Banco novo não precisa disto: o schema.sql já cria tudo.
-- Se rodar de novo, o ALTER falha ("Duplicate column") e nada mais é executado.
SET NAMES utf8mb4;
USE spice_galery;

ALTER TABLE Usuario
  ADD COLUMN apelido       VARCHAR(60)  NULL AFTER saldo,
  ADD COLUMN especialidade VARCHAR(80)  NULL AFTER apelido,
  ADD COLUMN estilos       VARCHAR(255) NULL AFTER especialidade,
  ADD COLUMN fotoPerfil    VARCHAR(255) NULL AFTER estilos,
  ADD COLUMN molduraFoto   VARCHAR(20)  NOT NULL DEFAULT 'camafeu' AFTER fotoPerfil,
  ADD COLUMN wallpaper     VARCHAR(255) NULL AFTER molduraFoto;

CREATE TABLE IF NOT EXISTS RedeSocial (
    id_usuario BIGINT       NOT NULL,
    rede       VARCHAR(20)  NOT NULL,
    url        VARCHAR(255) NOT NULL,
    PRIMARY KEY (id_usuario, rede),
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id) ON DELETE CASCADE
) ENGINE=InnoDB;
