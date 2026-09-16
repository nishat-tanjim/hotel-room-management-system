<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if (
    $_SESSION["role"] !== "admin" &&
    $_SESSION["role"] !== "manager"
) {
    die("Access denied. Admin or Manager only.");
}

require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| Total Revenue
|--------------------------------------------------------------------------
*/

$totalRevenueSql = "
    SELECT
        COALESCE(SUM(amount), 0) AS total_revenue
    FROM payments
    WHERE payment_status = 'paid'
";

$totalRevenueResult = mysqli_query(
    $conn,
    $totalRevenueSql
);

$totalRevenueData = mysqli_fetch_assoc(
    $totalRevenueResult
);

$totalRevenue = (float) $totalRevenueData["total_revenue"];


/*
|--------------------------------------------------------------------------
| Today's Revenue
|--------------------------------------------------------------------------
*/

$todayRevenueSql = "
    SELECT
        COALESCE(SUM(amount), 0) AS today_revenue
    FROM payments
    WHERE payment_status = 'paid'
    AND DATE(payment_date) = CURDATE()
";

$todayRevenueResult = mysqli_query(
    $conn,
    $todayRevenueSql
);

$todayRevenueData = mysqli_fetch_assoc(
    $todayRevenueResult
);

$todayRevenue = (float) $todayRevenueData["today_revenue"];


/*
|--------------------------------------------------------------------------
| Current Month Revenue
|--------------------------------------------------------------------------
*/

$monthRevenueSql = "
    SELECT
        COALESCE(SUM(amount), 0) AS month_revenue
    FROM payments
    WHERE payment_status = 'paid'
    AND YEAR(payment_date) = YEAR(CURDATE())
    AND MONTH(payment_date) = MONTH(CURDATE())
";

$monthRevenueResult = mysqli_query(
    $conn,
    $monthRevenueSql
);

$monthRevenueData = mysqli_fetch_assoc(
    $monthRevenueResult
);

$monthRevenue = (float) $monthRevenueData["month_revenue"];


/*
|--------------------------------------------------------------------------
| Payment History
|--------------------------------------------------------------------------
*/

$historySql = "
    SELECT
        payments.id,
        payments.amount,
        payments.payment_method,
        payments.transaction_reference,
        payments.payment_date,

        reservations.booking_code,

        guests.full_name AS guest_name,

        rooms.room_number

    FROM payments

    INNER JOIN reservations
        ON payments.reservation_id = reservations.id

    INNER JOIN guests
        ON reservations.guest_id = guests.id

    INNER JOIN rooms
        ON reservations.room_id = rooms.id

    WHERE payments.payment_status = 'paid'

    ORDER BY payments.payment_date DESC
";

$historyResult = mysqli_query(
    $conn,
    $historySql
);

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
        Revenue Report - Hotel Management System
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
            Revenue Report
        </h2>

        <div class="user-info">

            Logged in as:
            <strong>
                <?php
                echo htmlspecialchars(
                    $_SESSION["name"]
                );
                ?>
            </strong>

            |

            Role:
            <strong>
                <?php
                echo ucfirst(
                    htmlspecialchars(
                        $_SESSION["role"]
                    )
                );
                ?>
            </strong>

        </div>

    </div>


    <div class="stats-grid">

        <div class="stat-card">

            <h3>
                Total Revenue
            </h3>

            <p>
                BDT
                <?php
                echo number_format(
                    $totalRevenue,
                    2
                );
                ?>
            </p>

        </div>


        <div class="stat-card">

            <h3>
                Today's Revenue
            </h3>

            <p>
                BDT
                <?php
                echo number_format(
                    $todayRevenue,
                    2
                );
                ?>
            </p>

        </div>


        <div class="stat-card">

            <h3>
                This Month's Revenue
            </h3>

            <p>
                BDT
                <?php
                echo number_format(
                    $monthRevenue,
                    2
                );
                ?>
            </p>

        </div>

    </div>


    <div class="card">

        <h3>
            Payment History
        </h3>


        <div style="overflow-x: auto;">

            <table>

                <tr>

                    <th>Booking Code</th>

                    <th>Guest</th>

                    <th>Room</th>

                    <th>Amount</th>

                    <th>Payment Method</th>

                    <th>Transaction Reference</th>

                    <th>Payment Date</th>

                </tr>


                <?php while ($payment = mysqli_fetch_assoc($historyResult)) { ?>

                    <tr>

                        <td>
                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $payment["booking_code"]
                                );
                                ?>
                            </strong>
                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                $payment["guest_name"]
                            );
                            ?>
                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                $payment["room_number"]
                            );
                            ?>
                        </td>


                        <td>

                            BDT

                            <?php
                            echo number_format(
                                $payment["amount"],
                                2
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo ucfirst(
                                str_replace(
                                    "_",
                                    " ",
                                    htmlspecialchars(
                                        $payment["payment_method"]
                                    )
                                )
                            );
                            ?>

                        </td>


                        <td>

                            <?php

                            if (
                                !empty(
                                    $payment["transaction_reference"]
                                )
                            ) {

                                echo htmlspecialchars(
                                    $payment["transaction_reference"]
                                );

                            } else {

                                echo "-";
                            }

                            ?>

                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                $payment["payment_date"]
                            );
                            ?>
                        </td>

                    </tr>

                <?php } ?>

            </table>

        </div>

    </div>


    <a
        class="back-link"
        href="../dashboard.php"
    >
        Back to Dashboard
    </a>

</div>

</body>

</html>