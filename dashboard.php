<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Get user information
$user_query = "SELECT * FROM users WHERE user_id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Check user roles
$role_query = "SELECT * FROM user_roles WHERE user_id = $user_id";
$role_result = mysqli_query($conn, $role_query);

if (mysqli_num_rows($role_result) > 0) {
    $role = mysqli_fetch_assoc($role_result);
    $is_donor = $role['is_donor'];
    $is_recipient = $role['is_recipient'];
} else {
    // Create default role as both donor and recipient
    $role_insert = "INSERT INTO user_roles (user_id, is_donor, is_recipient) VALUES ($user_id, TRUE, TRUE)";
    mysqli_query($conn, $role_insert);
    $is_donor = true;
    $is_recipient = true;
}

// Handle emergency alert dismissal
if (isset($_GET['dismiss_emergency']) && is_numeric($_GET['dismiss_emergency'])) {
    $emergency_id = intval($_GET['dismiss_emergency']);
    
    // Create dismissed_alerts table if it doesn't exist
    $create_table_query = "CREATE TABLE IF NOT EXISTS dismissed_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        alert_id INT NOT NULL,
        dismissal_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        UNIQUE KEY (user_id, alert_id)
    )";
    mysqli_query($conn, $create_table_query);
    
    // Add the dismissed alert to user's dismissed alerts
    $check_query = "SELECT * FROM dismissed_alerts WHERE user_id = $user_id AND alert_id = $emergency_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) == 0) {
        $insert_query = "INSERT INTO dismissed_alerts (user_id, alert_id) VALUES ($user_id, $emergency_id)";
        mysqli_query($conn, $insert_query);
    }
    
    // Redirect to prevent form resubmission
    header("Location: dashboard.php");
    exit();
}

// Handle emergency response (redirect to chat)
if (isset($_GET['respond_to_emergency']) && is_numeric($_GET['respond_to_emergency']) && isset($_GET['emergency_id']) && is_numeric($_GET['emergency_id'])) {
    $responder_id = intval($_GET['respond_to_emergency']);
    $emergency_id = intval($_GET['emergency_id']);
    
    // First mark as dismissed
    $check_query = "SELECT * FROM dismissed_alerts WHERE user_id = $user_id AND alert_id = $emergency_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) == 0) {
        $insert_query = "INSERT INTO dismissed_alerts (user_id, alert_id) VALUES ($user_id, $emergency_id)";
        mysqli_query($conn, $insert_query);
    }
    
    // Redirect to chat
    header("Location: chat.php?user_id=$responder_id");
    exit();
}

// Check if donor profile exists
$donor_profile_exists = false;
if ($is_donor) {
    $profile_check_query = "SELECT * FROM donor_profiles WHERE user_id = $user_id";
    $profile_result = mysqli_query($conn, $profile_check_query);
    $donor_profile_exists = mysqli_num_rows($profile_result) > 0;
}

// Handle post submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_content'])) {
    $post_content = trim($_POST['post_content']);
    
    if (!empty($post_content)) {
        // Sanitize the post content
        $post_content = mysqli_real_escape_string($conn, $post_content);
        
        $post_query = "INSERT INTO posts (user_id, content) VALUES ($user_id, '$post_content')";
        if (mysqli_query($conn, $post_query)) {
            // Check if post is a blood request
            checkBloodRequestPost($post_content, $user_id, $conn);
            
            // Post created successfully
            header("Location: dashboard.php");
            exit();
        }
    }
}

// Get all posts with user information
$posts_query = "SELECT p.*, u.full_name, u.user_type 
                FROM posts p 
                JOIN users u ON p.user_id = u.user_id 
                ORDER BY p.post_date DESC";
$posts_result = mysqli_query($conn, $posts_query);

// Get count of unread notifications
$notifications_query = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = $user_id AND is_read = 0";
$notifications_result = mysqli_query($conn, $notifications_query);
$notifications_data = mysqli_fetch_assoc($notifications_result);
$unread_notifications = $notifications_data['unread_count'];

