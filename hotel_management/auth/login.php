<?php

session_start();

require_once "../config/database.php";



if (isset($_SESSION["user_id"])) {

    header("Location: ../dashboard.php");
    exit;
}


$error = "";




if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];


    $sql = "
        SELECT
            id,
            name,
            email,
            password,
            role,
            status
        FROM users
        WHERE email = ?
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $email
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $user = mysqli_fetch_assoc($result);


    if (!$user) {

        $error = "Invalid email or password.";

    } elseif ($user["status"] !== "active") {

        $error = "Your account is inactive.";

    } elseif (!password_verify($password, $user["password"])) {

        $error = "Invalid email or password.";

    } else {

        session_regenerate_id(true);

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["name"] = $user["name"];
        $_SESSION["email"] = $user["email"];
        $_SESSION["role"] = $user["role"];

        header("Location: ../dashboard.php");
        exit;
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
        Login - Hotel Management System
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>

<div class="container">

    <div
        class="page-header"
        style="max-width: 500px; margin: 70px auto 20px;"
    >

        <h1>
            Hotel Management System
        </h1>

        <h2>
            Staff Login
        </h2>

    </div>


    <form
        method="POST"
        style="max-width: 500px; margin: 0 auto;"
    >


        <?php if ($error != "") { ?>

            <div class="message">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php } ?>


        <label>
            Email Address
        </label>

        <input
            type="email"
            name="email"
            placeholder="Enter your email"
            required
        >


        <br><br>


        <label>
            Password
        </label>

        <input
            type="password"
            name="password"
            placeholder="Enter your password"
            required
        >


        <br><br>


        <button
            class="btn btn-primary"
            type="submit"
        >
            Login
        </button>


    </form>

</div>

</body>

</html>