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

// Process recipient registration
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $blood_group = $_POST['blood_group'];
    $medical_history = isset($_POST['medical_history']) ? trim($_POST['medical_history']) : '';
    $preferred_hospital = isset($_POST['preferred_hospital']) ? trim($_POST['preferred_hospital']) : '';
    
    // Basic validation
    $errors = [];
    
    if (empty($blood_group)) {
        $errors[] = "Blood group is required";
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Check if recipient profile exists
            $check_query = "SELECT * FROM recipient_profiles WHERE user_id = $user_id";
            $check_result = mysqli_query($conn, $check_query);
            
            if (mysqli_num_rows($check_result) > 0) {
                // Update existing profile
                $update_query = "UPDATE recipient_profiles 
                                SET blood_group = '$blood_group', 
                                    medical_history = '$medical_history', 
                                    preferred_hospital = '$preferred_hospital' 
                                WHERE user_id = $user_id";
                
                if (!mysqli_query($conn, $update_query)) {
                    throw new Exception(mysqli_error($conn));
                }
            } else {
                // Create new recipient profile
                $insert_query = "INSERT INTO recipient_profiles (user_id, blood_group, medical_history, preferred_hospital) 
                                VALUES ($user_id, '$blood_group', '$medical_history', '$preferred_hospital')";
                
                if (!mysqli_query($conn, $insert_query)) {
                    throw new Exception(mysqli_error($conn));
                }
            }
            
            // Update user_roles to mark as recipient
            // Update user_roles to mark as recipient
            $role_check_query = "SELECT * FROM user_roles WHERE user_id = $user_id";
            $role_result = mysqli_query($conn, $role_check_query);
            
            if (mysqli_num_rows($role_result) > 0) {
                // Update existing role
                $role_update_query = "UPDATE user_roles SET is_recipient = 1 WHERE user_id = $user_id";
                
                if (!mysqli_query($conn, $role_update_query)) {
                    throw new Exception(mysqli_error($conn));
                }
            } else {
                // Create new role entry
                $role_insert_query = "INSERT INTO user_roles (user_id, is_donor, is_recipient) 
                                     VALUES ($user_id, 0, 1)";
                
                if (!mysqli_query($conn, $role_insert_query)) {
                    throw new Exception(mysqli_error($conn));
                }
            }
            
            // Create a notification
            $notification_content = "You have successfully registered as a recipient. You can now request blood.";
            $notification_query = "INSERT INTO notifications (user_id, content) VALUES ($user_id, '$notification_content')";
            
            if (!mysqli_query($conn, $notification_query)) {
                throw new Exception(mysqli_error($conn));
            }
            
            // Commit the transaction
            mysqli_commit($conn);
            
            // Set success session variable
            $_SESSION['recipient_registered'] = true;
            
            // Redirect to looking for blood page
            header("Location: looking_for_blood.php");
            exit();
        } catch (Exception $e) {
            // Rollback the transaction if any query fails
            mysqli_rollback($conn);
            
            // Set error session variable
            $_SESSION['recipient_error'] = $e->getMessage();
            
            // Redirect to the registration page
            header("Location: looking_for_blood.php");
            exit();
        }
    } else {
        // Set error session variable
        $_SESSION['recipient_errors'] = $errors;
        
        // Redirect to the registration page
        header("Location: looking_for_blood.php");
        exit();
    }
} else {
    // Redirect to looking for blood page if accessed directly
    header("Location: looking_for_blood.php");
    exit();
}
?>