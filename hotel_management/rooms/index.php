<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";


$sql = "
    SELECT 
        rooms.id,
        rooms.room_number,
        rooms.floor,
        rooms.status,
        rooms.description,
        room_types.name AS room_type,
        room_types.price_per_night,
        room_types.capacity
    FROM rooms
    INNER JOIN room_types
        ON rooms.room_type_id = room_types.id
    ORDER BY rooms.room_number ASC
";

$result = mysqli_query($conn, $sql);

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
        Rooms - Hotel Room Management System
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
            Hotel Room Management System
        </h1>

        <h2>
            Room List
        </h2>

        <div class="user-info">

            Logged in as:
            <strong>
                <?php echo htmlspecialchars($_SESSION["name"]); ?>
            </strong>

            |

            Role:
            <strong>
                <?php echo ucfirst(htmlspecialchars($_SESSION["role"])); ?>
            </strong>

        </div>

    </div>


    <div class="card">

        <?php if ($_SESSION["role"] === "admin") { ?>

            <a
                class="btn btn-primary"
                href="add.php"
            >
                Add New Room
            </a>

        <?php } ?>


        <table>

            <tr>

                <th>Room Number</th>

                <th>Room Type</th>

                <th>Floor</th>

                <th>Capacity</th>

                <th>Price Per Night</th>

                <th>Status</th>

                <th>Description</th>

                <?php if ($_SESSION["role"] === "admin") { ?>

                    <th>Action</th>

                <?php } ?>

            </tr>


            <?php while ($room = mysqli_fetch_assoc($result)) { ?>

                <tr>

                    <td>
                        <?php echo htmlspecialchars($room["room_number"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($room["room_type"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($room["floor"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($room["capacity"]); ?>
                    </td>

                    <td>
                        BDT
                        <?php
                        echo number_format(
                            $room["price_per_night"],
                            2
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

                    <td>
                        <?php echo htmlspecialchars($room["description"]); ?>
                    </td>


                    <?php if ($_SESSION["role"] === "admin") { ?>

                        <td>

                            <a
                                class="btn btn-warning"
                                href="edit.php?id=<?php echo $room["id"]; ?>"
                            >
                                Edit
                            </a>

                            <a
                                class="btn btn-danger"
                                href="delete.php?id=<?php echo $room["id"]; ?>"
                                onclick="return confirm('Are you sure you want to delete this room?');"
                            >
                                Delete
                            </a>

                        </td>

                    <?php } ?>

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