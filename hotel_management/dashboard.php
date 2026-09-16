<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

require_once "config/database.php";

$role = $_SESSION["role"];


/*
|--------------------------------------------------------------------------
| Total Rooms
|--------------------------------------------------------------------------
*/

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM rooms"
);

$data = mysqli_fetch_assoc($result);

$totalRooms = (int) $data["total"];


/*
|--------------------------------------------------------------------------
| Available Rooms
|--------------------------------------------------------------------------
*/

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM rooms WHERE status = 'available'"
);

$data = mysqli_fetch_assoc($result);

$availableRooms = (int) $data["total"];


/*
|--------------------------------------------------------------------------
| Occupied Rooms
|--------------------------------------------------------------------------
*/

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM rooms WHERE status = 'occupied'"
);

$data = mysqli_fetch_assoc($result);

$occupiedRooms = (int) $data["total"];


/*
|--------------------------------------------------------------------------
| Cleaning Rooms
|--------------------------------------------------------------------------
*/

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM rooms WHERE status = 'cleaning'"
);

$data = mysqli_fetch_assoc($result);

$cleaningRooms = (int) $data["total"];


/*
|--------------------------------------------------------------------------
| Guest Statistics
|--------------------------------------------------------------------------
*/

$totalGuests = 0;

if (
    $role === "admin" ||
    $role === "receptionist"
) {

    $result = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total FROM guests"
    );

    $data = mysqli_fetch_assoc($result);

    $totalGuests = (int) $data["total"];
}


/*
|--------------------------------------------------------------------------
| Reservation Statistics
|--------------------------------------------------------------------------
*/

$totalReservations = 0;

if (
    $role === "admin" ||
    $role === "receptionist"
) {

    $result = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total FROM reservations"
    );

    $data = mysqli_fetch_assoc($result);

    $totalReservations = (int) $data["total"];
}


/*
|--------------------------------------------------------------------------
| Revenue Statistics
|--------------------------------------------------------------------------
*/

$totalRevenue = 0;

if (
    $role === "admin" ||
    $role === "manager"
) {

    $result = mysqli_query(
        $conn,
        "
        SELECT
            COALESCE(SUM(amount), 0) AS total
        FROM payments
        WHERE payment_status = 'paid'
        "
    );

    $data = mysqli_fetch_assoc($result);

    $totalRevenue = (float) $data["total"];
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
        Dashboard - Hotel Management System
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>

<div class="container">


    <div class="page-header">

        <h1>
            Hotel Room Management System
        </h1>

        <h2>
            Dashboard
        </h2>

        <div class="user-info">

            Welcome,
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
                    htmlspecialchars($role)
                );
                ?>
            </strong>

        </div>

    </div>


    <div class="stats-grid">


        <div class="stat-card">

            <h3>
                Total Rooms
            </h3>

            <p>
                <?php echo $totalRooms; ?>
            </p>

        </div>


        <div class="stat-card">

            <h3>
                Available Rooms
            </h3>

            <p>
                <?php echo $availableRooms; ?>
            </p>

        </div>


        <div class="stat-card">

            <h3>
                Occupied Rooms
            </h3>

            <p>
                <?php echo $occupiedRooms; ?>
            </p>

        </div>


        <div class="stat-card">

            <h3>
                Rooms Cleaning
            </h3>

            <p>
                <?php echo $cleaningRooms; ?>
            </p>

        </div>


        <?php
        if (
            $role === "admin" ||
            $role === "receptionist"
        ) {
        ?>

            <div class="stat-card">

                <h3>
                    Total Guests
                </h3>

                <p>
                    <?php echo $totalGuests; ?>
                </p>

            </div>


            <div class="stat-card">

                <h3>
                    Total Reservations
                </h3>

                <p>
                    <?php echo $totalReservations; ?>
                </p>

            </div>

        <?php } ?>


        <?php
        if (
            $role === "admin" ||
            $role === "manager"
        ) {
        ?>

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

        <?php } ?>


    </div>


    <div class="card">

        <h2>
            Navigation
        </h2>

        <br>


        <div class="navigation">


            <?php if ($role === "admin") { ?>

                <a
                    class="nav-link"
                    href="rooms/"
                >
                    Room Management
                </a>

                <a
                    class="nav-link"
                    href="guests/"
                >
                    Guest Management
                </a>

                <a
                    class="nav-link"
                    href="reservations/"
                >
                    Reservation Management
                </a>

                <a
                    class="nav-link"
                    href="housekeeping/"
                >
                    Housekeeping
                </a>

                <a
                    class="nav-link"
                    href="users/"
                >
                    User Management
                </a>

                <a
                    class="nav-link"
                    href="reports/occupancy.php"
                >
                    Occupancy Report
                </a>

                <a
                    class="nav-link"
                    href="reports/revenue.php"
                >
                    Revenue Report
                </a>

            <?php } ?>


            <?php if ($role === "receptionist") { ?>

                <a
                    class="nav-link"
                    href="rooms/"
                >
                    Rooms
                </a>

                <a
                    class="nav-link"
                    href="guests/"
                >
                    Guest Management
                </a>

                <a
                    class="nav-link"
                    href="reservations/"
                >
                    Reservation Management
                </a>

                <a
                    class="nav-link"
                    href="housekeeping/"
                >
                    Housekeeping
                </a>

            <?php } ?>


            <?php if ($role === "manager") { ?>

                <a
                    class="nav-link"
                    href="rooms/"
                >
                    Rooms
                </a>

                <a
                    class="nav-link"
                    href="reports/occupancy.php"
                >
                    Occupancy Report
                </a>

                <a
                    class="nav-link"
                    href="reports/revenue.php"
                >
                    Revenue Report
                </a>

            <?php } ?>


        </div>

    </div>


    <a
        class="logout-link"
        href="auth/logout.php"
    >
        Logout
    </a>


</div>

</body>

</html>