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


/*
|--------------------------------------------------------------------------
| Get Active Housekeeping Tasks
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        housekeeping.id AS task_id,
        housekeeping.room_id,
        housekeeping.status AS task_status,
        housekeeping.notes,
        housekeeping.assigned_at,

        rooms.room_number,
        rooms.floor,
        rooms.status AS room_status,

        room_types.name AS room_type,

        users.name AS assigned_staff

    FROM housekeeping

    INNER JOIN rooms
        ON housekeeping.room_id = rooms.id

    INNER JOIN room_types
        ON rooms.room_type_id = room_types.id

    LEFT JOIN users
        ON housekeeping.assigned_to = users.id

    WHERE housekeeping.status IN ('pending', 'in_progress')

    ORDER BY housekeeping.assigned_at ASC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Could not load housekeeping tasks.");
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
        Housekeeping - Hotel Management System
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
            Housekeeping
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

        <h3>
            Active Cleaning Tasks
        </h3>


        <?php if (mysqli_num_rows($result) > 0) { ?>

            <div style="overflow-x: auto;">

                <table>

                    <tr>

                        <th>Task ID</th>

                        <th>Room</th>

                        <th>Room Type</th>

                        <th>Floor</th>

                        <th>Task Status</th>

                        <th>Room Status</th>

                        <th>Assigned Staff</th>

                        <th>Notes</th>

                        <th>Created At</th>

                        <th>Action</th>

                    </tr>


                    <?php while ($task = mysqli_fetch_assoc($result)) { ?>

                        <tr>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $task["task_id"]
                                );
                                ?>
                            </td>


                            <td>
                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $task["room_number"]
                                    );
                                    ?>
                                </strong>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $task["room_type"]
                                );
                                ?>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $task["floor"]
                                );
                                ?>
                            </td>


                            <td>

                                <strong>

                                    <?php
                                    echo ucfirst(
                                        str_replace(
                                            "_",
                                            " ",
                                            htmlspecialchars(
                                                $task["task_status"]
                                            )
                                        )
                                    );
                                    ?>

                                </strong>

                            </td>


                            <td>

                                <span
                                    class="status-<?php echo htmlspecialchars($task["room_status"]); ?>"
                                >

                                    <?php
                                    echo ucfirst(
                                        htmlspecialchars(
                                            $task["room_status"]
                                        )
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <?php

                                if ($task["assigned_staff"]) {

                                    echo htmlspecialchars(
                                        $task["assigned_staff"]
                                    );

                                } else {

                                    echo "Not Assigned";
                                }

                                ?>

                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $task["notes"]
                                );
                                ?>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $task["assigned_at"]
                                );
                                ?>
                            </td>


                            <td>

                                <a
                                    class="btn btn-success"
                                    href="complete.php?id=<?php echo $task["room_id"]; ?>"
                                    onclick="return confirm('Mark this room as cleaned and available?');"
                                >
                                    Complete Cleaning
                                </a>

                            </td>

                        </tr>

                    <?php } ?>

                </table>

            </div>

        <?php } else { ?>

            <div class="message">

                No active housekeeping tasks.

            </div>

        <?php } ?>

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