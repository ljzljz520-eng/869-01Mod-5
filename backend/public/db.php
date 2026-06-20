<?php
// backend/public/db.php

class Database
{
    private static $pdo;

    public static function connect()
    {
        if (self::$pdo === null) {
            try {
                $dbPath = __DIR__ . '/data/visitors.sqlite';
                $dir = dirname($dbPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }

                self::$pdo = new PDO("sqlite:" . $dbPath);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                self::initTables();

            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }

    private static function initTables()
    {
        $sql = "CREATE TABLE IF NOT EXISTS visitors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip TEXT,
            country TEXT,
            city TEXT,
            isp TEXT,
            user_agent TEXT,
            
            browser TEXT,
            browser_version TEXT,
            os TEXT,
            os_version TEXT,
            device_type TEXT,
            
            screen_width INTEGER,
            screen_height INTEGER,
            window_width INTEGER,
            window_height INTEGER,
            
            language TEXT,
            timezone TEXT,
            platform TEXT,
            cookie_enabled INTEGER,
            
            touch_points INTEGER,
            device_memory REAL,
            cpu_cores INTEGER,
            connection_type TEXT,
            
            referrer TEXT,
            remark TEXT,
            email TEXT,
            is_anonymized INTEGER DEFAULT 0,
            is_deleted INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        self::$pdo->exec($sql);

        $columns = self::$pdo->query("PRAGMA table_info(visitors)")->fetchAll();
        $colNames = array_column($columns, 'name');
        if (!in_array('email', $colNames)) {
            self::$pdo->exec("ALTER TABLE visitors ADD COLUMN email TEXT");
        }
        if (!in_array('is_anonymized', $colNames)) {
            self::$pdo->exec("ALTER TABLE visitors ADD COLUMN is_anonymized INTEGER DEFAULT 0");
        }
        if (!in_array('is_deleted', $colNames)) {
            self::$pdo->exec("ALTER TABLE visitors ADD COLUMN is_deleted INTEGER DEFAULT 0");
        }

        $sql = "CREATE TABLE IF NOT EXISTS consent_records (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            visitor_id INTEGER,
            email TEXT,
            consent_type TEXT,
            consent_value TEXT,
            ip_address TEXT,
            user_agent TEXT,
            is_deleted INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        self::$pdo->exec($sql);

        $sql = "CREATE TABLE IF NOT EXISTS export_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            visitor_id INTEGER,
            email TEXT,
            export_type TEXT,
            export_format TEXT,
            file_path TEXT,
            exported_by TEXT,
            is_deleted INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        self::$pdo->exec($sql);

        $sql = "CREATE TABLE IF NOT EXISTS deletion_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            request_code TEXT UNIQUE,
            email TEXT,
            visitor_id INTEGER,
            request_type TEXT,
            status TEXT DEFAULT 'pending',
            data_scope TEXT,
            admin_note TEXT,
            submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            reviewed_at DATETIME,
            completed_at DATETIME
        )";
        self::$pdo->exec($sql);

        $sql = "CREATE TABLE IF NOT EXISTS deletion_receipts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            request_id INTEGER,
            receipt_code TEXT UNIQUE,
            content TEXT,
            deleted_count INTEGER DEFAULT 0,
            anonymized_count INTEGER DEFAULT 0,
            retained_count INTEGER DEFAULT 0,
            retention_reasons TEXT,
            generated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        self::$pdo->exec($sql);

        $sql = "CREATE TABLE IF NOT EXISTS aggregated_reports (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            report_date DATE UNIQUE,
            total_visitors INTEGER DEFAULT 0,
            total_browsers TEXT,
            total_countries TEXT,
            generated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        self::$pdo->exec($sql);

        // 检查是否需要插入演示数据
        $count = self::$pdo->query("SELECT COUNT(*) FROM visitors")->fetchColumn();
        if ($count == 0) {
            self::seedData();
        }
    }

    private static function seedData()
    {
        $stmt = self::$pdo->prepare("INSERT INTO visitors (
            ip, country, city, isp, user_agent, browser, os, screen_width, screen_height, remark, email, created_at
        ) VALUES (
            :ip, :country, :city, :isp, :user_agent, :browser, :os, :screen_width, :screen_height, :remark, :email, :created_at
        )");

        $demos = [
            [
                ':ip' => '192.168.1.101',
                ':country' => 'China',
                ':city' => 'Shanghai',
                ':isp' => 'China Telecom',
                ':user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)...',
                ':browser' => 'Chrome',
                ':os' => 'Mac OS X',
                ':screen_width' => 1920,
                ':screen_height' => 1080,
                ':remark' => '测试数据 A',
                ':email' => 'zhangsan@example.com',
                ':created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
            ],
            [
                ':ip' => '10.0.0.5',
                ':country' => 'China',
                ':city' => 'Beijing',
                ':isp' => 'China Unicom',
                ':user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X)...',
                ':browser' => 'Safari',
                ':os' => 'iOS',
                ':screen_width' => 390,
                ':screen_height' => 844,
                ':remark' => '测试数据 B - 手机端',
                ':email' => 'lisi@example.com',
                ':created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
            ],
            [
                ':ip' => '172.16.0.23',
                ':country' => 'China',
                ':city' => 'Guangzhou',
                ':isp' => 'China Mobile',
                ':user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)...',
                ':browser' => 'Edge',
                ':os' => 'Windows 10',
                ':screen_width' => 2560,
                ':screen_height' => 1440,
                ':remark' => '测试数据 C',
                ':email' => 'wangwu@example.com',
                ':created_at' => date('Y-m-d H:i:s', strtotime('-5 hours'))
            ],
            [
                ':ip' => '192.168.1.101',
                ':country' => 'China',
                ':city' => 'Shanghai',
                ':isp' => 'China Telecom',
                ':user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)...',
                ':browser' => 'Firefox',
                ':os' => 'Mac OS X',
                ':screen_width' => 1920,
                ':screen_height' => 1080,
                ':remark' => '测试数据 A 第二次访问',
                ':email' => 'zhangsan@example.com',
                ':created_at' => date('Y-m-d H:i:s', strtotime('-30 minutes'))
            ]
        ];

        foreach ($demos as $demo) {
            $stmt->execute($demo);
        }

        $consentStmt = self::$pdo->prepare("INSERT INTO consent_records (
            visitor_id, email, consent_type, consent_value, ip_address, user_agent
        ) VALUES (
            :visitor_id, :email, :consent_type, :consent_value, :ip_address, :user_agent
        )");
        $consentStmt->execute([
            ':visitor_id' => 1,
            ':email' => 'zhangsan@example.com',
            ':consent_type' => 'analytics',
            ':consent_value' => 'granted',
            ':ip_address' => '192.168.1.101',
            ':user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)...'
        ]);
        $consentStmt->execute([
            ':visitor_id' => 1,
            ':email' => 'zhangsan@example.com',
            ':consent_type' => 'marketing',
            ':consent_value' => 'denied',
            ':ip_address' => '192.168.1.101',
            ':user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)...'
        ]);
        $consentStmt->execute([
            ':visitor_id' => 2,
            ':email' => 'lisi@example.com',
            ':consent_type' => 'analytics',
            ':consent_value' => 'granted',
            ':ip_address' => '10.0.0.5',
            ':user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X)...'
        ]);

        $exportStmt = self::$pdo->prepare("INSERT INTO export_history (
            visitor_id, email, export_type, export_format, file_path, exported_by
        ) VALUES (
            :visitor_id, :email, :export_type, :export_format, :file_path, :exported_by
        )");
        $exportStmt->execute([
            ':visitor_id' => 1,
            ':email' => 'zhangsan@example.com',
            ':export_type' => 'visitor_data',
            ':export_format' => 'csv',
            ':file_path' => '/exports/visitor_1_20240115.csv',
            ':exported_by' => 'admin'
        ]);
        $exportStmt->execute([
            ':visitor_id' => 1,
            ':email' => 'zhangsan@example.com',
            ':export_type' => 'visitor_data',
            ':export_format' => 'json',
            ':file_path' => '/exports/visitor_1_20240220.json',
            ':exported_by' => 'admin'
        ]);

        $reportStmt = self::$pdo->prepare("INSERT OR IGNORE INTO aggregated_reports (
            report_date, total_visitors, total_browsers, total_countries
        ) VALUES (
            :report_date, :total_visitors, :total_browsers, :total_countries
        )");
        $reportStmt->execute([
            ':report_date' => date('Y-m-d', strtotime('-1 day')),
            ':total_visitors' => 156,
            ':total_browsers' => '{"Chrome": 98, "Safari": 35, "Firefox": 15, "Edge": 8}',
            ':total_countries' => '{"China": 142, "United States": 8, "Japan": 4, "Others": 2}'
        ]);
        $reportStmt->execute([
            ':report_date' => date('Y-m-d', strtotime('-2 days')),
            ':total_visitors' => 203,
            ':total_browsers' => '{"Chrome": 125, "Safari": 45, "Firefox": 22, "Edge": 11}',
            ':total_countries' => '{"China": 185, "United States": 10, "Japan": 5, "Others": 3}'
        ]);
    }
}
