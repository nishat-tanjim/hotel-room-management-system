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
| Get Reservation Information
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        reservations.id,
        reservations.booking_code,
        reservations.check_in_date,
        reservations.check_out_date,
        reservations.price_per_night,
        reservations.total_amount,
        reservations.reservation_status,

        guests.full_name AS guest_name,
        guests.phone AS guest_phone,
        guests.email AS guest_email,

        rooms.room_number,

        room_types.name AS room_type

    FROM reservations

    INNER JOIN guests
        ON reservations.guest_id = guests.id

    INNER JOIN rooms
        ON reservations.room_id = rooms.id

    INNER JOIN room_types
        ON rooms.room_type_id = room_types.id

    WHERE reservations.id = ?
";

$stmt = mysqli_prepare(
    $conn,
    $sql
);

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

$paymentResult =
    mysqli_stmt_get_result($paymentStmt);

$paymentData =
    mysqli_fetch_assoc($paymentResult);

$total_paid =
    (float) $paymentData["total_paid"];

$remaining_balance =
    (float) $reservation["total_amount"]
    - $total_paid;


/*
|--------------------------------------------------------------------------
| Check Existing Invoice
|--------------------------------------------------------------------------
*/

$invoiceCheckSql = "
    SELECT *
    FROM invoices
    WHERE reservation_id = ?
";

$invoiceCheckStmt =
    mysqli_prepare(
        $conn,
        $invoiceCheckSql
    );

mysqli_stmt_bind_param(
    $invoiceCheckStmt,
    "i",
    $reservation_id
);

mysqli_stmt_execute(
    $invoiceCheckStmt
);

$invoiceCheckResult =
    mysqli_stmt_get_result(
        $invoiceCheckStmt
    );

$invoice =
    mysqli_fetch_assoc(
        $invoiceCheckResult
    );


/*
|--------------------------------------------------------------------------
| Create Invoice If It Does Not Exist
|--------------------------------------------------------------------------
*/

