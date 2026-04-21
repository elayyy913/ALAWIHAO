<?php
// functions.php
function generateNextID($conn) {
    $year = date('Y');
    // Finds the highest number for the current year across both tables
    $query = "SELECT MAX(CAST(SUBSTRING_INDEX(generated_id, '-', -1) AS UNSIGNED)) as max_num 
              FROM (
                  SELECT generated_id FROM users WHERE generated_id LIKE '$year-%'
                  UNION
                  SELECT generated_id FROM health_workers WHERE generated_id LIKE '$year-%'
              ) as temp";
              
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $next_num = ($row['max_num'] ? (int)$row['max_num'] + 1 : 1);
    
    return $year . '-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
}
?>