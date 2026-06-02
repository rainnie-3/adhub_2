<?php
/**
 * includes/header.php
 * Shared HTML <head> + top navbar shell.
 * Expects:  $pageTitle  (string)
 *           $bodyClass  (string, optional)
 */
$pageTitle = $pageTitle ?? 'AdHub';
$bodyClass = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> – AdHub</title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts: Poppins + Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Global styles -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <?php if (userRole() === 'admin'): ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <?php else: ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/client.css">
    <?php endif; ?>
</head>
<body class="<?= htmlspecialchars($bodyClass) ?>">