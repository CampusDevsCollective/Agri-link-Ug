-- =========================================================
-- AgriLink Uganda — Database Schema (MySQL 8.x)
-- Smart Agricultural Market Intelligence & Farmer-Buyer
-- Linkage System
-- =========================================================
-- Notes:
--  * InnoDB used throughout for FK support and transactions.
--  * All monetary values in UGX, stored as DECIMAL to avoid
--    float rounding errors.
--  * Timestamps use DATETIME with DEFAULT CURRENT_TIMESTAMP.
--  * Soft "status" workflow columns use ENUM for speed of
--    querying; convert to lookup tables later if you need the
--    admin to edit statuses without a migration.
-- =========================================================

CREATE DATABASE IF NOT EXISTS agrilink_uganda
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE agrilink_uganda;

-- ---------------------------------------------------------
-- 1. Reference / lookup tables
-- ---------------------------------------------------------

CREATE TABLE districts (
    district_id     INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL UNIQUE,
    region          VARCHAR(50)                -- e.g. Central, Western
) ENGINE=InnoDB;

CREATE TABLE commodities (
    commodity_id    INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(80) NOT NULL UNIQUE,   -- Beans, Maize, Matooke...
    category        VARCHAR(50),                   -- Cereal, Legume, Tuber...
    unit_of_measure VARCHAR(20) NOT NULL DEFAULT 'kg',
    is_active       BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

CREATE TABLE markets (
    market_id       INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(120) NOT NULL,
    district_id     INT NOT NULL,
    latitude        DECIMAL(9,6),
    longitude       DECIMAL(9,6),
    is_active       BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (district_id) REFERENCES districts(district_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 2. Users and role-specific profiles
-- ---------------------------------------------------------

CREATE TABLE users (
    user_id         INT AUTO_INCREMENT PRIMARY KEY,
    full_name       VARCHAR(120) NOT NULL,
    phone_number    VARCHAR(20) NOT NULL UNIQUE,
    email           VARCHAR(120) UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('farmer','buyer','market_officer','admin') NOT NULL,
    district_id     INT,
    is_verified     BOOLEAN NOT NULL DEFAULT FALSE,
    is_active       BOOLEAN NOT NULL DEFAULT TRUE,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                        ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (district_id) REFERENCES districts(district_id)
) ENGINE=InnoDB;

CREATE TABLE farmers (
    farmer_id       INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL UNIQUE,
    village         VARCHAR(100),
    farm_size_acres DECIMAL(6,2),
    national_id_no  VARCHAR(30),                -- optional, for verification
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE buyers (
    buyer_id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL UNIQUE,
    business_name   VARCHAR(150),
    buyer_type      ENUM('trader','processor','institution','exporter','other')
                        NOT NULL DEFAULT 'trader',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE market_officers (
    market_officer_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id            INT NOT NULL UNIQUE,
    assigned_market_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_market_id) REFERENCES markets(market_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 3. Market price intelligence
-- ---------------------------------------------------------

CREATE TABLE market_prices (
    price_id        INT AUTO_INCREMENT PRIMARY KEY,
    market_id       INT NOT NULL,
    commodity_id    INT NOT NULL,
    price_per_unit  DECIMAL(12,2) NOT NULL,      -- UGX per unit_of_measure
    date_recorded   DATE NOT NULL,
    recorded_by     INT,                          -- market_officer_id
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (market_id) REFERENCES markets(market_id),
    FOREIGN KEY (commodity_id) REFERENCES commodities(commodity_id),
    FOREIGN KEY (recorded_by) REFERENCES market_officers(market_officer_id),
    UNIQUE KEY uq_price_per_day (market_id, commodity_id, date_recorded)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 4. Produce listings (farmer supply) and buyer requirements
-- ---------------------------------------------------------

CREATE TABLE produce_listings (
    listing_id          INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id           INT NOT NULL,
    commodity_id        INT NOT NULL,
    quantity_available  DECIMAL(10,2) NOT NULL,
    unit                VARCHAR(20) NOT NULL DEFAULT 'kg',
    harvest_date         DATE,                    -- actual/expected harvest
    availability_date    DATE NOT NULL,           -- when it can be collected
    asking_price_per_unit DECIMAL(12,2),
    pickup_location_note VARCHAR(255),
    status              ENUM('available','partially_reserved','matched',
                              'aggregating','sold','expired','withdrawn')
                              NOT NULL DEFAULT 'available',
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                              ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES farmers(farmer_id),
    FOREIGN KEY (commodity_id) REFERENCES commodities(commodity_id)
) ENGINE=InnoDB;

CREATE TABLE buyer_requirements (
    requirement_id      INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id            INT NOT NULL,
    commodity_id        INT NOT NULL,
    quantity_required   DECIMAL(10,2) NOT NULL,
    unit                VARCHAR(20) NOT NULL DEFAULT 'kg',
    offered_price_per_unit DECIMAL(12,2),
    collection_market_id INT,                    -- preferred collection point
    collection_deadline  DATE,
    status               ENUM('open','partially_matched','matched',
                               'aggregating','fulfilled','closed','cancelled')
                               NOT NULL DEFAULT 'open',
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                               ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES buyers(buyer_id),
    FOREIGN KEY (commodity_id) REFERENCES commodities(commodity_id),
    FOREIGN KEY (collection_market_id) REFERENCES markets(market_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 5. Matching and aggregation
-- ---------------------------------------------------------

-- One row per requirement that the matching engine has evaluated.
-- match_type = 'direct'      -> one listing fully/partly covers the requirement
-- match_type = 'aggregated'  -> requirement is covered via an aggregation group
CREATE TABLE matches (
    match_id        INT AUTO_INCREMENT PRIMARY KEY,
    requirement_id  INT NOT NULL,
    listing_id      INT,                          -- NULL when match_type = 'aggregated'
    match_type      ENUM('direct','aggregated') NOT NULL,
    matched_quantity DECIMAL(10,2) NOT NULL,
    match_status    ENUM('proposed','confirmed_farmer','confirmed_buyer',
                          'confirmed_both','rejected','expired')
                          NOT NULL DEFAULT 'proposed',
    proposed_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at     DATETIME,
    FOREIGN KEY (requirement_id) REFERENCES buyer_requirements(requirement_id),
    FOREIGN KEY (listing_id) REFERENCES produce_listings(listing_id)
) ENGINE=InnoDB;

-- A group formed when no single farmer meets a requirement alone.
CREATE TABLE aggregations (
    aggregation_id  INT AUTO_INCREMENT PRIMARY KEY,
    requirement_id  INT NOT NULL,
    commodity_id    INT NOT NULL,
    target_quantity DECIMAL(10,2) NOT NULL,       -- = requirement quantity
    total_pledged_quantity DECIMAL(10,2) NOT NULL DEFAULT 0,
    status          ENUM('proposed','confirmed','fulfilled','cancelled')
                          NOT NULL DEFAULT 'proposed',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requirement_id) REFERENCES buyer_requirements(requirement_id),
    FOREIGN KEY (commodity_id) REFERENCES commodities(commodity_id)
) ENGINE=InnoDB;

-- The individual farmer contributions inside one aggregation group.
CREATE TABLE aggregation_members (
    aggregation_member_id INT AUTO_INCREMENT PRIMARY KEY,
    aggregation_id  INT NOT NULL,
    listing_id      INT NOT NULL,
    farmer_id       INT NOT NULL,
    quantity_committed DECIMAL(10,2) NOT NULL,
    status          ENUM('proposed','accepted','declined','delivered')
                          NOT NULL DEFAULT 'proposed',
    FOREIGN KEY (aggregation_id) REFERENCES aggregations(aggregation_id)
                          ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES produce_listings(listing_id),
    FOREIGN KEY (farmer_id) REFERENCES farmers(farmer_id),
    UNIQUE KEY uq_listing_per_aggregation (aggregation_id, listing_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 6. Transactions (post-match / post-aggregation record)
-- ---------------------------------------------------------

CREATE TABLE transactions (
    transaction_id  INT AUTO_INCREMENT PRIMARY KEY,
    match_id        INT,                          -- direct-match transaction
    aggregation_id  INT,                          -- aggregated transaction
    buyer_id        INT NOT NULL,
    total_quantity  DECIMAL(10,2) NOT NULL,
    agreed_price_per_unit DECIMAL(12,2) NOT NULL,
    transaction_date DATE,
    status          ENUM('pending','completed','cancelled','disputed')
                          NOT NULL DEFAULT 'pending',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (match_id) REFERENCES matches(match_id),
    FOREIGN KEY (aggregation_id) REFERENCES aggregations(aggregation_id),
    FOREIGN KEY (buyer_id) REFERENCES buyers(buyer_id),
    CHECK (match_id IS NOT NULL OR aggregation_id IS NOT NULL)
) ENGINE=InnoDB;

-- Per-farmer payout line within a transaction (needed when the
-- transaction came from an aggregation with several contributors).
CREATE TABLE transaction_items (
    transaction_item_id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id  INT NOT NULL,
    farmer_id       INT NOT NULL,
    listing_id      INT NOT NULL,
    quantity        DECIMAL(10,2) NOT NULL,
    amount_due      DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id)
                          ON DELETE CASCADE,
    FOREIGN KEY (farmer_id) REFERENCES farmers(farmer_id),
    FOREIGN KEY (listing_id) REFERENCES produce_listings(listing_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 7. Market recommendation support
-- ---------------------------------------------------------

-- Precomputed / cached distance-cost pairs used by the
-- recommendation engine (price vs. estimated transport cost).
CREATE TABLE market_distance_estimates (
    estimate_id     INT AUTO_INCREMENT PRIMARY KEY,
    origin_district_id INT NOT NULL,
    market_id       INT NOT NULL,
    distance_km     DECIMAL(6,2),
    estimated_transport_cost_per_unit DECIMAL(10,2),
    FOREIGN KEY (origin_district_id) REFERENCES districts(district_id),
    FOREIGN KEY (market_id) REFERENCES markets(market_id),
    UNIQUE KEY uq_origin_market (origin_district_id, market_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 8. Notifications / alerts (in-app + SMS)
-- ---------------------------------------------------------

CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    type            ENUM('match_proposed','aggregation_invite','price_alert',
                          'requirement_posted','transaction_update','system')
                          NOT NULL,
    channel         ENUM('in_app','sms','both') NOT NULL DEFAULT 'in_app',
    message         VARCHAR(500) NOT NULL,
    related_table   VARCHAR(50),                  -- e.g. 'matches','aggregations'
    related_id      INT,
    status          ENUM('pending','sent','failed','read')
                          NOT NULL DEFAULT 'pending',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at         DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 9. Audit log (recommended for an academic project —
--    demonstrates traceability in your evaluation chapter)
-- ---------------------------------------------------------

CREATE TABLE audit_log (
    audit_id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT,
    action          VARCHAR(100) NOT NULL,        -- e.g. 'LISTING_CREATED'
    table_name      VARCHAR(50),
    record_id       INT,
    details         TEXT,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Helpful indexes for the queries the system will run most
-- ---------------------------------------------------------

CREATE INDEX idx_listings_status_commodity ON produce_listings(status, commodity_id);
CREATE INDEX idx_requirements_status_commodity ON buyer_requirements(status, commodity_id);
CREATE INDEX idx_prices_commodity_market_date ON market_prices(commodity_id, market_id, date_recorded);
CREATE INDEX idx_notifications_user_status ON notifications(user_id, status);
