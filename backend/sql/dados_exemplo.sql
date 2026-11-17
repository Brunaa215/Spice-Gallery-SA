-- Dados de exemplo (opcional), os mesmos artistas e obras do protótipo do front.
-- Rode depois do schema.sql: mysql -u root -p < sql/dados_exemplo.sql
-- Senha de todos os artistas abaixo: Spice@12345
-- As datas dos leilões são relativas a NOW(), então sempre há um ao vivo, um em breve e um encerrado.
SET NAMES utf8mb4;
USE spice_galery;

SET @hash = '$2y$10$/IGeh6j6hOYyHBBPNECfku4I2A2bvIPFGnFhL3r63HuKRz.4Xh4ie';

INSERT INTO Usuario (nome, email, senhaHash, perfil, aceiteTermosEm, criadoEm) VALUES
('Lia Moraes',    'lia@spice.com',   @hash, 'ARTISTA', NOW(), NOW() - INTERVAL 200 DAY),
('Caio Ventura',  'caio@spice.com',  @hash, 'ARTISTA', NOW(), NOW() - INTERVAL 180 DAY),
('Nina Takeda',   'nina@spice.com',  @hash, 'ARTISTA', NOW(), NOW() - INTERVAL 150 DAY),
('Rafa Quintino', 'rafa@spice.com',  @hash, 'ARTISTA', NOW(), NOW() - INTERVAL 120 DAY),
('Bento Ayala',   'bento@spice.com', @hash, 'ARTISTA', NOW(), NOW() - INTERVAL 90 DAY);

SET @lia   = (SELECT id FROM Usuario WHERE email = 'lia@spice.com');
SET @caio  = (SELECT id FROM Usuario WHERE email = 'caio@spice.com');
SET @nina  = (SELECT id FROM Usuario WHERE email = 'nina@spice.com');
SET @rafa  = (SELECT id FROM Usuario WHERE email = 'rafa@spice.com');
SET @bento = (SELECT id FROM Usuario WHERE email = 'bento@spice.com');

INSERT INTO Obra (titulo, descricao, urlImagem, status, categoria, criadoEm, id_usuario) VALUES
('Sol de Açafrão', 'Pintei pensando nas manhãs de feira com a minha avó: o calor do açafrão, o azul do toldo, a sombra comprida de quem chega cedo. Formas simples, porque a memória também simplifica.', 'assets/img/obras/sol-de-acafrao.webp', 'PUBLICADA', 'digital', NOW() - INTERVAL 30 DAY, @lia),
('Dunas de Canela', 'Acrílica sobre tela. As dunas da Joaquina vistas do ônibus, no fim da tarde.', 'assets/img/obras/dunas-de-canela.webp', 'PUBLICADA', 'pintura', NOW() - INTERVAL 29 DAY, @caio),
('Órbitas Lentas', 'Gravura digital sobre o tempo que a gente leva para voltar ao mesmo lugar.', 'assets/img/obras/orbitas-lentas.webp', 'PUBLICADA', 'gravura', NOW() - INTERVAL 28 DAY, @nina),
('Mercado, 6h', 'Óleo sobre tela. O mercado municipal antes de abrir, quando só os feirantes estão lá.', 'assets/img/obras/mercado-6h.webp', 'PUBLICADA', 'pintura', NOW() - INTERVAL 27 DAY, @lia),
('Jardim de Cardamomo', 'Aquarela feita no quintal da minha tia, com as folhas ainda molhadas.', 'assets/img/obras/jardim-de-cardamomo.webp', 'PUBLICADA', 'aquarela', NOW() - INTERVAL 26 DAY, @bento),
('Campo Vermelho', 'Óleo sobre linho. Grandes campos de vermelho, preto e rosa com bordas esfumadas.', 'assets/img/obras/campo-vermelho.webp', 'PUBLICADA', 'pintura', NOW() - INTERVAL 25 DAY, @rafa),
('Mármore Pimenta', 'Fotografia de uma bancada de cozinha, bem de perto.', 'assets/img/obras/marmore-pimenta.webp', 'PUBLICADA', 'fotografia', NOW() - INTERVAL 24 DAY, @nina),
('Arcos do Meio-dia', 'Serigrafia sobre as sombras dos arcos da estação ao meio-dia.', 'assets/img/obras/arcos-do-meio-dia.webp', 'PUBLICADA', 'gravura', NOW() - INTERVAL 23 DAY, @caio),
('Pólen', 'Ilustração digital. Um enxame de cor espalhado pelo vento.', 'assets/img/obras/polen.webp', 'PUBLICADA', 'digital', NOW() - INTERVAL 22 DAY, @bento),
('Noite de Cravo', 'Óleo sobre tela. A primeira obra que pendurei numa parede de verdade.', 'assets/img/obras/noite-de-cravo.webp', 'PUBLICADA', 'pintura', NOW() - INTERVAL 21 DAY, @rafa),
('Maré Alta', 'Pintura digital da baía da Babitonga em dia de vento sul.', 'assets/img/obras/mare-alta.webp', 'PUBLICADA', 'digital', NOW() - INTERVAL 20 DAY, @lia),
('Eclipse Doce', 'Gravura em metal. Dois círculos que quase se tocam.', 'assets/img/obras/eclipse-doce.webp', 'PUBLICADA', 'gravura', NOW() - INTERVAL 19 DAY, @nina);

