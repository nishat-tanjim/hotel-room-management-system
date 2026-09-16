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
    die("Guest ID not provided.");
}

$guest_id = (int) $_GET["id"];

$message = "";


/*
|--------------------------------------------------------------------------
| Get Guest
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT *
    FROM guests
    WHERE id = ?
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $guest_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$guest = mysqli_fetch_assoc($result);


if (!$guest) {
    die("Guest not found.");
}



if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"]);
    $phone = trim($_POST["phone"]);
    $email = trim($_POST["email"]);
    $address = trim($_POST["address"]);
    $national_id = trim($_POST["national_id"]);
    $gender = $_POST["gender"];


    $updateSql = "
        UPDATE guests
        SET
            full_name = ?,
            phone = ?,
            email = ?,
            address = ?,
            national_id = ?,
            gender = ?
        WHERE id = ?
    ";

    $updateStmt = mysqli_prepare(
        $conn,
        $updateSql
    );

    mysqli_stmt_bind_param(
        $updateStmt,
        "ssssssi",
        $full_name,
        $phone,
        $email,
        $address,
        $national_id,
        $gender,
        $guest_id
    );


    if (mysqli_stmt_execute($updateStmt)) {

        $message = "Guest updated successfully.";


        /*
        |--------------------------------------------------------------------------
        | Reload Updated Guest
        |--------------------------------------------------------------------------
        */

        $reloadSql = "
            SELECT *
            FROM guests
            WHERE id = ?
        ";

        $reloadStmt = mysqli_prepare(
            $conn,
            $reloadSql
        );

        mysqli_stmt_bind_param(
            $reloadStmt,
            "i",
            $guest_id
        );

        mysqli_stmt_execute($reloadStmt);

        $reloadResult =
            mysqli_stmt_get_result($reloadStmt);

        $guest =
            mysqli_fetch_assoc($reloadResult);

    } else {

        $message = "Could not update guest.";
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
        Edit Guest - Hotel Management System
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
            Edit Guest
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
            Full Name
        </label>

        <input
            type="text"
            name="full_name"
            value="<?php echo htmlspecialchars($guest["full_name"]); ?>"
            required
        >


        <br><br>


        <label>
            Phone
        </label>

        <input
            type="text"
            name="phone"
            value="<?php echo htmlspecialchars($guest["phone"]); ?>"
            required
        >


        <br><br>


        <label>
            Email
        </label>

        <input
            type="email"
            name="email"
            value="<?php echo htmlspecialchars($guest["email"]); ?>"
        >


        <br><br>


        <label>
            National ID
        </label>

        <input
            type="text"
            name="national_id"
            value="<?php echo htmlspecialchars($guest["national_id"]); ?>"
        >


        <br><br>


        <label>
            Gender
        </label>

        <select name="gender">

            <option value="">
                Select Gender
            </option>

            <option
                value="male"
                <?php
                if ($guest["gender"] === "male") {
                    echo "selected";
                }
                ?>
            >
                Male
            </option>

            <option
                value="female"
                <?php
                if ($guest["gender"] === "female") {
                    echo "selected";
                }
                ?>
            >
                Female
            </option>

            <option
                value="other"
                <?php
                if ($guest["gender"] === "other") {
                    echo "selected";
                }
                ?>
            >
                Other
            </option>

        </select>


        <br><br>


        <label>
            Address
        </label>

        <textarea
            name="address"
            rows="4"
        ><?php echo htmlspecialchars($guest["address"]); ?></textarea>


        <br><br>


        <button
            class="btn btn-primary"
            type="submit"
        >
            Update Guest
        </button>

    </form>


    <a
        class="back-link"
        href="index.php"
    >
        Back to Guest List
    </a>

</div>

</body>

</html>