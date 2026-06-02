-- ============================================================
-- AdHub – Agency-Client Marketing Campaign Manager
-- Database: adhub
-- ============================================================

CREATE DATABASE IF NOT EXISTS adhub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE adhub;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120)  NOT NULL,
    email       VARCHAR(180)  NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,          -- bcrypt hash
    role        ENUM('admin','client') NOT NULL DEFAULT 'client',
    avatar      VARCHAR(255)  DEFAULT NULL,
    is_active   TINYINT(1)    NOT NULL DEFAULT 1,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: clients
-- ============================================================
CREATE TABLE clients (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT          DEFAULT NULL,          -- linked login account
    company_name    VARCHAR(180) NOT NULL,
    contact_person  VARCHAR(120) NOT NULL,
    email           VARCHAR(180) NOT NULL,
    phone           VARCHAR(30)  DEFAULT NULL,
    address         TEXT         DEFAULT NULL,
    retainer_budget DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    notes           TEXT         DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: campaigns
-- ============================================================
CREATE TABLE campaigns (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    client_id       INT          NOT NULL,
    title           VARCHAR(220) NOT NULL,
    description     TEXT         DEFAULT NULL,
    status          ENUM('Planning','Active','Under Review','Approved','Completed') NOT NULL DEFAULT 'Planning',
    budget          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    start_date      DATE         DEFAULT NULL,
    end_date        DATE         DEFAULT NULL,
    created_by      INT          DEFAULT NULL,   -- admin user id
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id)  REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)   ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: milestones
-- ============================================================
CREATE TABLE milestones (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id     INT          NOT NULL,
    title           VARCHAR(220) NOT NULL,
    description     TEXT         DEFAULT NULL,
    status          ENUM('Pending','Under Review','Approved','Revision Requested','Completed') NOT NULL DEFAULT 'Pending',
    due_date        DATE         DEFAULT NULL,
    client_comment  TEXT         DEFAULT NULL,
    reviewed_at     DATETIME     DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: assets
-- ============================================================
CREATE TABLE assets (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id     INT          NOT NULL,
    client_id       INT          NOT NULL,
    uploaded_by     INT          DEFAULT NULL,   -- user id
    original_name   VARCHAR(255) NOT NULL,
    stored_name     VARCHAR(255) NOT NULL,        -- hashed filename on disk
    file_type       VARCHAR(80)  DEFAULT NULL,
    file_size       BIGINT       DEFAULT 0,
    description     TEXT         DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id)  REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id)    REFERENCES clients(id)   ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by)  REFERENCES users(id)     ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: time_logs
-- ============================================================
CREATE TABLE time_logs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id     INT          NOT NULL,
    logged_by       INT          DEFAULT NULL,   -- admin/staff user id
    hours           DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    hourly_rate     DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    amount          DECIMAL(10,2) GENERATED ALWAYS AS (hours * hourly_rate) STORED,
    description     TEXT         DEFAULT NULL,
    log_date        DATE         NOT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (logged_by)   REFERENCES users(id)     ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: reports  (saved report metadata)
-- ============================================================
CREATE TABLE reports (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id     INT          NOT NULL,
    generated_by    INT          DEFAULT NULL,
    title           VARCHAR(255) NOT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id)   REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by)  REFERENCES users(id)     ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Admin user  (password: Admin@1234)
INSERT INTO users (name, email, password, role) VALUES
('Agency Admin',  'admin@adhub.com',   '$2y$10$E6XyLT2XvC2Nh5QXu02XcObkPYzzimvwgChIyINXEr5ouz9bxAmgW', 'admin'),
('Sarah Connor',  'sarah@adhub.com',   '$2y$10$E6XyLT2XvC2Nh5QXu02XcObkPYzzimvwgChIyINXEr5ouz9bxAmgW', 'admin');

-- Client users  (password: Client@1234)
INSERT INTO users (name, email, password, role) VALUES
('Marcus Webb',   'marcus@techcorp.com',   '$2y$10$zs7o3GfTZLyqLdbOrC2AjeqCciT.nN9RtPSJbsDcUsjNfY.F4Yh2e', 'client'),
('Diana Park',    'diana@novabrand.com',   '$2y$10$zs7o3GfTZLyqLdbOrC2AjeqCciT.nN9RtPSJbsDcUsjNfY.F4Yh2e', 'client'),
('James Holloway','james@apexmedia.com',   '$2y$10$zs7o3GfTZLyqLdbOrC2AjeqCciT.nN9RtPSJbsDcUsjNfY.F4Yh2e', 'client');

