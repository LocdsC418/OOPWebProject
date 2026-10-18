-- ระบบยืม-คืนอุปกรณ์ (Equipment Lending System)
-- นำเข้าไฟล์นี้ผ่าน phpMyAdmin หรือ: mysql -u root < database.sql

DROP DATABASE IF EXISTS equipment_lending;
CREATE DATABASE equipment_lending CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE equipment_lending;

CREATE TABLE categories (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE equipment (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    code          VARCHAR(20)  NOT NULL UNIQUE,
    name          VARCHAR(150) NOT NULL,
    category_id   INT          NOT NULL,
    total_qty     INT          NOT NULL CHECK (total_qty > 0),
    available_qty INT          NOT NULL CHECK (available_qty >= 0),
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_equipment_category FOREIGN KEY (category_id) REFERENCES categories(id),
    CONSTRAINT chk_available CHECK (available_qty <= total_qty)
) ENGINE=InnoDB;

CREATE TABLE members (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(13)  NOT NULL UNIQUE,
    name       VARCHAR(100) NOT NULL,
    phone      VARCHAR(20)  NULL
) ENGINE=InnoDB;

CREATE TABLE loans (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT          NOT NULL,
    member_id    INT          NOT NULL,
    borrowed_at  DATETIME     NOT NULL,
    due_date     DATE         NOT NULL,
    returned_at  DATETIME     NULL,
    fine         DECIMAL(8,2) NOT NULL DEFAULT 0,
    CONSTRAINT fk_loans_equipment FOREIGN KEY (equipment_id) REFERENCES equipment(id),
    CONSTRAINT fk_loans_member    FOREIGN KEY (member_id)    REFERENCES members(id),
    INDEX idx_loans_active (returned_at)
) ENGINE=InnoDB;

-- ---------- ข้อมูลตัวอย่าง ----------

INSERT INTO categories (name) VALUES
    ('อุปกรณ์ถ่ายภาพและวิดีโอ'),
    ('โน้ตบุ๊กและอุปกรณ์พกพา'),
    ('เครื่องเสียงและการนำเสนอ'),
    ('ชุดทดลองอิเล็กทรอนิกส์');

INSERT INTO equipment (code, name, category_id, total_qty, available_qty) VALUES
    ('PH-101', 'กล้อง Sony ZV-E10',                 1, 3, 2),
    ('PH-102', 'ไมโครโฟนไร้สาย Rode Wireless GO II', 1, 2, 2),
    ('NB-201', 'โน้ตบุ๊ก Lenovo ThinkPad E14',       2, 4, 4),
    ('NB-202', 'แท็บเล็ต Samsung Galaxy Tab S9',     2, 2, 1),
    ('AV-301', 'ลำโพงพกพา JBL Charge 5',            3, 3, 3),
    ('AV-302', 'เลเซอร์พอยเตอร์ Logitech R500s',     3, 6, 6),
    ('EL-401', 'ชุดบอร์ด ESP32 DevKit',              4, 8, 8),
    ('EL-402', 'มัลติมิเตอร์ดิจิทัล Fluke 101',       4, 2, 2),
    ('EL-403', 'ชุดบอร์ด micro:bit V2',              4, 5, 5);

INSERT INTO members (student_id, name, phone) VALUES
    ('6704062630125', 'ปิยะพงษ์ แสงทอง',      '092-415-7763'),
    ('6704062630347', 'กมลชนก วงศ์สวัสดิ์',    '086-302-1948'),
    ('6704062630582', 'ณัฐวุฒิ ศรีประเสริฐ',    NULL),
    ('6704062630916', 'อรอุมา บุญมา',         '095-660-2381');

-- กำลังยืม 1 รายการ (ยังไม่ถึงกำหนด) และ 1 รายการที่เกินกำหนดแล้ว
INSERT INTO loans (equipment_id, member_id, borrowed_at, due_date) VALUES
    (1, 2, NOW() - INTERVAL 3 DAY, CURDATE() + INTERVAL 4 DAY),
    (4, 4, NOW() - INTERVAL 9 DAY, CURDATE() - INTERVAL 2 DAY);

-- ประวัติที่คืนแล้ว (คืนช้า 3 วัน ค่าปรับ 60 บาท และคืนตรงเวลา)
INSERT INTO loans (equipment_id, member_id, borrowed_at, due_date, returned_at, fine) VALUES
    (5, 1, NOW() - INTERVAL 22 DAY, CURDATE() - INTERVAL 15 DAY, NOW() - INTERVAL 12 DAY, 60.00),
    (7, 3, NOW() - INTERVAL 14 DAY, CURDATE() - INTERVAL 7 DAY,  NOW() - INTERVAL 8 DAY,  0.00);
