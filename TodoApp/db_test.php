<?php
require 'config.php';

try {
    $stmt = $conn->query("SELECT 'Database Connected Successfully!' AS message");
    $result = $stmt->fetch();
    echo json_encode(["status" => "success", "message" => $result["message"]]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Connection Failed: " . $e->getMessage()]);
}
?>