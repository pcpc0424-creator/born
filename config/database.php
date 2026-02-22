<?php
/**
 * 본투어 인터내셔날 - 데이터베이스 연결 설정
 */

// 데이터베이스 설정
define('DB_HOST', 'localhost');
define('DB_NAME', 'born_tour');
define('DB_USER', 'born_user');
define('DB_PASS', 'BornTour2026!@#');
define('DB_CHARSET', 'utf8mb4');

/**
 * PDO 데이터베이스 연결 클래스
 */
class Database {
    private static ?PDO $instance = null;

    /**
     * 싱글톤 패턴으로 PDO 인스턴스 반환
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = sprintf(
                    "mysql:host=%s;dbname=%s;charset=%s",
                    DB_HOST,
                    DB_NAME,
                    DB_CHARSET
                );

                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ];

                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);

                // 시간대 설정 (KST)
                self::$instance->exec("SET time_zone = '+09:00'");

            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw new Exception("데이터베이스 연결에 실패했습니다.");
            }
        }

        return self::$instance;
    }

    /**
     * 연결 종료
     */
    public static function close(): void {
        self::$instance = null;
    }

    /**
     * 생성자 비활성화 (싱글톤)
     */
    private function __construct() {}

    /**
     * 복제 비활성화 (싱글톤)
     */
    private function __clone() {}
}

/**
 * 간편 DB 헬퍼 함수
 */
function db(): PDO {
    return Database::getInstance();
}