if (!$invoice) {

    $invoice_number =
        "INV"
        . date("YmdHis")
        . rand(100, 999);

    $room_charge =
        (float) $reservation["total_amount"];

    $additional_charge = 0.00;

    $discount = 0.00;

    $invoice_total =
        $room_charge
        + $additional_charge
        - $discount;


    $insertSql = "
        INSERT INTO invoices
        (
            reservation_id,
            invoice_number,
            room_charge,
            additional_charge,
            discount,
            total_amount
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ";

    $insertStmt =
        mysqli_prepare(
            $conn,
            $insertSql
        );

    mysqli_stmt_bind_param(
        $insertStmt,
        "isdddd",
        $reservation_id,
        $invoice_number,
        $room_charge,
        $additional_charge,
        $discount,
        $invoice_total
    );

    mysqli_stmt_execute(
        $insertStmt
    );


    /*
    |--------------------------------------------------------------------------
    | Reload Invoice
    |--------------------------------------------------------------------------
    */

    $invoiceCheckStmt =
        mysqli_prepare(
            $conn,
            $invoiceCheckSql
        );

    mysqli_stmt_bind_param(
        $invoiceCheckStmt,
        "i",
        $reservation_id
    );

    mysqli_stmt_execute(
        $invoiceCheckStmt
    );

    $invoiceCheckResult =
        mysqli_stmt_get_result(
            $invoiceCheckStmt
        );

    $invoice =
        mysqli_fetch_assoc(
            $invoiceCheckResult
        );
}


/*
|--------------------------------------------------------------------------
| Calculate Number of Nights
|--------------------------------------------------------------------------
*/

$checkIn =
    new DateTime(
        $reservation["check_in_date"]
    );

$checkOut =
    new DateTime(
        $reservation["check_out_date"]
    );

$number_of_nights =
    $checkIn
    ->diff($checkOut)
    ->days;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Invoice - Hotel Management System
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>

<div class="container">

    <div class="page-header">

        <h1>
            Hotel Management System
        </h1>

        <h2>
            Invoice
        </h2>

        <div class="user-info">

            Invoice Number:

            <strong>
                <?php
                echo htmlspecialchars(
                    $invoice["invoice_number"]
                );
                ?>
            </strong>

            |

            Booking Code:

            <strong>
                <?php
                echo htmlspecialchars(
                    $reservation["booking_code"]
                );
                ?>
            </strong>

        </div>

    </div>


    <div class="card">

        <h3>
            Guest Information
        </h3>

        <br>

        <p>
            <strong>Name:</strong>

            <?php
            echo htmlspecialchars(
                $reservation["guest_name"]
            );
            ?>
        </p>


        <p>
            <strong>Phone:</strong>

            <?php
            echo htmlspecialchars(
                $reservation["guest_phone"]
            );
            ?>
        </p>


        <p>
            <strong>Email:</strong>

            <?php
            echo htmlspecialchars(
                $reservation["guest_email"]
            );
            ?>
        </p>

    </div>


    <div class="card">

        <h3>
            Stay Information
        </h3>

        <br>

        <p>
            <strong>Room:</strong>

            <?php
            echo htmlspecialchars(
                $reservation["room_number"]
            );
            ?>
        </p>


        <p>
            <strong>Room Type:</strong>

            <?php
            echo htmlspecialchars(
                $reservation["room_type"]
            );
            ?>
        </p>


        <p>
            <strong>Check-in Date:</strong>

            <?php
            echo htmlspecialchars(
                $reservation["check_in_date"]
            );
            ?>
        </p>


        <p>
            <strong>Check-out Date:</strong>

            <?php
            echo htmlspecialchars(
                $reservation["check_out_date"]
            );
            ?>
        </p>


        <p>
            <strong>Number of Nights:</strong>

            <?php
            echo $number_of_nights;
            ?>
        </p>


        <p>
            <strong>Price Per Night:</strong>

            BDT

            <?php
            echo number_format(
                $reservation["price_per_night"],
                2
            );
            ?>
        </p>

    </div>


    <div class="stats-grid">

        <div class="stat-card">

            <h3>
                Room Charge
            </h3>

            <p>
                BDT
                <?php
                echo number_format(
                    $invoice["room_charge"],
                    2
                );
                ?>
            </p>

        </div>


        <div class="stat-card">

            <h3>
                Additional Charge
            </h3>

            <p>
                BDT
                <?php
                echo number_format(
                    $invoice["additional_charge"],
                    2
                );
                ?>
            </p>

        </div>


        <div class="stat-card">

            <h3>
                Discount
            </h3>

            <p>
                BDT
                <?php
                echo number_format(
                    $invoice["discount"],
                    2
                );
                ?>
            </p>

        </div>


        <div class="stat-card">

            <h3>
                Total Amount
            </h3>

            <p>
                BDT
                <?php
                echo number_format(
                    $invoice["total_amount"],
                    2
                );
                ?>
            </p>

        </div>


        <div class="stat-card">

            <h3>
                Total Paid
            </h3>

            <p>
                BDT
                <?php
                echo number_format(
                    $total_paid,
                    2
                );
                ?>
            </p>

        </div>


        <div class="stat-card">

            <h3>
                Remaining Balance
            </h3>

            <p>
                BDT
                <?php
                echo number_format(
                    $remaining_balance,
                    2
                );
                ?>
            </p>

        </div>

    </div>


    <div class="card">

        <?php if ($remaining_balance <= 0.01) { ?>

            <h2>
                Payment Status: PAID ✅
            </h2>

        <?php } else { ?>

            <h2>
                Payment Status: DUE
            </h2>

        <?php } ?>

        <br>

        <button
            class="btn btn-primary"
            onclick="window.print()"
        >
            Print Invoice
        </button>

    </div>


    <a
        class="back-link"
        href="../reservations/index.php"
    >
        Back to Reservation List
    </a>

</div>

</body>

</html>