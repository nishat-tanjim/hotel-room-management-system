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


$sql = "
    SELECT
        id,
        full_name,
        phone,
        email,
        national_id,
        gender,
        address,
        created_at
    FROM guests
    ORDER BY id DESC
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
        Guests - Hotel Management System
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
            Guest Management
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
            Add New Guest
        </a>


        <table>

            <tr>

                <th>Name</th>

                <th>Phone</th>

                <th>Email</th>

                <th>National ID</th>

                <th>Gender</th>

                <th>Address</th>

                <th>Created At</th>

                <th>Action</th>

            </tr>


            <?php while ($guest = mysqli_fetch_assoc($result)) { ?>

                <tr>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $guest["full_name"]
                        );
                        ?>
                    </td>


                    <td>
                        <?php
                        echo htmlspecialchars(
                            $guest["phone"]
                        );
                        ?>
                    </td>


                    <td>
                        <?php
                        echo htmlspecialchars(
                            $guest["email"]
                        );
                        ?>
                    </td>


                    <td>
                        <?php
                        echo htmlspecialchars(
                            $guest["national_id"]
                        );
                        ?>
                    </td>


                    <td>
                        <?php
                        echo ucfirst(
                            htmlspecialchars(
                                $guest["gender"]
                            )
                        );
                        ?>
                    </td>


                    <td>
                        <?php
                        echo htmlspecialchars(
                            $guest["address"]
                        );
                        ?>
                    </td>


                    <td>
                        <?php
                        echo htmlspecialchars(
                            $guest["created_at"]
                        );
                        ?>
                    </td>


                    <td>

                        <a
                            class="btn btn-warning"
                            href="edit.php?id=<?php echo $guest["id"]; ?>"
                        >
                            Edit
                        </a>

                        <a
                            class="btn btn-danger"
                            href="delete.php?id=<?php echo $guest["id"]; ?>"
                            onclick="return confirm('Are you sure you want to delete this guest?');"
                        >
                            Delete
                        </a>

                    </td>

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