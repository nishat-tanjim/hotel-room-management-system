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


/*
|--------------------------------------------------------------------------
| Get Reservations
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        reservations.id,
        reservations.booking_code,
        reservations.check_in_date,
        reservations.check_out_date,
        reservations.adults,
        reservations.children,
        reservations.price_per_night,
        reservations.total_amount,
        reservations.reservation_status,
        reservations.created_at,

        guests.full_name AS guest_name,

        rooms.room_number,

        users.name AS created_by_name

    FROM reservations

    INNER JOIN guests
        ON reservations.guest_id = guests.id

    INNER JOIN rooms
        ON reservations.room_id = rooms.id

    INNER JOIN users
        ON reservations.created_by = users.id

    ORDER BY reservations.id DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Could not load reservations.");
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
        Reservations - Hotel Management System
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
            Reservation Management
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


    <div class="card">

        <a
            class="btn btn-primary"
            href="add.php"
        >
            Add New Reservation
        </a>


        <div style="overflow-x: auto;">

            <table>

                <tr>

                    <th>Booking Code</th>

                    <th>Guest</th>

                    <th>Room</th>

                    <th>Check In</th>

                    <th>Check Out</th>

                    <th>Adults</th>

                    <th>Children</th>

                    <th>Price / Night</th>

                    <th>Total</th>

                    <th>Status</th>

                    <th>Created By</th>

                    <th>Action</th>

                </tr>


                <?php while ($reservation = mysqli_fetch_assoc($result)) { ?>

                    <tr>

                        <td>
                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $reservation["booking_code"]
                                );
                                ?>
                            </strong>
                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                $reservation["guest_name"]
                            );
                            ?>
                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                $reservation["room_number"]
                            );
                            ?>
                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                $reservation["check_in_date"]
                            );
                            ?>
                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                $reservation["check_out_date"]
                            );
                            ?>
                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                $reservation["adults"]
                            );
                            ?>
                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                $reservation["children"]
                            );
                            ?>
                        </td>


                        <td>
                            BDT
                            <?php
                            echo number_format(
                                $reservation["price_per_night"],
                                2
                            );
                            ?>
                        </td>


                        <td>
                            BDT
                            <?php
                            echo number_format(
                                $reservation["total_amount"],
                                2
                            );
                            ?>
                        </td>


                        <td>

                            <strong>

                                <?php
                                echo ucfirst(
                                    str_replace(
                                        "_",
                                        " ",
                                        htmlspecialchars(
                                            $reservation["reservation_status"]
                                        )
                                    )
                                );
                                ?>

                            </strong>

                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                $reservation["created_by_name"]
                            );
                            ?>
                        </td>


                        <td>

                            <?php
                            if (
                                $reservation["reservation_status"]
                                === "confirmed"
                            ) {
                            ?>

                                <a
                                    class="btn btn-success"
                                    href="checkin.php?id=<?php echo $reservation["id"]; ?>"
                                    onclick="return confirm('Check in this guest?');"
                                >
                                    Check In
                                </a>

                                <a
                                    class="btn btn-warning"
                                    href="edit.php?id=<?php echo $reservation["id"]; ?>"
                                >
                                    Edit
                                </a>

                                <a
                                    class="btn btn-danger"
                                    href="cancel.php?id=<?php echo $reservation["id"]; ?>"
                                    onclick="return confirm('Cancel this reservation?');"
                                >
                                    Cancel
                                </a>


                            <?php
                            } elseif (
                                $reservation["reservation_status"]
                                === "pending"
                            ) {
                            ?>

                                <a
                                    class="btn btn-warning"
                                    href="edit.php?id=<?php echo $reservation["id"]; ?>"
                                >
                                    Edit
                                </a>

                                <a
                                    class="btn btn-danger"
                                    href="cancel.php?id=<?php echo $reservation["id"]; ?>"
                                    onclick="return confirm('Cancel this reservation?');"
                                >
                                    Cancel
                                </a>


                            <?php
                            } elseif (
                                $reservation["reservation_status"]
                                === "checked_in"
                            ) {
                            ?>

                                <a
                                    class="btn btn-success"
                                    href="../billing/payment.php?id=<?php echo $reservation["id"]; ?>"
                                >
                                    Payment
                                </a>

                                <a
                                    class="btn btn-secondary"
                                    href="../billing/invoice.php?id=<?php echo $reservation["id"]; ?>"
                                >
                                    Invoice
                                </a>

                                <a
                                    class="btn btn-primary"
                                    href="checkout.php?id=<?php echo $reservation["id"]; ?>"
                                    onclick="return confirm('Check out this guest?');"
                                >
                                    Check Out
                                </a>


                            <?php
                            } elseif (
                                $reservation["reservation_status"]
                                === "checked_out"
                            ) {
                            ?>

                                <a
                                    class="btn btn-secondary"
                                    href="../billing/invoice.php?id=<?php echo $reservation["id"]; ?>"
                                >
                                    Invoice
                                </a>


                            <?php
                            } else {
                            ?>

                                -

                            <?php } ?>

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