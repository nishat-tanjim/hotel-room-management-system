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
| Only Admin and Receptionist can cancel reservations.
*/

if (
    $_SESSION["role"] !== "admin" &&
    $_SESSION["role"] !== "receptionist"
) {
    die("Access denied. Admin or Receptionist only.");
}

require_once "../config/database.php";

if (!isset($_GET["id"])) {
    die("Reservation ID not provided.");
}

$reservation_id = (int) $_GET["id"];


/*
|--------------------------------------------------------------------------
| Get Reservation
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        reservation_status
    FROM reservations
    WHERE id = ?
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $reservation_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$reservation = mysqli_fetch_assoc($result);

if (!$reservation) {
    die("Reservation not found.");
}


/*
|--------------------------------------------------------------------------
| Prevent Cancelling Checked-In or Checked-Out Reservations
|--------------------------------------------------------------------------
*/

if (
    $reservation["reservation_status"] === "checked_in" ||
    $reservation["reservation_status"] === "checked_out"
) {
    die("This reservation cannot be cancelled.");
}


/*
|--------------------------------------------------------------------------
| Cancel Reservation
|--------------------------------------------------------------------------
*/

$updateSql = "
    UPDATE reservations
    SET reservation_status = 'cancelled'
    WHERE id = ?
";

$updateStmt = mysqli_prepare(
    $conn,
    $updateSql
);

mysqli_stmt_bind_param(
    $updateStmt,
    "i",
    $reservation_id
);

if (mysqli_stmt_execute($updateStmt)) {

    header("Location: index.php");
    exit;

} else {

    die("Could not cancel reservation.");
}

?>