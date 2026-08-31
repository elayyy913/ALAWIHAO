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
    $delete_password = $_POST['delete_password'];

    // Subukang hanapin gamit ang ID
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // Kung walang makita sa ID, hanapin gamit ang pangalan
    if (!$row) {
        $stmt_name = $conn->prepare("SELECT * FROM users WHERE first_name = ?");
        $stmt_name->bind_param("s", $session_name);
        $stmt_name->execute();
        $result_name = $stmt_name->get_result();
        $row = $result_name->fetch_assoc();
    }

    if ($row) {
        $db_id = $row['id'];

        if ($delete_password === $row['password']) {
            $del_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $del_stmt->bind_param("i", $db_id);
            
            if ($del_stmt->execute()) {
                session_unset();
                session_destroy();
                header("Location: login.php?account_deleted=1");
                exit();
            } else {
                echo "Database Error sa pag-delete: " . $conn->error;
            }
        } else {
            echo "<script>alert('Mali ang password na inilagay mo para sa pag-delete!'); window.location.href='user_settings.php';</script>";
        }
    } else {
        echo "Error: Hindi mahanap ang account sa database para mabura.";
    }

    $conn->close();
} else {
    header("Location: user_settings.php");
    exit();
}
?>