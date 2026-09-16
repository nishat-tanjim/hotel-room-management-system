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
| Add Guest
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"]);
    $phone = trim($_POST["phone"]);
    $email = trim($_POST["email"]);
    $address = trim($_POST["address"]);
    $national_id = trim($_POST["national_id"]);
    $gender = $_POST["gender"];


    $sql = "
        INSERT INTO guests
        (
            full_name,
            phone,
            email,
            address,
            national_id,
            gender
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ";

    $stmt = mysqli_prepare(
        $conn,
        $sql
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ssssss",
        $full_name,
        $phone,
        $email,
        $address,
        $national_id,
        $gender
    );


    if (mysqli_stmt_execute($stmt)) {

        header("Location: index.php");
        exit;

    } else {

        $message = "Could not add guest.";
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
        Add Guest - Hotel Management System
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
            Add New Guest
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
            placeholder="Enter guest full name"
            required
        >


        <br><br>


        <label>
            Phone
        </label>

        <input
            type="text"
            name="phone"
            placeholder="Enter phone number"
            required
        >


        <br><br>


        <label>
            Email
        </label>

        <input
            type="email"
            name="email"
            placeholder="Enter email address"
        >


        <br><br>


        <label>
            National ID
        </label>

        <input
            type="text"
            name="national_id"
            placeholder="Enter National ID"
        >


        <br><br>


        <label>
            Gender
        </label>

        <select name="gender">

            <option value="">
                Select Gender
            </option>

            <option value="male">
                Male
            </option>

            <option value="female">
                Female
            </option>

            <option value="other">
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
            placeholder="Enter guest address"
        ></textarea>


        <br><br>


        <button
            class="btn btn-primary"
            type="submit"
        >
            Add Guest
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