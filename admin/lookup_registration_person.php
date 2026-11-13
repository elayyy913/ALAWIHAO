<?php
session_start();
include '../db_connect.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['found' => false, 'error' => 'Unauthorized']);
    exit;
}

$type = $_GET['type'] ?? '';
$rawContact = $_GET['contact'] ?? '';
$contact = is_string($rawContact) ? preg_replace('/\D+/', '', $rawContact) : '';
$country = $_GET['country'] ?? 'PH';

if ($country === 'PH' && preg_match('/^63\d{10}$/', $contact)) {
    $contact = '0' . substr($contact, 2);
}

if (!in_array($type, ['maternal', 'infant'], true) || strlen($contact) < 9 || strlen($contact) > 15) {
    http_response_code(400);
    echo json_encode(['found' => false, 'error' => 'Enter a valid contact number.']);
    exit;
}

$cleanContact = "REPLACE(REPLACE(REPLACE(contact, '-', ''), ' ', ''), '+', '')";
$normalizedContact = $country === 'PH'
    ? "CASE WHEN LEFT({$cleanContact}, 2) = '63' THEN CONCAT('0', SUBSTRING({$cleanContact}, 3)) ELSE {$cleanContact} END"
    : $cleanContact;
$table = $type === 'maternal' ? 'maternal_registration' : 'children';

$columns = $type === 'maternal'
    ? 'family_serial, client_lname, client_fname, client_mi, client_ext, dob, blood_type, highest_educ, occupation, spouse_lname, spouse_fname, spouse_mi, spouse_ext, spouse_dob, spouse_blood, street, barangay, municipality, province, income, phic_cat, philhealth_no'
    : 'family_no, address, barangay, mother_name, father_name';
$sql = "SELECT {$columns} FROM {$table} WHERE {$normalizedContact} = ? ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['found' => false, 'error' => 'Could not prepare patient lookup.']);
    exit;
}

$stmt->bind_param('s', $contact);
if (!$stmt->execute()) {
    $stmt->close();
    http_response_code(500);
    echo json_encode(['found' => false, 'error' => 'Could not look up patient details.']);
    exit;
}

$record = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$record) {
    echo json_encode(['found' => false]);
    exit;
}

echo json_encode(['found' => true, 'data' => $record], JSON_INVALID_UTF8_SUBSTITUTE);
