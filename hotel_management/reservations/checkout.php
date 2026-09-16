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
        total_amount,
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
| Only Checked-In Reservation Can Check Out
|--------------------------------------------------------------------------
*/

if ($reservation["reservation_status"] !== "checked_in") {
    die("Only checked-in guests can be checked out.");
}


/*
|--------------------------------------------------------------------------
| Calculate Total Paid
|--------------------------------------------------------------------------
*/

$paymentSql = "
    SELECT
        COALESCE(SUM(amount), 0) AS total_paid
    FROM payments
    WHERE reservation_id = ?
    AND payment_status = 'paid'
";

$paymentStmt = mysqli_prepare(
    $conn,
    $paymentSql
);

mysqli_stmt_bind_param(
    $paymentStmt,
    "i",
    $reservation_id
);

mysqli_stmt_execute($paymentStmt);

$paymentResult = mysqli_stmt_get_result($paymentStmt);

$paymentData = mysqli_fetch_assoc($paymentResult);

$total_paid = (float) $paymentData["total_paid"];

$total_amount = (float) $reservation["total_amount"];

$remaining_balance = $total_amount - $total_paid;


/*
|--------------------------------------------------------------------------
| Prevent Checkout If Payment Is Due
|--------------------------------------------------------------------------
*/

if ($remaining_balance > 0.01) {

    die(
        "Checkout cannot be completed. Remaining balance: BDT "
        . number_format($remaining_balance, 2)
    );
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
    | Update Reservation
    |--------------------------------------------------------------------------
    */

    $reservationSql = "
        UPDATE reservations
        SET reservation_status = 'checked_out'
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

        throw new Exception(
            "Could not update reservation."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Change Room Status to Cleaning
    |--------------------------------------------------------------------------
    */

    $roomSql = "
        UPDATE rooms
        SET status = 'cleaning'
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

        throw new Exception(
            "Could not update room status."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Create Housekeeping Task
    |--------------------------------------------------------------------------
    */

    $housekeepingSql = "
        INSERT INTO housekeeping
        (
            room_id,
            assigned_to,
            status,
            notes
        )
        VALUES
        (
            ?,
            NULL,
            'pending',
            'Room requires cleaning after guest checkout.'
        )
    ";

    $housekeepingStmt = mysqli_prepare(
        $conn,
        $housekeepingSql
    );

    mysqli_stmt_bind_param(
        $housekeepingStmt,
        "i",
        $room_id
    );

    if (!mysqli_stmt_execute($housekeepingStmt)) {

        throw new Exception(
            "Could not create housekeeping task."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Save Everything
    |--------------------------------------------------------------------------
    */

    mysqli_commit($conn);

    header("Location: index.php");
    exit;


} catch (Exception $e) {

    mysqli_rollback($conn);

    die(
        "Checkout failed: "
        . $e->getMessage()
    );
}

?>