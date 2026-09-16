<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Role Protection
|--------------------------------------------------------------------------
| Only Admin can delete rooms.
*/

if ($_SESSION["role"] !== "admin") {
    die("Access denied. Admin only.");
}

require_once "../config/database.php";


if (!isset($_GET["id"])) {
    die("Room ID not provided.");
}

$room_id = (int) $_GET["id"];


/*
|--------------------------------------------------------------------------
| Check Room Exists
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT id
    FROM rooms
    WHERE id = ?
";

$stmt = mysqli_prepare(
    $conn,
    $sql
);

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


/*
|--------------------------------------------------------------------------
| Delete Room
|--------------------------------------------------------------------------
*/

$deleteSql = "
    DELETE FROM rooms
    WHERE id = ?
";

$deleteStmt = mysqli_prepare(
    $conn,
    $deleteSql
);

mysqli_stmt_bind_param(
    $deleteStmt,
    "i",
    $room_id
);


if (mysqli_stmt_execute($deleteStmt)) {

    header("Location: index.php");
    exit;

} else {

    die(
        "Could not delete room. This room may already be linked to a reservation."
    );
}

?>