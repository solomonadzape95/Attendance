<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}
include 'db.php';

// Single course: COS 341
define('DEFAULT_COURSE', 'COS 341');

$course = DEFAULT_COURSE;
$stmt = $mysqli->prepare("
    SELECT s.student_name, s.roll_no,
           SUM(a.status='Present') AS presents,
           SUM(a.status='Absent') AS absents,
           COUNT(a.id) AS total_classes
    FROM students s
    LEFT JOIN attendance a ON s.id = a.student_id
    WHERE s.class = ?
    GROUP BY s.id
    ORDER BY s.student_name ASC
");
$stmt->bind_param("s", $course);
$stmt->execute();
$result = $stmt->get_result();

$classDates = $mysqli->query("SELECT DISTINCT date FROM attendance ORDER BY date DESC");
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
        <h3>Attendance Reports <small class="text-muted">COS 341</small></h3>

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
                            <td><?= $row['presents'] ?? 0 ?></td>
                            <td><?= $row['absents'] ?? 0 ?></td>
                            <td><?= $row['total_classes'] ?></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= $percentage ?>%</span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>

</html>