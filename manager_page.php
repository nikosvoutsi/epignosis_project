<?php
session_start();
require_once "Database.php";
require_once "User.php";

$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

unset($_SESSION['success_message'], $_SESSION['error_message']);

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->connect();
$userModel = new User($db);

// Fetch users for the table
$users = $db->query("SELECT id, name, email, employee_code FROM users WHERE usertype_id = 2" )->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create_user') {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $employee_code = trim($_POST['employee_code']);
            $password = trim($_POST['password']);
            $confirm_password = trim($_POST['confirm_password']);
        
            // Validation flags and error messages
            if (empty($name) || empty($email) || empty($employee_code) || empty($password) || empty($confirm_password)) {
                $_SESSION['error_message'] = "All fields are required.";
            } elseif (strlen($employee_code) !== 7) {
                $_SESSION['error_message'] = "Employee code must be exactly 7 characters.";
            } elseif (strlen($password) < 6) {
                $_SESSION['error_message'] = "Password must be at least 6 characters.";
            } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
                $_SESSION['error_message'] = "Password must include at least one letter, one number, and one special character.";
            } elseif ($password !== $confirm_password) {
                $_SESSION['error_message'] = "Passwords do not match.";
            } else {
                // Check if email already exists
                $query = $db->prepare("SELECT id FROM users WHERE email = :email");
                $query->bindParam(":email", $email);
                $query->execute();
        
                // Check if employee_code already exists
                $code_query = $db->prepare("SELECT id FROM users WHERE employee_code = :employee_code");
                $code_query->bindParam(":employee_code", $employee_code);
                $code_query->execute();
        
                if ($query->rowCount() > 0) {
                    $_SESSION['error_message'] = "User with this email already exists!";
                } elseif ($code_query->rowCount() > 0) {
                    $_SESSION['error_message'] = "Employee code must be unique!";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $insert_query = $db->prepare("INSERT INTO users (name, email, password, usertype_id, employee_code) VALUES (:name, :email, :password, 2, :employee_code)");
                    $insert_query->bindParam(":name", $name);
                    $insert_query->bindParam(":email", $email);
                    $insert_query->bindParam(":password", $hashed_password);
                    $insert_query->bindParam(":employee_code", $employee_code);
                    $insert_query->execute();
                    $_SESSION['success_message'] = "User created successfully!";
                }
            }
            header("Location: manager_page.php");
            exit();
        } elseif ($_POST['action'] === 'edit_user') {
            $id = $_POST['id'];
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $employee_code = trim($_POST['employee_code']);
            $old_password = trim($_POST['old_password']);
            $new_password = trim($_POST['new_password']);
            $confirm_new_password = trim($_POST['confirm_new_password']);
        
            // Check if employee_code is unique (excluding current user)
            $code_query = $db->prepare("SELECT id FROM users WHERE employee_code = :employee_code AND id != :id");
            $code_query->bindParam(":employee_code", $employee_code);
            $code_query->bindParam(":id", $id);
            $code_query->execute();
        
            if ($code_query->rowCount() > 0) {
                $_SESSION['error_message'] = "Employee code must be unique!";
                header("Location: manager_page.php");
                exit();
            }
        
            // Validate employee code length
            if (strlen($employee_code) !== 7) {
                $_SESSION['error_message'] = "Employee code must be exactly 7 characters.";
                header("Location: manager_page.php");
                exit();
            }
        
            // Update basic user info
            $update_query = $db->prepare("UPDATE users SET name = :name, email = :email, employee_code = :employee_code WHERE id = :id");
            $update_query->bindParam(":name", $name);
            $update_query->bindParam(":email", $email);
            $update_query->bindParam(":employee_code", $employee_code);
            $update_query->bindParam(":id", $id);
            $update_query->execute();
        
            // Handle password update
            if (!empty($old_password) && !empty($new_password) && $new_password === $confirm_new_password) {
                if (strlen($new_password) < 6) {
                    $_SESSION['error_message'] = "Password must be at least 6 characters.";
                } elseif (!preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password) || !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password)) {
                    $_SESSION['error_message'] = "Password must include at least one letter, one number, and one special character.";
                } else {
                    $query = $db->prepare("SELECT password FROM users WHERE id = :id");
                    $query->bindParam(":id", $id);
                    $query->execute();
                    $user = $query->fetch(PDO::FETCH_ASSOC);
        
                    if ($user && password_verify($old_password, $user['password'])) {
                        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                        $password_query = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
                        $password_query->bindParam(":password", $hashed_password);
                        $password_query->bindParam(":id", $id);
                        $password_query->execute();
                        $_SESSION['success_message'] = "User updated successfully!";
                    } else {
                        $_SESSION['error_message'] = "Old password is incorrect!";
                    }
                }
            } elseif (!empty($new_password) || !empty($confirm_new_password)) {
                $_SESSION['error_message'] = "Passwords must match and follow the password rules.";
            } else {
                $_SESSION['success_message'] = "User updated successfully!";
            }
            header("Location: manager_page.php");
            exit();
        }
        elseif ($_POST['action'] === 'delete_user') {
            // Handle user deletion
            $id = $_POST['id'];
            $delete_query = $db->prepare("DELETE FROM users WHERE id = :id");
            $delete_query->bindParam(":id", $id);
            $delete_query->execute();
            $_SESSION['success_message'] = "User deleted successfully!";
            header("Location: manager_page.php");
            exit();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Page</title>
    <link rel="icon" type="image/jpeg" href="images/icon.jpg">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css"> 
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>
<?php require_once "layouts/header.php"; ?>

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
        <h3>Users</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">Create User</button>
    </div>

    <?php if (!empty($error_message)) : ?>
        <div class="alert alert-danger"><?= $error_message ?></div>
    <?php endif; ?>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Employee Code</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['employee_code']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editUserModal" data-id="<?= $user['id'] ?>" data-name="<?= $user['name'] ?>" data-email="<?= $user['email'] ?>" data-employee_code="<?= $user['employee_code'] ?>">Edit</button>
                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal" data-id="<?= $user['id'] ?>">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>


    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createUserModalLabel">Create User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="employee_code" class="form-label">Employee Code</label>
                            <input type="text" class="form-control" id="employee_code" name="employee_code" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="action" value="create_user">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

   <!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" id="edit_user_id" name="id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="employee_code" class="form-label">Employee Code</label>
                        <input type="text" class="form-control" id="edit_employee_code" name="employee_code" required>
                    </div>
                    <div class="mb-3">
                        <label for="old_password" class="form-label">Old Password</label>
                        <input type="password" class="form-control" id="old_password" name="old_password">
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                    </div>
                    <div class="mb-3">
                        <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-warning" name="action" value="edit_user">Edit</button>
                </div>
            </form>
        </div>
    </div>
</div>


    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" id="delete_user_id" name="id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteUserModalLabel">Delete User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete this user?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                        <button type="submit" class="btn btn-danger" name="action" value="delete_user">Yes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
       document.addEventListener('DOMContentLoaded', function () {
    const editUserModal = document.getElementById('editUserModal');
    editUserModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const name = button.getAttribute('data-name');
        const email = button.getAttribute('data-email');
        const employee_code = button.getAttribute('data-employee_code');

        editUserModal.querySelector('#edit_user_id').value = id;
        editUserModal.querySelector('#edit_name').value = name;
        editUserModal.querySelector('#edit_email').value = email;
        editUserModal.querySelector('#edit_employee_code').value = employee_code;
        editUserModal.querySelector('#old_password').value = '';
        editUserModal.querySelector('#new_password').value = '';
        editUserModal.querySelector('#confirm_new_password').value = '';
    });

    const deleteUserModal = document.getElementById('deleteUserModal');
deleteUserModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const id = button.getAttribute('data-id');
    deleteUserModal.querySelector('#delete_user_id').value = id;
});

});

    </script>
</body>
</html>
