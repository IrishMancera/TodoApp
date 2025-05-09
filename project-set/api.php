<?php
header("Content-Type: application/json");
require 'config.php';  // Ensure config.php includes your database connection and sets $pdo

// Get the HTTP method and action from the request
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Route the request based on the action
switch ($action) {
    case 'get_projects':
        getProjects($pdo);
        break;
    case 'create_project':
        createProject($pdo);
        break;
    case 'get_tasks':
        getTasks($pdo);
        break;
    case 'create_task':
        createTask($pdo);
        break;
    case 'update_task':
        updateTask($pdo);
        break;
    case 'delete_task':
        deleteTask($pdo);
        break;
    default:
        echo json_encode([
            "status" => "error",
            "message" => "Invalid action"
        ]);
        break;
}

// --------------------- Functions ---------------------

/**
 * Function to fetch all projects from the database.
 */
function getProjects($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM projects");
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($projects);
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }
}

/**
 * Function to create a new project.
 */
function createProject($pdo) {
    // Read JSON input from the request body
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['project_name']) || empty(trim($data['project_name']))) {
        echo json_encode([
            "status" => "error",
            "message" => "Project name required"
        ]);
        return;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO projects (project_name) VALUES (:project_name)");
        $stmt->bindParam(":project_name", $data['project_name']);
        $stmt->execute();
        echo json_encode([
            "status" => "success", 
            "message" => "Project created", 
            "id" => $pdo->lastInsertId()
        ]);
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }
}

/**
 * Function to fetch tasks from the database (optionally filtering by status).
 */
function getTasks($pdo) {
    try {
        $query = "SELECT t.*, p.project_name FROM tasks t JOIN projects p ON t.project_id = p.id";
        $params = [];

        if (isset($_GET['status'])) {
            $query .= " WHERE t.status = :status";
            $params[':status'] = $_GET['status'];
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($tasks);
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }
}

/**
 * Function to create a new task.
 */
function createTask($pdo) {
    // Read JSON input
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['project_id']) || !isset($data['task_name'])) {
        echo json_encode([
            "status" => "error",
            "message" => "Required fields missing"
        ]);
        return;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO tasks 
            (project_id, task_name, hours, start_date, end_date, status, comment) 
            VALUES (:project_id, :task_name, :hours, :start_date, :end_date, :status, :comment)"
        );

        $stmt->execute([
            ":project_id"  => $data['project_id'],
            ":task_name"   => $data['task_name'],
            ":hours"       => $data['hours'] ?? null,
            ":start_date"  => $data['start_date'] ?? null,
            ":end_date"    => $data['end_date'] ?? null,
            ":status"      => $data['status'] ?? 'pending',
            ":comment"     => $data['comment'] ?? ''
        ]);

        echo json_encode([
            "status"  => "success", 
            "message" => "Task created", 
            "id"      => $pdo->lastInsertId()
        ]);
    } catch (Exception $e) {
        echo json_encode([
            "status"  => "error",
            "message" => $e->getMessage()
        ]);
    }
}

/**
 * Function to update an existing task.
 */
function updateTask($pdo) {
    // Read JSON input
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['id'])) {
        echo json_encode([
            "status" => "error",
            "message" => "Task ID required"
        ]);
        return;
    }

    try {
        $stmt = $pdo->prepare("UPDATE tasks 
            SET project_id = :project_id, 
                task_name  = :task_name, 
                hours      = :hours, 
                start_date = :start_date, 
                end_date   = :end_date, 
                status     = :status, 
                comment    = :comment 
            WHERE id = :id"
        );

        $stmt->execute([
            ":project_id" => $data['project_id'],
            ":task_name"  => $data['task_name'],
            ":hours"      => $data['hours'],
            ":start_date" => $data['start_date'],
            ":end_date"   => $data['end_date'],
            ":status"     => $data['status'],
            ":comment"    => $data['comment'],
            ":id"         => $data['id']
        ]);

        echo json_encode([
            "status"  => "success",
            "message" => "Task updated"
        ]);
    } catch (Exception $e) {
        echo json_encode([
            "status"  => "error",
            "message" => $e->getMessage()
        ]);
    }
}

/**
 * Function to delete a task.
 */
function deleteTask($pdo) {
    // Expect a GET parameter 'id' for deletion
    if (!isset($_GET['id'])) {
        echo json_encode([
            "status"  => "error",
            "message" => "Task ID required"
        ]);
        return;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = :id");
        $stmt->execute([":id" => $_GET['id']]);
        echo json_encode([
            "status"  => "success",
            "message" => "Task deleted"
        ]);
    } catch (Exception $e) {
        echo json_encode([
            "status"  => "error",
            "message" => $e->getMessage()
        ]);
    }
}
?>
