<?php

function communityChatCurrentMember(mysqli $conn): ?array
{
    $userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$userId || $userId < 1) {
        return null;
    }

    $userStmt = $conn->prepare("SELECT first_name, last_name, role FROM users WHERE id = ? LIMIT 1");
    if (!$userStmt) {
        throw new RuntimeException('Unable to check Community access: ' . $conn->error);
    }
    $userStmt->bind_param('i', $userId);
    if (!$userStmt->execute()) {
        $error = $userStmt->error;
        $userStmt->close();
        throw new RuntimeException('Unable to check Community access: ' . $error);
    }
    $user = $userStmt->get_result()->fetch_assoc();
    $userStmt->close();

    if (!$user) {
        return null;
    }

    $role = trim((string) $user['role']);
    if (!in_array($role, ['Admin', 'Super Admin'], true)) {
        if ($role !== 'User') {
            return null;
        }

        $eligibilityStmt = $conn->prepare("
            SELECT EXISTS (
                SELECT 1 FROM maternal_registration
                WHERE user_id = ? AND status = 'Approved'
            ) OR EXISTS (
                SELECT 1 FROM children
                WHERE user_id = ? AND status = 'Approved'
            ) AS is_eligible
        ");
        if (!$eligibilityStmt) {
            throw new RuntimeException('Unable to check Community eligibility: ' . $conn->error);
        }
        $eligibilityStmt->bind_param('ii', $userId, $userId);
        if (!$eligibilityStmt->execute()) {
            $error = $eligibilityStmt->error;
            $eligibilityStmt->close();
            throw new RuntimeException('Unable to check Community eligibility: ' . $error);
        }
        $eligibility = $eligibilityStmt->get_result()->fetch_assoc();
        $eligibilityStmt->close();

        if (empty($eligibility['is_eligible'])) {
            return null;
        }
    }

    $name = trim((string) $user['first_name'] . ' ' . (string) $user['last_name']);
    return [
        'id' => (int) $userId,
        'name' => $name !== '' ? $name : 'Member ' . (int) $userId,
        'role' => $role,
    ];
}

function ensureCommunityChatTable(mysqli $conn): void
{
    $tableCheck = $conn->query("
        SELECT TABLE_NAME
        FROM information_schema.tables
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'community_messages'
        LIMIT 1
    ");
    if (!$tableCheck) {
        throw new RuntimeException('Unable to check Community chat storage: ' . $conn->error);
    }

    if ($tableCheck->num_rows > 0) {
        $tableCheck->free();
        return;
    }
    $tableCheck->free();

    $createTable = "
        CREATE TABLE IF NOT EXISTS community_messages (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT NOT NULL,
            message VARCHAR(1000) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_community_messages_user (user_id, id),
            KEY idx_community_messages_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    if (!$conn->query($createTable)) {
        throw new RuntimeException('Unable to create Community chat storage: ' . $conn->error);
    }
}
