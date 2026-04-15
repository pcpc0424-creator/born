-- =============================================
-- 본투어 인터내셔날 행사 관리 시스템
-- Database Schema - MySQL 8.x
-- =============================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- 데이터베이스 생성
CREATE DATABASE IF NOT EXISTS born_tour
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE born_tour;

-- =============================================
-- 2.1 관리자 테이블
-- =============================================

-- 관리자 계정
CREATE TABLE IF NOT EXISTS admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 기본 관리자 계정 생성 (비밀번호: admin123)
INSERT INTO admin (username, password) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- 사이트 회원 (여행자)
CREATE TABLE IF NOT EXISTS members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    login_id VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name_ko VARCHAR(50) NOT NULL,
    name_en VARCHAR(100),
    phone VARCHAR(20),
    birth_date DATE,
    gender ENUM('M', 'F'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name_birth (name_ko, birth_date),
    INDEX idx_login_id (login_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 2.2 행사 관련 테이블
-- =============================================

-- 행사
CREATE TABLE IF NOT EXISTS events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_name VARCHAR(200) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    airline VARCHAR(100),
    flight_departure VARCHAR(50),
    flight_return VARCHAR(50),
    flight_time_departure TIME,
    flight_time_departure_arrival TIME,
    flight_time_return TIME,
    flight_time_return_arrival TIME,
    departure_airport VARCHAR(100) DEFAULT '인천국제공항',
    departure_airport_code VARCHAR(10) DEFAULT 'ICN',
    arrival_airport VARCHAR(100),
    arrival_airport_code VARCHAR(10) DEFAULT '',
    baggage_info TEXT,
    flight_etc TEXT,
    client_logo VARCHAR(255),
    schedule_url VARCHAR(500),
    hotel_url VARCHAR(500),
    meeting_place VARCHAR(200),
    meeting_time TIME,
    meeting_date DATE,
    meeting_manager VARCHAR(50),
    manager_phone VARCHAR(20),
    meeting_notice TEXT,
    travel_notice TEXT,
    departure_checklist TEXT,
    prohibited_items TEXT,
    weather_image VARCHAR(255),
    unique_code VARCHAR(50) UNIQUE,
    qr_code VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dates (start_date, end_date),
    INDEX idx_status (status),
    INDEX idx_unique_code (unique_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 일정표 (일차별)
CREATE TABLE IF NOT EXISTS schedule_days (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    day_number INT NOT NULL,
    location VARCHAR(200),
    hotel_name VARCHAR(200),
    hotel_id INT DEFAULT NULL,
    meal_breakfast VARCHAR(100),
    meal_lunch VARCHAR(100),
    meal_dinner VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_event_day (event_id, day_number),
    INDEX idx_event_id (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 일정표 세부 항목
CREATE TABLE IF NOT EXISTS schedule_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    schedule_day_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_day_id) REFERENCES schedule_days(id) ON DELETE CASCADE,
    INDEX idx_day_id (schedule_day_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 행사-회원 매칭
CREATE TABLE IF NOT EXISTS event_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    member_id INT NOT NULL,
    optional_tour_ids JSON,
    bus_number VARCHAR(20),
    dinner_table VARCHAR(20),
    room_number VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    UNIQUE KEY unique_event_member (event_id, member_id),
    INDEX idx_event_id (event_id),
    INDEX idx_member_id (member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 2.3 선택관광 테이블
-- =============================================

CREATE TABLE IF NOT EXISTS optional_tours (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    tour_name VARCHAR(200) NOT NULL,
    event_dates JSON,
    description TEXT,
    notice TEXT,
    price INT DEFAULT 0,
    duration VARCHAR(50),
    meeting_time TIME,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_event_id (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 회원 선택관광 신청
CREATE TABLE IF NOT EXISTS member_optional_tours (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_member_id INT NOT NULL,
    optional_tour_id INT NOT NULL,
    tour_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_member_id) REFERENCES event_members(id) ON DELETE CASCADE,
    FOREIGN KEY (optional_tour_id) REFERENCES optional_tours(id) ON DELETE CASCADE,
    UNIQUE KEY unique_member_tour (event_member_id, optional_tour_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 2.4 공지/문의 테이블
-- =============================================

CREATE TABLE IF NOT EXISTS notices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT,
    category ENUM('notice', 'faq') NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_category (category),
    INDEX idx_event_id (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 2.5 설문 테이블
-- =============================================

-- 설문
CREATE TABLE IF NOT EXISTS surveys (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    start_date DATE,
    end_date DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 설문 페이지
CREATE TABLE IF NOT EXISTS survey_pages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    survey_id INT NOT NULL,
    page_title VARCHAR(200),
    page_order INT DEFAULT 1,
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE,
    INDEX idx_survey_id (survey_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 설문 질문
CREATE TABLE IF NOT EXISTS survey_questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    survey_id INT NOT NULL,
    page_id INT NOT NULL,
    question_type ENUM('multiple', 'short', 'long') NOT NULL,
    question_text TEXT NOT NULL,
    options JSON,
    is_required BOOLEAN DEFAULT FALSE,
    question_order INT DEFAULT 1,
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE,
    FOREIGN KEY (page_id) REFERENCES survey_pages(id) ON DELETE CASCADE,
    INDEX idx_survey_id (survey_id),
    INDEX idx_page_id (page_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 설문 응답
CREATE TABLE IF NOT EXISTS survey_responses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    survey_id INT NOT NULL,
    member_id INT NOT NULL,
    question_id INT NOT NULL,
    answer TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES survey_questions(id) ON DELETE CASCADE,
    INDEX idx_survey_member (survey_id, member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 설문 완료 기록
CREATE TABLE IF NOT EXISTS survey_completions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    survey_id INT NOT NULL,
    member_id INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    UNIQUE KEY unique_survey_member (survey_id, member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 설문 임시저장
CREATE TABLE IF NOT EXISTS survey_drafts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    survey_id INT NOT NULL,
    member_id INT NOT NULL,
    draft_data JSON,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_draft (survey_id, member_id),
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 2.6 여권 테이블
-- =============================================

CREATE TABLE IF NOT EXISTS passports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    event_id INT NOT NULL,
    name_ko VARCHAR(50),
    name_en VARCHAR(100),
    gender ENUM('M', 'F'),
    birth_date_encrypted VARBINARY(255),
    passport_no_encrypted VARBINARY(255),
    ssn_back_encrypted VARBINARY(255),
    expiry_date DATE,
    phone VARCHAR(20),
    passport_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_member_event_passport (member_id, event_id),
    INDEX idx_event_id (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 2.7 페이지 노출 설정
-- =============================================

CREATE TABLE IF NOT EXISTS page_visibility (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    notice BOOLEAN DEFAULT TRUE,
    event_name BOOLEAN DEFAULT TRUE,
    event_date BOOLEAN DEFAULT TRUE,
    schedule BOOLEAN DEFAULT TRUE,
    flight BOOLEAN DEFAULT TRUE,
    meeting BOOLEAN DEFAULT TRUE,
    hotel BOOLEAN DEFAULT TRUE,
    travel_notice BOOLEAN DEFAULT TRUE,
    reservation BOOLEAN DEFAULT TRUE,
    passport_upload BOOLEAN DEFAULT TRUE,
    optional_tour BOOLEAN DEFAULT TRUE,
    survey BOOLEAN DEFAULT TRUE,
    announcements BOOLEAN DEFAULT TRUE,
    faq BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_event_visibility (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 트리거: 행사 생성 시 기본 노출 설정 자동 생성
-- =============================================

DELIMITER //
CREATE TRIGGER after_event_insert
AFTER INSERT ON events
FOR EACH ROW
BEGIN
    INSERT INTO page_visibility (event_id) VALUES (NEW.id);
END//
DELIMITER ;

-- =============================================
-- 세션 테이블 (PHP 세션 DB 저장용)
-- =============================================

CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT,
    user_type ENUM('admin', 'member') NOT NULL,
    data TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id, user_type),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
