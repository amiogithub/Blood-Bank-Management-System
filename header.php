<?php
ob_start(); // Start output buffering

session_start();
require_once $_SERVER['DOCUMENT_ROOT'].'/BloodBankSystem/config/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/BloodBankSystem/includes/functions.php';

// Set timezone to Bangladesh timezone
date_default_timezone_set('Asia/Dhaka');

// Set default theme if not set
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'light';
}

// Handle theme toggle
if (isset($_GET['toggle_theme'])) {
    $_SESSION['theme'] = ($_SESSION['theme'] == 'light') ? 'dark' : 'light';
    
    // Redirect back to the referring page
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

// Set default user_type if not set
if (isset($_SESSION['user_id']) && !isset($_SESSION['user_type'])) {
    $_SESSION['user_type'] = 'user'; // Default to normal user
}

$is_dark_mode = $_SESSION['theme'] == 'dark';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $_SESSION['theme']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Bank Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="/BloodBankSystem/css/style.css">
    <?php if ($is_dark_mode): ?>
    <link rel="stylesheet" href="/BloodBankSystem/css/dark-mode.css">
    <?php endif; ?>
</head>
<body class="<?php echo $is_dark_mode ? 'dark-mode' : ''; ?>">
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
        <div class="container">
            <a class="navbar-brand" href="/BloodBankSystem/index.php">
                <i class="fas fa-heartbeat mr-2"></i>Blood Bank
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/BloodBankSystem/index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/BloodBankSystem/pages/about.php">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/BloodBankSystem/pages/looking_for_blood.php">Looking for Blood</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/BloodBankSystem/pages/donate_blood.php">Want to Donate Blood</a>
                    </li>
                    
                    <!-- Theme Toggle -->
                    <li class="nav-item">
                        <a class="nav-link" href="?toggle_theme=1">
                            <i class="fas <?php echo $is_dark_mode ? 'fa-sun' : 'fa-moon'; ?>"></i>
                        </a>
                    </li>
                    
                    <?php if(isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] != 'admin'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown">
                                Account
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="/BloodBankSystem/pages/donor_list.php">Available Donors</a>
                                <a class="dropdown-item" href="/BloodBankSystem/pages/blood_stock.php">Blood Stock</a>
                                <a class="dropdown-item" href="/BloodBankSystem/pages/notifications.php">Notifications</a>
                                <a class="dropdown-item" href="/BloodBankSystem/pages/donor_profile.php">Donor Profile</a>
                                <a class="dropdown-item" href="/BloodBankSystem/pages/donation_request.php">Donate Blood</a>
                                <a class="dropdown-item" href="/BloodBankSystem/pages/blood_request.php">Request Blood</a>
                                <a class="dropdown-item text-danger" href="/BloodBankSystem/pages/emergency_alert.php">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> Emergency Alert
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="/BloodBankSystem/pages/dashboard.php">Dashboard</a>
                                <a class="dropdown-item" href="/BloodBankSystem/pages/profile_picture.php">
                                    <i class="fas fa-user-circle mr-1"></i> Profile Settings
                                </a>
                                <a class="dropdown-item" href="/BloodBankSystem/pages/logout.php">Logout</a>
                            </div>
                        </li>
                    <?php elseif(isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/BloodBankSystem/admin/dashboard.php">Admin Panel</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/BloodBankSystem/pages/logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/BloodBankSystem/pages/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/BloodBankSystem/admin/login.php">Admin Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>