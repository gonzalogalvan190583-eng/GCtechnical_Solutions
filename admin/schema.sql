CREATE TABLE users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL,
  display_name VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
  must_change_password TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE service_requests (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(254) NOT NULL,
  subject VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('pendiente', 'respondida', 'realizada') NOT NULL DEFAULT 'pendiente',
  created_at DATETIME NOT NULL,
  responded_at DATETIME NULL,
  responded_by BIGINT UNSIGNED NULL,
  completed_at DATETIME NULL,
  completed_by BIGINT UNSIGNED NULL,
  PRIMARY KEY (id),
  KEY idx_requests_created_at (created_at),
  KEY idx_requests_status_created (status, created_at),
  CONSTRAINT fk_requests_responded_by FOREIGN KEY (responded_by) REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT fk_requests_completed_by FOREIGN KEY (completed_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE request_rate_limits (
  ip_hash CHAR(64) NOT NULL,
  last_submission_at DATETIME NOT NULL,
  PRIMARY KEY (ip_hash),
  KEY idx_request_limits_last_submission (last_submission_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
