<?php
session_start();

// Detect environment - Replit uses DATABASE_URL with PostgreSQL
$is_replit = getenv('DATABASE_URL') !== false;

if ($is_replit) {
    // Replit PostgreSQL Configuration
    $database_url = getenv('DATABASE_URL');
    $db_parts = parse_url($database_url);
    
    $db_host = $db_parts['host'];
    $db_port = $db_parts['port'] ?? 5432;
    $db_user = $db_parts['user'];
    $db_pass = $db_parts['pass'];
    $db_name = ltrim($db_parts['path'], '/');
    
    // PostgreSQL connection using PDO
    try {
        $pdo = new PDO(
            "pgsql:host=$db_host;port=$db_port;dbname=$db_name",
            $db_user,
            $db_pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $use_pdo = true;
    } catch (PDOException $e) {
        $use_pdo = false;
        $con = null;
    }
} else {
    // Local XAMPP MySQL Configuration
    $db_host = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "bhairavnath_construction";
    
    $con = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    $use_pdo = false;
    
    if ($con) {
        mysqli_set_charset($con, "utf8mb4");
    }
}

// Site Configuration
define('SITE_NAME', 'Bhairavnath Construction');
define('SITE_URL', $is_replit ? 'https://' . getenv('REPL_SLUG') . '.' . getenv('REPL_OWNER') . '.repl.co/' : 'http://localhost/bhairavnath-construction/');
define('UPLOADS_PATH', 'uploads/');
define('IS_REPLIT', $is_replit);

// Helper Functions
function sanitize($data) {
    global $con, $pdo, $use_pdo;
    $data = htmlspecialchars(trim($data));
    if ($use_pdo) {
        return $data;
    } elseif ($con) {
        return mysqli_real_escape_string($con, $data);
    }
    return $data;
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function db_query($query) {
    global $con, $pdo, $use_pdo;
    
    if ($use_pdo) {
        try {
            return $pdo->query($query);
        } catch (PDOException $e) {
            return false;
        }
    } elseif ($con) {
        return mysqli_query($con, $query);
    }
    return false;
}

function db_fetch($result) {
    global $use_pdo;
    
    if (!$result) return null;
    
    if ($use_pdo) {
        return $result->fetch(PDO::FETCH_ASSOC);
    } else {
        return mysqli_fetch_assoc($result);
    }
}

function db_num_rows($result) {
    global $use_pdo;
    
    if (!$result) return 0;
    
    if ($use_pdo) {
        return $result->rowCount();
    } else {
        return mysqli_num_rows($result);
    }
}

function db_insert_id() {
    global $con, $pdo, $use_pdo;
    
    if ($use_pdo) {
        return $pdo->lastInsertId();
    } elseif ($con) {
        return mysqli_insert_id($con);
    }
    return 0;
}

function getAdmin() {
    if (isLoggedIn()) {
        $id = $_SESSION['admin_id'];
        $result = db_query("SELECT * FROM admin WHERE id = $id");
        return db_fetch($result);
    }
    return null;
}

function uploadImage($file, $folder = 'projects') {
    $target_dir = UPLOADS_PATH . $folder . '/';
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($file_extension, $allowed)) {
        return false;
    }
    
    $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $target_file;
    }
    
    return false;
}

function deleteImage($path) {
    if (file_exists($path)) {
        unlink($path);
        return true;
    }
    return false;
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function getProjectCount($status = null) {
    if ($status) {
        $result = db_query("SELECT COUNT(*) as count FROM projects WHERE status = '$status'");
    } else {
        $result = db_query("SELECT COUNT(*) as count FROM projects");
    }
    $row = db_fetch($result);
    return $row['count'] ?? 0;
}

function getUnreadInquiries() {
    $result = db_query("SELECT COUNT(*) as count FROM inquiries WHERE is_read = 0");
    $row = db_fetch($result);
    return $row['count'] ?? 0;
}
?>
