<?php
// api.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
require 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['action'])) {
    echo json_encode(["status" => "error", "message" => "Missing 'action' parameter"]);
    exit();
}

$action = $data['action'];

switch ($action) {
    case "register":
        if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
            echo json_encode(["status" => "error", "message" => "All fields are required!"]);
            exit();
        }

        $username = $conn->real_escape_string($data['username']);
        $email = $conn->real_escape_string($data['email']);
        $password = password_hash($data['password'], PASSWORD_BCRYPT);

        $checkUserQuery = "SELECT id FROM users WHERE username=? OR email=?";
        $stmt = $conn->prepare($checkUserQuery);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "User already exists!"]);
            exit();
        }
        $stmt->close();

        $query = "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $username, $email, $password);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "User registered successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Registration failed"]);
        }
        $stmt->close();
        break;

    case "login":
        if (!isset($data['username']) || !isset($data['password'])) {
            echo json_encode(["status" => "error", "message" => "Username and password are required!"]);
            exit();
        }

        $username = $conn->real_escape_string($data['username']);
        $password = $data['password'];

        $query = "SELECT id, password_hash FROM users WHERE username=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($userId, $hash);
        $stmt->fetch();

        if ($userId && password_verify($password, $hash)) {
            echo json_encode(["status" => "success", "user" => ["id" => $userId, "username" => $username]]);
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid credentials"]);
        }
        $stmt->close();
        break;

    case "get_tasks":
        if (!isset($data['user_id'])) {
            echo json_encode(["status" => "error", "message" => "User ID required!"]);
            exit();
        }

        $userId = intval($data['user_id']);
        $query = "SELECT id, title, description, start_date, end_date, status FROM tasks WHERE user_id=? ORDER BY start_date ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $tasks = [];
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }

        echo json_encode(["status" => "success", "tasks" => $tasks]);
        $stmt->close();
        break;

    case "create_task":
        if (!isset($data['user_id']) || !isset($data['title']) || !isset($data['description']) || !isset($data['status']) || !isset($data['start_date']) || !isset($data['end_date'])) {
            echo json_encode(["status" => "error", "message" => "All fields are required for creating a task!"]);
            exit();
        }

        $userId = intval($data['user_id']);
        $title = $conn->real_escape_string($data['title']);
        $description = $conn->real_escape_string($data['description']);
        $status = $conn->real_escape_string($data['status']);
        $startDate = $conn->real_escape_string($data['start_date']);
        $endDate = $conn->real_escape_string($data['end_date']);

        $query = "INSERT INTO tasks (user_id, title, description, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssss", $userId, $title, $description, $startDate, $endDate, $status);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Task created successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Task creation failed"]);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Invalid action"]);
}

$conn->close();
