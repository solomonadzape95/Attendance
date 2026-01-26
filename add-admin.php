<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}
include 'db.php';

// ADD student
if (isset($_POST['add'])) {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $mysqli->prepare("INSERT INTO users (full_name, username, password) VALUES (?,?,?)");
    $stmt->bind_param("sss", $full_name, $username, $hashedPassword);
    $stmt->execute();
    header("Location: add-admin.php");
}

// DELETE student
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $mysqli->query("DELETE FROM users WHERE id=$id");
    header("Location: add-admin.php");
}
$users = $mysqli->query("SELECT * FROM users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>

<head>
    <title>Add Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>

<body>
    <?php include __DIR__ . '/partials/header.php'; ?>
    <div class="container mt-4">
        <h3>Add an Admin</h3>
        <form method="POST" class="form mt-3 mb-4">
            <input type="text" name="full_name" class="form-control mb-2" placeholder="Full Name"
                    required>
            <input type="text" name="username" class="form-control mb-2" placeholder="Username" required>
            
            <input type="password" name="password" class="form-control mb-2" placeholder="******" required>
            
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