-- Clients
INSERT INTO clients (user_id, company_name, contact_person, email, phone, address, retainer_budget, notes) VALUES
(3, 'TechCorp Solutions',    'Marcus Webb',    'marcus@techcorp.com',  '+63 917 123 4567', '5F Ayala Tower, BGC, Taguig',           85000.00, 'Q3 brand refresh campaign'),
(4, 'Nova Brand Studio',     'Diana Park',     'diana@novabrand.com',  '+63 918 234 5678', '3F Rockwell Center, Makati',            60000.00, 'Social media & content focus'),
(5, 'Apex Media Group',      'James Holloway', 'james@apexmedia.com',  '+63 919 345 6789', '12F One San Miguel Ave, Pasig',         120000.00,'Annual retainer – integrated campaigns');

-- Campaigns
INSERT INTO campaigns (client_id, title, description, status, budget, start_date, end_date, created_by) VALUES
(1, 'TechCorp Q3 Brand Refresh',      'Full brand identity refresh including digital assets and collateral.',       'Active',        40000.00, '2025-07-01', '2025-09-30', 1),
(1, 'TechCorp Social Media Launch',   'Organic + paid social rollout across LinkedIn and Meta.',                    'Planning',      20000.00, '2025-08-15', '2025-11-30', 1),
(2, 'Nova Content Strategy Q3',       'Editorial calendar, copywriting, and graphic production.',                   'Under Review',  30000.00, '2025-07-10', '2025-09-10', 1),
(2, 'Nova Influencer Campaign',       'Micro-influencer seeding and campaign reporting.',                           'Approved',      18000.00, '2025-07-01', '2025-08-31', 1),
(3, 'Apex Annual Brand Campaign',     'Integrated ATL/BTL campaign across digital, OOH, and print.',               'Active',        80000.00, '2025-06-01', '2025-12-31', 2),
(3, 'Apex Product Launch – Series X', 'Go-to-market strategy, creative production, and media buying.',             'Planning',      35000.00, '2025-09-01', '2025-11-15', 2);

-- Milestones
INSERT INTO milestones (campaign_id, title, description, status, due_date) VALUES
(1, 'Brand Audit & Discovery',      'Audit existing brand touchpoints, conduct stakeholder interviews.', 'Approved',           '2025-07-14'),
(1, 'Mood Board & Style Guide',     'Deliver visual direction document and typography selections.',      'Approved',           '2025-07-28'),
(1, 'Logo Redesign Concepts',       'Present 3 logo directions for client review.',                     'Under Review',       '2025-08-11'),
(1, 'Final Brand Manual',           'Complete brand manual with all assets.',                            'Pending',            '2025-09-15'),
(3, 'Content Calendar – July',      'Deliver 30-day content calendar for approval.',                    'Approved',           '2025-07-05'),
(3, 'Copy Batch 1',                 'First batch of 15 social captions and 4 blog articles.',           'Revision Requested', '2025-07-19'),
(5, 'Creative Brief Sign-off',      'Align on campaign message, tone, and visual direction.',           'Approved',           '2025-06-15'),
(5, 'TV Commercial Script',         'First draft script for 30-second spot.',                           'Under Review',       '2025-07-30');

-- Time logs
INSERT INTO time_logs (campaign_id, logged_by, hours, hourly_rate, description, log_date) VALUES
(1, 1, 8.00,  1500.00, 'Brand audit workshop and documentation',        '2025-07-08'),
(1, 1, 6.50,  1500.00, 'Mood board creation and internal review',       '2025-07-22'),
(1, 2, 4.00,  1200.00, 'Logo concept exploration – round 1',             '2025-08-05'),
(3, 1, 5.00,  1500.00, 'Editorial planning and content calendar',       '2025-07-03'),
(3, 2, 7.00,  1200.00, 'Copywriting – batch 1 social and blog',         '2025-07-17'),
(5, 2, 10.00, 1200.00, 'Creative brief sessions and strategy deck',     '2025-06-12'),
(5, 2, 8.50,  1200.00, 'TV script writing and revisions',               '2025-07-28');
