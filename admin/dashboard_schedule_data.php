<?php
date_default_timezone_set('Asia/Manila');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
include __DIR__ . '/../db_connect.php';

header('Content-Type: application/json; charset=utf-8');

if (!in_array($_SESSION['role'] ?? '', ['Admin', 'Super Admin'], true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

mysqli_report(MYSQLI_REPORT_OFF);

$scheduleTable = mysqli_query($conn, "SHOW TABLES LIKE 'schedules'");
if (!$scheduleTable) {
    error_log('Unable to check schedules table for dashboard refresh: ' . mysqli_error($conn));
    http_response_code(500);
    echo json_encode(['error' => 'Unable to load dashboard schedules.']);
    exit();
}

$schedules = ['Maternal' => [], 'Child' => []];
if (mysqli_num_rows($scheduleTable) > 0) {
    $scheduleQuery = mysqli_query($conn, "SELECT id, category, patient_name, service_type,
            schedule_date, schedule_time, status
        FROM schedules
        WHERE category IN ('Maternal', 'Child')
          AND LOWER(TRIM(status)) != 'completed'
          AND schedule_date >= CURDATE()
        ORDER BY schedule_date ASC, schedule_time ASC");
    if (!$scheduleQuery) {
        error_log('Unable to load dashboard schedules: ' . mysqli_error($conn));
        http_response_code(500);
        echo json_encode(['error' => 'Unable to load dashboard schedules.']);
        exit();
    }

    while ($schedule = mysqli_fetch_assoc($scheduleQuery)) {
        $category = strcasecmp($schedule['category'], 'Maternal') === 0 ? 'Maternal' : 'Child';
        $limit = $category === 'Maternal' ? 10 : 5;
        if (count($schedules[$category]) < $limit) {
            $schedules[$category][] = $schedule;
        }
    }

    $todayCountsQuery = mysqli_query($conn, "SELECT category, COUNT(*) AS total
        FROM schedules
        WHERE category IN ('Maternal', 'Child')
          AND LOWER(TRIM(status)) != 'completed'
          AND schedule_date = CURDATE()
        GROUP BY category");
    if (!$todayCountsQuery) {
        error_log('Unable to count today dashboard schedules: ' . mysqli_error($conn));
        http_response_code(500);
        echo json_encode(['error' => 'Unable to count dashboard schedules.']);
        exit();
    }
    $todayCounts = ['Maternal' => 0, 'Child' => 0];
    while ($count = mysqli_fetch_assoc($todayCountsQuery)) {
        $category = strcasecmp($count['category'], 'Maternal') === 0 ? 'Maternal' : 'Child';
        $todayCounts[$category] = (int) $count['total'];
    }
} else {
    $todayCounts = ['Maternal' => 0, 'Child' => 0];
}

echo json_encode([
    'maternal' => $schedules['Maternal'],
    'infant' => $schedules['Child'],
    'today_maternal_count' => $todayCounts['Maternal'],
    'today_infant_count' => $todayCounts['Child']
], JSON_INVALID_UTF8_SUBSTITUTE);
