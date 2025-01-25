<?php
require_once "Database.php";

$database = new Database();
$db = $database->connect();

// Fetch the number of unread notifications
$query = $db->prepare("SELECT COUNT(*) AS unread_count FROM notifications WHERE isRead = 0");
$query->execute();
$unread_count = $query->fetch(PDO::FETCH_ASSOC)['unread_count'];

// Fetch the unread notifications
$notifications_query = $db->prepare("
    SELECT n.title, u.name AS user_name, n.created_at 
    FROM notifications n
    INNER JOIN users u ON n.user_id = u.id
    WHERE n.isRead = 0
    ORDER BY n.created_at DESC
");
$notifications_query->execute();
$notifications = $notifications_query->fetchAll(PDO::FETCH_ASSOC);
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="#">Manager Panel</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'manager_page.php') echo 'active'; ?>" href="manager_page.php">Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'vacations.php') echo 'active'; ?>" href="vacations.php">Vacations</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
                <!-- Notifications Icon -->
                <li class="nav-item position-relative">
                    <a class="nav-link" href="#" id="notifications-toggle">
                        <span class="position-relative">
                            <i class="fas fa-bell fa-lg"></i>
                            <?php if ($unread_count > 0): ?>
                                <span class="badge bg-danger position-absolute top-0 start-100 translate-middle">
                                    <?= $unread_count ?>
                                </span>
                            <?php endif; ?>
                        </span>
                    </a>
                    <!-- Notifications Dropdown -->
                    <div id="notifications-dropdown" class="position-absolute bg-white shadow rounded p-3 d-none" style="right: 0; width: 300px;">
                        <h6 class="border-bottom pb-2" style="color: #2a5298">Notifications</h6>
                        <?php if (count($notifications) > 0): ?>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="mb-3">
                                    <h6 class="mb-1"><?= htmlspecialchars($notification['title']) ?></h6>
                                    <small class="text-muted"> <?= htmlspecialchars($notification['user_name']) ?></small><br>
                                    <small class="text-muted"><?= htmlspecialchars($notification['created_at']) ?></small>
                                </div>
                                <hr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-muted">No new notifications</div>
                        <?php endif; ?>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<link href="node_modules/@fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.getElementById('notifications-toggle');
        const dropdown = document.getElementById('notifications-dropdown');

        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            dropdown.classList.toggle('d-none');
        });

        document.addEventListener('click', function (e) {
            if (!toggle.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('d-none');
            }
        });
    });
</script>

<style>
    .navbar {
        background: linear-gradient(90deg, #1e3c72, #2a5298);
        border-bottom: 3px solid #ffcc00;
        box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.2);
    }

    .navbar-brand {
        color: #ffd700 !important;
        text-transform: uppercase;
        font-size: 1.5rem;
        letter-spacing: 1px;
    }

    .nav-link {
        color: #fff !important;
        font-size: 1rem;
        font-weight: 600;
        padding: 8px 15px;
        transition: all 0.3s ease-in-out;
    }

    .nav-link:hover {
        color: #1e3c72 !important;
        background-color: #ffcc00;
    }

    .nav-link.active {
        background-color: #ffcc00 !important;
        font-weight: bold;
    }

    .badge {
        font-size: 0.75rem;
        padding: 4px 6px;
    }

    #notifications-dropdown {
        z-index: 1050;
    }

    #notifications-dropdown h6 {
        font-size: 1rem;
        font-weight: bold;
    }

    #notifications-dropdown .text-muted {
        font-size: 0.9rem;
    }
</style>
