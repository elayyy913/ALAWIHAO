<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$role    = $_SESSION['role'] ?? 'User';

if ($role === 'Admin' || $role === 'Super Admin') {
    $stmt = $conn->prepare("
        SELECT id, schedule_id, title, message, type, is_read, created_at
        FROM notifications
        WHERE (user_id = ? OR user_id = 0) AND target_role = ?
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $stmt->bind_param("is", $user_id, $role);
} else {
    $stmt = $conn->prepare("
        SELECT id, schedule_id, title, message, type, is_read, created_at
        FROM notifications
        WHERE user_id = ? AND target_role = 'User'
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
$unread_count  = 0;

while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'id'          => (int) $row['id'],
        'schedule_id' => (int) $row['schedule_id'],
        'title'       => $row['title'],
        'message'     => $row['message'],
        'type'        => $row['type'],
        'is_read'     => (bool) $row['is_read'],
        'time_ago'    => time_ago($row['created_at']),
        'created_at'  => $row['created_at'],
    ];
    if (!$row['is_read']) $unread_count++;
}
$stmt->close();

echo json_encode([
    'success'       => true,
    'unread_count'  => $unread_count,
    'notifications' => $notifications,
]);

function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'Just now';
    if ($diff < 3600)   return floor($diff / 60) . ' minute(s) ago';
    if ($diff < 86400)  return floor($diff / 3600) . ' hour(s) ago';
    if ($diff < 604800) return floor($diff / 86400) . ' day(s) ago';
    return date('M j, Y', strtotime($datetime));
}
