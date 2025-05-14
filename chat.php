<?php
require_once '../includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sender_id = $user_id;
$errors = [];
$success_message = '';

// Check if the recipient user_id is provided
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$recipient_id = intval($_GET['user_id']);

// Check if recipient exists
$recipient_query = "SELECT * FROM users WHERE user_id = $recipient_id";
$recipient_result = mysqli_query($conn, $recipient_query);

if (mysqli_num_rows($recipient_result) == 0) {
    header("Location: dashboard.php");
    exit();
}

$recipient = mysqli_fetch_assoc($recipient_result);

// Process message submission via AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message']) && isset($_POST['ajax'])) {
    $message_content = trim($_POST['message']);
    
    if (empty($message_content)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => "Message cannot be empty"
        ]);
        exit();
    }
    
    // Sanitize message content
    $message_content = mysqli_real_escape_string($conn, $message_content);
    
    $message_query = "INSERT INTO messages (sender_id, receiver_id, content) 
                     VALUES ($sender_id, $recipient_id, '$message_content')";
    
    if (mysqli_query($conn, $message_query)) {
        $last_message_id = mysqli_insert_id($conn);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message_id' => $last_message_id,
            'time' => date('M j, Y g:i A')
        ]);
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => "Error sending message: " . mysqli_error($conn)
        ]);
        exit();
    }
}
// Process regular form submission (non-AJAX)
else if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $message_content = trim($_POST['message']);
    
    if (empty($message_content)) {
        $errors[] = "Message cannot be empty";
    }
    
    // If no errors, send the message
    if (empty($errors)) {
        // Sanitize message content
        $message_content = mysqli_real_escape_string($conn, $message_content);
        
        $message_query = "INSERT INTO messages (sender_id, receiver_id, content) 
                         VALUES ($sender_id, $recipient_id, '$message_content')";
        
        if (mysqli_query($conn, $message_query)) {
            $success_message = "Message sent successfully!";
        } else {
            $errors[] = "Error sending message: " . mysqli_error($conn);
        }
    }
}

// AJAX endpoint to fetch new messages
if (isset($_GET['fetch_messages']) && $_GET['fetch_messages'] == 'true') {
    // Get the last message ID the client has
    $last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
    
    // Get conversation history with this user, only new messages
    $conversation_query = "SELECT m.*, 
                          u1.full_name as sender_name,
                          u2.full_name as receiver_name
                          FROM messages m
                          JOIN users u1 ON m.sender_id = u1.user_id
                          JOIN users u2 ON m.receiver_id = u2.user_id
                          WHERE ((m.sender_id = $user_id AND m.receiver_id = $recipient_id)
                             OR (m.sender_id = $recipient_id AND m.receiver_id = $user_id))
                          AND m.message_id > $last_id
                          ORDER BY m.message_date ASC";
    $conversation_result = mysqli_query($conn, $conversation_query);
    
    $new_messages = [];
    $last_message_id = $last_id;
    
    while ($message = mysqli_fetch_assoc($conversation_result)) {
        $new_messages[] = [
            'message_id' => $message['message_id'],
            'sender_id' => $message['sender_id'],
            'content' => htmlspecialchars($message['content']),
            'date' => date('M j, Y g:i A', strtotime($message['message_date'])),
            'is_sender' => $message['sender_id'] == $user_id
        ];
        $last_message_id = max($last_message_id, $message['message_id']);
    }
    
    // Mark all messages from this user as read
    $mark_read_query = "UPDATE messages SET is_read = TRUE 
                      WHERE sender_id = $recipient_id AND receiver_id = $user_id AND is_read = FALSE";
    mysqli_query($conn, $mark_read_query);
    
    header('Content-Type: application/json');
    echo json_encode([
        'messages' => $new_messages,
        'last_id' => $last_message_id
    ]);
    exit();
}

// Get conversation history with this user
$conversation_query = "SELECT m.*, 
                      u1.full_name as sender_name,
                      u2.full_name as receiver_name
                      FROM messages m
                      JOIN users u1 ON m.sender_id = u1.user_id
                      JOIN users u2 ON m.receiver_id = u2.user_id
                      WHERE (m.sender_id = $user_id AND m.receiver_id = $recipient_id)
                         OR (m.sender_id = $recipient_id AND m.receiver_id = $user_id)
                      ORDER BY m.message_date ASC";
$conversation_result = mysqli_query($conn, $conversation_query);

// Store the latest message ID for polling
$last_message_id = 0;

// Mark all messages from this user as read
$mark_read_query = "UPDATE messages SET is_read = TRUE 
                  WHERE sender_id = $recipient_id AND receiver_id = $user_id AND is_read = FALSE";
