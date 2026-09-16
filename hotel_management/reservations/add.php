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

$message = "";


/*
|--------------------------------------------------------------------------
| Get Guests
|--------------------------------------------------------------------------
*/

$guestsResult = mysqli_query(
    $conn,
    "
    SELECT
        id,
        full_name,
        phone
    FROM guests
    ORDER BY full_name ASC
    "
);


/*
|--------------------------------------------------------------------------
| Get Rooms
|--------------------------------------------------------------------------
*/

$roomsResult = mysqli_query(
    $conn,
    "
    SELECT
        rooms.id,
        rooms.room_number,
        rooms.status,
        room_types.name AS room_type,
        room_types.price_per_night,
        room_types.capacity

    FROM rooms

    INNER JOIN room_types
        ON rooms.room_type_id = room_types.id

    ORDER BY rooms.room_number ASC
    "
);


/*
|--------------------------------------------------------------------------
| Create Reservation
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $guest_id = (int) $_POST["guest_id"];

    $room_id = (int) $_POST["room_id"];

    $check_in_date = $_POST["check_in_date"];

    $check_out_date = $_POST["check_out_date"];

    $adults = (int) $_POST["adults"];

    $children = (int) $_POST["children"];


    /*
    |--------------------------------------------------------------------------
    | Validate Dates
    |--------------------------------------------------------------------------
    */

    if ($check_out_date <= $check_in_date) {

        $message =
            "Check-out date must be after check-in date.";

    } else {


        /*
        |--------------------------------------------------------------------------
        | Check Room Exists and Get Price
        |--------------------------------------------------------------------------
        */

        $roomSql = "
            SELECT
                rooms.id,
                room_types.price_per_night
            FROM rooms

            INNER JOIN room_types
                ON rooms.room_type_id = room_types.id

            WHERE rooms.id = ?
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

        mysqli_stmt_execute($roomStmt);

        $roomResult =
            mysqli_stmt_get_result($roomStmt);

        $room =
            mysqli_fetch_assoc($roomResult);


        if (!$room) {

            $message = "Selected room was not found.";

        } else {


            /*
            |--------------------------------------------------------------------------
            | Double Booking Check
            |--------------------------------------------------------------------------
            |
            | Existing booking overlaps if:
            |
            | existing check-in < new check-out
            | AND
            | existing check-out > new check-in
            |
            */

            $bookingCheckSql = "
                SELECT COUNT(*) AS total

                FROM reservations

                WHERE room_id = ?

                AND reservation_status IN (
                    'pending',
                    'confirmed',
                    'checked_in'
                )

                AND check_in_date < ?

                AND check_out_date > ?
            ";

            $bookingCheckStmt =
                mysqli_prepare(
                    $conn,
                    $bookingCheckSql
                );

            mysqli_stmt_bind_param(
                $bookingCheckStmt,
                "iss",
                $room_id,
                $check_out_date,
                $check_in_date
            );

            mysqli_stmt_execute(
                $bookingCheckStmt
            );

            $bookingCheckResult =
                mysqli_stmt_get_result(
                    $bookingCheckStmt
                );

            $bookingCheckData =
                mysqli_fetch_assoc(
                    $bookingCheckResult
                );

            $bookingExists =
                (int) $bookingCheckData["total"];


            if ($bookingExists > 0) {

                $message =
                    "This room is already reserved for the selected dates.";

            } else {


                /*
                |--------------------------------------------------------------------------
                | Calculate Number of Nights
                |--------------------------------------------------------------------------
                */

                $checkIn =
                    new DateTime($check_in_date);

                $checkOut =
                    new DateTime($check_out_date);

                $number_of_nights =
                    $checkIn
                    ->diff($checkOut)
                    ->days;


                $price_per_night =
                    (float) $room["price_per_night"];

                $total_amount =
                    $number_of_nights
                    * $price_per_night;


                /*
                |--------------------------------------------------------------------------
                | Generate Booking Code
                |--------------------------------------------------------------------------
                */

                $booking_code =
                    "BK"
                    . date("YmdHis")
                    . rand(100, 999);


                $created_by =
                    (int) $_SESSION["user_id"];


                /*
                |--------------------------------------------------------------------------
                | Insert Reservation
                |--------------------------------------------------------------------------
                */

                $insertSql = "
                    INSERT INTO reservations
                    (
                        booking_code,
                        guest_id,
                        room_id,
                        check_in_date,
                        check_out_date,
                        adults,
                        children,
                        price_per_night,
                        total_amount,
                        reservation_status,
                        created_by
                    )

                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        'confirmed',
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
                    "siissiiddi",
                    $booking_code,
                    $guest_id,
                    $room_id,
                    $check_in_date,
                    $check_out_date,
                    $adults,
                    $children,
                    $price_per_night,
                    $total_amount,
                    $created_by
                );


                if (
                    mysqli_stmt_execute(
                        $insertStmt
                    )
                ) {

                    header(
                        "Location: index.php"
                    );

                    exit;

                } else {

                    $message =
                        "Could not create reservation.";
                }
            }
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
        Add Reservation - Hotel Management System
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
            Add New Reservation
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
            Guest
        </label>

        <select
            name="guest_id"
            required
        >

            <option value="">
                Select Guest
            </option>


            <?php while ($guest = mysqli_fetch_assoc($guestsResult)) { ?>

                <option
                    value="<?php echo $guest["id"]; ?>"
                >

                    <?php
                    echo htmlspecialchars(
                        $guest["full_name"]
                    );
                    ?>

                    -

                    <?php
                    echo htmlspecialchars(
                        $guest["phone"]
                    );
                    ?>

                </option>

            <?php } ?>

        </select>


        <br><br>


        <label>
            Room
        </label>

        <select
            name="room_id"
            required
        >

            <option value="">
                Select Room
            </option>


            <?php while ($room = mysqli_fetch_assoc($roomsResult)) { ?>

                <option
                    value="<?php echo $room["id"]; ?>"
                >

                    Room
                    <?php
                    echo htmlspecialchars(
                        $room["room_number"]
                    );
                    ?>

                    -

                    <?php
                    echo htmlspecialchars(
                        $room["room_type"]
                    );
                    ?>

                    -

                    BDT
                    <?php
                    echo number_format(
                        $room["price_per_night"],
                        2
                    );
                    ?>

                    / night

                </option>

            <?php } ?>

        </select>


        <br><br>


        <label>
            Check-in Date
        </label>

        <input
            type="date"
            name="check_in_date"
            required
        >


        <br><br>


        <label>
            Check-out Date
        </label>

        <input
            type="date"
            name="check_out_date"
            required
        >


        <br><br>


        <label>
            Adults
        </label>

        <input
            type="number"
            name="adults"
            min="1"
            value="1"
            required
        >


        <br><br>


        <label>
            Children
        </label>

        <input
            type="number"
            name="children"
            min="0"
            value="0"
            required
        >


        <br><br>


        <button
            class="btn btn-primary"
            type="submit"
        >
            Create Reservation
        </button>

    </form>


    <a
        class="back-link"
        href="index.php"
    >
        Back to Reservation List
    </a>

</div>

</body>

</html>