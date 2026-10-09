<?php
/**
 * Smart Library Management System - Database Layer (PDO SQL)
 * Replaces old flat-file JSON with SQL-based relational architecture.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get or initialize the PDO database connection
 * @return PDO
 */
function get_db() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';
    $driver = $config['driver'] ?? 'mysql';

    if ($driver === 'mysql') {
        $cfg = $config['mysql'];
        $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['database']};charset={$cfg['charset']}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], $options);
            return $pdo;
        } catch (PDOException $e) {
            // Attempt to create database if it does not exist on MySQL
            try {
                $rootDsn = "mysql:host={$cfg['host']};port={$cfg['port']};charset={$cfg['charset']}";
                $tempPdo = new PDO($rootDsn, $cfg['username'], $cfg['password'], $options);
                $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['database']}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                unset($tempPdo);
                
                $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], $options);
                initialize_tables($pdo, 'mysql');
                return $pdo;
            } catch (PDOException $mysqlError) {
                // If MySQL is completely down or unreachable, seamlessly fallback to SQLite for local development
                return init_sqlite_fallback($config['sqlite']['path']);
            }
        }
    } else {
        return init_sqlite_fallback($config['sqlite']['path']);
    }
}

/**
 * Initialize SQLite fallback instance
 */
function init_sqlite_fallback($sqlitePath) {
    static $sqlitePdo = null;
    if ($sqlitePdo !== null) {
        return $sqlitePdo;
    }

    if (!in_array('sqlite', PDO::getAvailableDrivers())) {
        json_response(false, "Database Error: Neither 'pdo_mysql' nor 'pdo_sqlite' extension is enabled in your php.ini. Please enable extension=pdo_mysql in your PHP configuration.", null, 500);
    }

    $isNew = !file_exists($sqlitePath);
    $sqlitePdo = new PDO("sqlite:" . $sqlitePath);
    $sqlitePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sqlitePdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    if ($isNew || filesize($sqlitePath) === 0) {
        initialize_tables($sqlitePdo, 'sqlite');
    }
    return $sqlitePdo;
}

/**
 * Ensure database tables and default seed data exist
 */
