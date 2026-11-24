-- Hostel Mess Portal - import this in phpMyAdmin or MySQL Workbench
SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS hostel_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hostel_db;

DROP TABLE IF EXISTS dues, menu, settings, students, admins;

CREATE TABLE admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
);

CREATE TABLE students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  roll_no VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  course VARCHAR(60) NOT NULL,
  branch VARCHAR(100) NOT NULL,
  batch VARCHAR(20) NULL,
  room_no VARCHAR(10) NOT NULL,
  monthly_fee DECIMAL(10,2) NOT NULL DEFAULT 2700,
  password_hash VARCHAR(255) NULL,          -- NULL until the student creates an account
  activation_code_hash VARCHAR(255) NULL,   -- one-time code (hashed); cleared after registration
  activation_expires DATETIME NULL,
  activation_attempts TINYINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE dues (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  month DATE NOT NULL,                      -- always the 1st of the month
  base_amount DECIMAL(10,2) NOT NULL,
  deduction DECIMAL(10,2) NOT NULL DEFAULT 0,   -- set by warden (holiday / vacation)
  deduction_reason VARCHAR(150) NULL,
  fine DECIMAL(10,2) NOT NULL DEFAULT 0,
  due_date DATE NOT NULL,
  status ENUM('pending','paid') NOT NULL DEFAULT 'pending',
  paid_on DATE NULL,
  UNIQUE KEY uq_student_month (student_id, month),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE menu (
  day_no TINYINT NOT NULL,                  -- 1 = Monday ... 7 = Sunday
  meal ENUM('breakfast','lunch','snack','dinner') NOT NULL,
  items VARCHAR(255) NOT NULL,
  PRIMARY KEY (day_no, meal)
);

CREATE TABLE settings (
  name VARCHAR(50) PRIMARY KEY,
  value VARCHAR(255) NOT NULL
);

-- Warden login: username warden / password admin123  (change it in Settings after first login)
INSERT INTO admins (username, password_hash) VALUES ('warden', '$2y$10$BAbbGOj1nncpa1fmlvDDYuKjNAeNVpPXTV2llfHLOQQFVJufmyfZy');

INSERT INTO settings (name, value) VALUES
 ('hostel_name', 'Government Polytechnic Barabanki'),
 ('upi_id', 'udaygupta3635-1@okicici'),
 ('payee_name', 'Uday Gupta'),
 ('default_fee', '2700'),
 ('due_day', '20'),
 ('t_breakfast', '8-9 AM'),
 ('t_lunch', '1-2 PM'),
 ('t_snack', '5-6 PM'),
 ('t_dinner', '8-9 PM');

-- Sample student with a demo activation code: DEMO-2026 (valid 30 days). Edit or delete the student in the warden panel.
INSERT INTO students (roll_no, name, course, branch, room_no, monthly_fee, activation_code_hash, activation_expires)
VALUES ('78626264', 'Uday Gupta', 'Diploma', 'Computer Science and Engineering', '16', 2700, '$2y$10$pm9/Jo0UARhJa2OtdhyDC.6lU6wmuPUQagSogFRvua3tw4sUZm4Re', DATE_ADD(NOW(), INTERVAL 30 DAY));

INSERT INTO dues (student_id, month, base_amount, deduction, deduction_reason, fine, due_date, status, paid_on) VALUES
 (1, DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH, '%Y-%m-01'), 2700, 0, NULL, 0, DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH, '%Y-%m-20'), 'paid', DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH, '%Y-%m-12')),
 (1, DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01'), 2700, 300, 'Diwali vacation', 0, DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-20'), 'paid', DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-15')),
 (1, DATE_FORMAT(CURDATE(), '%Y-%m-01'), 2700, 0, NULL, 0, DATE_FORMAT(CURDATE(), '%Y-%m-28'), 'pending', NULL);

-- Weekly mess menu (from the 2026-27 menu chart, in English). Warden can edit it in the panel.
INSERT INTO menu (day_no, meal, items) VALUES
 (1,'breakfast','Tea, Aloo paratha'),
 (1,'lunch','Masoor dal, seasonal dry vegetable, roti, rice'),
 (1,'snack','Tea, 2 Jeera biscuits'),
 (1,'dinner','Arhar dal, rice, roti, aloo bhujia (dry potato)'),
 (2,'breakfast','Tea, Samosa'),
 (2,'lunch','Chana dal, rice, aloo matar sabzi, roti'),
 (2,'snack','Tea, 2 rusk'),
 (2,'dinner','Chana chhole, aloo sabzi, roti'),
 (3,'breakfast','Sattu paratha, tea'),
 (3,'lunch','Rice, roti, rajma'),
 (3,'snack','Tea, macaroni'),
 (3,'dinner','Chana dal, rice, mixed green vegetable, roti'),
 (4,'breakfast','Sandwich / bread with sweet chutney, tea'),
 (4,'lunch','Arhar dal, rice, aloo palak sabzi, roti'),
 (4,'snack','Tea, chana'),
 (4,'dinner','Rice, chhole, puri, salad, kheer'),
 (5,'breakfast','Aloo paratha, tea'),
 (5,'lunch','Kadhi chawal, aloo sabzi, salad, roti'),
 (5,'snack','Tea, 2 rusk'),
 (5,'dinner','Aloo gobhi matar sabzi, rice, roti'),
 (6,'breakfast','Puri sabzi, tea'),
 (6,'lunch','Chana dal, rice, aloo bhujia, roti'),
 (6,'snack','Tea, 2 round buns'),
 (6,'dinner','Arhar dal, rice, roti, aloo matar (dry)'),
 (7,'breakfast','Raw chana, tea'),
 (7,'lunch','Paneer, puri, rice, aloo sabzi'),
 (7,'snack','Tea, rasgulla, chowmein'),
 (7,'dinner','Bhuja, rice, pickle');
