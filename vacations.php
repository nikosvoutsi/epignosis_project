<?php
session_start();
require_once "Database.php";
require_once "vacation.php";
require_once "notification.php";

// Redirect if the user is not logged in or not a manager
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->connect();
$vacationModel = new Vacation($db);
$notificationModel = new Notification($db);

// Fetch vacation requests
$vacations = $vacationModel->fetchVacations();

// Mark all unread notifications as read
$notificationModel->markNotificationsAsRead();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_status') {
    $vacation_id = $_POST['vacation_id'];
    $new_status = $_POST['status_id'];

    $vacationModel->updateStatus($vacation_id, $new_status);

    $_SESSION['success_message'] = "Status updated successfully!";
    header("Location: vacations.php");
    exit();
}

$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
unset($_SESSION['success_message']);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vacation Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/jpeg" href="images/icon.jpg">
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <?php require_once "layouts/header.php"; ?>

    <div class="container mt-5">
        <h3 class="mb-3">Vacation Requests</h3>

        <?php if (!empty($success_message)) : ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

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
                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editStatusModal" 
                    data-id="<?= $vacation['id'] ?>" 
                    data-status="<?= $vacation['status_id'] ?>">
                    Edit
                </button>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>

        </table>
    </div>

    <!-- Edit Status Modal -->
    <div class="modal fade" id="editStatusModal" tabindex="-1" aria-labelledby="editStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="vacations.php">
                    <input type="hidden" id="vacation_id" name="vacation_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editStatusModalLabel">Edit Vacation Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="status_id" class="form-label">Status</label>
                            <select class="form-select" id="status_id" name="status_id" required>
                                <option value="1">Pending</option>
                                <option value="2">Approved</option>
                                <option value="3">Rejected</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="action" value="edit_status">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editStatusModal = document.getElementById('editStatusModal');
            editStatusModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const vacationId = button.getAttribute('data-id');
                const statusId = button.getAttribute('data-status');

                // Set hidden input and dropdown values
                editStatusModal.querySelector('#vacation_id').value = vacationId;
                editStatusModal.querySelector('#status_id').value = statusId;
            });
        });
    </script>
</body>
</html>
