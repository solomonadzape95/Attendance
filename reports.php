<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}
include 'db.php';

// Default course code
define('DEFAULT_COURSE', 'COS 341');

// Get filter course if set
$filterCourse = isset($_GET['course']) ? trim($_GET['course']) : '';

// Get distinct courses for filter dropdown
$courses = $mysqli->query("SELECT DISTINCT class FROM students ORDER BY class ASC");

// Build query based on filter
if (!empty($filterCourse)) {
    $stmt = $mysqli->prepare("
        SELECT s.student_name, s.roll_no, s.class,
               SUM(a.status='Present') AS presents,
               SUM(a.status='Absent') AS absents,
               COUNT(a.id) AS total_classes
        FROM students s
        LEFT JOIN attendance a ON s.id = a.student_id
        WHERE s.class = ?
        GROUP BY s.id
        ORDER BY s.student_name ASC
    ");
    $stmt->bind_param("s", $filterCourse);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $mysqli->query("
        SELECT s.student_name, s.roll_no, s.class,
               SUM(a.status='Present') AS presents,
               SUM(a.status='Absent') AS absents,
               COUNT(a.id) AS total_classes
        FROM students s
        LEFT JOIN attendance a ON s.id = a.student_id
        GROUP BY s.id
        ORDER BY s.student_name ASC
    ");
}

// Get class dates for the selected course
if (!empty($filterCourse)) {
    $dateStmt = $mysqli->prepare("
        SELECT DISTINCT a.date 
        FROM attendance a 
        JOIN students s ON a.student_id = s.id 
        WHERE s.class = ? 
        ORDER BY a.date DESC
    ");
    $dateStmt->bind_param("s", $filterCourse);
    $dateStmt->execute();
    $classDates = $dateStmt->get_result();
} else {
    $classDates = $mysqli->query("SELECT DISTINCT date FROM attendance ORDER BY date DESC");
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Attendance Reports</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js" defer></script>
</head>

<body>
    <?php include __DIR__ . '/partials/nav.php'; ?>
    <div class="container mt-4">
        <h3>Attendance Reports</h3>

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

        <!-- Class Dates Reference -->
        <?php if ($classDates->num_rows > 0): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <strong>Class Sessions</strong>
                    <span class="badge bg-primary"><?= $classDates->num_rows ?> sessions</span>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <?php while ($dateRow = $classDates->fetch_assoc()): ?>
                            <span
                                class="badge bg-light text-dark border"><?= date('M d, Y', strtotime($dateRow['date'])) ?></span>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>Registration No</th>
                    <th>Course</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Total</th>
                    <th>% Attendance</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()):
                        $percentage = ($row['total_classes'] > 0) ? round(($row['presents'] / $row['total_classes']) * 100, 2) : 0;
                        $badgeClass = $percentage >= 75 ? 'bg-success' : ($percentage >= 50 ? 'bg-warning' : 'bg-danger');
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['student_name']) ?></td>
                            <td><?= htmlspecialchars($row['roll_no']) ?></td>
                            <td><?= htmlspecialchars($row['class']) ?></td>
                            <td><?= $row['presents'] ?? 0 ?></td>
                            <td><?= $row['absents'] ?? 0 ?></td>
                            <td><?= $row['total_classes'] ?></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= $percentage ?>%</span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">No records found.
                            <?= !empty($filterCourse) ? 'Try a different filter.' : '' ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>

</html>