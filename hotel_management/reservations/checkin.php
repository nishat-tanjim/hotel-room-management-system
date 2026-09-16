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
| Only Admin and Receptionist can check guests in.
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
        room_id,
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
| Only Confirmed Reservations Can Check In
|--------------------------------------------------------------------------
*/

if ($reservation["reservation_status"] !== "confirmed") {
    die("Only confirmed reservations can be checked in.");
}

$room_id = (int) $reservation["room_id"];


/*
|--------------------------------------------------------------------------
| Start Transaction
|--------------------------------------------------------------------------
*/

mysqli_begin_transaction($conn);

try {

    /*
    |--------------------------------------------------------------------------
    | Update Reservation Status
    |--------------------------------------------------------------------------
    */

    $reservationSql = "
        UPDATE reservations
        SET reservation_status = 'checked_in'
        WHERE id = ?
    ";

    $reservationStmt = mysqli_prepare(
        $conn,
        $reservationSql
    );

    mysqli_stmt_bind_param(
        $reservationStmt,
        "i",
        $reservation_id
    );

    if (!mysqli_stmt_execute($reservationStmt)) {
        throw new Exception("Could not update reservation.");
    }


    /*
    |--------------------------------------------------------------------------
    | Update Room Status
    |--------------------------------------------------------------------------
    */

    $roomSql = "
        UPDATE rooms
        SET status = 'occupied'
        WHERE id = ?
    ";

    $roomStmt = mysqli_prepare(
        $conn,
        $roomSql
    );

    mysqli_stmt_bind_param(
        $roomStmt,
        "i",
        $room_id
    );

    if (!mysqli_stmt_execute($roomStmt)) {
        throw new Exception("Could not update room status.");
    }


    /*
    |--------------------------------------------------------------------------
    | Save Changes
    |--------------------------------------------------------------------------
    */

    mysqli_commit($conn);

    header("Location: index.php");
    exit;

} catch (Exception $e) {

    mysqli_rollback($conn);

    die(
        "Check-in failed: "
        . $e->getMessage()
    );
}

?>