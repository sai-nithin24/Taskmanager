CREATE DATABASE IF NOT EXISTS taskboard
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE taskboard;

-- tasks 
CREATE TABLE IF NOT EXISTS tasks (
  id          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  title       VARCHAR(120)     NOT NULL,
  priority    ENUM('high','medium','low') NOT NULL DEFAULT 'medium',
  status      ENUM('pending','completed')  NOT NULL DEFAULT 'pending',
  created_at  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
                               ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  INDEX idx_status   (status),
  INDEX idx_priority (priority),
  INDEX idx_created  (created_at DESC)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- seed data 
INSERT INTO tasks (title, priority, status) VALUES
  ('Kick-off design review with stakeholders', 'high',   'completed'),
  ('Set up repo and CI pipeline',              'high',   'completed'),
  ('Define API contracts for task endpoints',  'medium', 'pending'),
  ('Write component unit tests',               'medium', 'pending'),
  ('Deploy preview build to staging',          'low',    'pending');
