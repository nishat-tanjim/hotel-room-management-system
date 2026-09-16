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

$message = "";


/*
|--------------------------------------------------------------------------
| Get Room Types
|--------------------------------------------------------------------------
*/

$roomTypes = mysqli_query(
    $conn,
    "SELECT * FROM room_types ORDER BY name ASC"
);


/*
|--------------------------------------------------------------------------
| Add Room
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $room_number = trim($_POST["room_number"]);
    $room_type_id = (int) $_POST["room_type_id"];
    $floor = (int) $_POST["floor"];
    $status = $_POST["status"];
    $description = trim($_POST["description"]);


    $sql = "
        INSERT INTO rooms
        (
            room_number,
            room_type_id,
            floor,
            status,
            description
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "siiss",
        $room_number,
        $room_type_id,
        $floor,
        $status,
        $description
    );


    if (mysqli_stmt_execute($stmt)) {

        $message = "Room added successfully.";

    } else {

        $message = "Could not add room. Room number may already exist.";
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
        Add Room - Hotel Management System
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
            Add New Room
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
            placeholder="Example: 401"
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

            <option value="">
                Select Room Type
            </option>

            <?php while ($type = mysqli_fetch_assoc($roomTypes)) { ?>

                <option
                    value="<?php echo $type["id"]; ?>"
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
            placeholder="Example: 4"
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

            <option value="available">
                Available
            </option>

            <option value="reserved">
                Reserved
            </option>

            <option value="occupied">
                Occupied
            </option>

            <option value="cleaning">
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
            placeholder="Optional room description"
        ></textarea>


        <br><br>


        <button
            class="btn btn-primary"
            type="submit"
        >
            Add Room
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