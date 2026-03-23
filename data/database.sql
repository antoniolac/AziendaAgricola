-- ============================================================
-- AZIENDA AGRICOLA - Database Schema + Dati di esempio
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

DROP TABLE IF EXISTS DETTAGLIO_VENDITA;
DROP TABLE IF EXISTS VENDITA;
DROP TABLE IF EXISTS MOV_MAGAZZINO;
DROP TABLE IF EXISTS GIACENZA;
DROP TABLE IF EXISTS E_OUTPUT_DI;
DROP TABLE IF EXISTS E_INPUT_DI;
DROP TABLE IF EXISTS EVENTO;
DROP TABLE IF EXISTS PREZZO_STORICO;
DROP TABLE IF EXISTS PRODOTTO;
DROP TABLE IF EXISTS CATEGORIA;
DROP TABLE IF EXISTS CLIENTE;
DROP TABLE IF EXISTS LUOGO;

-- ============================================================
CREATE TABLE LUOGO (
    id_luogo INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descrizione TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
CREATE TABLE CATEGORIA (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descrizione TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
CREATE TABLE PRODOTTO (
    id_prodotto INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    descrizione TEXT,
    tipo_prodotto ENUM('FRESCO','LAVORATO','RISERVA','CONFEZIONATO') NOT NULL,
    unita_misura ENUM('kg','g','litro','ml','pezzo','bustina','vasetto','bottiglia') NOT NULL DEFAULT 'kg',
    vendita ENUM('a_peso','a_pezzo') NOT NULL DEFAULT 'a_peso',
    quantita_disponibile DECIMAL(10,3) DEFAULT 0,
    stato ENUM('disponibile','esaurito','archiviato') NOT NULL DEFAULT 'disponibile',
    luogo_provenienza VARCHAR(150),
    id_categoria INT NOT NULL,
    FOREIGN KEY (id_categoria) REFERENCES CATEGORIA(id_categoria) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
CREATE TABLE PREZZO_STORICO (
    id_prezzo INT AUTO_INCREMENT PRIMARY KEY,
    prezzo_unitario DECIMAL(10,2) NOT NULL,
    data_inizio DATE NOT NULL,
    data_fine DATE DEFAULT NULL,
    id_prodotto INT NOT NULL,
    FOREIGN KEY (id_prodotto) REFERENCES PRODOTTO(id_prodotto) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
CREATE TABLE EVENTO (
    id_evento INT AUTO_INCREMENT PRIMARY KEY,
    data_evento DATE NOT NULL,
    tipo_evento ENUM('raccolta','smielatura','distillazione','smallatura','sgusciatura','essiccazione','confezionamento','lavorazione_generica') NOT NULL,
    quantita_input DECIMAL(10,3),
    quantita_output DECIMAL(10,3),
    luogo_provenienza VARCHAR(150),
    note TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
CREATE TABLE E_INPUT_DI (
    id_evento INT NOT NULL,
    id_prodotto INT NOT NULL,
    PRIMARY KEY (id_evento, id_prodotto),
    FOREIGN KEY (id_evento) REFERENCES EVENTO(id_evento) ON DELETE CASCADE,
    FOREIGN KEY (id_prodotto) REFERENCES PRODOTTO(id_prodotto) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
CREATE TABLE E_OUTPUT_DI (
    id_evento INT NOT NULL,
    id_prodotto INT NOT NULL,
    PRIMARY KEY (id_evento, id_prodotto),
    FOREIGN KEY (id_evento) REFERENCES EVENTO(id_evento) ON DELETE CASCADE,
    FOREIGN KEY (id_prodotto) REFERENCES PRODOTTO(id_prodotto) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
CREATE TABLE GIACENZA (
    id_giacenza INT AUTO_INCREMENT PRIMARY KEY,
    quantita_disponibile DECIMAL(10,3) NOT NULL DEFAULT 0,
    data_aggiornamento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    id_prodotto INT NOT NULL UNIQUE,
    FOREIGN KEY (id_prodotto) REFERENCES PRODOTTO(id_prodotto) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
CREATE TABLE MOV_MAGAZZINO (
    id_movimento INT AUTO_INCREMENT PRIMARY KEY,
    data_movimento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipo_movimento ENUM('carico','scarico','trasferimento','rettifica') NOT NULL,
    quantita DECIMAL(10,3) NOT NULL,
    note TEXT,
    id_prodotto INT NOT NULL,
    FOREIGN KEY (id_prodotto) REFERENCES PRODOTTO(id_prodotto) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
CREATE TABLE CLIENTE (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    nickname VARCHAR(100),
    telefono VARCHAR(20),
    email VARCHAR(150),
    tipo_cliente ENUM('privato','famiglia','amico','collega','rivenditore','occasionale') NOT NULL DEFAULT 'privato'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
CREATE TABLE VENDITA (
    id_vendita INT AUTO_INCREMENT PRIMARY KEY,
    data_vendita DATE NOT NULL,
    totale_calcolato DECIMAL(10,2) NOT NULL DEFAULT 0,
    totale_pagato DECIMAL(10,2) NOT NULL DEFAULT 0,
    note TEXT,
    id_cliente INT NOT NULL,
    FOREIGN KEY (id_cliente) REFERENCES CLIENTE(id_cliente) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
CREATE TABLE DETTAGLIO_VENDITA (
    id_dettaglio INT AUTO_INCREMENT PRIMARY KEY,
    quantita DECIMAL(10,3) NOT NULL,
    prezzo_unitario DECIMAL(10,2) NOT NULL,
    totale_riga DECIMAL(10,2) NOT NULL,
    tipo_vendita ENUM('fresco_sfuso','confezionato','riserva_sfuso') NOT NULL,
    id_vendita INT NOT NULL,
    id_prodotto INT NOT NULL,
    id_prezzo INT,
    FOREIGN KEY (id_vendita) REFERENCES VENDITA(id_vendita) ON DELETE CASCADE,
    FOREIGN KEY (id_prodotto) REFERENCES PRODOTTO(id_prodotto) ON DELETE RESTRICT,
    FOREIGN KEY (id_prezzo) REFERENCES PREZZO_STORICO(id_prezzo) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DATI DI ESEMPIO
-- ============================================================

INSERT INTO LUOGO (nome, descrizione) VALUES
('Dispensa Principale', 'Magazzino centrale di conservazione'),
('Campo Nord', 'Terreno coltivato a nord'),
('Laboratorio', 'Laboratorio di lavorazione e trasformazione'),
('Punto Vendita', 'Area vendita diretta al cliente'),
('Serra', 'Serra per piante aromatiche e fiori');

INSERT INTO CATEGORIA (nome, descrizione) VALUES
('Frutta Fresca', 'Frutta raccolta e venduta fresca'),
('Verdure e Ortaggi', 'Verdure, ortaggi e legumi freschi'),
('Miele e Derivati', 'Miele grezzo, confezionato e derivati'),
('Olio e Derivati', 'Olio extravergine, oleoliti, oli essenziali'),
('Marmellate e Conserve', 'Confetture, vincotto, cotognata'),
('Frutta Secca e Disidratata', 'Fichi secchi, mandorle, frutta disidratata'),
('Piante Aromatiche', 'Erbe fresche ed essiccate, tisane'),
('Cosmetici Naturali', 'Unguenti, saponette, idrolati'),
('Fiori Essiccati', 'Fiori secchi decorativi e per tisane'),
('Distillati e Succhi', 'Succhi di frutta, distillati, idrolati');

-- Prodotti FRESCHI
INSERT INTO PRODOTTO (nome, descrizione, tipo_prodotto, unita_misura, vendita, quantita_disponibile, stato, luogo_provenienza, id_categoria) VALUES
('Mele', 'Mele del frutteto biologiche', 'FRESCO', 'kg', 'a_peso', 0, 'disponibile', 'Campo Nord', 1),
('Fichi freschi', 'Fichi bianchi e neri', 'FRESCO', 'kg', 'a_peso', 0, 'disponibile', 'Campo Nord', 1),
('Zucchine', 'Zucchine fresche di stagione', 'FRESCO', 'kg', 'a_peso', 0, 'disponibile', 'Campo Nord', 2),
('Pomodori', 'Pomodori cuore di bue', 'FRESCO', 'kg', 'a_peso', 0, 'disponibile', 'Campo Nord', 2),
('Mandorle con guscio', 'Mandorle appena raccolte', 'FRESCO', 'kg', 'a_peso', 0, 'disponibile', 'Campo Nord', 6),
('Rosmarino fresco', 'Rosmarino appena tagliato', 'FRESCO', 'pezzo', 'a_pezzo', 0, 'disponibile', 'Serra', 7),
('Lavanda fresca', 'Mazzi di lavanda profumata', 'FRESCO', 'pezzo', 'a_pezzo', 0, 'disponibile', 'Serra', 7);

-- Prodotti RISERVA
INSERT INTO PRODOTTO (nome, descrizione, tipo_prodotto, unita_misura, vendita, quantita_disponibile, stato, luogo_provenienza, id_categoria) VALUES
('Miele grezzo (secchio)', 'Miele non filtrato in secchio 25kg', 'RISERVA', 'kg', 'a_peso', 47.5, 'disponibile', 'Laboratorio', 3),
('Olio EVO (bidone)', 'Olio extravergine in bidone', 'RISERVA', 'litro', 'a_peso', 62.0, 'disponibile', 'Dispensa Principale', 4),
('Mandorle sgusciate (sacco)', 'Mandorle sgusciate in sacco juta', 'RISERVA', 'kg', 'a_peso', 15.3, 'disponibile', 'Dispensa Principale', 6),
('Fiori di lavanda (contenitore)', 'Fiori essiccati in contenitore vetro', 'RISERVA', 'kg', 'a_peso', 3.2, 'disponibile', 'Laboratorio', 9),
('Cotognata (contenitore latta)', 'Cotognata in grandi contenitori', 'RISERVA', 'kg', 'a_peso', 8.0, 'disponibile', 'Dispensa Principale', 5),
('Fichi secchi (contenitore latta)', 'Fichi naturali essiccati', 'RISERVA', 'kg', 'a_peso', 12.0, 'disponibile', 'Dispensa Principale', 6),
('Oleolito di Iperico (bottiglia)', 'Oleolito grezzo in bottiglia grande', 'RISERVA', 'ml', 'a_peso', 2500, 'disponibile', 'Laboratorio', 8);

-- Prodotti CONFEZIONATI
INSERT INTO PRODOTTO (nome, descrizione, tipo_prodotto, unita_misura, vendita, quantita_disponibile, stato, luogo_provenienza, id_categoria) VALUES
('Miele di Arancio (vasetto 250g)', 'Miele monoflora arancio', 'CONFEZIONATO', 'vasetto', 'a_pezzo', 24, 'disponibile', 'Laboratorio', 3),
('Miele di Tiglio (vasetto 500g)', 'Miele monoflora tiglio', 'CONFEZIONATO', 'vasetto', 'a_pezzo', 18, 'disponibile', 'Laboratorio', 3),
('Olio EVO (bottiglia 500ml)', 'Olio extravergine biologico', 'CONFEZIONATO', 'bottiglia', 'a_pezzo', 36, 'disponibile', 'Laboratorio', 4),
('Olio EVO (lattina 750ml)', 'Olio EVO in lattina da regalo', 'CONFEZIONATO', 'bottiglia', 'a_pezzo', 12, 'disponibile', 'Laboratorio', 4),
('Marmellata di fichi (vasetto 250g)', 'Confettura di fichi biologica', 'CONFEZIONATO', 'vasetto', 'a_pezzo', 30, 'disponibile', 'Laboratorio', 5),
('Marmellata di mele cotogne (vasetto 250g)', 'Confettura di mele cotogne', 'CONFEZIONATO', 'vasetto', 'a_pezzo', 20, 'disponibile', 'Laboratorio', 5),
('Vincotto di Fichi (bottiglia 250ml)', 'Vincotto artigianale di fichi', 'CONFEZIONATO', 'bottiglia', 'a_pezzo', 15, 'disponibile', 'Laboratorio', 5),
('Fichi secchi mandorlati (vaschetta 200g)', 'Fichi farciti con mandorle', 'CONFEZIONATO', 'vasetto', 'a_pezzo', 22, 'disponibile', 'Laboratorio', 6),
('Mandorle sgusciate (bustina 200g)', 'Mandorle pelate e confezionate', 'CONFEZIONATO', 'bustina', 'a_pezzo', 40, 'disponibile', 'Laboratorio', 6),
('Lavanda essiccata (bustina 30g)', 'Fiori di lavanda essiccati', 'CONFEZIONATO', 'bustina', 'a_pezzo', 35, 'disponibile', 'Laboratorio', 9),
('Rosmarino essiccato (bustina 20g)', 'Rosmarino aromatico essiccato', 'CONFEZIONATO', 'bustina', 'a_pezzo', 28, 'disponibile', 'Laboratorio', 7),
('Unguento di Iperico (vasetto 50ml)', 'Balsamo lenitivo all iperico', 'CONFEZIONATO', 'vasetto', 'a_pezzo', 16, 'disponibile', 'Laboratorio', 8),
('Unguento di Calendula (vasetto 50ml)', 'Crema calmante alla calendula', 'CONFEZIONATO', 'vasetto', 'a_pezzo', 14, 'disponibile', 'Laboratorio', 8),
('Saponetta alla Lavanda', 'Sapone naturale artigianale', 'CONFEZIONATO', 'pezzo', 'a_pezzo', 50, 'disponibile', 'Laboratorio', 8),
('Idrolato di Rosa (spray 100ml)', 'Acqua floreale di rosa', 'CONFEZIONATO', 'bottiglia', 'a_pezzo', 20, 'disponibile', 'Laboratorio', 10),
('Olio Essenziale di Lavanda (10ml)', 'Olio essenziale puro', 'CONFEZIONATO', 'bottiglia', 'a_pezzo', 25, 'disponibile', 'Laboratorio', 4);

INSERT INTO PREZZO_STORICO (prezzo_unitario, data_inizio, id_prodotto) VALUES
(1.50,'2024-01-01',1),(2.00,'2024-01-01',2),(1.20,'2024-01-01',3),(1.80,'2024-01-01',4),
(4.00,'2024-01-01',5),(0.80,'2024-01-01',6),(1.50,'2024-01-01',7),
(12.00,'2024-01-01',8),(8.00,'2024-01-01',9),(10.00,'2024-01-01',10),
(15.00,'2024-01-01',11),(8.00,'2024-01-01',12),(9.00,'2024-01-01',13),(0.04,'2024-01-01',14),
(6.00,'2024-01-01',15),(9.00,'2024-01-01',16),(7.50,'2024-01-01',17),(11.00,'2024-01-01',18),
(4.50,'2024-01-01',19),(4.50,'2024-01-01',20),(6.00,'2024-01-01',21),(5.00,'2024-01-01',22),
(4.00,'2024-01-01',23),(3.50,'2024-01-01',24),(2.50,'2024-01-01',25),(8.00,'2024-01-01',26),
(8.00,'2024-01-01',27),(4.50,'2024-01-01',28),(7.00,'2024-01-01',29),(12.00,'2024-01-01',30);

INSERT INTO GIACENZA (quantita_disponibile, id_prodotto) VALUES
(47.5,8),(62.0,9),(15.3,10),(3.2,11),(8.0,12),(12.0,13),(2500,14),
(24,15),(18,16),(36,17),(12,18),(30,19),(20,20),(15,21),(22,22),
(40,23),(35,24),(28,25),(16,26),(14,27),(50,28),(20,29),(25,30);

INSERT INTO CLIENTE (nome, nickname, telefono, email, tipo_cliente) VALUES
('ClienteX', NULL, NULL, NULL, 'occasionale'),
('Maria Rossi', 'famiglia', '333-1234567', 'maria.rossi@example.com', 'famiglia'),
('Luca Bianchi', 'amico', '347-9876543', 'luca.bianchi@example.com', 'amico'),
('Azienda BioMarket', NULL, '080-5556677', 'ordini@biomarket.it', 'rivenditore'),
('Giulia Verdi', 'collega', '389-4445566', NULL, 'collega'),
('Franco Neri', NULL, '320-1112233', 'franco.neri@example.com', 'privato');

INSERT INTO VENDITA (data_vendita, totale_calcolato, totale_pagato, note, id_cliente) VALUES
('2024-09-15', 31.50, 30.00, 'Sconto amici', 3),
('2024-10-02', 52.00, 52.00, NULL, 4),
('2024-11-10', 18.50, 18.50, NULL, 2),
('2024-12-20', 24.00, 20.00, 'Omaggio saponetta', 5),
('2025-01-08', 15.00, 15.00, NULL, 1),
('2025-03-14', 47.50, 47.50, 'Ordine primaverile', 4);

INSERT INTO DETTAGLIO_VENDITA (quantita, prezzo_unitario, totale_riga, tipo_vendita, id_vendita, id_prodotto, id_prezzo) VALUES
(2,6.00,12.00,'confezionato',1,15,15),(1,9.00,9.00,'confezionato',1,16,16),(1,8.00,8.00,'confezionato',1,26,26),
(4,7.50,30.00,'confezionato',2,17,17),(2,11.00,22.00,'confezionato',2,18,18),
(1,4.50,4.50,'confezionato',3,19,19),(2,6.00,12.00,'confezionato',3,21,21),(0.25,8.00,2.00,'riserva_sfuso',3,9,9),
(2,6.00,12.00,'confezionato',4,15,15),(3,4.00,12.00,'confezionato',4,23,23),
(3,5.00,15.00,'confezionato',5,22,22),
(3,7.50,22.50,'confezionato',6,17,17),(5,5.00,25.00,'confezionato',6,22,22);

INSERT INTO MOV_MAGAZZINO (data_movimento, tipo_movimento, quantita, note, id_prodotto) VALUES
('2024-08-20 10:00:00','carico',50.0,'Smielatura estate 2024',8),
('2024-09-01 09:30:00','carico',70.0,'Raccolta olive e frangitura',9),
('2024-09-15 14:00:00','scarico',2.5,'Vendita diretta',8),
('2024-10-02 11:00:00','scarico',8.0,'Vendita BioMarket',9),
('2024-11-05 16:00:00','carico',12.0,'Sgusciatura mandorle',10),
('2025-01-15 10:00:00','carico',5.0,'Confezionamento miele arancio',15);

INSERT INTO EVENTO (data_evento, tipo_evento, quantita_input, quantita_output, luogo_provenienza, note) VALUES
('2024-08-15','smielatura',60.0,50.0,'Laboratorio','Smielatura arnie estate 2024'),
('2024-09-01','smallatura',80.0,70.0,'Laboratorio','Prima spremitura olive Ogliarola'),
('2024-10-10','essiccazione',5.0,3.2,'Serra','Essiccazione fiori lavanda 2024'),
('2024-10-20','sgusciatura',20.0,15.3,'Laboratorio','Sgusciatura mandorle autunno 2024'),
('2024-11-01','confezionamento',10.0,NULL,'Laboratorio','Confezionamento marmellate di fichi');

INSERT INTO E_INPUT_DI (id_evento, id_prodotto) VALUES (1,8),(2,9),(3,11),(4,10),(5,13);
INSERT INTO E_OUTPUT_DI (id_evento, id_prodotto) VALUES (1,15),(1,16),(2,17),(2,18),(3,24),(4,23),(5,19);

-- ============================================================
-- TRIGGERS
-- ============================================================

DELIMITER //

CREATE TRIGGER after_dettaglio_vendita_insert
AFTER INSERT ON DETTAGLIO_VENDITA
FOR EACH ROW
BEGIN
    IF NEW.tipo_vendita != 'fresco_sfuso' THEN
        UPDATE GIACENZA
        SET quantita_disponibile = quantita_disponibile - NEW.quantita,
            data_aggiornamento = NOW()
        WHERE id_prodotto = NEW.id_prodotto;

        UPDATE PRODOTTO
        SET quantita_disponibile = quantita_disponibile - NEW.quantita
        WHERE id_prodotto = NEW.id_prodotto;

        UPDATE PRODOTTO
        SET stato = 'esaurito'
        WHERE id_prodotto = NEW.id_prodotto AND quantita_disponibile <= 0;
    END IF;
END //

CREATE TRIGGER after_mov_magazzino_insert
AFTER INSERT ON MOV_MAGAZZINO
FOR EACH ROW
BEGIN
    DECLARE v_delta DECIMAL(10,3);
    IF NEW.tipo_movimento = 'carico' THEN
        SET v_delta = NEW.quantita;
    ELSEIF NEW.tipo_movimento = 'scarico' THEN
        SET v_delta = -NEW.quantita;
    ELSE
        SET v_delta = 0;
    END IF;

    INSERT INTO GIACENZA (quantita_disponibile, data_aggiornamento, id_prodotto)
    VALUES (v_delta, NOW(), NEW.id_prodotto)
    ON DUPLICATE KEY UPDATE
        quantita_disponibile = quantita_disponibile + v_delta,
        data_aggiornamento = NOW();

    UPDATE PRODOTTO
    SET quantita_disponibile = quantita_disponibile + v_delta
    WHERE id_prodotto = NEW.id_prodotto;

    UPDATE PRODOTTO
    SET stato = CASE WHEN quantita_disponibile > 0 THEN 'disponibile' ELSE 'esaurito' END
    WHERE id_prodotto = NEW.id_prodotto;
END //

DELIMITER ;