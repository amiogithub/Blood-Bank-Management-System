<?php
require_once '../includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if post ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$post_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['user_type'] == 'admin');

// Get post information
$post_query = "SELECT * FROM posts WHERE post_id = $post_id";
$post_result = mysqli_query($conn, $post_query);

if (mysqli_num_rows($post_result) === 0) {
    // Post not found
    $_SESSION['error_message'] = "Post not found.";
    header("Location: dashboard.php");
    exit();
}

$post = mysqli_fetch_assoc($post_result);

// Check if current user is the post owner or an admin
if ($post['user_id'] != $user_id && !$is_admin) {
    $_SESSION['error_message'] = "You do not have permission to delete this post.";
    header("Location: dashboard.php");
    exit();
}

// Delete post
$delete_query = "DELETE FROM posts WHERE post_id = $post_id";

if (mysqli_query($conn, $delete_query)) {
    $_SESSION['success_message'] = "Post deleted successfully.";
} else {
    $_SESSION['error_message'] = "Error deleting post: " . mysqli_error($conn);
}

// Redirect back
if ($is_admin) {
    header("Location: ../admin/posts.php");
} else {
    header("Location: dashboard.php");
}
exit();
?>