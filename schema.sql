-- =============================================================
--  APPLICATION ÉVÉNEMENTIELLE — SCHÉMA BDD
--  MySQL 8.0+  |  Encodage : utf8mb4  |  Moteur : InnoDB
-- =============================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
--  1. UTILISATEURS (backoffice)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name          VARCHAR(100)    NOT NULL,
    email         VARCHAR(180)    NOT NULL UNIQUE,
    password_hash VARCHAR(255)    NOT NULL,
    role          ENUM('super_admin','admin','scanner') NOT NULL DEFAULT 'admin',
    is_active     TINYINT(1)      NOT NULL DEFAULT 1,
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_email (email),
    INDEX idx_role  (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
--  2. ÉVÉNEMENTS
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS events (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    slug            VARCHAR(120)    NOT NULL UNIQUE,   -- URL publique : /inscription/{slug}
    name            VARCHAR(200)    NOT NULL,
    description     TEXT,
    location        VARCHAR(300),
    event_date      DATETIME        NOT NULL,
    -- Options paramétrables (extensible sans ALTER TABLE)
    options         JSON            NOT NULL DEFAULT ('{}'),
    -- Exemples de clés JSON :
    --   { "transport": true, "meal": false, "dress_code": "black_tie",
    --     "quota": 200, "waitlist": true }
    max_guests      INT UNSIGNED    NULL DEFAULT NULL, -- NULL = pas de limite
    waitlist_enabled TINYINT(1)     NOT NULL DEFAULT 0,
    registration_open TINYINT(1)   NOT NULL DEFAULT 1,
    response_deadline DATETIME      NULL DEFAULT NULL, -- date limite de réponse invité
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    created_by      INT UNSIGNED    NOT NULL,          -- super_admin créateur
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE INDEX idx_slug (slug),
    INDEX idx_event_date (event_date),
    CONSTRAINT fk_events_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
--  3. ADMINS PAR ÉVÉNEMENT (pivot multi-admin)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS event_admins (
    event_id    INT UNSIGNED    NOT NULL,
    user_id     INT UNSIGNED    NOT NULL,
    -- Niveau de droits sur cet événement
    level       ENUM('full','read_only') NOT NULL DEFAULT 'full',
    assigned_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT UNSIGNED    NOT NULL,  -- super_admin qui a fait l'affectation
    PRIMARY KEY (event_id, user_id),
    CONSTRAINT fk_ea_event   FOREIGN KEY (event_id)    REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_ea_user    FOREIGN KEY (user_id)     REFERENCES users(id)  ON DELETE CASCADE,
    CONSTRAINT fk_ea_assigned FOREIGN KEY (assigned_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
--  4. INVITÉS
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS guests (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    event_id        INT UNSIGNED    NOT NULL,
    first_name      VARCHAR(100)    NOT NULL,
    last_name       VARCHAR(100)    NOT NULL,
    email           VARCHAR(180)    NOT NULL,
    phone           VARCHAR(30)     NULL DEFAULT NULL,
    -- Statut du cycle de vie
    status          ENUM(
                        'pending',      -- en attente de modération
                        'approved',     -- validé par admin, email envoyé
                        'rejected',     -- refusé par admin
                        'confirmed',    -- a confirmé sa participation
                        'declined',     -- a décliné
                        'waitlist',     -- liste d'attente (quota atteint)
                        'no_show'       -- absent le jour J (mis à jour par scan)
                    ) NOT NULL DEFAULT 'pending',
    -- Source de l'inscription
    source          ENUM('form','import','manual') NOT NULL DEFAULT 'form',
    -- Modération
    moderated_by    INT UNSIGNED    NULL DEFAULT NULL,
    moderated_at    DATETIME        NULL DEFAULT NULL,
    rejection_reason TEXT           NULL DEFAULT NULL,
    -- Email de confirmation envoyé
    confirmation_sent_at  DATETIME  NULL DEFAULT NULL,
    -- Relance
    reminder_sent_at      DATETIME  NULL DEFAULT NULL,
    -- Token pour lien "Mon invitation" (accès sans compte)
    access_token    VARCHAR(64)     NOT NULL UNIQUE,  -- bin2hex(random_bytes(32))
    token_expires_at DATETIME       NULL DEFAULT NULL,
    -- Import
    import_batch_id INT UNSIGNED    NULL DEFAULT NULL,
    -- Timestamps
    registered_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_event_status  (event_id, status),
    INDEX idx_email_event   (email, event_id),
    INDEX idx_access_token  (access_token),
    CONSTRAINT fk_guests_event    FOREIGN KEY (event_id)    REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_guests_moderated FOREIGN KEY (moderated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
--  5. RÉPONSES INVITÉS (participation + options)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS guest_responses (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    guest_id        INT UNSIGNED    NOT NULL UNIQUE,  -- 1 réponse par invité
    attending       TINYINT(1)      NOT NULL,          -- 1 = présent, 0 = absent
    -- Transport (si option activée sur l'événement)
    takes_bus       TINYINT(1)      NULL DEFAULT NULL,
    bus_stop        VARCHAR(100)    NULL DEFAULT NULL, -- arrêt choisi si liste définie
    -- Champ libre pour options futures (repas, table, etc.)
    extra           JSON            NOT NULL DEFAULT ('{}'),
    responded_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_gr_guest FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
--  6. TOKENS QR CODE
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS qr_tokens (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    guest_id        INT UNSIGNED    NOT NULL,
    event_id        INT UNSIGNED    NOT NULL,
    -- Token signé : UUID v4 + HMAC-SHA256(secret, guest_id|event_id|uuid)
    token           VARCHAR(128)    NOT NULL UNIQUE,
    token_hash      VARCHAR(64)     NOT NULL,          -- HMAC pour vérification serveur
    status          ENUM('active','used','revoked','expired') NOT NULL DEFAULT 'active',
    -- Scan
    scanned_at      DATETIME        NULL DEFAULT NULL,
    scanned_by      INT UNSIGNED    NULL DEFAULT NULL, -- user scanner
    scan_device     VARCHAR(200)    NULL DEFAULT NULL, -- user-agent
    -- Génération
    generated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    generated_by    INT UNSIGNED    NULL DEFAULT NULL, -- NULL si automatique
    expires_at      DATETIME        NULL DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX idx_token         (token),
    INDEX idx_guest_event   (guest_id, event_id),
    CONSTRAINT fk_qr_guest       FOREIGN KEY (guest_id)      REFERENCES guests(id) ON DELETE CASCADE,
    CONSTRAINT fk_qr_event       FOREIGN KEY (event_id)      REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_qr_scanned_by  FOREIGN KEY (scanned_by)    REFERENCES users(id),
    CONSTRAINT fk_qr_generated_by FOREIGN KEY (generated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
--  7. LOGS DE SCAN (historique complet)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS scan_logs (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    event_id        INT UNSIGNED    NOT NULL,
    qr_token_id     INT UNSIGNED    NULL DEFAULT NULL,  -- NULL si token inconnu
    token_raw       VARCHAR(128)    NOT NULL,            -- token scanné tel quel
    result          ENUM(
                        'success',          -- entrée validée
                        'already_used',     -- QR déjà scanné
                        'invalid_token',    -- token inexistant ou HMAC invalide
                        'wrong_event',      -- QR valide mais mauvais événement
                        'revoked',          -- QR révoqué manuellement
                        'expired'           -- QR expiré
                    ) NOT NULL,
    scanned_by      INT UNSIGNED    NULL DEFAULT NULL,
    scan_device     VARCHAR(200)    NULL DEFAULT NULL,
    ip_address      VARCHAR(45)     NULL DEFAULT NULL,
    scanned_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_event_scanned (event_id, scanned_at),
    INDEX idx_result        (result),
    CONSTRAINT fk_sl_event      FOREIGN KEY (event_id)    REFERENCES events(id),
    CONSTRAINT fk_sl_qr         FOREIGN KEY (qr_token_id) REFERENCES qr_tokens(id),
    CONSTRAINT fk_sl_scanned_by FOREIGN KEY (scanned_by)  REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
--  8. IMPORTS (traçabilité des imports CSV/Excel)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS import_batches (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    event_id        INT UNSIGNED    NOT NULL,
    imported_by     INT UNSIGNED    NOT NULL,
    filename        VARCHAR(255)    NOT NULL,
    total_rows      INT UNSIGNED    NOT NULL DEFAULT 0,
    imported_count  INT UNSIGNED    NOT NULL DEFAULT 0,
    skipped_count   INT UNSIGNED    NOT NULL DEFAULT 0,
    error_count     INT UNSIGNED    NOT NULL DEFAULT 0,
    errors_detail   JSON            NULL DEFAULT NULL,
    imported_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_ib_event FOREIGN KEY (event_id)    REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_ib_user  FOREIGN KEY (imported_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Liaison import_batches ← guests
ALTER TABLE guests
    ADD CONSTRAINT fk_guests_import
    FOREIGN KEY (import_batch_id) REFERENCES import_batches(id) ON DELETE SET NULL;

-- -------------------------------------------------------------
--  9. JOURNAL D'AUDIT (RGPD — toutes actions admin)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_logs (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED    NULL DEFAULT NULL,  -- NULL si action système
    action          VARCHAR(100)    NOT NULL,            -- ex: 'guest.approved', 'qr.revoked'
    entity_type     VARCHAR(50)     NOT NULL,            -- 'guest', 'event', 'qr_token'...
    entity_id       INT UNSIGNED    NULL DEFAULT NULL,
    old_value       JSON            NULL DEFAULT NULL,
    new_value       JSON            NULL DEFAULT NULL,
    ip_address      VARCHAR(45)     NULL DEFAULT NULL,
    user_agent      VARCHAR(300)    NULL DEFAULT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_action      (action),
    INDEX idx_entity      (entity_type, entity_id),
    INDEX idx_user_date   (user_id, created_at),
    CONSTRAINT fk_al_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------
--  10. DONNÉES INITIALES (super admin par défaut)
-- -------------------------------------------------------------
-- Email : admin@gestevent.solappli.com  |  Mot de passe : GestEvent2024!
-- Changer le mot de passe immédiatement après la première connexion
INSERT IGNORE INTO users (name, email, password_hash, role) VALUES (
    'Super Admin',
    'admin@gestevent.solappli.com',
    '$2y$12$6bGNgIRrKFBgXoTGORrcBubxTzXzCCQnhkSWECKODuF7fwYOoMclK',
    'super_admin'
);