// Get count of unread messages
$messages_query = "SELECT COUNT(*) as unread_count FROM messages WHERE receiver_id = $user_id AND is_read = 0";
$messages_result = mysqli_query($conn, $messages_query);
$messages_data = mysqli_fetch_assoc($messages_result);
$unread_messages = $messages_data['unread_count'];

// Check for pending requests
$donation_requests = 0;
$blood_requests = 0;

// Check pending donation requests if user is a donor
if ($is_donor) {
    $donation_query = "SELECT COUNT(*) as count FROM donation_requests WHERE user_id = $user_id AND status = 'pending'";
    $donation_result = mysqli_query($conn, $donation_query);
    $donation_data = mysqli_fetch_assoc($donation_result);
    $donation_requests = $donation_data['count'];
}

// Check pending blood requests if user is a recipient
if ($is_recipient) {
    $blood_query = "SELECT COUNT(*) as count FROM blood_requests WHERE recipient_id = $user_id AND status = 'pending'";
    $blood_result = mysqli_query($conn, $blood_query);
    $blood_data = mysqli_fetch_assoc($blood_result);
    $blood_requests = $blood_data['count'];
}

// Get user profile picture
$profile_picture_path = getUserProfilePicture($user_id, $conn);

// Ensure emergency_logs table has post_id column
$check_column_query = "SHOW COLUMNS FROM emergency_logs LIKE 'post_id'";
$column_result = mysqli_query($conn, $check_column_query);
if (mysqli_num_rows($column_result) == 0) {
    $alter_table_query = "ALTER TABLE emergency_logs ADD COLUMN post_id INT NULL, ADD FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE SET NULL";
    mysqli_query($conn, $alter_table_query);
}

// Get active emergency alerts that the user hasn't dismissed
$emergency_query = "SELECT e.*, u.full_name, u.user_id as emergency_user_id, p.post_id
                  FROM emergency_logs e
                  JOIN users u ON e.user_id = u.user_id
                  LEFT JOIN posts p ON e.post_id = p.post_id
                  WHERE e.emergency_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  AND e.log_id NOT IN (
                      SELECT alert_id FROM dismissed_alerts WHERE user_id = $user_id
                  )
                  ORDER BY e.emergency_date DESC
                  LIMIT 3";
$emergency_result = mysqli_query($conn, $emergency_query);
?>