function initialize_tables(PDO $pdo, $type = 'mysql') {
    $autoInc = ($type === 'sqlite') ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';
    $pk = ($type === 'sqlite') ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';

    // 1. Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id {$pk},
        user_id VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL DEFAULT 'student',
        created_at DATE NOT NULL
    )");

    // 2. Books Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS books (
        id {$pk},
        title VARCHAR(255) NOT NULL,
        author VARCHAR(150) NOT NULL,
        category VARCHAR(100) NOT NULL DEFAULT 'General',
        isbn VARCHAR(50) NOT NULL UNIQUE,
        total_copies INT NOT NULL DEFAULT 1,
        available_copies INT NOT NULL DEFAULT 1,
        image VARCHAR(255) DEFAULT 'b1.jpg',
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 3. Issued Books Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS issued_books (
        id {$pk},
        user_id VARCHAR(50) NOT NULL,
        student_name VARCHAR(100) NOT NULL,
        book_id INT NOT NULL,
        book_title VARCHAR(255) NOT NULL,
        isbn VARCHAR(50) NOT NULL,
        issue_date DATE NOT NULL,
        due_date DATE NOT NULL,
        return_date DATE DEFAULT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'Issued',
        fine DECIMAL(10,2) NOT NULL DEFAULT 0.00
    )");

    // 4. Messages Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id {$pk},
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL,
        subject VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'Unread',
        created_at DATE NOT NULL
    )");

    // Seed default admin if table is empty
    $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare("INSERT INTO users (user_id, name, email, password, role, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['ADMIN01', 'System Librarian', 'admin@library.com', 'admin123', 'admin', '2026-01-01']);
        $stmt->execute(['STU101', 'John Doe', 'john@example.com', 'student123', 'student', '2026-02-15']);
        $stmt->execute(['STU102', 'Emily Clark', 'emily@example.com', 'student123', 'student', '2026-03-10']);
        $stmt->execute(['STU103', 'David Miller', 'david@example.com', 'student123', 'student', '2026-04-05']);
    }

    // Seed default books if table is empty
    $bookCount = (int)$pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
    if ($bookCount === 0) {
        $stmt = $pdo->prepare("INSERT INTO books (title, author, category, isbn, total_copies, available_copies, image, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $books = [
            ["The Wealth of Nations", "Adam Smith", "Economics", "978-0140432084", 5, 4, "b9.jpg", "A foundational work of classical economics exploring division of labor, productivity, and free markets."],
            ["The World as I See It", "Albert Einstein", "Science", "978-0806527901", 4, 4, "b10.jpg", "Albert Einstein's personal reflections on physics, humanity, peace, and the universe."],
            ["Computer Networks & Systems", "Andrew S. Tanenbaum", "Technology", "978-0132126953", 4, 3, "b1.jpg", "Complete guide to modern networking principles, architecture, and protocols."],
            ["Clean Architecture & Code", "Robert C. Martin", "Technology", "978-0134494166", 3, 2, "b2.jpg", "A craftsman's guide to software structure, design patterns, and clean programming principles."],
            ["Brief History of Time", "Stephen Hawking", "Science", "978-0553380163", 6, 6, "b3.jpg", "An iconic exploration of space, black holes, time, and the origins of the cosmos."],
            ["To Kill a Mockingbird", "Harper Lee", "Literature", "978-0061120084", 5, 5, "b4.jpg", "Classic literary masterpiece exploring justice, empathy, and integrity in American history."],
            ["Sapiens: A Brief History", "Yuval Noah Harari", "History", "978-0062316097", 4, 4, "b5.jpg", "How humankind evolved from primitive foragers to masters of Planet Earth."],
            ["Principles of Economics", "N. Gregory Mankiw", "Economics", "978-1305585126", 3, 2, "b6.jpg", "Standard textbook on micro and macroeconomic principles and fiscal policies."],
            ["The Great Gatsby", "F. Scott Fitzgerald", "Literature", "978-0743273565", 4, 4, "b7.jpg", "A portrait of the Jazz Age, wealth, disillusionment, and the American dream."],
            ["Ancient World Civilizations", "Peter Heather", "History", "978-0195155846", 4, 4, "b8.jpg", "Detailed study of early civilizations, empire building, and societal evolutions."]
        ];
        foreach ($books as $b) {
            $stmt->execute($b);
        }
    }

    // Seed default issued records
    $issuedCount = (int)$pdo->query("SELECT COUNT(*) FROM issued_books")->fetchColumn();
    if ($issuedCount === 0) {
        $stmt = $pdo->prepare("INSERT INTO issued_books (user_id, student_name, book_id, book_title, isbn, issue_date, due_date, return_date, status, fine) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(['STU101', 'John Doe', 1, 'The Wealth of Nations', '978-0140432084', '2026-09-18', '2026-10-02', null, 'Issued', 0.00]);
        $stmt->execute(['STU102', 'Emily Clark', 4, 'Clean Architecture & Code', '978-0134494166', '2026-09-10', '2026-09-24', null, 'Overdue', 40.00]);
        $stmt->execute(['STU103', 'David Miller', 7, 'Sapiens: A Brief History', '978-0062316097', '2026-08-01', '2026-08-15', '2026-08-14', 'Returned', 0.00]);
    }
}

// Send JSON API Response
function json_response($status, $message, $data = null, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Get Currently Logged In User
function get_logged_in_user() {
    return $_SESSION['user'] ?? null;
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user']);
}

// Check if current user is Admin
function is_admin() {
    return isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin';
}

// Automated Overdue Fine Calculation Engine (₹5 per day overdue)
function calculate_overdue_fine($due_date_str, $return_date_str = null) {
    $dueDate = new DateTime($due_date_str);
    $checkDate = $return_date_str ? new DateTime($return_date_str) : new DateTime();

    // Reset time components for accurate day comparison
    $dueDate->setTime(0, 0, 0);
    $checkDate->setTime(0, 0, 0);

    if ($checkDate > $dueDate) {
        $interval = $dueDate->diff($checkDate);
        $overdueDays = (int)$interval->days;
        $fineRatePerDay = 5; // ₹5 per day
        return [
            'is_overdue' => true,
            'overdue_days' => $overdueDays,
            'fine_amount' => $overdueDays * $fineRatePerDay
        ];
    }

    return [
        'is_overdue' => false,
        'overdue_days' => 0,
        'fine_amount' => 0
    ];
}
?>
