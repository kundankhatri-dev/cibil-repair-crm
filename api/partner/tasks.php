<?php
// api/partner/tasks.php
// Daily task management and checklist

session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$partner_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';

// Create tasks table
$tasksTable = 'partner_tasks';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$tasksTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $createTable = "CREATE TABLE $tasksTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_id INT NOT NULL,
        title VARCHAR(255),
        description TEXT,
        priority ENUM('high', 'medium', 'low') DEFAULT 'medium',
        status ENUM('pending', 'completed', 'overdue') DEFAULT 'pending',
        due_date DATE,
        completed_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_partner (partner_id),
        INDEX idx_status (status)
    )";
    mysqli_query($conn, $createTable);
}

// Predefined daily checklist
$checklist = [
    ['task' => 'Follow up with new leads', 'priority' => 'high', 'points' => 10],
    ['task' => 'Update lead statuses', 'priority' => 'high', 'points' => 5],
    ['task' => 'Review pending payouts', 'priority' => 'medium', 'points' => 5],
    ['task' => 'Check support tickets', 'priority' => 'medium', 'points' => 5],
    ['task' => 'Share promotional content on social media', 'priority' => 'low', 'points' => 5]
];

if ($action === 'list') {
    // Get today's tasks
    $today = date('Y-m-d');
    $query = "SELECT * FROM $tasksTable WHERE partner_id = ? AND due_date = ? ORDER BY FIELD(priority, 'high', 'medium', 'low')";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "is", $partner_id, $today);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    // If no tasks for today, create from checklist
    if (empty($tasks)) {
        foreach ($checklist as $item) {
            $insert = mysqli_prepare($conn, "INSERT INTO $tasksTable (partner_id, title, priority, due_date) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($insert, "isss", $partner_id, $item['task'], $item['priority'], $today);
            mysqli_stmt_execute($insert);
        }
        
        // Refetch tasks
        mysqli_stmt_execute($stmt);
        $tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
    
    // Calculate completion score
    $completed = count(array_filter($tasks, fn($t) => $t['status'] === 'completed'));
    $completion_score = count($tasks) > 0 ? round(($completed / count($tasks)) * 100) : 0;
    
    echo json_encode([
        'success' => true,
        'tasks' => $tasks,
        'checklist' => $checklist,
        'stats' => [
            'total_tasks' => count($tasks),
            'completed' => $completed,
            'pending' => count(array_filter($tasks, fn($t) => $t['status'] === 'pending')),
            'completion_score' => $completion_score,
            'completion_message' => $completion_score == 100 ? '🎉 Perfect! All tasks completed!' : ($completion_score >= 70 ? '👍 Great progress!' : '💪 Keep going!')
        ]
    ]);
    
} elseif ($action === 'complete') {
    $data = json_decode(file_get_contents('php://input'), true);
    $task_id = $data['task_id'] ?? 0;
    
    $update = mysqli_prepare($conn, "UPDATE $tasksTable SET status = 'completed', completed_at = NOW() WHERE id = ? AND partner_id = ?");
    mysqli_stmt_bind_param($update, "ii", $task_id, $partner_id);
    
    if (mysqli_stmt_execute($update)) {
        echo json_encode(['success' => true, 'message' => 'Task marked as completed']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update task']);
    }
}

mysqli_close($conn);
?>