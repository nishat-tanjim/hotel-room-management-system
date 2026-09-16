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
| Room Statistics
|--------------------------------------------------------------------------
*/

$totalResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM rooms"
);

$totalData = mysqli_fetch_assoc($totalResult);

$totalRooms = (int) $totalData["total"];


$availableResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM rooms WHERE status = 'available'"
);

$availableData = mysqli_fetch_assoc($availableResult);

$availableRooms = (int) $availableData["total"];


$reservedResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM rooms WHERE status = 'reserved'"
);

$reservedData = mysqli_fetch_assoc($reservedResult);

$reservedRooms = (int) $reservedData["total"];


$occupiedResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM rooms WHERE status = 'occupied'"
);

$occupiedData = mysqli_fetch_assoc($occupiedResult);

$occupiedRooms = (int) $occupiedData["total"];


$cleaningResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM rooms WHERE status = 'cleaning'"
);

$cleaningData = mysqli_fetch_assoc($cleaningResult);

$cleaningRooms = (int) $cleaningData["total"];


/*
|--------------------------------------------------------------------------
| Occupancy Rate
|--------------------------------------------------------------------------
*/

$occupancyRate = 0;

if ($totalRooms > 0) {

    $occupancyRate =
        ($occupiedRooms / $totalRooms) * 100;
}


/*
|--------------------------------------------------------------------------
| Room Status Details
|--------------------------------------------------------------------------
*/

$roomsSql = "
    SELECT
        rooms.room_number,
        rooms.floor,
        rooms.status,
        room_types.name AS room_type

    FROM rooms

    INNER JOIN room_types
        ON rooms.room_type_id = room_types.id

    ORDER BY rooms.room_number ASC
";

$roomsResult = mysqli_query(
    $conn,
    $roomsSql
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
        Occupancy Report - Hotel Management System
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
            Occupancy Report
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
                Reserved Rooms
            </h3>

            <p>
                <?php echo $reservedRooms; ?>
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
                Cleaning Rooms
            </h3>

            <p>
                <?php echo $cleaningRooms; ?>
            </p>

        </div>


        <div class="stat-card">

            <h3>
                Occupancy Rate
            </h3>

            <p>
                <?php
                echo number_format(
                    $occupancyRate,
                    2
                );
                ?>%
            </p>

        </div>

    </div>


    <div class="card">

        <h3>
            Room Status Details
        </h3>


        <table>

            <tr>

                <th>Room Number</th>

                <th>Room Type</th>

                <th>Floor</th>

                <th>Status</th>

            </tr>


            <?php while ($room = mysqli_fetch_assoc($roomsResult)) { ?>

                <tr>

                    <td>
                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $room["room_number"]
                            );
                            ?>
                        </strong>
                    </td>


                    <td>
                        <?php
                        echo htmlspecialchars(
                            $room["room_type"]
                        );
                        ?>
                    </td>


                    <td>
                        <?php
                        echo htmlspecialchars(
                            $room["floor"]
                        );
                        ?>
                    </td>


                    <td>

                        <span
                            class="status-<?php echo htmlspecialchars($room["status"]); ?>"
                        >

                            <?php
                            echo ucfirst(
                                htmlspecialchars(
                                    $room["status"]
                                )
                            );
                            ?>

                        </span>

                    </td>

                </tr>

            <?php } ?>

        </table>

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