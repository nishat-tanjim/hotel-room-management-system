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

$message = "";


/*
|--------------------------------------------------------------------------
| Get Reservation Information
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        reservations.id,
        reservations.booking_code,
        reservations.total_amount,
        reservations.reservation_status,
        guests.full_name AS guest_name,
        rooms.room_number
    FROM reservations
    INNER JOIN guests
        ON reservations.guest_id = guests.id
    INNER JOIN rooms
        ON reservations.room_id = rooms.id
    WHERE reservations.id = ?
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
| Payment Only for Checked-In Reservation
|--------------------------------------------------------------------------
*/

if ($reservation["reservation_status"] !== "checked_in") {
    die("Payment can only be recorded for a checked-in reservation.");
}


/*
|--------------------------------------------------------------------------
| Calculate Previous Payments
|--------------------------------------------------------------------------
*/

$paidSql = "
    SELECT
        COALESCE(SUM(amount), 0) AS paid_total
    FROM payments
    WHERE reservation_id = ?
    AND payment_status = 'paid'
";

$paidStmt = mysqli_prepare(
    $conn,
    $paidSql
);

mysqli_stmt_bind_param(
    $paidStmt,
    "i",
    $reservation_id
);

mysqli_stmt_execute($paidStmt);

$paidResult = mysqli_stmt_get_result($paidStmt);

$paidData = mysqli_fetch_assoc($paidResult);

$paid_total = (float) $paidData["paid_total"];

$remaining_amount =
    (float) $reservation["total_amount"]
    - $paid_total;


/*
|--------------------------------------------------------------------------
| Record Payment
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $amount = (float) $_POST["amount"];

    $payment_method = $_POST["payment_method"];

    $transaction_reference =
        trim($_POST["transaction_reference"]);


    if ($amount <= 0) {

        $message =
            "Payment amount must be greater than zero.";

    } elseif ($amount > $remaining_amount) {

        $message =
            "Payment amount cannot be greater than the remaining balance.";

    } else {

        $insertSql = "
            INSERT INTO payments
            (
                reservation_id,
                amount,
                payment_method,
                payment_status,
                transaction_reference
            )
            VALUES
            (
                ?,
                ?,
                ?,
                'paid',
                ?
            )
        ";

        $insertStmt = mysqli_prepare(
            $conn,
            $insertSql
        );

        mysqli_stmt_bind_param(
            $insertStmt,
            "idss",
            $reservation_id,
            $amount,
            $payment_method,
            $transaction_reference
        );


        if (mysqli_stmt_execute($insertStmt)) {

            $message =
                "Payment recorded successfully.";

            $paid_total += $amount;

            $remaining_amount =
                (float) $reservation["total_amount"]
                - $paid_total;

        } else {

            $message =
                "Could not record payment.";
        }
    }
}

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
        Payment - Hotel Management System
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
            Record Payment
        </h2>

    </div>


    <div class="card">

        <h3>
            Reservation Details
        </h3>

        <br>


        <p>
            <strong>Booking Code:</strong>

            <?php
            echo htmlspecialchars(
                $reservation["booking_code"]
            );
            ?>
        </p>


        <p>
            <strong>Guest:</strong>

            <?php
            echo htmlspecialchars(
                $reservation["guest_name"]
            );
            ?>
        </p>


        <p>
            <strong>Room:</strong>

            <?php
            echo htmlspecialchars(
                $reservation["room_number"]
            );
            ?>
        </p>

    </div>


    <div class="stats-grid">

        <div class="stat-card">

            <h3>
                Total Bill
            </h3>

            <p>
                BDT
                <?php
                echo number_format(
                    $reservation["total_amount"],
                    2
                );
                ?>
            </p>

        </div>


        <div class="stat-card">

            <h3>
                Already Paid
            </h3>

            <p>
                BDT
                <?php
                echo number_format(
                    $paid_total,
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
                    $remaining_amount,
                    2
                );
                ?>
            </p>

        </div>

    </div>


    <?php if ($message != "") { ?>

        <div class="message">

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php } ?>


    <?php if ($remaining_amount > 0) { ?>

        <form method="POST">

            <label>
                Payment Amount
            </label>

            <input
                type="number"
                name="amount"
                min="0.01"
                step="0.01"
                max="<?php echo $remaining_amount; ?>"
                value="<?php echo $remaining_amount; ?>"
                required
            >


            <br><br>


            <label>
                Payment Method
            </label>

            <select
                name="payment_method"
                required
            >

                <option value="">
                    Select Payment Method
                </option>

                <option value="cash">
                    Cash
                </option>

                <option value="card">
                    Card
                </option>

                <option value="mobile_banking">
                    Mobile Banking
                </option>

            </select>


            <br><br>


            <label>
                Transaction Reference
            </label>

            <input
                type="text"
                name="transaction_reference"
                placeholder="Optional for cash"
            >


            <br><br>


            <button
                class="btn btn-success"
                type="submit"
            >
                Record Payment
            </button>

        </form>


    <?php } else { ?>

        <div class="card">

            <h3>
                Payment Completed ✅
            </h3>

            <p>
                No balance remaining.
            </p>

        </div>

    <?php } ?>


    <a
        class="back-link"
        href="../reservations/index.php"
    >
        Back to Reservation List
    </a>

</div>

</body>

</html>