<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}
include 'db.php';

define('DEFAULT_COURSE', 'COS 341');
$minDate = date('Y-m-d', strtotime('-1 year'));
$maxDate = date('Y-m-d');

$filterCourse = isset($_GET['course']) ? trim($_GET['course']) : '';
$filterDate   = isset($_GET['date']) ? trim($_GET['date']) : '';

$courses = $mysqli->query("SELECT DISTINCT class FROM students ORDER BY class ASC");

// Get distinct dates that have attendance (for quick links)
$datesWithAttendance = $mysqli->query("SELECT DISTINCT date FROM attendance ORDER BY date DESC LIMIT 60");

$records = [];
$dateLabel = '';
$summary = ['present' => 0, 'absent' => 0, 'not_marked' => 0];

if ($filterDate) {
    $dateObj = DateTime::createFromFormat('Y-m-d', $filterDate);
    if ($dateObj && $dateObj->format('Y-m-d') === $filterDate && $filterDate >= $minDate && $filterDate <= $maxDate) {
        $dateLabel = date('l, F j, Y', strtotime($filterDate));

        if (!empty($filterCourse)) {
            $stmt = $mysqli->prepare("
                SELECT s.id, s.student_name, s.roll_no, s.class, a.status
                FROM students s
                LEFT JOIN attendance a ON s.id = a.student_id AND a.date = ?
                WHERE s.class = ?
                ORDER BY s.student_name ASC
            ");
            $stmt->bind_param("ss", $filterDate, $filterCourse);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $stmt = $mysqli->prepare("
                SELECT s.id, s.student_name, s.roll_no, s.class, a.status
                FROM students s
                LEFT JOIN attendance a ON s.id = a.student_id AND a.date = ?
                ORDER BY s.student_name ASC
            ");
            $stmt->bind_param("s", $filterDate);
            $stmt->execute();
            $result = $stmt->get_result();
        }

        while ($row = $result->fetch_assoc()) {
            $status = $row['status'];
            if ($status === 'Present') {
                $summary['present']++;
            } elseif ($status === 'Absent') {
                $summary['absent']++;
            } else {
                $summary['not_marked']++;
            }
            $records[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Attendance by Date</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body class="bg-light">
    <?php include __DIR__ . '/partials/nav.php'; ?>
    <div class="container mt-4">
        <h3><i class="bi bi-calendar3 me-2"></i>Attendance by Date</h3>
        <p class="text-muted">View who was present or absent on a specific day.</p>

        <!-- Date & course filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filterDate) ?>"
                            min="<?= $minDate ?>" max="<?= $maxDate ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Course (optional)</label>
                        <select name="course" class="form-select">
                            <option value="">All courses</option>
                            <?php
                            $courses->data_seek(0);
                            while ($row = $courses->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($row['class']) ?>"
                                    <?= $filterCourse === $row['class'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['class']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">View</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick date links -->
        <?php if ($datesWithAttendance->num_rows > 0): ?>
            <div class="card mb-4">
                <div class="card-header"><strong>Recent dates with attendance</strong></div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <?php while ($dr = $datesWithAttendance->fetch_assoc()):
                            $d = $dr['date'];
                            $url = "attendance-by-date.php?date=" . urlencode($d) . ($filterCourse ? "&course=" . urlencode($filterCourse) : "");
                            $active = ($d === $filterDate) ? 'bg-primary' : 'bg-light text-dark border';
                            ?>
                            <a href="<?= $url ?>" class="badge <?= $active ?> text-decoration-none"><?= date('M j, Y', strtotime($d)) ?></a>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($filterDate && $dateLabel): ?>
            <!-- Summary -->
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="card border-success">
                        <div class="card-body py-2">
                            <span class="text-success fw-bold"><?= $summary['present'] ?></span> Present
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-danger">
                        <div class="card-body py-2">
                            <span class="text-danger fw-bold"><?= $summary['absent'] ?></span> Absent
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-secondary">
                        <div class="card-body py-2">
                            <span class="text-muted fw-bold"><?= $summary['not_marked'] ?></span> Not marked
                        </div>
                    </div>
                </div>
            </div>

            <h5 class="mb-3"><?= htmlspecialchars($dateLabel) ?></h5>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Name</th>
                                <th>Registration No</th>
                                <th>Course</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $r):
                                $status = $r['status'];
                                if ($status === 'Present') {
                                    $badge = 'bg-success';
                                    $text = 'Present';
                                } elseif ($status === 'Absent') {
                                    $badge = 'bg-danger';
                                    $text = 'Absent';
                                } else {
                                    $badge = 'bg-secondary';
                                    $text = 'Not marked';
                                }
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['student_name']) ?></td>
                                    <td><?= htmlspecialchars($r['roll_no']) ?></td>
                                    <td><?= htmlspecialchars($r['class']) ?></td>
                                    <td><span class="badge <?= $badge ?>"><?= $text ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (empty($records)): ?>
                    <div class="card-body text-center text-muted">No students found for this date and course.</div>
                <?php endif; ?>
            </div>
        <?php elseif ($filterDate && !$dateLabel): ?>
            <div class="alert alert-warning">Please select a valid date between <?= date('M j, Y', strtotime($minDate)) ?> and <?= date('M j, Y', strtotime($maxDate)) ?>.</div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