SET @sol     = (SELECT id FROM Obra WHERE titulo = 'Sol de Açafrão');
SET @dunas   = (SELECT id FROM Obra WHERE titulo = 'Dunas de Canela');
SET @jardim  = (SELECT id FROM Obra WHERE titulo = 'Jardim de Cardamomo');
SET @campo   = (SELECT id FROM Obra WHERE titulo = 'Campo Vermelho');
SET @marmore = (SELECT id FROM Obra WHERE titulo = 'Mármore Pimenta');
SET @noite   = (SELECT id FROM Obra WHERE titulo = 'Noite de Cravo');
SET @eclipse = (SELECT id FROM Obra WHERE titulo = 'Eclipse Doce');
SET @polen   = (SELECT id FROM Obra WHERE titulo = 'Pólen');

INSERT INTO Avaliacao (nota, comentario, dataHora, id_usuario, id_obra) VALUES
(5, 'A paleta é perfeita. Esse amarelo contra o azul tem a temperatura exata de manhã de verão.', NOW() - INTERVAL 3 DAY, @caio, @sol),
(5, 'Lembra cartaz da Bauhaus, mas com afeto. As linhas no canto parecem um caderno de receitas.', NOW() - INTERVAL 10 DAY, @nina, @sol),
(4, 'Composição forte. Senti falta de alguma textura, mas talvez a limpeza seja justamente o ponto.', NOW() - INTERVAL 18 DAY, @rafa, @sol),
(5, 'Dá vontade de entrar no quadro.', NOW() - INTERVAL 5 DAY, @lia, @jardim),
(5, 'As folhas parecem respirar.', NOW() - INTERVAL 6 DAY, @nina, @jardim),
(4, 'Bonito o calor dessas dunas.', NOW() - INTERVAL 7 DAY, @bento, @dunas),
(5, 'O vermelho grita.', NOW() - INTERVAL 2 DAY, @lia, @campo),
(4, 'Queria ver de perto as bordas.', NOW() - INTERVAL 4 DAY, @caio, @noite),
(5, 'Esse azul é noite de verdade.', NOW() - INTERVAL 8 DAY, @bento, @noite),
(5, 'Simples e certeiro.', NOW() - INTERVAL 9 DAY, @rafa, @eclipse),
(4, 'Parece pólen mesmo.', NOW() - INTERVAL 1 DAY, @caio, @polen);

-- Leilões: ao vivo, em breve, encerrado e um esperando a curadoria
INSERT INTO Leilao (dataInicio, dataFim, valorInicial, incremento, status, id_obra) VALUES
(NOW() - INTERVAL 3 DAY,  NOW() + INTERVAL 12 DAY, 600, 50, 'APROVADO', @campo),
(NOW() - INTERVAL 1 DAY,  NOW() + INTERVAL 20 DAY, 400, 20, 'APROVADO', @eclipse),
(NOW() + INTERVAL 15 DAY, NOW() + INTERVAL 20 DAY, 600, 30, 'APROVADO', @noite),
(NOW() - INTERVAL 30 DAY, NOW() - INTERVAL 10 DAY, 900, 50, 'APROVADO', @marmore),
(NOW() + INTERVAL 5 DAY,  NOW() + INTERVAL 10 DAY, 300, 10, 'AGUARDANDO_CURADORIA', @polen);

SET @l_campo   = (SELECT id FROM Leilao WHERE id_obra = @campo);
SET @l_eclipse = (SELECT id FROM Leilao WHERE id_obra = @eclipse);
SET @l_marmore = (SELECT id FROM Leilao WHERE id_obra = @marmore);

INSERT INTO Lance (valor, dataHora, id_usuario, id_leilao) VALUES
(900,  NOW() - INTERVAL 50 HOUR, @lia,   @l_campo),
(1100, NOW() - INTERVAL 40 HOUR, @caio,  @l_campo),
(1250, NOW() - INTERVAL 30 HOUR, @nina,  @l_campo),
(1400, NOW() - INTERVAL 10 HOUR, @bento, @l_campo),
(1480, NOW() - INTERVAL 2 HOUR,  @caio,  @l_campo),
(420,  NOW() - INTERVAL 20 HOUR, @rafa,  @l_eclipse),
(500,  NOW() - INTERVAL 5 HOUR,  @lia,   @l_eclipse),
(1500, NOW() - INTERVAL 25 DAY,  @caio,  @l_marmore),
(2150, NOW() - INTERVAL 12 DAY,  @rafa,  @l_marmore);
