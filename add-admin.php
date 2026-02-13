<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}
include 'db.php';
require __DIR__ . '/auth.php';

$errors = [];

// ADD admin
if (isset($_POST['add'])) {
    verify_csrf();
    $full_name = trim($_POST['full_name']);
    $username  = trim($_POST['username']);
    $password  = $_POST['password'];

    // Validation: Full name - only letters, spaces, hyphens, apostrophes (2-100 chars)
    if (!preg_match("/^[a-zA-Z\s\-']{2,100}$/", $full_name)) {
        $errors[] = "Full name must contain only letters, spaces, hyphens and apostrophes (2-100 characters).";
    }

    // Validation: Username - only alphanumeric and underscore (3-50 chars)
    if (!preg_match("/^[a-zA-Z0-9_]{3,50}$/", $username)) {
        $errors[] = "Username must contain only letters, numbers and underscore (3-50 characters).";
    }

    // Validation: Password - minimum 6 chars, at least one letter and one number
    if (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&]{6,50}$/", $password)) {
        $errors[] = "Password must be 6-50 characters with at least one letter and one number.";
    }

    // Check if username already exists
    $checkStmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
    $checkStmt->bind_param("s", $username);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        $errors[] = "Username already exists.";
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt           = $mysqli->prepare("INSERT INTO users (full_name, username, password) VALUES (?,?,?)");
        $stmt->bind_param("sss", $full_name, $username, $hashedPassword);
        $stmt->execute();
        header("Location: add-admin.php?success=1");
        exit();
    }
}

// DELETE admin/user
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $mysqli->query("DELETE FROM users WHERE id=$id");
    header("Location: add-admin.php");
}
$users = $mysqli->query("SELECT * FROM users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js" defer></script>
</head>

<body>
    <?php include __DIR__ . '/partials/nav.php'; ?>
    <div class="container mt-4">
        <h3>Add an Admin</h3>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Admin added successfully!</div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="form mt-3 mb-4" id="adminForm">
            <?= csrf_field(); ?>
            <div class="mb-2">
                <input type="text" name="full_name" class="form-control"
                    placeholder="Full Name (letters, spaces, hyphens only)" pattern="^[a-zA-Z\s\-']{2,100}$"
                    title="Only letters, spaces, hyphens and apostrophes (2-100 characters)" required>
            </div>
            <div class="mb-2">
                <input type="text" name="username" class="form-control"
                    placeholder="Username (letters, numbers, underscore)" pattern="^[a-zA-Z0-9_]{3,50}$"
                    title="Only letters, numbers and underscore (3-50 characters)" required>
            </div>
            <div class="mb-2">
                <input type="password" name="password" class="form-control"
                    placeholder="Password (min 6 chars, letter + number)"
                    pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&]{6,50}$"
                    title="6-50 characters with at least one letter and one number" required>
            </div>
            <button class="btn btn-primary w-100 mt-2" name="add">Add Admin</button>
        </form>
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= $row['full_name'] ?></td>
                        <td><?= $row['username'] ?></td>
                        <td>
                            <?php
                            if ($row['id'] !== '1'):
                                ?>
                                <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Delete this admin?')">Delete</a>
                                <?php
                            endif;
                            ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>

</html>