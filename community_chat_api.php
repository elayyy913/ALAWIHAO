<?php
session_start();
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/community_chat_helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function communityChatRespond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $member = communityChatCurrentMember($conn);
    if ($member === null) {
        communityChatRespond(403, ['success' => false, 'message' => 'Hindi ka kabilang sa mga miyembro ng Community.']);
    }
    ensureCommunityChatTable($conn);
} catch (RuntimeException $error) {
    error_log($error->getMessage());
    communityChatRespond(500, ['success' => false, 'message' => 'Hindi muna ma-access ang Community. Subukan ulit mamaya.']);
}

$method = $_SERVER['REQUEST_METHOD'] ?? '';
if ($method === 'GET') {
    $afterIdValue = $_GET['after_id'] ?? '0';
    if (!is_string($afterIdValue) || !ctype_digit($afterIdValue) || strlen($afterIdValue) > 18) {
        communityChatRespond(422, ['success' => false, 'message' => 'Hindi valid ang message cursor.']);
    }
    $afterId = (int) $afterIdValue;

    if ($afterId === 0) {
        $sql = "
            SELECT m.id, m.user_id, m.message, m.created_at, u.first_name, u.last_name, u.role
            FROM (
                SELECT id, user_id, message, created_at
                FROM community_messages
                ORDER BY id DESC
                LIMIT 100
            ) AS m
            INNER JOIN users u ON u.id = m.user_id
            ORDER BY m.id ASC
        ";
        $result = $conn->query($sql);
    } else {
        $stmt = $conn->prepare("
            SELECT m.id, m.user_id, m.message, m.created_at, u.first_name, u.last_name, u.role
            FROM community_messages m
            INNER JOIN users u ON u.id = m.user_id
            WHERE m.id > ?
            ORDER BY m.id ASC
            LIMIT 100
        ");
        if (!$stmt) {
            error_log('Unable to prepare Community message query: ' . $conn->error);
            communityChatRespond(500, ['success' => false, 'message' => 'Hindi ma-load ang mga mensahe. Subukan ulit mamaya.']);
        }
        $stmt->bind_param('i', $afterId);
        if (!$stmt->execute()) {
            error_log('Unable to load Community messages: ' . $stmt->error);
            $stmt->close();
            communityChatRespond(500, ['success' => false, 'message' => 'Hindi ma-load ang mga mensahe. Subukan ulit mamaya.']);
        }
        $result = $stmt->get_result();
    }

    if (!$result) {
        error_log('Unable to load Community messages: ' . $conn->error);
        communityChatRespond(500, ['success' => false, 'message' => 'Hindi ma-load ang mga mensahe. Subukan ulit mamaya.']);
    }

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $name = trim((string) $row['first_name'] . ' ' . (string) $row['last_name']);
        $messages[] = [
            'id' => (int) $row['id'],
            'user_id' => (int) $row['user_id'],
            'name' => $name !== '' ? $name : 'Member',
            'role' => (string) $row['role'],
            'message' => (string) $row['message'],
            'created_at' => (string) $row['created_at'],
        ];
    }
    if (isset($stmt)) {
        $stmt->close();
    } else {
        $result->free();
    }

    communityChatRespond(200, ['success' => true, 'messages' => $messages]);
}

if ($method === 'POST') {
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $sessionToken = $_SESSION['community_chat_csrf'] ?? '';
    if (!is_string($csrfToken) || !is_string($sessionToken) || $sessionToken === '' || !hash_equals($sessionToken, $csrfToken)) {
        communityChatRespond(403, ['success' => false, 'message' => 'Nag-expire ang session. I-refresh ang page at subukan ulit.']);
    }
    if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 8192) {
        communityChatRespond(413, ['success' => false, 'message' => 'Masyadong mahaba ang mensahe.']);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $message = is_array($input) && is_string($input['message'] ?? null)
        ? trim($input['message'])
        : '';
    if ($message === '' || mb_strlen($message, 'UTF-8') > 1000) {
        communityChatRespond(422, ['success' => false, 'message' => 'Maglagay ng mensaheng hanggang 1000 characters.']);
    }

    $now = time();
    $recentMessages = array_values(array_filter(
        $_SESSION['community_chat_requests'] ?? [],
        static fn($timestamp) => is_int($timestamp) && $timestamp > $now - 60
    ));
    if (count($recentMessages) >= 15) {
        communityChatRespond(429, ['success' => false, 'message' => 'Sandali muna bago magpadala ulit ng mensahe.']);
    }

    $stmt = $conn->prepare('INSERT INTO community_messages (user_id, message) VALUES (?, ?)');
    if (!$stmt) {
        error_log('Unable to prepare Community message insert: ' . $conn->error);
        communityChatRespond(500, ['success' => false, 'message' => 'Hindi naipadala ang mensahe. Subukan ulit mamaya.']);
    }
    $stmt->bind_param('is', $member['id'], $message);
    if (!$stmt->execute()) {
        error_log('Unable to save Community message: ' . $stmt->error);
        $stmt->close();
        communityChatRespond(500, ['success' => false, 'message' => 'Hindi naipadala ang mensahe. Subukan ulit mamaya.']);
    }
    $stmt->close();

    $recentMessages[] = $now;
    $_SESSION['community_chat_requests'] = $recentMessages;
    communityChatRespond(201, ['success' => true]);
}

header('Allow: GET, POST');
communityChatRespond(405, ['success' => false, 'message' => 'Hindi suportado ang request na ito.']);
