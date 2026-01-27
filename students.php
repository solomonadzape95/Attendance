<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}
include 'db.php';

// Default course code
define('DEFAULT_COURSE', 'COS 341');
$MAX_STUDENTS = 50;

$errors      = [];
$success_msg = '';

// ADD student
if (isset($_POST['add'])) {
    $name   = trim($_POST['student_name']);
    $roll   = trim($_POST['roll_no']);
    $course = trim($_POST['course']);

    // Validation: Student name - only letters, spaces, hyphens, apostrophes (2-100 chars)
    if (!preg_match("/^[a-zA-Z\s\-']{2,100}$/", $name)) {
        $errors[] = "Student name must contain only letters, spaces, hyphens and apostrophes (2-100 characters).";
    }

    // Validation: Roll number - alphanumeric, hyphens, slashes (1-50 chars)
    if (!preg_match("/^[a-zA-Z0-9\-\/]{1,50}$/", $roll)) {
        $errors[] = "Roll number must contain only letters, numbers, hyphens and slashes (1-50 characters).";
    }

    // Validation: Course - alphanumeric, spaces, hyphens (2-50 chars)
    if (!preg_match("/^[a-zA-Z0-9\s\-]{2,50}$/", $course)) {
        $errors[] = "Course must contain only letters, numbers, spaces and hyphens (2-50 characters).";
    }

    // Check max students limit
    $countResult   = $mysqli->query("SELECT COUNT(*) as total FROM students");
    $totalStudents = $countResult->fetch_assoc()['total'];
    if ($totalStudents >= $MAX_STUDENTS) {
        $errors[] = "Maximum student limit of $MAX_STUDENTS reached. Cannot add more students.";
    }

    // Check if roll number already exists
    $checkStmt = $mysqli->prepare("SELECT id FROM students WHERE roll_no = ?");
    $checkStmt->bind_param("s", $roll);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        $errors[] = "Roll number already exists.";
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
    $file = $_FILES['csv_file'];

    if ($file['error'] === UPLOAD_ERR_OK && pathinfo($file['name'], PATHINFO_EXTENSION) === 'csv') {
        $handle = fopen($file['tmp_name'], 'r');
        $header = fgetcsv($handle); // Skip header row

        $imported     = 0;
        $skipped      = 0;
        $importErrors = [];

        // Check current student count
        $countResult  = $mysqli->query("SELECT COUNT(*) as total FROM students");
        $currentCount = $countResult->fetch_assoc()['total'];

        while (($row = fgetcsv($handle)) !== false && $currentCount < $MAX_STUDENTS) {
            if (count($row) >= 3) {
                $name   = trim($row[0]);
                $roll   = trim($row[1]);
                $course = trim($row[2]);

                // Validate each row
                if (
                    preg_match("/^[a-zA-Z\s\-']{2,100}$/", $name) &&
                    preg_match("/^[a-zA-Z0-9\-\/]{1,50}$/", $roll) &&
                    preg_match("/^[a-zA-Z0-9\s\-]{2,50}$/", $course)
                ) {

                    // Check if roll number exists
                    $checkStmt = $mysqli->prepare("SELECT id FROM students WHERE roll_no = ?");
                    $checkStmt->bind_param("s", $roll);
                    $checkStmt->execute();

                    if ($checkStmt->get_result()->num_rows === 0) {
                        $stmt = $mysqli->prepare("INSERT INTO students (student_name, roll_no, class) VALUES (?,?,?)");
                        $stmt->bind_param("sss", $name, $roll, $course);
                        if ($stmt->execute()) {
                            $imported++;
                            $currentCount++;
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

        if ($currentCount >= $MAX_STUDENTS && !feof($handle)) {
            $errors[] = "Import stopped: Maximum student limit of $MAX_STUDENTS reached.";
        }

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
    <title>Manage Students</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>

<body>
    <?php include __DIR__ . '/partials/header.php'; ?>
    <div class="container mt-4">
        <h3>Manage Students <small class="text-muted">(<?= $totalStudents ?>/<?= $MAX_STUDENTS ?>)</small></h3>

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

        <!-- Add Student Form -->
        <form method="POST" class="row g-2 mt-3 mb-3">
            <div class="col-md-3">
                <input type="text" name="student_name" class="form-control" placeholder="Student Name"
                    pattern="^[a-zA-Z\s\-']{2,100}$" title="Only letters, spaces, hyphens, apostrophes (2-100 chars)"
                    required>
            </div>
            <div class="col-md-3">
                <input type="text" name="roll_no" class="form-control" placeholder="Roll No"
                    pattern="^[a-zA-Z0-9\-\/]{1,50}$" title="Only letters, numbers, hyphens, slashes (1-50 chars)"
                    required>
            </div>
            <div class="col-md-3">
                <input type="text" name="course" class="form-control" placeholder="Course"
                    value="<?= htmlspecialchars(DEFAULT_COURSE) ?>" pattern="^[a-zA-Z0-9\s\-]{2,50}$"
                    title="Only letters, numbers, spaces, hyphens (2-50 chars)" required>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100" name="add" <?= $totalStudents >= $MAX_STUDENTS ? 'disabled' : '' ?>>Add Student</button>
            </div>
        </form>

        <!-- Import CSV Form -->
        <div class="card mb-4">
            <div class="card-header">
                <strong>Import Class List (CSV)</strong>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" class="row g-2 align-items-center">
                    <div class="col-md-6">
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-success w-100" name="import" <?= $totalStudents >= $MAX_STUDENTS ? 'disabled' : '' ?>>Import CSV</button>
                    </div>
                    <div class="col-md-3">
                        <a href="#" class="btn btn-outline-secondary w-100" data-bs-toggle="modal"
                            data-bs-target="#csvHelpModal">CSV Format Help</a>
                    </div>
                </form>
                <small class="text-muted mt-2 d-block">Maximum class size: <?= $MAX_STUDENTS ?> students</small>
            </div>
        </div>

        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Roll No</th>
                    <th>Course</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $students->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['student_name']) ?></td>
                        <td><?= htmlspecialchars($row['roll_no']) ?></td>
                        <td><?= htmlspecialchars($row['class']) ?></td>
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
                    <p>Your CSV file should have the following format:</p>
                    <pre class="bg-light p-2">student_name,roll_no,course
John Doe,CSC/2021/001,COS 341
Jane Smith,CSC/2021/002,COS 341</pre>
                    <p><strong>Rules:</strong></p>
                    <ul>
                        <li>First row should be header (will be skipped)</li>
                        <li>Name: Letters, spaces, hyphens, apostrophes only</li>
                        <li>Roll No: Letters, numbers, hyphens, slashes only</li>
                        <li>Course: Letters, numbers, spaces, hyphens only</li>
                        <li>Maximum <?= $MAX_STUDENTS ?> students allowed</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>