<?php
session_start();
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Process donor registration
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $blood_group = mysqli_real_escape_string($conn, $_POST['blood_group']);
    $last_donation_date = !empty($_POST['last_donation_date']) ? mysqli_real_escape_string($conn, $_POST['last_donation_date']) : NULL;
    $medical_info = isset($_POST['medical_info']) ? mysqli_real_escape_string($conn, $_POST['medical_info']) : '';
    $smoker = mysqli_real_escape_string($conn, $_POST['smoker']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    
    // Basic validation
    $errors = [];
    
    if (empty($blood_group)) {
        $errors[] = "Blood group is required";
    }
    
    if (empty($smoker)) {
        $errors[] = "Please indicate if you are a smoker";
    }
    
    if (empty($gender)) {
        $errors[] = "Gender is required";
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Check if donor profile exists
            $check_query = "SELECT * FROM donor_profiles WHERE user_id = $user_id";
            $check_result = mysqli_query($conn, $check_query);
            
            if (mysqli_num_rows($check_result) > 0) {
                // Update existing profile
                $update_query = "UPDATE donor_profiles 
                                SET blood_group = '$blood_group',
                                    last_donation_date = " . ($last_donation_date ? "'$last_donation_date'" : "NULL") . ",
                                    medical_info = '$medical_info',
                                    smoker = '$smoker',
                                    gender = '$gender',
                                    is_available = TRUE
                                WHERE user_id = $user_id";
                
                if (!mysqli_query($conn, $update_query)) {
                    throw new Exception(mysqli_error($conn));
                }
            } else {
                // Create new donor profile
                $insert_query = "INSERT INTO donor_profiles (user_id, blood_group, last_donation_date, medical_info, smoker, gender, is_available) 
                                VALUES ($user_id, '$blood_group', " . ($last_donation_date ? "'$last_donation_date'" : "NULL") . ", '$medical_info', '$smoker', '$gender', TRUE)";
                
                if (!mysqli_query($conn, $insert_query)) {
                    throw new Exception(mysqli_error($conn));
                }
            }
            
            // Update user_roles to mark as donor
            $role_check_query = "SELECT * FROM user_roles WHERE user_id = $user_id";
            $role_result = mysqli_query($conn, $role_check_query);
            
            if (mysqli_num_rows($role_result) > 0) {
                // Update existing role
                $role_update_query = "UPDATE user_roles SET is_donor = 1 WHERE user_id = $user_id";
                
                if (!mysqli_query($conn, $role_update_query)) {
                    throw new Exception(mysqli_error($conn));
                }
            } else {
                // Create new role entry
                $role_insert_query = "INSERT INTO user_roles (user_id, is_donor, is_recipient) 
                                     VALUES ($user_id, 1, 0)";
                
                if (!mysqli_query($conn, $role_insert_query)) {
                    throw new Exception(mysqli_error($conn));
                }
            }
            
            // Check and award badges
            checkAndAwardBadges($user_id, $conn);
            
            // Create a notification
            $notification_content = "You have successfully registered as a donor. Thank you for your willingness to save lives!";
            $notification_query = "INSERT INTO notifications (user_id, content) VALUES ($user_id, '$notification_content')";
            
            if (!mysqli_query($conn, $notification_query)) {
                throw new Exception(mysqli_error($conn));
            }
            
            // Notify admin
            $admin_query = "SELECT user_id FROM users WHERE user_type = 'admin' LIMIT 1";
            $admin_result = mysqli_query($conn, $admin_query);
            
            if (mysqli_num_rows($admin_result) > 0) {
                $admin_id = mysqli_fetch_assoc($admin_result)['user_id'];
                
                // Get user information
                $user_query = "SELECT full_name FROM users WHERE user_id = $user_id";
                $user_result = mysqli_query($conn, $user_query);
                $user_name = mysqli_fetch_assoc($user_result)['full_name'];
                
                $admin_notification = "New donor registered: $user_name ($blood_group)";
                $admin_notification_query = "INSERT INTO notifications (user_id, content) VALUES ($admin_id, '$admin_notification')";
                
                mysqli_query($conn, $admin_notification_query);
            }
            
            // Commit the transaction
            mysqli_commit($conn);
            
            // Set success session variable
            $_SESSION['donor_registered'] = true;
            
            // Redirect to donate blood page
            header("Location: donate_blood.php");
            exit();
        } catch (Exception $e) {
            // Rollback the transaction if any query fails
            mysqli_rollback($conn);
            
            // Set error session variable
            $_SESSION['donor_error'] = $e->getMessage();
            
            // Redirect to the registration page
            header("Location: donate_blood.php");
            exit();
        }
    } else {
        // Set error session variable
        $_SESSION['donor_errors'] = $errors;
        
        // Redirect to the registration page
        header("Location: donate_blood.php");
        exit();
    }
} else {
    // Redirect to donate blood page if accessed directly
    header("Location: donate_blood.php");
    exit();
}
?>