CREATE TABLE IF NOT EXISTS `0_route_delivery_gps` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `debtor_no` INT NOT NULL,
    `branch_no` INT NOT NULL,
    `latitude` DECIMAL(10, 6) NOT NULL,
    `longitude` DECIMAL(11, 6) NOT NULL,
    `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE `DEBTOR_BRANCH` (`debtor_no`, `branch_no`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS 0_route_delivery_shippers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    shipper_id INT UNIQUE NOT NULL,  -- Still keep shipper_id, but no foreign key
    latitude DECIMAL(9, 6),
    longitude DECIMAL(9, 6),
    service_radius DECIMAL(5,2),
    availability_days VARCHAR(255),
    availability_start_time TIME,
    availability_end_time TIME,
    employment_type ENUM('Full-Time', 'Part-Time', 'Contractor', 'Other'),
    tax_id VARCHAR(255),
    hourly_rate DECIMAL(10,2),
    production_percent DECIMAL(5,2),
    production_fixed DECIMAL(10,2),
    certifications TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS 0_route_delivery_rtx (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    order_no INT(11) NOT NULL,  -- Links to `0_sales_orders.order_no`
    debtor_no INT(11) NOT NULL, -- Links to `0_debtors_master.debtor_no`
    branch_code INT(11) NOT NULL, -- Links to `0_cust_branch.branch_code`
    type ENUM('invoice', 'delivery') NOT NULL, -- Type of transaction
    uuid CHAR(36) NOT NULL UNIQUE,
    frequency ENUM('DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY') NOT NULL,
    `interval` INT(11) DEFAULT 1, -- Every X weeks, months, etc.
    weekdays VARCHAR(20) DEFAULT NULL, -- Comma-separated (MO,WE,FR)
    month_day INT(11) DEFAULT NULL, -- 1-31 for monthly rules
    week_number INT(11) DEFAULT NULL, -- 1st, 2nd, 3rd, 4th, last (-1)
    weekday_of_month VARCHAR(2) DEFAULT NULL, -- MO, TU, WE, etc.
    start_date DATE NOT NULL,
    end_date DATE DEFAULT NULL,
    next_run DATETIME NOT NULL,
    last_run DATETIME DEFAULT NULL,
    summary VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    status ENUM('active', 'paused', 'completed') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_order_no` (`order_no`),
    KEY `idx_debtor_no` (`debtor_no`),
    KEY `idx_branch_code` (`branch_code`)
) ENGINE=InnoDB;

