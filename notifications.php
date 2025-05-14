<?php
require_once '../includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark all notifications as read
if (isset($_GET['mark_all_read']) && $_GET['mark_all_read'] == 'true') {
    $update_query = "UPDATE notifications SET is_read = 1 WHERE user_id = $user_id";
    mysqli_query($conn, $update_query);
    
    // Redirect to prevent form resubmission
    header("Location: notifications.php");
    exit();
}

// Delete notification
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $notification_id = $_GET['delete'];
    $delete_query = "DELETE FROM notifications WHERE notification_id = $notification_id AND user_id = $user_id";
    mysqli_query($conn, $delete_query);
    
    // Redirect to prevent form resubmission
    header("Location: notifications.php");
    exit();
}

// Get all notifications for the user
$notifications_query = "SELECT * FROM notifications WHERE user_id = $user_id ORDER BY notification_date DESC";
$notifications_result = mysqli_query($conn, $notifications_query);

// Count unread notifications
$unread_query = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = $user_id AND is_read = 0";
$unread_result = mysqli_query($conn, $unread_query);
$unread_data = mysqli_fetch_assoc($unread_result);
$unread_count = $unread_data['unread_count'];
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><i class="fas fa-bell mr-2"></i> Notifications</h2>
                    <?php if ($unread_count > 0): ?>
                        <a href="?mark_all_read=true" class="btn btn-sm btn-light">
                            <i class="fas fa-check-double mr-1"></i> Mark All as Read
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($notifications_result) > 0): ?>
                        <div class="list-group">
                            <?php while ($notification = mysqli_fetch_assoc($notifications_result)): ?>
                                <?php 
                                // Determine if this is a blood-related notification
                                $isBloodNotification = (
                                    strpos($notification['content'], 'blood') !== false || 
                                    strpos($notification['content'], 'donor') !== false ||
                                    strpos($notification['content'], 'donation') !== false
                                );
                                ?>
                                <div class="list-group-item list-group-item-action <?php echo $notification['is_read'] ? '' : 'bg-light'; ?> <?php echo $isBloodNotification ? 'border-danger' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <h6 class="mb-1">
                                            <?php if (!$notification['is_read']): ?>
                                                <span class="badge badge-danger mr-2">New</span>
                                            <?php endif; ?>
                                            <?php if ($isBloodNotification): ?>
                                                <i class="fas fa-tint text-danger mr-2"></i>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($notification['content']); ?>
                                        </h6>
                                        <div>
                                            <small class="text-muted mr-3">
                                                <?php 
                                                $notification_date = new DateTime($notification['notification_date']);
                                                $now = new DateTime();
                                                $interval = $notification_date->diff($now);
                                                
                                                if ($interval->y > 0) {
                                                    echo $interval->y . " year" . ($interval->y > 1 ? "s" : "") . " ago";
                                                } elseif ($interval->m > 0) {
                                                    echo $interval->m . " month" . ($interval->m > 1 ? "s" : "") . " ago";
                                                } elseif ($interval->d > 0) {
                                                    echo $interval->d . " day" . ($interval->d > 1 ? "s" : "") . " ago";
                                                } elseif ($interval->h > 0) {
                                                    echo $interval->h . " hour" . ($interval->h > 1 ? "s" : "") . " ago";
                                                } elseif ($interval->i > 0) {
                                                    echo $interval->i . " minute" . ($interval->i > 1 ? "s" : "") . " ago";
                                                } else {
                                                    echo "Just now";
                                                }
                                                ?>
                                            </small>
                                            <a href="?delete=<?php echo $notification['notification_id']; ?>" class="text-danger" onclick="return confirm('Are you sure you want to delete this notification?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <?php if ($isBloodNotification): ?>
                                        <div class="mt-2">
                                            <?php if (strpos($notification['content'], 'looking for blood') !== false || strpos($notification['content'], 'need blood') !== false): ?>
                                                <a href="blood_stock.php" class="btn btn-sm btn-outline-danger mr-2">Check Blood Stock</a>
                                                <a href="donor_list.php" class="btn btn-sm btn-outline-danger">View Donors</a>
                                            <?php elseif (strpos($notification['content'], 'available') !== false): ?>
                                                <a href="blood_request.php" class="btn btn-sm btn-outline-danger">Request Blood</a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-bell-slash text-muted display-4 mb-3"></i>
                            <h4>No Notifications</h4>
                            <p class="text-muted">You don't have any notifications at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>