<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if form was submitted and all required fields are present
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_id']) && isset($_POST['comment_content'])) {
    $post_id = $_POST['post_id'];
    $comment_content = trim($_POST['comment_content']);
    
    // Validate the input
    if (!empty($post_id) && !empty($comment_content)) {
        // Insert the comment
        $query = "INSERT INTO comments (post_id, user_id, content) VALUES ('$post_id', '$user_id', '$comment_content')";
        
        if (mysqli_query($conn, $query)) {
            // Check if comment is a blood request
            checkBloodRequestPost($comment_content, $user_id, $conn);
            
            // Get post author ID to notify them about the comment
            $post_query = "SELECT user_id FROM posts WHERE post_id = '$post_id'";
            $post_result = mysqli_query($conn, $post_query);
            
            if (mysqli_num_rows($post_result) > 0) {
                $post_data = mysqli_fetch_assoc($post_result);
                $post_user_id = $post_data['user_id'];
                
                // Only notify if the commenter is not the post author
                if ($post_user_id != $user_id) {
                    // Get commenter name
                    $user_query = "SELECT full_name FROM users WHERE user_id = '$user_id'";
                    $user_result = mysqli_query($conn, $user_query);
                    $user_data = mysqli_fetch_assoc($user_result);
                    $commenter_name = $user_data['full_name'];
                    
                    // Create notification
                    $notification_content = "$commenter_name commented on your post.";
                    $notification_query = "INSERT INTO notifications (user_id, content) VALUES ('$post_user_id', '$notification_content')";
                    mysqli_query($conn, $notification_query);
                }
            }
            
            // Redirect back to the dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            // Error handling
            echo "Error: " . mysqli_error($conn);
        }
    }
}

// If there's an error or no form submitted, redirect to dashboard
header("Location: dashboard.php");
exit();
?>