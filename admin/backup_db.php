<?php
session_start();
include '../db_connect.php';

// Kunin ang pangalan ng database mula sa connection o itakda ito
// Sinusuri nito ang lahat ng tables sa database para ma-export
$tables = array();
$result = mysqli_query($conn, "SHOW TABLES");
if ($result) {
    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }
} else {
    exit("Error retrieving tables.");
}

$sql_content = "-- Alawihao Health Center Database Backup\n";
$sql_content .= "-- Generated: " . date("Y-m-d H:i:s") . "\n\n";
$sql_content .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$sql_content .= "SET time_zone = \"+08:00\";\n\n";

foreach ($tables as $table) {
    // I-export ang structure ng table
    $row2 = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
    if ($row2) {
        $row3 = mysqli_fetch_row($row2);
        $sql_content .= "\n\n" . $row3[1] . ";\n\n";
    }

    // I-export ang mga laman/data ng bawat table
    $result = mysqli_query($conn, "SELECT * FROM `$table`");
    if ($result) {
        $num_fields = mysqli_num_fields($result);
        while ($row = mysqli_fetch_row($result)) {
            $sql_content .= "INSERT INTO `$table` VALUES(";
            for ($j = 0; $j < $num_fields; $j++) {
                $row[$j] = addslashes($row[$j]);
                $row[$j] = str_replace("\n", "\\n", $row[$j]);
                if (isset($row[$j])) {
                    $sql_content .= '"' . $row[$j] . '"';
                } else {
                    $sql_content .= '""';
                }
                if ($j < ($num_fields - 1)) {
                    $sql_content .= ',';
                }
            }
            $sql_content .= ");\n";
        }
    }
}

// Pangalan ng file na ida-download kasama ang petsa at oras ngayon
$filename = "alawihao_backup_" . date("Y-m-d_H-i-s") . ".sql";

// I-trigger ang file download sa browser
header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: Binary");
header("Content-Disposition: attachment; filename=\"$filename\"");
echo $sql_content;
exit;
?>