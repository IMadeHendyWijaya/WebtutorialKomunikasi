<?php
require_once 'config.php';

echo "<h2>Transaction Data Check</h2>";

// Check all transactions
$query = "SELECT COUNT(*) as total, payment_status FROM transactions GROUP BY payment_status";
$result = $conn->query($query);

if ($result) {
    echo "<h3>Transactions by Status:</h3>";
    while ($row = $result->fetch_assoc()) {
        echo "Status: " . $row['payment_status'] . " - Count: " . $row['total'] . "<br>";
    }
} else {
    echo "Error checking transactions: " . $conn->error;
}

// Check recent transactions
$query = "SELECT t.*, u.username, c.title as course_title 
          FROM transactions t 
          LEFT JOIN users u ON t.user_id = u.id 
          LEFT JOIN courses c ON t.course_id = c.id 
          ORDER BY t.transaction_date DESC 
          LIMIT 5";
$result = $conn->query($query);

if ($result) {
    echo "<h3>Recent Transactions:</h3>";
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . 
             " | User: " . ($row['username'] ?? 'Unknown') . 
             " | Course: " . ($row['course_title'] ?? 'Unknown') .
             " | Amount: " . number_format($row['amount'], 0, ',', '.') .
             " | Status: " . $row['payment_status'] .
             " | Date: " . $row['transaction_date'] . "<br>";
    }
} else {
    echo "Error checking recent transactions: " . $conn->error;
} 