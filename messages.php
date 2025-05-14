<?php
require_once '../includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark all notifications as read when user visits the messages page
$mark_read_query = "UPDATE notifications SET is_read = 1 
                  WHERE user_id = $user_id 
                  AND content LIKE '%message%'";
mysqli_query($conn, $mark_read_query);

// Handle AJAX refresh request
if (isset($_GET['refresh'])) {
    // Get all unique conversations
    $conversations_query = "SELECT 
                          IF(m.sender_id = $user_id, m.receiver_id, m.sender_id) as other_user_id,
                          MAX(m.message_date) as last_message_date,
                          COUNT(CASE WHEN m.is_read = 0 AND m.receiver_id = $user_id THEN 1 END) as unread_count
                          FROM messages m
                          WHERE m.sender_id = $user_id OR m.receiver_id = $user_id
                          GROUP BY IF(m.sender_id = $user_id, m.receiver_id, m.sender_id)
                          ORDER BY last_message_date DESC";
    $conversations_result = mysqli_query($conn, $conversations_query);
    
    $html = '';
    
    if (mysqli_num_rows($conversations_result) > 0) {
        while ($row = mysqli_fetch_assoc($conversations_result)) {
            $other_user_id = $row['other_user_id'];
            
            // Get user data
            $user_query = "SELECT user_id, full_name FROM users WHERE user_id = $other_user_id";
            $user_result = mysqli_query($conn, $user_query);
            
            if (mysqli_num_rows($user_result) == 0) {
                continue; // Skip if user not found
            }
            
            $user_data = mysqli_fetch_assoc($user_result);
            
            // Get last message
            $last_message_query = "SELECT content, message_date, sender_id, message_id, is_read
                                  FROM messages
                                  WHERE (sender_id = $user_id AND receiver_id = $other_user_id)
                                     OR (sender_id = $other_user_id AND receiver_id = $user_id)
                                  ORDER BY message_date DESC
                                  LIMIT 1";
            $last_message_result = mysqli_query($conn, $last_message_query);
            
            if (mysqli_num_rows($last_message_result) == 0) {
                continue; // Skip if no messages found
            }
            
            $last_message = mysqli_fetch_assoc($last_message_result);
            $is_sent_by_me = $last_message['sender_id'] == $user_id;
            $unread_class = ($row['unread_count'] > 0) ? 'bg-light' : '';
            
            // Format time display
            $message_date = new DateTime($row['last_message_date']);
            $now = new DateTime();
            $diff = $message_date->diff($now);
            
            if ($diff->days == 0) {
                $time_display = date('g:i A', strtotime($row['last_message_date']));
            } elseif ($diff->days == 1) {
                $time_display = 'Yesterday';
            } else {
                $time_display = date('M j', strtotime($row['last_message_date']));
            }
            
            // Create conversation list item
            $html .= '<a href="chat.php?user_id=' . $user_data['user_id'] . '" class="list-group-item list-group-item-action ' . $unread_class . '">';
            $html .= '<div class="d-flex w-100 justify-content-between align-items-center">';
            $html .= '<div>';
            $html .= '<h5 class="mb-1">' . htmlspecialchars($user_data['full_name']) . '</h5>';
            $html .= '<p class="mb-1 text-muted">';
            
            if ($is_sent_by_me) {
                $html .= '<span class="text-muted">You: </span>';
            }
            
            $html .= htmlspecialchars(substr($last_message['content'], 0, 50)) . (strlen($last_message['content']) > 50 ? '...' : '');
            $html .= '</p>';
            $html .= '</div>';
            $html .= '<div class="text-right">';
            $html .= '<small class="text-muted d-block">' . $time_display . '</small>';
            
            if ($row['unread_count'] > 0) {
                $html .= '<span class="badge badge-danger">' . $row['unread_count'] . '</span>';
            }
            
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</a>';
        }
    } else {
        $html = '<div class="text-center py-5">';
        $html .= '<i class="fas fa-comments text-muted fa-4x mb-3"></i>';
        $html .= '<h3 class="text-muted">No conversations yet</h3>';
        $html .= '<p>Start chatting with donors to arrange blood donations</p>';
        $html .= '<a href="donor_list.php" class="btn btn-danger mt-3">';
        $html .= '<i class="fas fa-search mr-2"></i> Find Donors';
        $html .= '</a>';
        $html .= '</div>';
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
    exit();
}

// Get all unique conversations for regular page load
$conversations_query = "SELECT 
                      IF(m.sender_id = $user_id, m.receiver_id, m.sender_id) as other_user_id,
                      MAX(m.message_date) as last_message_date,
                      COUNT(CASE WHEN m.is_read = 0 AND m.receiver_id = $user_id THEN 1 END) as unread_count
                      FROM messages m
                      WHERE m.sender_id = $user_id OR m.receiver_id = $user_id
                      GROUP BY IF(m.sender_id = $user_id, m.receiver_id, m.sender_id)
                      ORDER BY last_message_date DESC";
$conversations_result = mysqli_query($conn, $conversations_query);

// Get user information for each conversation
$conversations = [];
while ($row = mysqli_fetch_assoc($conversations_result)) {
    $other_user_id = $row['other_user_id'];
    $user_query = "SELECT user_id, full_name FROM users WHERE user_id = $other_user_id";
    $user_result = mysqli_query($conn, $user_query);
    
    if (mysqli_num_rows($user_result) == 0) {
        continue; // Skip if user not found
    }
    
    $user_data = mysqli_fetch_assoc($user_result);
    
    // Get last message
    $last_message_query = "SELECT content, message_date, sender_id, message_id, is_read
                          FROM messages
                          WHERE (sender_id = $user_id AND receiver_id = $other_user_id)
                             OR (sender_id = $other_user_id AND receiver_id = $user_id)
                          ORDER BY message_date DESC
                          LIMIT 1";
    $last_message_result = mysqli_query($conn, $last_message_query);
    
    if (mysqli_num_rows($last_message_result) == 0) {
        continue; // Skip if no messages found
    }
    
    $last_message = mysqli_fetch_assoc($last_message_result);
    
    $conversations[] = [
        'user_id' => $user_data['user_id'],
        'full_name' => $user_data['full_name'],
        'last_message' => $last_message['content'],
        'last_message_date' => $last_message['message_date'],
        'is_sent_by_me' => $last_message['sender_id'] == $user_id,
        'unread_count' => $row['unread_count'],
        'is_read' => $last_message['is_read']
    ];
}
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><i class="fas fa-comments mr-2"></i> Messages</h2>
                    <a href="donor_list.php" class="btn btn-light btn-sm">
                        <i class="fas fa-user-plus mr-1"></i> Find Donors
                    </a>
                </div>
                <div class="card-body">
                    <div id="conversation-list">
                        <?php if (!empty($conversations)): ?>
                            <div class="list-group">
                                <?php foreach ($conversations as $conversation): ?>
                                    <a href="chat.php?user_id=<?php echo $conversation['user_id']; ?>" class="list-group-item list-group-item-action <?php echo ($conversation['unread_count'] > 0) ? 'bg-light' : ''; ?>">
                                        <div class="d-flex w-100 justify-content-between align-items-center">
                                            <div>
                                                <h5 class="mb-1"><?php echo htmlspecialchars($conversation['full_name']); ?></h5>
                                                <p class="mb-1 text-muted">
                                                    <?php if ($conversation['is_sent_by_me']): ?>
                                                        <span class="text-muted">You: </span>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars(substr($conversation['last_message'], 0, 50)) . (strlen($conversation['last_message']) > 50 ? '...' : ''); ?>
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <small class="text-muted d-block">
                                                    <?php 
                                                    $message_date = new DateTime($conversation['last_message_date']);
                                                    $now = new DateTime();
                                                    $diff = $message_date->diff($now);
                                                    
                                                    if ($diff->days == 0) {
                                                        echo date('g:i A', strtotime($conversation['last_message_date']));
                                                    } elseif ($diff->days == 1) {
                                                        echo 'Yesterday';
                                                    } else {
                                                        echo date('M j', strtotime($conversation['last_message_date']));
                                                    }
                                                    ?>
                                                </small>
                                                <?php if ($conversation['unread_count'] > 0): ?>
                                                    <span class="badge badge-danger"><?php echo $conversation['unread_count']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-comments text-muted fa-4x mb-3"></i>
                                <h3 class="text-muted">No conversations yet</h3>
                                <p>Start chatting with donors to arrange blood donations</p>
                                <a href="donor_list.php" class="btn btn-danger mt-3">
                                    <i class="fas fa-search mr-2"></i> Find Donors
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Auto-refresh conversations
    function refreshConversations() {
        $.ajax({
            url: '<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?refresh=true',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    $('#conversation-list').html(data.html);
                }
            },
            error: function(xhr, status, error) {
                console.error("Error refreshing conversations:", error);
            }
        });
    }
    
    // Refresh every 10 seconds
    var refreshInterval = setInterval(refreshConversations, 10000);
    
    // Pause refresh when page is not visible
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            clearInterval(refreshInterval);
        } else {
            refreshInterval = setInterval(refreshConversations, 10000);
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>