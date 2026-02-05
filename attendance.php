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

// Date restrictions - allow dates from current academic year (e.g., 1 year back, current date max)
$minDate     = date('Y-m-d', strtotime('-1 year'));
$maxDate     = date('Y-m-d'); // Today
$currentDate = date('Y-m-d');

$errors = [];
$msg    = '';

if (isset($_POST['save'])) {
    verify_csrf();
    $date = $_POST['date'];

    // Validate date format and range
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
        $errors[] = "Invalid date format.";
    } elseif ($date < $minDate || $date > $maxDate) {
        $errors[] = "Date must be between " . date('M d, Y', strtotime($minDate)) . " and " . date('M d, Y', strtotime($maxDate)) . ".";
    } else {
        // Save attendance for each student
        foreach ($_POST['status'] as $student_id => $status) {
            $student_id = intval($student_id);
            $status     = in_array($status, ['Present', 'Absent']) ? $status : 'Absent';

            $stmt = $mysqli->prepare("INSERT INTO attendance (student_id, date, status) VALUES (?,?,?) 
                                    ON DUPLICATE KEY UPDATE status=?");
            $stmt->bind_param("isss", $student_id, $date, $status, $status);
            $stmt->execute();
        }
        $msg = "Attendance saved successfully for " . date('M d, Y', strtotime($date)) . "!";
    }
}

// Get filter course if set
$filterCourse = isset($_GET['course']) ? trim($_GET['course']) : '';

// Get students (optionally filtered by course)
if (!empty($filterCourse)) {
    $stmt = $mysqli->prepare("SELECT * FROM students WHERE class = ? ORDER BY student_name ASC");
    $stmt->bind_param("s", $filterCourse);
    $stmt->execute();
    $students = $stmt->get_result();
} else {
    $students = $mysqli->query("SELECT * FROM students ORDER BY student_name ASC");
}

// Get distinct courses for filter dropdown
$courses = $mysqli->query("SELECT DISTINCT class FROM students ORDER BY class ASC");

// Get attendance dates for reference (last 30 class dates)
$attendanceDates = $mysqli->query("SELECT DISTINCT date FROM attendance ORDER BY date DESC LIMIT 30");
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mark Attendance</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js" defer></script>
</head>

<body>
    <?php include __DIR__ . '/partials/nav.php'; ?>
    <div class="container mt-4">
        <h3>Mark Attendance</h3>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <!-- Course Filter -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <label class="form-label mb-0">Filter by Course:</label>
                    </div>
                    <div class="col-md-5">
                        <select name="course" class="form-select">
                            <option value="">All Courses</option>
                            <?php while ($courseRow = $courses->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($courseRow['class']) ?>"
                                    <?= $filterCourse === $courseRow['class'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($courseRow['class']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-secondary w-100" type="submit">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Previous Class Dates Reference -->
        <?php if ($attendanceDates->num_rows > 0): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <strong>Previous Class Dates</strong> <small class="text-muted">(Last 30 sessions)</small>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <?php while ($dateRow = $attendanceDates->fetch_assoc()): ?>
                            <span class="badge bg-info text-dark"><?= date('M d, Y', strtotime($dateRow['date'])) ?></span>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST">
            <?= csrf_field(); ?>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Select Date:</label>
                    <input type="date" name="date" class="form-control" min="<?= $minDate ?>" max="<?= $maxDate ?>"
                        value="<?= $currentDate ?>" required>
                    <small class="text-muted">Allowed: <?= date('M d, Y', strtotime($minDate)) ?> to
                        <?= date('M d, Y', strtotime($maxDate)) ?></small>
                </div>
            </div>
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Registration No</th>
                        <th>Course</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($students->num_rows > 0): ?>
                        <?php while ($row = $students->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['student_name']) ?></td>
                                <td><?= htmlspecialchars($row['roll_no']) ?></td>
                                <td><?= htmlspecialchars($row['class']) ?></td>
                                <td>
                                    <select name="status[<?= $row['id'] ?>]" class="form-select">
                                        <option value="Present">Present</option>
                                        <option value="Absent">Absent</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No students found.
                                <?= !empty($filterCourse) ? 'Try a different filter.' : 'Add students first.' ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php if ($students->num_rows > 0): ?>
                <button class="btn btn-success" name="save">Save Attendance</button>
            <?php endif; ?>
        </form>
    </div>
</body>

</html>