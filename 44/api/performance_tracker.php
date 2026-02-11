<?php
/**
 * Performance Tracker - Track API response times and performance metrics
 */

require __DIR__ . '/../config.php';

// Create performance_logs table if it doesn't exist
try {
    $conn->query("
        CREATE TABLE IF NOT EXISTS performance_logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            endpoint VARCHAR(255) NOT NULL,
            method VARCHAR(10) NOT NULL,
            response_time_ms DECIMAL(10,2) NOT NULL,
            status_code INT NOT NULL,
            memory_usage INT UNSIGNED,
            query_count INT UNSIGNED DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_endpoint (endpoint),
            INDEX idx_created_at (created_at),
            INDEX idx_response_time (response_time_ms)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (Exception $e) {
    // Table might already exist
}

// Track performance
function track_performance($endpoint, $method, $response_time_ms, $status_code, $memory_usage = null, $query_count = 0) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO performance_logs 
            (endpoint, method, response_time_ms, status_code, memory_usage, query_count)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssdiii", 
            $endpoint, 
            $method, 
            $response_time_ms, 
            $status_code,
            $memory_usage ?? memory_get_usage(true),
            $query_count
        );
        $stmt->execute();
    } catch (Exception $e) {
        // Silently fail - don't break the application
        error_log("Performance tracking failed: " . $e->getMessage());
    }
}

// Get performance statistics
function get_performance_stats($endpoint = null, $hours = 24) {
    global $conn;
    
    // Simplified query without complex subqueries
    $query = "
        SELECT 
            endpoint,
            method,
            COUNT(*) as request_count,
            AVG(response_time_ms) as avg_response_time,
            MIN(response_time_ms) as min_response_time,
            MAX(response_time_ms) as max_response_time,
            AVG(memory_usage) as avg_memory,
            AVG(query_count) as avg_queries,
            SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as error_count
        FROM performance_logs
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
    ";
    
    $params = [$hours];
    $types = "i";
    
    if ($endpoint) {
        $query .= " AND endpoint = ?";
        $params[] = $endpoint;
        $types .= "s";
    }
    
    $query .= " GROUP BY endpoint, method ORDER BY avg_response_time DESC";
    
    $stmt = $conn->prepare($query);
    if (count($params) > 1) {
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt->bind_param($types, $params[0]);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $stats = [];
    while ($row = $result->fetch_assoc()) {
        $stats[] = [
            'endpoint' => $row['endpoint'],
            'method' => $row['method'],
            'request_count' => (int)$row['request_count'],
            'avg_response_time_ms' => round((float)$row['avg_response_time'], 2),
            'min_response_time_ms' => round((float)$row['min_response_time'], 2),
            'max_response_time_ms' => round((float)$row['max_response_time'], 2),
            'p95_response_time_ms' => calculate_percentile($conn, $row['endpoint'], $row['method'], $hours, 95),
            'p99_response_time_ms' => calculate_percentile($conn, $row['endpoint'], $row['method'], $hours, 99),
            'avg_memory_bytes' => (int)$row['avg_memory'],
            'avg_queries' => round((float)$row['avg_queries'], 2),
            'error_count' => (int)$row['error_count'],
            'error_rate' => round(($row['error_count'] / $row['request_count']) * 100, 2)
        ];
    }
    
    return $stats;
}

// Calculate percentile (simplified - uses ORDER BY and LIMIT)
function calculate_percentile($conn, $endpoint, $method, $hours, $percentile) {
    try {
        // Get total count first
        $count_stmt = $conn->prepare("
            SELECT COUNT(*) as total
            FROM performance_logs
            WHERE endpoint = ? AND method = ?
              AND created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
        ");
        $count_stmt->bind_param("ssi", $endpoint, $method, $hours);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        $total = (int)$count_row['total'];
        
        if ($total === 0) {
            return null;
        }
        
        // Calculate offset for percentile (e.g., 95th percentile = skip bottom 5%)
        $offset = floor($total * (100 - $percentile) / 100);
        
        // Get value at that position
        $stmt = $conn->prepare("
            SELECT response_time_ms
            FROM performance_logs
            WHERE endpoint = ? AND method = ?
              AND created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
            ORDER BY response_time_ms DESC
            LIMIT 1 OFFSET ?
        ");
        $stmt->bind_param("ssii", $endpoint, $method, $hours, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return round((float)$row['response_time_ms'], 2);
        }
    } catch (Exception $e) {
        // If percentile calculation fails, return null
        error_log("Percentile calculation failed: " . $e->getMessage());
    }
    return null;
}

// Cleanup old performance logs (keep last 30 days)
function cleanup_performance_logs() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            DELETE FROM performance_logs
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        return $stmt->affected_rows;
    } catch (Exception $e) {
        error_log("Performance log cleanup failed: " . $e->getMessage());
        return 0;
    }
}
