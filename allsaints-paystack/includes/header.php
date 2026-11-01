<?php
/**
 * header.php
 * ---------------------------------------------------------------------
 * Shared page header + navigation bar for all public-facing pages.
 * Included at the top of every page with: require 'includes/header.php';
 * ---------------------------------------------------------------------
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? e($pageTitle) . ' — ' : ''; echo e(CHURCH_NAME); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <div class="brand">
            <img src="/assets/images/church-logo.png" alt="<?php echo e(CHURCH_NAME); ?> logo" class="logo" onerror="this.style.display='none'">
            <div class="brand-text">
                <span class="brand-name"><?php echo e(CHURCH_NAME); ?></span>
                <span class="brand-tagline">Give online, securely, in a few taps</span>
            </div>
        </div>
        <nav class="main-nav">
            <a href="/payments/donate.php">Donate</a>
        </nav>
    </div>
</header>
