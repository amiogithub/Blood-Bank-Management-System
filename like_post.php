<?php
require_once '../includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Return JSON response for AJAX request
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$response = ['success' => false];

// Process like/unlike request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_id'])) {
    $post_id = $_POST['post_id'];
    $reaction_type = isset($_POST['reaction_type']) ? $_POST['reaction_type'] : 'like';
    
    // Check if post exists
    $post_query = "SELECT * FROM posts WHERE post_id = $post_id";
    $post_result = mysqli_query($conn, $post_query);
    
    if (mysqli_num_rows($post_result) > 0) {
        $post = mysqli_fetch_assoc($post_result);
        $post_owner_id = $post['user_id'];
        
        // Check if user already liked this post
        $check_query = "SELECT * FROM likes WHERE user_id = $user_id AND post_id = $post_id";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            // User already liked this post, toggle behavior based on request
            $like = mysqli_fetch_assoc($check_result);
            
            if ($like['reaction_type'] == $reaction_type && !isset($_POST['force'])) {
                // Unlike - remove the like if it's the same reaction
                $delete_query = "DELETE FROM likes WHERE user_id = $user_id AND post_id = $post_id";
                if (mysqli_query($conn, $delete_query)) {
                    $response = [
                        'success' => true,
                        'action' => 'unliked',
                        'post_id' => $post_id
                    ];
                }
            } else {
                // Update reaction type
                $update_query = "UPDATE likes SET reaction_type = '$reaction_type' WHERE user_id = $user_id AND post_id = $post_id";
                if (mysqli_query($conn, $update_query)) {
                    $response = [
                        'success' => true,
                        'action' => 'changed',
                        'reaction' => $reaction_type,
                        'post_id' => $post_id
                    ];
                }
            }
        } else {
            // Add new like
            $insert_query = "INSERT INTO likes (user_id, post_id, reaction_type) VALUES ($user_id, $post_id, '$reaction_type')";
            
            if (mysqli_query($conn, $insert_query)) {
                // If post owner is not the liker, create notification
                if ($post_owner_id != $user_id) {
                    // Get user info for notification
                    $user_query = "SELECT full_name FROM users WHERE user_id = $user_id";
                    $user_result = mysqli_query($conn, $user_query);
                    $user_data = mysqli_fetch_assoc($user_result);
                    $liker_name = $user_data['full_name'];
                    
                    // Create notification
                    $notification_content = "$liker_name reacted to your post.";
                    $notification_query = "INSERT INTO notifications (user_id, content) VALUES ($post_owner_id, '$notification_content')";
                    mysqli_query($conn, $notification_query);
                }
                
                $response = [
                    'success' => true,
                    'action' => 'liked',
                    'reaction' => $reaction_type,
                    'post_id' => $post_id
                ];
            }
        }
        
        // Get updated like counts for response
        $count_query = "SELECT COUNT(*) as like_count FROM likes WHERE post_id = $post_id";
        $count_result = mysqli_query($conn, $count_query);
        $count_data = mysqli_fetch_assoc($count_result);
        $response['count'] = $count_data['like_count'];
    }
}

// Same process for comments if needed
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment_id'])) {
    $comment_id = $_POST['comment_id'];
    $reaction_type = isset($_POST['reaction_type']) ? $_POST['reaction_type'] : 'like';
    
    // Check if comment exists
    $comment_query = "SELECT c.*, p.user_id as post_owner_id 
                     FROM comments c 
                     JOIN posts p ON c.post_id = p.post_id 
                     WHERE c.comment_id = $comment_id";
    $comment_result = mysqli_query($conn, $comment_query);
    
    if (mysqli_num_rows($comment_result) > 0) {
        $comment = mysqli_fetch_assoc($comment_result);
        $comment_owner_id = $comment['user_id'];
        
        // Similar logic for comments...
        // [Similar code as above for handling comment likes]
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit();
?>