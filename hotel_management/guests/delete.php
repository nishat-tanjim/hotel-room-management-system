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
    die("Guest ID not provided.");
}

$guest_id = (int) $_GET["id"];




$sql = "
    SELECT id
    FROM guests
    WHERE id = ?
";

$stmt = mysqli_prepare(
    $conn,
    $sql
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $guest_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$guest = mysqli_fetch_assoc($result);


if (!$guest) {
    die("Guest not found.");
}




$deleteSql = "
    DELETE FROM guests
    WHERE id = ?
";

$deleteStmt = mysqli_prepare(
    $conn,
    $deleteSql
);

mysqli_stmt_bind_param(
    $deleteStmt,
    "i",
    $guest_id
);


if (mysqli_stmt_execute($deleteStmt)) {

    header("Location: index.php");
    exit;

} else {

    die(
        "Could not delete guest. This guest may already be linked to a reservation."
    );
}

?>