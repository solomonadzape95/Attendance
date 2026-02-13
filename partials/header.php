<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$config  = require __DIR__ . '/../config.php';
$appName = htmlspecialchars($config['app_name'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $appName; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js" defer></script>
</head>

<body class="bg-light">
<?php include __DIR__ . '/nav.php'; ?>