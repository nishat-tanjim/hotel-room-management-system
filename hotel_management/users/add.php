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
| Add User
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $role = $_POST["role"];
    $status = $_POST["status"];


    if (
        $name === "" ||
        $email === "" ||
        $password === "" ||
        $role === "" ||
        $status === ""
    ) {

        $message = "Please fill in all required fields.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Check Email Already Exists
        |--------------------------------------------------------------------------
        */

        $checkSql = "
            SELECT id
            FROM users
            WHERE email = ?
            LIMIT 1
        ";

        $checkStmt = mysqli_prepare(
            $conn,
            $checkSql
        );

        mysqli_stmt_bind_param(
            $checkStmt,
            "s",
            $email
        );

        mysqli_stmt_execute($checkStmt);

        $checkResult =
            mysqli_stmt_get_result($checkStmt);

        $existingUser =
            mysqli_fetch_assoc($checkResult);


        if ($existingUser) {

            $message =
                "A user with this email already exists.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | Hash Password
            |--------------------------------------------------------------------------
            */

            $hashedPassword =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            /*
            |--------------------------------------------------------------------------
            | Insert User
            |--------------------------------------------------------------------------
            */

            $insertSql = "
                INSERT INTO users
                (
                    name,
                    email,
                    password,
                    role,
                    status
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

            $insertStmt =
                mysqli_prepare(
                    $conn,
                    $insertSql
                );

            mysqli_stmt_bind_param(
                $insertStmt,
                "sssss",
                $name,
                $email,
                $hashedPassword,
                $role,
                $status
            );


            if (mysqli_stmt_execute($insertStmt)) {

                header("Location: index.php");
                exit;

            } else {

                $message =
                    "Could not create user.";
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
        Add User - Hotel Management System
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
            Add New User
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
            name="name"
            placeholder="Enter user full name"
            required
        >


        <br><br>


        <label>
            Email Address
        </label>

        <input
            type="email"
            name="email"
            placeholder="Enter email address"
            required
        >


        <br><br>


        <label>
            Password
        </label>

        <input
            type="password"
            name="password"
            placeholder="Enter password"
            required
        >


        <br><br>


        <label>
            Role
        </label>

        <select
            name="role"
            required
        >

            <option value="">
                Select Role
            </option>

            <option value="admin">
                Admin
            </option>

            <option value="receptionist">
                Receptionist
            </option>

            <option value="manager">
                Manager
            </option>

        </select>


        <br><br>


        <label>
            Status
        </label>

        <select
            name="status"
            required
        >

            <option value="active">
                Active
            </option>

            <option value="inactive">
                Inactive
            </option>

        </select>


        <br><br>


        <button
            class="btn btn-primary"
            type="submit"
        >
            Create User
        </button>

    </form>


    <a
        class="back-link"
        href="index.php"
    >
        Back to User Management
    </a>

</div>

</body>

</html>