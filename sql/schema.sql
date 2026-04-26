-- ReportaBlu SQL

CREATE DATABASE IF NOT EXISTS reportablu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reportablu;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('morador', 'admin') NOT NULL DEFAULT 'morador',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(180) NOT NULL,
    descricao TEXT NOT NULL,
    localizacao VARCHAR(255) NOT NULL,
    status ENUM('aberto', 'em_andamento', 'solucionado', 'fechado') NOT NULL DEFAULT 'aberto',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    CONSTRAINT fk_tickets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_tickets_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_tickets_status (status),
    INDEX idx_tickets_created_at (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ticket_files (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ticket_files_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    INDEX idx_ticket_files_ticket (ticket_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ticket_status_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    status ENUM('aberto', 'em_andamento', 'solucionado', 'fechado') NOT NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ticket_status_history_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    INDEX idx_ticket_status_history_ticket (ticket_id),
    INDEX idx_ticket_status_history_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ticket_protocols (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    protocol_code VARCHAR(40) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ticket_protocols_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    UNIQUE KEY uq_ticket_protocols_ticket (ticket_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ticket_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    department VARCHAR(80) NOT NULL,
    note VARCHAR(255) NULL,
    assigned_by_user_id INT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ticket_assignments_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_ticket_assignments_user FOREIGN KEY (assigned_by_user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_ticket_assignments_ticket (ticket_id),
    INDEX idx_ticket_assignments_department (department)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ticket_responses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    author_user_id INT UNSIGNED NOT NULL,
    author_name VARCHAR(120) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ticket_responses_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_ticket_responses_user FOREIGN KEY (author_user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_ticket_responses_ticket (ticket_id),
    INDEX idx_ticket_responses_created (created_at)
) ENGINE=InnoDB;

INSERT INTO categories (nome)
VALUES
    ('Iluminacao publica'),
    ('Pavimentacao'),
    ('Saneamento'),
    ('Limpeza urbana'),
    ('Seguranca e transito')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- Senha para usuarios de teste: password
INSERT INTO users (nome, email, password_hash, role)
VALUES
    ('Administrador', 'admin@reportablu.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
    ('Morador Demo', 'morador@reportablu.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'morador')
ON DUPLICATE KEY UPDATE nome = VALUES(nome), role = VALUES(role);

INSERT INTO tickets (user_id, category_id, titulo, descricao, localizacao, status, created_at, updated_at, resolved_at)
SELECT
    u.id,
    c.id,
    'Poste sem luz na Rua das Palmeiras',
    'Trecho sem iluminacao publica ha varios dias, trazendo risco para pedestres no periodo noturno.',
    'Rua das Palmeiras, proximo ao numero 210',
    'solucionado',
    NOW() - INTERVAL 12 DAY,
    NOW() - INTERVAL 10 DAY,
    NOW() - INTERVAL 10 DAY
FROM users u
INNER JOIN categories c ON c.nome = 'Iluminacao publica'
WHERE u.email = 'morador@reportablu.local'
  AND NOT EXISTS (
      SELECT 1 FROM tickets WHERE titulo = 'Poste sem luz na Rua das Palmeiras'
  );

INSERT INTO tickets (user_id, category_id, titulo, descricao, localizacao, status, created_at, updated_at, resolved_at)
SELECT
    u.id,
    c.id,
    'Vazamento em via publica',
    'Agua correndo constantemente na calcada com desperdicio e risco de escorregamento.',
    'Rua do Comercio, esquina com Avenida Central',
    'em_andamento',
    NOW() - INTERVAL 4 DAY,
    NOW() - INTERVAL 1 DAY,
    NULL
FROM users u
INNER JOIN categories c ON c.nome = 'Saneamento'
WHERE u.email = 'morador@reportablu.local'
  AND NOT EXISTS (
      SELECT 1 FROM tickets WHERE titulo = 'Vazamento em via publica'
  );

INSERT INTO tickets (user_id, category_id, titulo, descricao, localizacao, status, created_at, updated_at, resolved_at)
SELECT
    u.id,
    c.id,
    'Buraco na avenida principal',
    'Buraco grande comprometendo o fluxo de veiculos e trazendo risco de acidente para motos.',
    'Avenida Brasil, faixa sentido centro',
    'solucionado',
    NOW() - INTERVAL 20 DAY,
    NOW() - INTERVAL 16 DAY,
    NOW() - INTERVAL 16 DAY
FROM users u
INNER JOIN categories c ON c.nome = 'Pavimentacao'
WHERE u.email = 'morador@reportablu.local'
  AND NOT EXISTS (
      SELECT 1 FROM tickets WHERE titulo = 'Buraco na avenida principal'
  );

INSERT INTO ticket_status_history (ticket_id, status, note, created_at)
SELECT
    t.id,
    t.status,
    'Registro inicial do chamado.',
    t.created_at
FROM tickets t
LEFT JOIN ticket_status_history h ON h.ticket_id = t.id
WHERE h.id IS NULL;

INSERT INTO ticket_protocols (ticket_id, protocol_code, created_at)
SELECT
    t.id,
    CONCAT('RB-', DATE_FORMAT(t.created_at, '%Y%m%d'), '-', LPAD(t.id, 6, '0')),
    t.created_at
FROM tickets t
LEFT JOIN ticket_protocols tp ON tp.ticket_id = t.id
WHERE tp.id IS NULL;