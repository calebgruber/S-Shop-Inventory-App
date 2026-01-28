<?php
/**
 * Database Configuration - EXAMPLE
 * Copy this file to database.php and update with your credentials
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'voxelnodes_sshop_dev');
define('DB_USER', 'voxelnodes_sshop_dev');
define('DB_PASS', '3[G0{=H;sw04');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get database connection
 * @return mysqli
 */
function getDbConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die('Database connection failed: ' . $conn->connect_error);
        }
        
        $conn->set_charset(DB_CHARSET);
    }
    
    return $conn;
}

/**
 * Execute a query safely with prepared statements
 * @param string $query SQL query with ? placeholders
 * @param array $params Parameters to bind
 * @param string $types Parameter types (s=string, i=int, d=double, b=blob)
 * @return mysqli_result|bool
 */
function executeQuery($query, $params = [], $types = '') {
    $conn = getDbConnection();
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log('SQL Error: ' . $conn->error);
        return false;
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result !== false ? $result : $stmt;
}

/**
 * Get last insert ID
 * @return int
 */
function getLastInsertId() {
    return getDbConnection()->insert_id;
}

/**
 * Escape string for SQL
 * @param string $str
 * @return string
 */
function escapeString($str) {
    return getDbConnection()->real_escape_string($str);
}
