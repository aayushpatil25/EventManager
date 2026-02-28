<?php
// Start session FIRST before anything else
session_start();

require 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Debug: check what's in session
    error_log('Session data: ' . print_r($_SESSION, true));
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'fetch';

// Set JSON header
header('Content-Type: application/json');

switch ($action) {
    case 'fetch':
        // Fetch notifications for user
        $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        
        // Count unread
        $unread_sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
        $unread_stmt = $conn->prepare($unread_sql);
        $unread_stmt->bind_param("i", $user_id);
        $unread_stmt->execute();
        $unread_result = $unread_stmt->get_result();
        $unread_count = $unread_result->fetch_assoc()['count'];
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unread_count
        ]);
        break;
        
    case 'mark_read':
        // Mark notification as read
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $notification_id = $_POST['notification_id'] ?? 0;
            
            $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $notification_id, $user_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to mark as read']);
            }
        }
        break;
        
    case 'clear_all':
        // Delete all notifications for user
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sql = "DELETE FROM notifications WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to clear notifications']);
            }
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>