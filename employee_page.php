<?php
session_start();
require_once "Database.php";

// Redirect if the user is not logged in or not an employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->connect();

// Handle vacation request creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create_vacation') {
        $date_from = trim($_POST['date_from']);
        $date_to = trim($_POST['date_to']);
        $reason = trim($_POST['reason']);

        if (!empty($date_from) && !empty($date_to) && !empty($reason)) {
            $start_date = new DateTime($date_from);
            $end_date = new DateTime($date_to);
            $days = 0;

            while ($start_date <= $end_date) {
                if ($start_date->format('N') < 6) { // Weekday
                    $days++;
                }
                $start_date->modify('+1 day');
            }

            try {
                // Start a transaction to ensure consistency
                $db->beginTransaction();

                // Insert into vacation_requests
                $insert_vacation_query = $db->prepare("
                    INSERT INTO vacation_requests (date_from, date_to, reason, days, user_id, status_id, created_at)
                    VALUES (:date_from, :date_to, :reason, :days, :user_id, 1, NOW())
                ");
                $insert_vacation_query->bindParam(":date_from", $date_from);
                $insert_vacation_query->bindParam(":date_to", $date_to);
                $insert_vacation_query->bindParam(":reason", $reason);
                $insert_vacation_query->bindParam(":days", $days);
                $insert_vacation_query->bindParam(":user_id", $_SESSION['user_id']);
                $insert_vacation_query->execute();

                // Get the ID of the newly created vacation request
                $vacation_request_id = $db->lastInsertId();

                // Insert into notifications
                $insert_notification_query = $db->prepare("
                    INSERT INTO notifications (title, user_id, vacation_request_id, created_at)
                    VALUES (:title, :user_id, :vacation_request_id, NOW())
                ");
                $notification_title = "New vacation request from " . $_SESSION['user_id'];
                $insert_notification_query->bindParam(":title", $notification_title);
                $insert_notification_query->bindParam(":user_id", $_SESSION['user_id']);
                $insert_notification_query->bindParam(":vacation_request_id", $vacation_request_id);
                $insert_notification_query->execute();

                // Commit the transaction
                $db->commit();

                $_SESSION['success_message'] = "Vacation request created successfully!";
            } catch (Exception $e) {
                // Roll back the transaction on failure
                $db->rollBack();
                $_SESSION['error_message'] = "An error occurred while creating the vacation request.";
            }
        } else {
            $_SESSION['error_message'] = "All fields are required!";
        }

        header("Location: employee_page.php");
        exit();
    }


    if (isset($_POST['action']) && $_POST['action'] === 'delete_vacation') {
        $vacation_id = $_POST['vacation_id'];

        $delete_query = $db->prepare("DELETE FROM vacation_requests WHERE id = :id AND user_id = :user_id");
        $delete_query->bindParam(":id", $vacation_id);
        $delete_query->bindParam(":user_id", $_SESSION['user_id']);
        $delete_query->execute();

        $_SESSION['success_message'] = "Vacation request deleted successfully!";
        header("Location: employee_page.php");
        exit();
    }
}

// Fetch user's vacation requests
$user_id = $_SESSION['user_id'];
$vacations = $db->query("
    SELECT v.id, v.user_id, v.date_from, v.date_to, v.days, v.reason, v.created_at, s.title as status_name, v.status_id
    FROM vacation_requests v
    INNER JOIN statuses s ON v.status_id = s.id
    WHERE v.user_id = $user_id
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>
<?php require_once "layouts/header2.php"; ?>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <?php if (!empty($success_message)) : ?>
        <div class="toast align-items-center text-white bg-success border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <?= htmlspecialchars($success_message) ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>
    <?php if (!empty($error_message)) : ?>
        <div class="toast align-items-center text-white bg-danger border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <?= htmlspecialchars($error_message) ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>My Vacation Requests</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createVacationModal">Create Request</button>
    </div>

    <table class="table table-bordered">
    <thead>
        <tr>
            <th>Date Submitted</th>
            <th>Date From</th>
            <th>Date To</th>
            <th>Days</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($vacations as $vacation): ?>
            <tr>
                <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($vacation['created_at']))) ?></td>
                <td><?= htmlspecialchars(date('d/m/Y', strtotime($vacation['date_from']))) ?></td>
                <td><?= htmlspecialchars(date('d/m/Y', strtotime($vacation['date_to']))) ?></td>
                <td><?= htmlspecialchars($vacation['days']) ?></td>
                <td><?= htmlspecialchars($vacation['reason']) ?></td>
                <td>
                    <span 
                        class="badge 
                            <?php if ($vacation['status_name'] !== 'Rejected' && $vacation['status_name'] !== 'Pending') echo 'bg-success'; ?>
                            <?php if ($vacation['status_name'] === 'Rejected') echo 'bg-danger'; ?>
                            <?php if ($vacation['status_name'] === 'Pending') echo 'bg-warning text-dark'; ?>">
                        <?= htmlspecialchars($vacation['status_name']) ?>
                    </span>
                </td>
                <td>
                    <?php if ($vacation['status_id'] == 1): // Allow Delete only if Pending ?>
                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteVacationModal" data-id="<?= $vacation['id'] ?>">Delete</button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</div>

<!-- Create Vacation Modal -->
<div class="modal fade" id="createVacationModal" tabindex="-1" aria-labelledby="createVacationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="create_vacation">
                <div class="modal-header">
                    <h5 class="modal-title" id="createVacationModalLabel">Create Vacation Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="date_from" class="form-label">Date From</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" required>
                    </div>
                    <div class="mb-3">
                        <label for="date_to" class="form-label">Date To</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" required>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason</label>
                        <textarea class="form-control" id="reason" name="reason" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Vacation Modal -->
<div class="modal fade" id="deleteVacationModal" tabindex="-1" aria-labelledby="deleteVacationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete_vacation">
                <input type="hidden" id="vacation_id" name="vacation_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteVacationModalLabel">Delete Vacation Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete the request?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                    <button type="submit" class="btn btn-danger">Yes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteVacationModal = document.getElementById('deleteVacationModal');

        deleteVacationModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const vacationId = button.getAttribute('data-id');
            deleteVacationModal.querySelector('#vacation_id').value = vacationId;
        });


        const dateFromInput = document.getElementById('date_from');
        const dateToInput = document.getElementById('date_to');

        // Disable past dates for "Date From"
        const today = new Date();
        const formattedToday = today.toISOString().split('T')[0];
        dateFromInput.setAttribute('min', formattedToday);

        // Disable weekends for "Date From"
        dateFromInput.addEventListener('input', function () {
            const selectedDate = new Date(dateFromInput.value);
            if (selectedDate.getDay() === 0 || selectedDate.getDay() === 6) { // Sunday or Saturday
                dateFromInput.value = '';
                alert("Weekends are not allowed. Please select a weekday.");
            }

            // Set "Date To" minimum based on "Date From"
            if (dateFromInput.value) {
                dateToInput.removeAttribute('disabled');
                dateToInput.setAttribute('min', dateFromInput.value);
            } else {
                dateToInput.value = '';
                dateToInput.setAttribute('disabled', 'true');
            }
        });

        // Disable past dates and weekends for "Date To"
        dateToInput.addEventListener('input', function () {
            const selectedDate = new Date(dateToInput.value);
            if (selectedDate.getDay() === 0 || selectedDate.getDay() === 6) { // Sunday or Saturday
                dateToInput.value = '';
                alert("Weekends are not allowed. Please select a weekday.");
            }
        });

        // Initially disable "Date To" until "Date From" is selected
        dateToInput.setAttribute('disabled', 'true');
    });
</script>
</body>
</html>
