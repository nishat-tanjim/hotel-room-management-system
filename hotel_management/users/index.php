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


/*
|--------------------------------------------------------------------------
| Get Users
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        name,
        email,
        role,
        status,
        created_at
    FROM users
    ORDER BY id ASC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Could not load users.");
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
        User Management - Hotel Management System
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
            User Management
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


    <div class="card">

        <a
            class="btn btn-primary"
            href="add.php"
        >
            Add New User
        </a>


        <div style="overflow-x: auto;">

            <table>

                <tr>

                    <th>ID</th>

                    <th>Name</th>

                    <th>Email</th>

                    <th>Role</th>

                    <th>Status</th>

                    <th>Created At</th>

                    <th>Action</th>

                </tr>


                <?php while ($user = mysqli_fetch_assoc($result)) { ?>

                    <tr>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $user["id"]
                            );
                            ?>
                        </td>


                        <td>
                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $user["name"]
                                );
                                ?>
                            </strong>
                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                $user["email"]
                            );
                            ?>
                        </td>


                        <td>
                            <?php
                            echo ucfirst(
                                htmlspecialchars(
                                    $user["role"]
                                )
                            );
                            ?>
                        </td>


                        <td>

                            <strong>

                                <?php
                                echo ucfirst(
                                    htmlspecialchars(
                                        $user["status"]
                                    )
                                );
                                ?>

                            </strong>

                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                $user["created_at"]
                            );
                            ?>
                        </td>


                        <td>

                            <a
                                class="btn btn-warning"
                                href="edit.php?id=<?php echo $user["id"]; ?>"
                            >
                                Edit
                            </a>

                        </td>

                    </tr>

                <?php } ?>

            </table>

        </div>

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