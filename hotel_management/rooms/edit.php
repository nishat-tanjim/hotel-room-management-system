<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SESSION["role"] !== "admin") {
    die("Access denied. Admin only.");
}

require_once "../config/database.php";


if (!isset($_GET["id"])) {
    die("Room ID not provided.");
}

$room_id = (int) $_GET["id"];

$message = "";


/*
|--------------------------------------------------------------------------
| Get Current Room
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT *
    FROM rooms
    WHERE id = ?
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $room_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$room = mysqli_fetch_assoc($result);


if (!$room) {
    die("Room not found.");
}


/*
|--------------------------------------------------------------------------
| Get Room Types
|--------------------------------------------------------------------------
*/

$roomTypes = mysqli_query(
    $conn,
    "
    SELECT *
    FROM room_types
    ORDER BY name ASC
    "
);


/*
|--------------------------------------------------------------------------
| Update Room
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $room_number = trim($_POST["room_number"]);

    $room_type_id = (int) $_POST["room_type_id"];

    $floor = (int) $_POST["floor"];

    $status = $_POST["status"];

    $description = trim($_POST["description"]);


    $updateSql = "
        UPDATE rooms
        SET
            room_number = ?,
            room_type_id = ?,
            floor = ?,
            status = ?,
            description = ?
        WHERE id = ?
    ";

    $updateStmt = mysqli_prepare(
        $conn,
        $updateSql
    );

    mysqli_stmt_bind_param(
        $updateStmt,
        "siissi",
        $room_number,
        $room_type_id,
        $floor,
        $status,
        $description,
        $room_id
    );


    if (mysqli_stmt_execute($updateStmt)) {

        $message = "Room updated successfully.";


        /*
        |--------------------------------------------------------------------------
        | Reload Updated Room
        |--------------------------------------------------------------------------
        */

        $reloadSql = "
            SELECT *
            FROM rooms
            WHERE id = ?
        ";

        $reloadStmt = mysqli_prepare(
            $conn,
            $reloadSql
        );

        mysqli_stmt_bind_param(
            $reloadStmt,
            "i",
            $room_id
        );

        mysqli_stmt_execute($reloadStmt);

        $reloadResult =
            mysqli_stmt_get_result($reloadStmt);

        $room =
            mysqli_fetch_assoc($reloadResult);

    } else {

        $message =
            "Could not update room.";
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
        Edit Room - Hotel Management System
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
            Edit Room
        </h2>

    </div>


    <?php if ($message != "") { ?>

        <div class="message">

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php } ?>


    <form method="POST">

        <label>
            Room Number
        </label>

        <input
            type="text"
            name="room_number"
            value="<?php
                echo htmlspecialchars(
                    $room["room_number"]
                );
            ?>"
            required
        >


        <br><br>


        <label>
            Room Type
        </label>

        <select
            name="room_type_id"
            required
        >

            <?php while ($type = mysqli_fetch_assoc($roomTypes)) { ?>

                <option
                    value="<?php echo $type["id"]; ?>"

                    <?php
                    if (
                        $type["id"] ==
                        $room["room_type_id"]
                    ) {
                        echo "selected";
                    }
                    ?>
                >

                    <?php
                    echo htmlspecialchars(
                        $type["name"]
                    );
                    ?>

                    -

                    BDT

                    <?php
                    echo number_format(
                        $type["price_per_night"],
                        2
                    );
                    ?>

                </option>

            <?php } ?>

        </select>


        <br><br>


        <label>
            Floor
        </label>

        <input
            type="number"
            name="floor"
            min="1"
            value="<?php
                echo htmlspecialchars(
                    $room["floor"]
                );
            ?>"
            required
        >


        <br><br>


        <label>
            Status
        </label>

        <select
            name="status"
            required
        >

            <option
                value="available"
                <?php
                if ($room["status"] === "available") {
                    echo "selected";
                }
                ?>
            >
                Available
            </option>

            <option
                value="reserved"
                <?php
                if ($room["status"] === "reserved") {
                    echo "selected";
                }
                ?>
            >
                Reserved
            </option>

            <option
                value="occupied"
                <?php
                if ($room["status"] === "occupied") {
                    echo "selected";
                }
                ?>
            >
                Occupied
            </option>

            <option
                value="cleaning"
                <?php
                if ($room["status"] === "cleaning") {
                    echo "selected";
                }
                ?>
            >
                Cleaning
            </option>

        </select>


        <br><br>


        <label>
            Description
        </label>

        <textarea
            name="description"
            rows="4"
        ><?php
            echo htmlspecialchars(
                $room["description"]
            );
        ?></textarea>


        <br><br>


        <button
            class="btn btn-primary"
            type="submit"
        >
            Update Room
        </button>

    </form>


    <a
        class="back-link"
        href="index.php"
    >
        Back to Room List
    </a>

</div>

</body>

</html>