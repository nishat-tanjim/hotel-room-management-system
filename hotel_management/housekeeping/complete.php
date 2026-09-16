<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}



if (
    $_SESSION["role"] !== "admin" &&
    $_SESSION["role"] !== "receptionist"
) {
    die("Access denied. Admin or Receptionist only.");
}

require_once "../config/database.php";


if (!isset($_GET["id"])) {
    die("Room ID not provided.");
}

$room_id = (int) $_GET["id"];




$sql = "
    SELECT
        id,
        status
    FROM rooms
    WHERE id = ?
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $room_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$room = mysqli_fetch_assoc($result);


if (!$room) {
    die("Room not found.");
}




if ($room["status"] !== "cleaning") {
    die("This room is not waiting for cleaning.");
}




$taskSql = "
    SELECT id
    FROM housekeeping
    WHERE room_id = ?
    AND status IN ('pending', 'in_progress')
    ORDER BY assigned_at DESC
    LIMIT 1
";

$taskStmt = mysqli_prepare(
    $conn,
    $taskSql
);

mysqli_stmt_bind_param(
    $taskStmt,
    "i",
    $room_id
);

mysqli_stmt_execute($taskStmt);

$taskResult = mysqli_stmt_get_result($taskStmt);

$task = mysqli_fetch_assoc($taskResult);


if (!$task) {
    die("No active housekeeping task found for this room.");
}

$task_id = (int) $task["id"];




mysqli_begin_transaction($conn);

try {


 

    $taskUpdateSql = "
        UPDATE housekeeping
        SET
            status = 'completed',
            completed_at = NOW()
        WHERE id = ?
    ";

    $taskUpdateStmt = mysqli_prepare(
        $conn,
        $taskUpdateSql
    );

    mysqli_stmt_bind_param(
        $taskUpdateStmt,
        "i",
        $task_id
    );

    if (!mysqli_stmt_execute($taskUpdateStmt)) {

        throw new Exception(
            "Could not complete housekeeping task."
        );
    }


 

    $roomUpdateSql = "
        UPDATE rooms
        SET status = 'available'
        WHERE id = ?
    ";

    $roomUpdateStmt = mysqli_prepare(
        $conn,
        $roomUpdateSql
    );

    mysqli_stmt_bind_param(
        $roomUpdateStmt,
        "i",
        $room_id
    );

    if (!mysqli_stmt_execute($roomUpdateStmt)) {

        throw new Exception(
            "Could not update room status."
        );
    }



    mysqli_commit($conn);

    header("Location: index.php");
    exit;


} catch (Exception $e) {

    mysqli_rollback($conn);

    die(
        "Cleaning completion failed: "
        . $e->getMessage()
    );
}

?>