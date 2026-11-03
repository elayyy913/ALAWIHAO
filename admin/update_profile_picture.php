<?php
session_start();

$role = $_SESSION['role'] ?? '';
$allowedSettings = [
    'Admin' => 'admin_settings.php',
    'Super Admin' => 'super_admin_setting.php',
    'User' => '../user_settings.php'
];
if (!isset($_SESSION['user_id']) || !isset($allowedSettings[$role])) {
    header("Location: ../login.php");
    exit();
}

$settingsPage = $allowedSettings[$role];
$redirectWithMessage = static function ($message, $type) use ($settingsPage) {
    $_SESSION['profile_picture_message'] = $message;
    $_SESSION['profile_picture_message_type'] = $type;
    header("Location: " . $settingsPage);
    exit();
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . $settingsPage);
    exit();
}

$sessionToken = $_SESSION['profile_picture_csrf_token'] ?? '';
$requestToken = $_POST['csrf_token'] ?? '';
if ($sessionToken === '' || !is_string($requestToken) || !hash_equals($sessionToken, $requestToken)) {
    $redirectWithMessage("Your session expired. Please try uploading the picture again.", 'error');
}

if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
    $redirectWithMessage("Select a JPG, PNG, or WEBP profile picture to upload.", 'error');
}

$file = $_FILES['profile_picture'];
if ($file['size'] < 1 || $file['size'] > 5 * 1024 * 1024 || !is_uploaded_file($file['tmp_name'])) {
    $redirectWithMessage("The profile picture must be a valid image no larger than 5MB.", 'error');
}

$imageInfo = @getimagesize($file['tmp_name']);
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
$extensions = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp'
];
if ($imageInfo === false || !isset($extensions[$mime]) || $imageInfo['mime'] !== $mime) {
    $redirectWithMessage("Only valid JPG, PNG, and WEBP images are allowed.", 'error');
}

include '../db_connect.php';
$userId = (int)$_SESSION['user_id'];
$accountStmt = $conn->prepare("SELECT role, profile_picture FROM users WHERE id = ?");
if (!$accountStmt) {
    error_log("Profile picture account lookup preparation failed: " . $conn->error);
    $redirectWithMessage("Unable to load your account. Please try again.", 'error');
}
$accountStmt->bind_param("i", $userId);
if (!$accountStmt->execute()) {
    error_log("Profile picture account lookup failed: " . $accountStmt->error);
    $accountStmt->close();
    $redirectWithMessage("Unable to load your account. Please try again.", 'error');
}
$account = $accountStmt->get_result()->fetch_assoc();
$accountStmt->close();
if (!$account || $account['role'] !== $role) {
    $redirectWithMessage("Your account could not be verified. Please sign in again.", 'error');
}

$uploadDirectory = __DIR__ . '/uploads/profile';
if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
    error_log("Unable to create profile image directory: " . $uploadDirectory);
    $redirectWithMessage("The profile picture could not be saved right now.", 'error');
}

$filename = 'profile_' . $userId . '_' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
$destination = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    error_log("Unable to move uploaded profile picture into place.");
    $redirectWithMessage("The profile picture could not be saved right now.", 'error');
}

$storedPath = 'uploads/profile/' . $filename;
$updateStmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ? AND role = ?");
if (!$updateStmt) {
    @unlink($destination);
    error_log("Profile picture update preparation failed: " . $conn->error);
    $redirectWithMessage("The profile picture could not be saved right now.", 'error');
}
$updateStmt->bind_param("sis", $storedPath, $userId, $role);
if (!$updateStmt->execute()) {
    @unlink($destination);
    error_log("Profile picture update failed: " . $updateStmt->error);
    $updateStmt->close();
    $redirectWithMessage("The profile picture could not be saved right now.", 'error');
}
$updateStmt->close();

$oldFilename = basename(str_replace('\\', '/', (string)($account['profile_picture'] ?? '')));
if (
    preg_match('/^profile_' . preg_quote((string)$userId, '/') . '_[A-Za-z0-9_-]+\.(jpg|png|webp)$/i', $oldFilename)
    && $oldFilename !== $filename
) {
    $oldFile = $uploadDirectory . DIRECTORY_SEPARATOR . $oldFilename;
    if (is_file($oldFile)) {
        @unlink($oldFile);
    }
}

$_SESSION['profile_picture'] = $storedPath;
$redirectWithMessage("Profile picture updated successfully.", 'success');
