<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}
include 'db.php';
require __DIR__ . '/auth.php';

// Default course code
define('DEFAULT_COURSE', 'COS 341');

$errors      = [];
$success_msg = '';

// ADD student (COS 341 only)
if (isset($_POST['add'])) {
    verify_csrf();
    $name = trim($_POST['student_name']);
    $roll = trim($_POST['roll_no']);
    $course = DEFAULT_COURSE;

    // Validation: Student name - only letters, spaces, hyphens, apostrophes (2-100 chars)
    if (!preg_match("/^[a-zA-Z\s\-']{2,100}$/", $name)) {
        $errors[] = "Student name must contain only letters, spaces, hyphens and apostrophes (2-100 characters).";
    }

    // Validation: Registration number - numbers and slashes only, exactly 11 chars
    if (strlen($roll) !== 11) {
        $errors[] = "Registration number must be exactly 11 characters.";
    } elseif (!preg_match("/^[0-9\/]{11}$/", $roll)) {
        $errors[] = "Registration number may only contain numbers and slashes (11 characters).";
    }

    // Check if registration number already exists (single course)
    $checkStmt = $mysqli->prepare("SELECT id FROM students WHERE roll_no = ? AND class = ?");
    $checkStmt->bind_param("ss", $roll, $course);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        $errors[] = "This registration number is already in the class list.";
    }

    if (empty($errors)) {
        $stmt = $mysqli->prepare("INSERT INTO students (student_name, roll_no, class) VALUES (?,?,?)");
        $stmt->bind_param("sss", $name, $roll, $course);
        $stmt->execute();
        header("Location: students.php?success=1");
        exit();
    }
}

// IMPORT students from CSV
if (isset($_POST['import']) && isset($_FILES['csv_file'])) {
    verify_csrf();
    $file = $_FILES['csv_file'];

    if ($file['error'] === UPLOAD_ERR_OK && pathinfo($file['name'], PATHINFO_EXTENSION) === 'csv') {
        $handle = fopen($file['tmp_name'], 'r');
        $header = fgetcsv($handle); // Skip header row

        $imported     = 0;
        $skipped      = 0;
        $importErrors = [];

        $course = DEFAULT_COURSE;
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 2) {
                $name = trim($row[0]);
                $roll = trim($row[1]);

                // Validate each row (registration number exactly 11 chars, numbers/slashes only)
                if (
                    preg_match("/^[a-zA-Z\s\-']{2,100}$/", $name) &&
                    strlen($roll) === 11 && preg_match("/^[0-9\/]{11}$/", $roll)
                ) {
                    $checkStmt = $mysqli->prepare("SELECT id FROM students WHERE roll_no = ? AND class = ?");
                    $checkStmt->bind_param("ss", $roll, $course);
                    $checkStmt->execute();

                    if ($checkStmt->get_result()->num_rows === 0) {
                        $stmt = $mysqli->prepare("INSERT INTO students (student_name, roll_no, class) VALUES (?,?,?)");
                        $stmt->bind_param("sss", $name, $roll, $course);
                        if ($stmt->execute()) {
                            $imported++;
                        }
                    } else {
                        $skipped++;
                    }
                } else {
                    $skipped++;
                }
            }
        }
        fclose($handle);

        $success_msg = "Imported $imported students. Skipped $skipped (duplicates or invalid data).";
    } else {
        $errors[] = "Please upload a valid CSV file.";
    }
}

// DELETE student
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $mysqli->query("DELETE FROM students WHERE id=$id");
    header("Location: students.php");
    exit();
}

$students      = $mysqli->query("SELECT * FROM students ORDER BY id DESC");
$countResult   = $mysqli->query("SELECT COUNT(*) as total FROM students");
$totalStudents = $countResult->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Students</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js" defer></script>
</head>

<body>
    <?php include __DIR__ . '/partials/nav.php'; ?>
    <div class="container mt-4">
        <h3>Manage Students <small class="text-muted">COS 341 (<?= $totalStudents ?> students)</small></h3>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Student added successfully!</div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
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

        <!-- Add Student Form (COS 341) -->
        <form method="POST" class="row g-2 mt-3 mb-3">
            <?= csrf_field(); ?>
            <div class="col-md-4">
                <input type="text" name="student_name" class="form-control" placeholder="Student Name"
                    pattern="^[a-zA-Z\s\-']{2,100}$" title="Only letters, spaces, hyphens, apostrophes (2-100 chars)"
                    required>
            </div>
            <div class="col-md-4">
                <input type="text" name="roll_no" class="form-control" placeholder="Registration No (11 chars, e.g. 2021/001234)"
                    pattern="^[0-9\/]{11}$" title="Numbers and slashes only, exactly 11 characters"
                    minlength="11" maxlength="11" required>
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary w-100" name="add">Add Student</button>
            </div>
        </form>

        <!-- Import CSV Form -->
        <div class="card mb-4">
            <div class="card-header">
                <strong>Import Class List (CSV)</strong>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" class="row g-2 align-items-center">
                    <?= csrf_field(); ?>
                    <div class="col-md-6">
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-success w-100" name="import">Import CSV</button>
                    </div>
                    <div class="col-md-3">
                        <a href="#" class="btn btn-outline-secondary w-100" data-bs-toggle="modal"
                            data-bs-target="#csvHelpModal">CSV Format Help</a>
                    </div>
                </form>
            </div>
        </div>

        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Registration No</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $students->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['student_name']) ?></td>
                        <td><?= htmlspecialchars($row['roll_no']) ?></td>
                        <td>
                            <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm"
                                onclick="return confirm('Delete this student?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- CSV Help Modal -->
    <div class="modal fade" id="csvHelpModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">CSV Format Guide</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Your CSV file should have the following format (course is COS 341):</p>
                    <pre class="bg-light p-2">student_name,roll_no
John Doe,2021/0012345
Jane Smith,2021/0012346</pre>
                    <p><strong>Rules:</strong></p>
                    <ul>
                        <li>First row should be header (will be skipped)</li>
                        <li>Name: Letters, spaces, hyphens, apostrophes only</li>
                        <li>Registration No: exactly 11 chars, numbers and slashes only</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>