mysqli_query($conn, $mark_read_query);

// Get user profile info
$profile_query = "SELECT * FROM users WHERE user_id = $recipient_id";
$profile_result = mysqli_query($conn, $profile_query);
$profile_data = mysqli_fetch_assoc($profile_result);

// Get donor profile if exists
$donor_profile = null;
$is_donor = false;
$donor_query = "SELECT dp.*, ur.is_donor FROM donor_profiles dp 
               LEFT JOIN user_roles ur ON dp.user_id = ur.user_id
               WHERE dp.user_id = $recipient_id";
$donor_result = mysqli_query($conn, $donor_query);

if (mysqli_num_rows($donor_result) > 0) {
    $donor_profile = mysqli_fetch_assoc($donor_result);
    $is_donor = (bool)$donor_profile['is_donor'];
}
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h2 class="mb-0">
                        <a href="messages.php" class="text-white mr-2"><i class="fas fa-arrow-left"></i></a>
                        Chat with <?php echo htmlspecialchars($recipient['full_name']); ?>
                    </h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle mr-2"></i> <?php echo $success_message; ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <!-- User profile card -->
                            <?php if ($is_donor && $donor_profile): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-2 text-center">
                                                <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center mx-auto" style="width: 60px; height: 60px; font-size: 1.5rem;">
                                                    <span><?php echo strtoupper(substr($recipient['full_name'], 0, 1)); ?></span>
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <h5><?php echo htmlspecialchars($recipient['full_name']); ?></h5>
                                                <p class="mb-1">
                                                    <span class="badge badge-danger"><?php echo $donor_profile['blood_group']; ?></span>
                                                    <?php if ($donor_profile['is_available']): ?>
                                                        <span class="badge badge-success">Available</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary">Unavailable</span>
                                                    <?php endif; ?>
                                                </p>
                                                <p class="small text-muted mb-0">
                                                    <?php echo ucfirst($donor_profile['gender']); ?> | 
                                                    Smoker: <?php echo ucfirst($donor_profile['smoker']); ?>
                                                </p>
                                            </div>
                                            <div class="col-md-5">
                                                <?php if ($donor_profile['is_available']): ?>
                                                    <a href="blood_request.php?donor_id=<?php echo $recipient_id; ?>" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-tint mr-1"></i> Request Blood
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Chat History -->
                    <div id="chatHistory" class="chat-history p-3 mb-4" style="height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9;">
                        <?php if (mysqli_num_rows($conversation_result) > 0): ?>
                            <?php while ($message = mysqli_fetch_assoc($conversation_result)): ?>
                                <?php 
                                $is_sender = $message['sender_id'] == $user_id;
                                $message_class = $is_sender ? 'bg-primary text-white ml-auto' : 'bg-light mr-auto';
                                $alignment = $is_sender ? 'ml-auto' : '';
                                $text_color = $is_sender ? 'text-white-50' : 'text-muted';
                                
                                // Update last message ID for AJAX polling
                                $last_message_id = max($last_message_id, $message['message_id']);
                                ?>
                                <div class="row mb-3 message-row" data-message-id="<?php echo $message['message_id']; ?>">
                                    <div class="col-md-8 <?php echo $alignment; ?>">
                                        <div class="card <?php echo $message_class; ?>" style="border-radius: 15px;">
                                            <div class="card-body py-2 px-3">
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($message['content'])); ?></p>
                                                <small class="<?php echo $text_color; ?> d-block mt-1">
                                                    <?php echo date('M j, Y g:i A', strtotime($message['message_date'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-5 text-muted" id="noMessagesPlaceholder">
                                <i class="fas fa-comments fa-3x mb-3"></i>
                                <p>No messages yet. Start the conversation!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Message Form -->
                    <form id="messageForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?user_id=" . $recipient_id); ?>">
                        <div class="form-group">
                            <textarea class="form-control" id="message" name="message" rows="3" placeholder="Type your message here..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-paper-plane mr-2"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header bg-danger text-white">
                    <h3 class="mb-0">Blood Donation Instructions</h3>
                </div>
                <div class="card-body">
                    <p>If you have arranged for a direct blood donation with this person, please follow these steps:</p>
                    <ol>
                        <li>Confirm the location and time for the donation</li>
                        <li>Make sure both parties bring valid ID</li>
                        <li>The donor should be well-hydrated and have eaten before donating</li>
                        <li>Follow all safety protocols at the donation center</li>
                        <li>After successful donation, please inform the blood bank for record-keeping purposes</li>
                    </ol>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i> For your safety, we recommend meeting at an official blood donation center or hospital.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include the fixed chat.js file -->
<script src="../js/chat.js"></script>

<?php require_once '../includes/footer.php'; ?>