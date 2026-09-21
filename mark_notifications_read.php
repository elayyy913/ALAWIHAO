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

// Accept both form-encoded POST and JSON body
if (!empty($_POST)) {
    $notif_id  = isset($_POST['id'])       ? (int) $_POST['id']  : 0;
    $mark_all  = isset($_POST['mark_all']) ? true                : false;
} else {
    $input    = json_decode(file_get_contents('php://input'), true) ?? [];
    $notif_id = isset($input['id'])       ? (int) $input['id']  : 0;
    $mark_all = isset($input['mark_all']) ? true                : false;
}

// mark_all=1 or id=0 both mean "mark everything"
if ($mark_all) $notif_id = 0;

if ($role === 'Admin' || $role === 'Super Admin') {
    if ($notif_id === 0) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE (user_id=? OR user_id=0) AND target_role=? AND is_read=0");
        $stmt->bind_param("is", $user_id, $role);
    } else {
        $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND (user_id=? OR user_id=0) AND target_role=?");
        $stmt->bind_param("iis", $notif_id, $user_id, $role);
    }
} else {
    if ($notif_id === 0) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE user_id=? AND target_role='User' AND is_read=0");
        $stmt->bind_param("i", $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=? AND target_role='User'");
        $stmt->bind_param("ii", $notif_id, $user_id);
    }
}

$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

echo json_encode(['success' => true, 'marked' => $affected]);