<div class="container my-5">
    <!-- Emergency Alerts Section -->
     
    <?php if (isset($emergency_result) && mysqli_num_rows($emergency_result) > 0): ?>
        <?php while ($emergency = mysqli_fetch_assoc($emergency_result)): ?>
            <div class="alert alert-danger alert-dismissible fade show emergency-alert mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 mr-3">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h4 class="alert-heading">EMERGENCY BLOOD REQUEST!</h4>
                        <p class="mb-1">
                            <strong><?php echo htmlspecialchars($emergency['full_name']); ?></strong> urgently needs
                            <span class="badge badge-danger"><?php echo $emergency['blood_group']; ?></span> blood at
                            <strong><?php echo htmlspecialchars($emergency['location']); ?></strong>
                        </p>
                        <p class="mb-1"><?php echo htmlspecialchars($emergency['details']); ?></p>
                        <p class="mb-0">
                            Contact: <strong><?php echo htmlspecialchars($emergency['contact']); ?></strong> | 
                            <small class="text-muted">Posted <?php echo timeElapsedString($emergency['emergency_date']); ?></small>
                        </p>
                        <div class="mt-2">
                            <?php if ($emergency['post_id']): ?>
                                <a href="view_post.php?id=<?php echo $emergency['post_id']; ?>" class="btn btn-sm btn-danger mr-2">
                                    <i class="fas fa-comments mr-1"></i> View Post
                                </a>
                            <?php endif; ?>
                            <?php if ($emergency['emergency_user_id'] != $user_id): ?>
                                <a href="dashboard.php?respond_to_emergency=<?php echo $emergency['emergency_user_id']; ?>&emergency_id=<?php echo $emergency['log_id']; ?>" class="btn btn-sm btn-danger mr-2">
                                    <i class="fas fa-reply mr-1"></i> Respond
                                </a>
                                <a href="chat.php?user_id=<?php echo $emergency['emergency_user_id']; ?>" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-envelope mr-1"></i> Message
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <button type="button" class="close" aria-label="Close" onclick="dismissEmergency(<?php echo $emergency['log_id']; ?>)">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endwhile; ?>
        <script>
            function dismissEmergency(emergencyId) {
                window.location.href = 'dashboard.php?dismiss_emergency=' + emergencyId;
            }
        </script>
    <?php endif; ?>

    <!-- Dashboard Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h2 class="mb-0">Welcome, <?php echo htmlspecialchars($full_name); ?>!</h2>
                            <p class="mb-0">
                                <i class="fas fa-user mr-2"></i>
                                Your Account
                            </p>
                        </div>
                        <div class="col-md-6 text-md-right">
                            <a href="blood_stock.php" class="btn btn-outline-light mr-2">
                                <i class="fas fa-tint mr-1"></i> Blood Stock
                            </a>
                            <a href="notifications.php" class="btn btn-outline-light position-relative mr-2">
                                <i class="fas fa-bell mr-1"></i> Notifications
                                <?php if ($unread_notifications > 0): ?>
                                    <span class="badge badge-light position-absolute" style="top: -8px; right: -8px;"><?php echo $unread_notifications; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="messages.php" class="btn btn-outline-light position-relative">
                                <i class="fas fa-envelope mr-1"></i> Messages
                                <?php if ($unread_messages > 0): ?>
                                    <span class="badge badge-light position-absolute" style="top: -8px; right: -8px;"><?php echo $unread_messages; ?></span>
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="row">
        <!-- Left Sidebar - User Information -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-user mr-2"></i> Your Profile</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <?php if ($profile_picture_path): ?>
                            <img src="<?php echo $profile_picture_path; ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" style="width: 100px; height: 100px; object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                                <span><?php echo strtoupper(substr($full_name, 0, 1)); ?></span>
                            </div>
                        <?php endif; ?>
                        <h5><?php echo htmlspecialchars($full_name); ?></h5>
                        <div>
                            <?php if ($is_donor): ?>
                                <span class="badge badge-success mr-1">Donor</span>
                            <?php endif; ?>
                            <?php if ($is_recipient): ?>
                                <span class="badge badge-info">Recipient</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Email</span>
                            <!-- Fix alignment by using text truncation with tooltip -->
                            <span class="text-muted text-truncate" style="max-width: 120px;" title="<?php echo htmlspecialchars($user['email']); ?>"><?php echo htmlspecialchars($user['email']); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Phone</span>
                            <span class="text-muted"><?php echo htmlspecialchars($user['contact_number']); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Donation Requests</span>
                            <span class="badge badge-danger badge-pill"><?php echo $donation_requests; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Blood Requests</span>
                            <span class="badge badge-danger badge-pill"><?php echo $blood_requests; ?></span>
                        </li>
                    </ul>
                    
                    <div class="mt-3">
                        <!-- Show donor options to all users - both can donate -->
                        <div class="mb-2">
                            <?php if ($donor_profile_exists): ?>
                                <a href="donor_profile.php" class="btn btn-outline-danger btn-sm btn-block">Edit Donor Profile</a>
                                <a href="donation_request.php" class="btn btn-danger btn-sm btn-block mt-2">Make Donation Request</a>
                            <?php else: ?>
                                <div class="alert alert-info small mb-3">
                                    Complete your donor profile to donate blood.
                                </div>
                                <a href="donor_profile.php" class="btn btn-danger btn-sm btn-block">Complete Donor Profile</a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Show recipient options to all users - both can request -->
                        <div class="mt-2">
                            <a href="blood_request.php" class="btn btn-danger btn-sm btn-block">Request Blood</a>
                            <a href="emergency_alert.php" class="btn btn-outline-danger btn-sm btn-block mt-2">
                                <i class="fas fa-exclamation-triangle mr-1"></i> Emergency Alert
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-link mr-2"></i> Quick Links</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <a href="donor_list.php" class="text-danger">
                                <i class="fas fa-users mr-2"></i> Available Donors
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="blood_stock.php" class="text-danger">
                                <i class="fas fa-tint mr-2"></i> Blood Stock
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="messages.php" class="text-danger">
                                <i class="fas fa-comments mr-2"></i> Messages
                                <?php if ($unread_messages > 0): ?>
                                    <span class="badge badge-danger"><?php echo $unread_messages; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="notifications.php" class="text-danger">
                                <i class="fas fa-bell mr-2"></i> Notifications
                                <?php if ($unread_notifications > 0): ?>
                                    <span class="badge badge-danger"><?php echo $unread_notifications; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Main Content - Posts and Feed -->
        <div class="col-md-9">
            <!-- Post Creation Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="form-group mb-3">
                            <textarea class="form-control" name="post_content" rows="3" placeholder="What's on your mind? Share your thoughts, ask for help, or offer support..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger">Post</button>
                    </form>
                </div>
            </div>
            
            <!-- Posts Feed -->
            <h4 class="mb-3"><i class="fas fa-stream mr-2"></i> Recent Posts</h4>
            
            <?php if (mysqli_num_rows($posts_result) > 0): ?>
                <?php while ($post = mysqli_fetch_assoc($posts_result)): ?>
                    <?php 
                    // Get comments for this post
                    $post_id = $post['post_id'];
                    $comments_query = "SELECT c.*, u.full_name 
                                      FROM comments c 
                                      JOIN users u ON c.user_id = u.user_id 
                                      WHERE c.post_id = $post_id 
                                      ORDER BY c.comment_date ASC";
                    $comments_result = mysqli_query($conn, $comments_query);
                    
                    // Get profile picture of post author
                    $post_author_pic = getUserProfilePicture($post['user_id'], $conn);
                    
                    // Check if this is an emergency post
                    $is_emergency = strpos($post['content'], '[EMERGENCY BLOOD ALERT]') !== false;
                    ?>
                    
                    <div class="card post-card mb-4 <?php echo $is_emergency ? 'border-danger' : ''; ?>" id="post-<?php echo $post_id; ?>">
                        <div class="card-header bg-white <?php echo $is_emergency ? 'border-danger' : ''; ?>">
                            <div class="d-flex align-items-center">
                                <?php if ($post_author_pic): ?>
                                    <img src="<?php echo $post_author_pic; ?>" alt="Profile Picture" class="rounded-circle mr-3" style="width: 40px; height: 40px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center mr-3" style="width: 40px; height: 40px;">
                                        <span><?php echo strtoupper(substr($post['full_name'], 0, 1)); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h5 class="mb-0"><?php echo htmlspecialchars($post['full_name']); ?></h5>
                                    <small class="text-muted">
                                        <?php echo timeElapsedString($post['post_date']); ?>
                                    </small>
                                </div>
                                <?php if ($is_emergency): ?>
                                    <span class="badge badge-danger ml-auto">EMERGENCY</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            
                            <hr class="my-2">
                            
                            <div class="d-flex">
                                <button class="btn btn-sm btn-outline-danger mr-2" onclick="document.getElementById('commentForm<?php echo $post_id; ?>').classList.toggle('d-none')">
                                    <i class="fas fa-comment-alt mr-1"></i> Comment
                                </button>
                                
                                <a href="<?php echo ($post['user_id'] != $user_id) ? 'chat.php?user_id='.$post['user_id'] : '#'; ?>" class="btn btn-sm btn-outline-danger <?php echo ($post['user_id'] == $user_id) ? 'disabled' : ''; ?>">
                                    <i class="fas fa-envelope mr-1"></i> Message
                                </a>
                                
                                <?php if ($post['user_id'] == $user_id || $_SESSION['user_type'] == 'admin'): ?>
                                <div class="dropdown ml-auto">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" id="postOptions<?php echo $post_id; ?>" data-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="postOptions<?php echo $post_id; ?>">
                                        <?php if ($post['user_id'] == $user_id): ?>
                                        <a class="dropdown-item" href="edit_post.php?id=<?php echo $post_id; ?>">
                                            <i class="fas fa-edit mr-2"></i> Edit
                                        </a>
                                        <?php endif; ?>
                                        <a class="dropdown-item text-danger" href="delete_post.php?id=<?php echo $post_id; ?>" onclick="return confirm('Are you sure you want to delete this post?')">
                                            <i class="fas fa-trash-alt mr-2"></i> Delete
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Comment Form (Hidden by Default) -->
                            <form id="commentForm<?php echo $post_id; ?>" action="add_comment.php" method="post" class="mt-3 d-none">
                                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                                <div class="form-group">
                                    <textarea class="form-control form-control-sm" name="comment_content" rows="2" placeholder="Write a comment..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-sm btn-danger">Submit</button>
                            </form>
                        </div>
                        
                        <?php if (mysqli_num_rows($comments_result) > 0): ?>
                            <div class="card-footer bg-light">
                                <h6 class="mb-3"><i class="fas fa-comments mr-2"></i> Comments</h6>
                                
                                <?php while ($comment = mysqli_fetch_assoc($comments_result)): ?>
                                    <?php
                                    // Get commenter's profile picture
                                    $commenter_pic = getUserProfilePicture($comment['user_id'], $conn);
                                    ?>
                                    <div class="comment mb-3">
                                        <div class="d-flex">
                                            <?php if ($commenter_pic): ?>
                                                <img src="<?php echo $commenter_pic; ?>" alt="Profile Picture" class="rounded-circle mr-2" style="width: 30px; height: 30px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mr-2" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                                    <span><?php echo strtoupper(substr($comment['full_name'], 0, 1)); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="d-flex align-items-center">
                                                    <h6 class="mb-0 mr-2"><?php echo htmlspecialchars($comment['full_name']); ?></h6>
                                                    <small class="text-muted">
                                                    <?php echo timeElapsedString($comment['comment_date']); ?>
                                                </small>
                                                </div>
                                                <p class="mb-0 mt-1"><?php echo htmlspecialchars($comment['content']); ?></p>
                                                <div class="mt-1">
                                                    <a href="#" class="text-muted small" onclick="replyToComment(<?php echo $comment['comment_id']; ?>, '<?php echo htmlspecialchars($comment['full_name']); ?>')">Reply</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i> No posts yet. Be the first to share something!
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Email text overflow */
.text-truncate {
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: inline-block;
}

/* Emergency alert styles */
.emergency-alert {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
    }
}

/* Dark mode compatibility */
.dark-mode .emergency-alert {
    background-color: rgba(220, 53, 69, 0.9);
    color: white;
}

.dark-mode .emergency-alert .close {
    color: white;
}
</style>

<script>
// Function to handle comment replies
function replyToComment(commentId, userName) {
    // Find the comment's post form
    const commentElement = event.target.closest('.comment');
    const postCard = commentElement.closest('.post-card');
    const postId = postCard.querySelector('form input[name="post_id"]').value;
    const commentForm = document.getElementById('commentForm' + postId);
    
    // Show the form and populate with a mention
    commentForm.classList.remove('d-none');
    commentForm.querySelector('textarea').value = '@' + userName + ' ';
    commentForm.querySelector('textarea').focus();
    
    // Prevent default link behavior
    event.preventDefault();
}

// Dismiss emergency alerts
function dismissEmergency(emergencyId) {
    window.location.href = 'dashboard.php?dismiss_emergency=' + emergencyId;
}
</script>

<?php require_once '../includes/footer.php'; ?>