CREATE TABLE IF NOT EXISTS tickets (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(20) NOT NULL UNIQUE,
    customer_name VARCHAR(120) NOT NULL,
    customer_phone VARCHAR(30) NOT NULL,
    device_type VARCHAR(80) NOT NULL,
    brand VARCHAR(80) DEFAULT NULL,
    model VARCHAR(100) DEFAULT NULL,
    serial_number VARCHAR(120) DEFAULT NULL,
    reported_problem TEXT NOT NULL,
    diagnosis TEXT DEFAULT NULL,
    repair_notes TEXT DEFAULT NULL,
    status ENUM(
        'Recibido',
        'En diagnóstico',
        'En reparación',
        'Esperando repuestos',
        'Listo para entregar',
        'Entregado',
        'No reparado'
    ) NOT NULL DEFAULT 'Recibido',
    received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    delivered_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_tickets_status (status),
    KEY idx_tickets_phone (customer_phone),
    KEY idx_tickets_customer (customer_name),
    KEY idx_tickets_received (received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ticket_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id INT UNSIGNED NOT NULL,
    old_status VARCHAR(40) DEFAULT NULL,
    new_status VARCHAR(40) NOT NULL,
    note TEXT DEFAULT NULL,
    changed_by VARCHAR(120) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_history_ticket_date (ticket_id, created_at),
    CONSTRAINT fk_history_ticket
        FOREIGN KEY (ticket_id) REFERENCES tickets(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
