<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $session_name = $_SESSION['name'];
    $current_password = trim($_POST['current_password']); // Tinatanggal ang extra spaces
    $new_password = trim($_POST['new_password']);

    // Kunin ang record mula sa database
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        $stmt_name = $conn->prepare("SELECT * FROM users WHERE first_name = ?");
        $stmt_name->bind_param("s", $session_name);
        $stmt_name->execute();
        $result_name = $stmt_name->get_result();
        $row = $result_name->fetch_assoc();
    }

    if ($row) {
        $db_id = $row['id'];
        $db_password = trim($row['password']); // Tinatanggal din ang space sa database value

        // Suriin kung tama ang password (sinusubukan ang parehong plain text at hashed kung sakali)
        $is_password_correct = ($current_password === $db_password) || password_verify($current_password, $db_password);

        if ($is_password_correct) {
            if (!empty($new_password)) {
                // Kung gusto mong i-hash ang bagong password para mas secure, o plain text kung plain text ang gamit mo
                // $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_stmt->bind_param("si", $new_password, $db_id); // Palitan ng $hashed_password kung naka-hash ang sistema mo
                
                if ($update_stmt->execute()) {
                    header("Location: user_settings.php?success=1");
                    exit();
                } else {
                    echo "Database Error: " . $conn->error;
                }
            } else {
                header("Location: user_settings.php?success=1");
                exit();
            }
        } else {
            echo "<script>alert('Mali ang iyong kasalukuyang password!'); window.location.href='user_settings.php';</script>";
        }
    } else {
        echo "Error: Hindi mahanap ang user sa database.";
    }

    $conn->close();
} else {
    header("Location: user_settings.php");
    exit();
}
?>