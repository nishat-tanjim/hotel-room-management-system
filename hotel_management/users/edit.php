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
    die("User ID not provided.");
}

$user_id = (int) $_GET["id"];

$message = "";


/*
|--------------------------------------------------------------------------
| Get User
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        name,
        email,
        role,
        status
    FROM users
    WHERE id = ?
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$user = mysqli_fetch_assoc($result);


if (!$user) {
    die("User not found.");
}


/*
|--------------------------------------------------------------------------
| Update User
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"]);

    $email = trim($_POST["email"]);

    $role = $_POST["role"];

    $status = $_POST["status"];

    $new_password = $_POST["new_password"];


    if (
        $name === "" ||
        $email === "" ||
        $role === "" ||
        $status === ""
    ) {

        $message =
            "Please fill in all required fields.";

    } else {


        /*
        |--------------------------------------------------------------------------
        | Check Email Is Not Used by Another User
        |--------------------------------------------------------------------------
        */

        $checkSql = "
            SELECT id
            FROM users
            WHERE email = ?
            AND id != ?
            LIMIT 1
        ";

        $checkStmt = mysqli_prepare(
            $conn,
            $checkSql
        );

        mysqli_stmt_bind_param(
            $checkStmt,
            "si",
            $email,
            $user_id
        );

        mysqli_stmt_execute($checkStmt);

        $checkResult =
            mysqli_stmt_get_result($checkStmt);

        $existingUser =
            mysqli_fetch_assoc($checkResult);


        if ($existingUser) {

            $message =
                "Another user already uses this email address.";

        } else {


            /*
            |--------------------------------------------------------------------------
            | Update With New Password
            |--------------------------------------------------------------------------
            */

            if ($new_password !== "") {

                $hashedPassword =
                    password_hash(
                        $new_password,
                        PASSWORD_DEFAULT
                    );


                $updateSql = "
                    UPDATE users
                    SET
                        name = ?,
                        email = ?,
                        role = ?,
                        status = ?,
                        password = ?
                    WHERE id = ?
                ";

                $updateStmt =
                    mysqli_prepare(
                        $conn,
                        $updateSql
                    );

                mysqli_stmt_bind_param(
                    $updateStmt,
                    "sssssi",
                    $name,
                    $email,
                    $role,
                    $status,
                    $hashedPassword,
                    $user_id
                );

            } else {


                /*
                |--------------------------------------------------------------------------
                | Update Without Changing Password
                |--------------------------------------------------------------------------
                */

                $updateSql = "
                    UPDATE users
                    SET
                        name = ?,
                        email = ?,
                        role = ?,
                        status = ?
                    WHERE id = ?
                ";

                $updateStmt =
                    mysqli_prepare(
                        $conn,
                        $updateSql
                    );

                mysqli_stmt_bind_param(
                    $updateStmt,
                    "ssssi",
                    $name,
                    $email,
                    $role,
                    $status,
                    $user_id
                );
            }


            if (mysqli_stmt_execute($updateStmt)) {

                $message =
                    "User updated successfully.";


                /*
                |--------------------------------------------------------------------------
                | Reload User
                |--------------------------------------------------------------------------
                */

                $reloadSql = "
                    SELECT
                        id,
                        name,
                        email,
                        role,
                        status
                    FROM users
                    WHERE id = ?
                ";

                $reloadStmt =
                    mysqli_prepare(
                        $conn,
                        $reloadSql
                    );

                mysqli_stmt_bind_param(
                    $reloadStmt,
                    "i",
                    $user_id
                );

                mysqli_stmt_execute(
                    $reloadStmt
                );

                $reloadResult =
                    mysqli_stmt_get_result(
                        $reloadStmt
                    );

                $user =
                    mysqli_fetch_assoc(
                        $reloadResult
                    );

            } else {

                $message =
                    "Could not update user.";
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
        Edit User - Hotel Management System
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
            Edit User
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
            value="<?php echo htmlspecialchars($user["name"]); ?>"
            required
        >


        <br><br>


        <label>
            Email Address
        </label>

        <input
            type="email"
            name="email"
            value="<?php echo htmlspecialchars($user["email"]); ?>"
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

            <option
                value="admin"
                <?php
                if ($user["role"] === "admin") {
                    echo "selected";
                }
                ?>
            >
                Admin
            </option>

            <option
                value="receptionist"
                <?php
                if ($user["role"] === "receptionist") {
                    echo "selected";
                }
                ?>
            >
                Receptionist
            </option>

            <option
                value="manager"
                <?php
                if ($user["role"] === "manager") {
                    echo "selected";
                }
                ?>
            >
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

            <option
                value="active"
                <?php
                if ($user["status"] === "active") {
                    echo "selected";
                }
                ?>
            >
                Active
            </option>

            <option
                value="inactive"
                <?php
                if ($user["status"] === "inactive") {
                    echo "selected";
                }
                ?>
            >
                Inactive
            </option>

        </select>


        <br><br>


        <label>
            New Password
        </label>

        <input
            type="password"
            name="new_password"
            placeholder="Leave blank to keep current password"
        >


        <br><br>


        <button
            class="btn btn-primary"
            type="submit"
        >
            Update User
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