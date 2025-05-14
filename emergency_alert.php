<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

// Handle dismissal of emergency alerts
if (isset($_GET['dismiss_alert']) && is_numeric($_GET['dismiss_alert'])) {
    $alert_id = intval($_GET['dismiss_alert']);
    
    // Create dismissed_alerts table if it doesn't exist
    $check_table_query = "SHOW TABLES LIKE 'dismissed_alerts'";
    $table_exists = mysqli_query($conn, $check_table_query);
    
    if (mysqli_num_rows($table_exists) == 0) {
        $create_table_query = "CREATE TABLE IF NOT EXISTS dismissed_alerts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            alert_id INT NOT NULL,
            dismissal_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            UNIQUE KEY (user_id, alert_id)
        )";
        mysqli_query($conn, $create_table_query);
    }
    
    // Add the dismissed alert to user's dismissed alerts
    $check_query = "SELECT * FROM dismissed_alerts WHERE user_id = $user_id AND alert_id = $alert_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) == 0) {
        $insert_query = "INSERT INTO dismissed_alerts (user_id, alert_id) VALUES ($user_id, $alert_id)";
        if (mysqli_query($conn, $insert_query)) {
            $_SESSION['success_message'] = "Emergency alert dismissed successfully.";
        } else {
            $_SESSION['error_message'] = "Error dismissing alert: " . mysqli_error($conn);
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['HTTP_REFERER'] ?? 'dashboard.php');
    exit();
}

// Process emergency response (chat with the user who posted the emergency)
if (isset($_GET['respond_to']) && is_numeric($_GET['respond_to'])) {
    $emergency_user_id = intval($_GET['respond_to']);
    $emergency_id = isset($_GET['emergency_id']) ? intval($_GET['emergency_id']) : 0;
    
    // Dismiss the alert first
    if ($emergency_id > 0) {
        $check_query = "SELECT * FROM dismissed_alerts WHERE user_id = $user_id AND alert_id = $emergency_id";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) == 0) {
            $insert_query = "INSERT INTO dismissed_alerts (user_id, alert_id) VALUES ($user_id, $emergency_id)";
            mysqli_query($conn, $insert_query);
        }
    }
    
    // Redirect to chat with the user
    header("Location: chat.php?user_id=$emergency_user_id");
    exit();
}

// Process emergency alert submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $blood_group = $_POST['blood_group'];
    $location = trim($_POST['location']);
    $contact = trim($_POST['contact']);
    $emergency_details = trim($_POST['emergency_details']);
    
    // Basic validation
    if (empty($blood_group)) {
        $errors[] = "Blood group is required";
    }
    
    if (empty($location)) {
        $errors[] = "Location is required";
    }
    
    if (empty($contact)) {
        $errors[] = "Contact number is required";
    }
    
    if (empty($emergency_details)) {
        $errors[] = "Emergency details are required";
    }
    
    // If no errors, proceed
    if (empty($errors)) {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Create a post with the emergency alert
            $post_content = "[EMERGENCY BLOOD ALERT] $blood_group blood needed at $location. Contact: $contact. Details: $emergency_details";
            $post_query = "INSERT INTO posts (user_id, content) VALUES ($user_id, '$post_content')";
            
            if (!mysqli_query($conn, $post_query)) {
                throw new Exception(mysqli_error($conn));
            }
            
            $post_id = mysqli_insert_id($conn);
            
            // Get user information
            $user_query = "SELECT full_name FROM users WHERE user_id = $user_id";
            $user_result = mysqli_query($conn, $user_query);
            $user_data = mysqli_fetch_assoc($user_result);
            $requester_name = $user_data['full_name'];
            
            // Update emergency_logs table to include post_id if it doesn't exist
            $check_column_query = "SHOW COLUMNS FROM emergency_logs LIKE 'post_id'";
            $column_result = mysqli_query($conn, $check_column_query);
            if (mysqli_num_rows($column_result) == 0) {
                $alter_table_query = "ALTER TABLE emergency_logs ADD COLUMN post_id INT NULL, ADD FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE SET NULL";
                mysqli_query($conn, $alter_table_query);
            }
            
            // Create emergency log
            $log_query = "INSERT INTO emergency_logs (user_id, blood_group, location, contact, details, emergency_date, post_id) 
                         VALUES ($user_id, '$blood_group', '$location', '$contact', '$emergency_details', CURRENT_TIMESTAMP, $post_id)";
            if (!mysqli_query($conn, $log_query)) {
                throw new Exception(mysqli_error($conn));
            }
            
            $emergency_id = mysqli_insert_id($conn);
            
            // Find compatible donors
            $compatible_groups = [];
            switch ($blood_group) {
                case 'A+':
                    $compatible_groups = ['A+', 'A-', 'O+', 'O-'];
                    break;
                case 'A-':
                    $compatible_groups = ['A-', 'O-'];
                    break;
                case 'B+':
                    $compatible_groups = ['B+', 'B-', 'O+', 'O-'];
                    break;
                case 'B-':
                    $compatible_groups = ['B-', 'O-'];
                    break;
                case 'AB+':
                    $compatible_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                    break;
                case 'AB-':
                    $compatible_groups = ['A-', 'B-', 'AB-', 'O-'];
                    break;
                case 'O+':
                    $compatible_groups = ['O+', 'O-'];
                    break;
                case 'O-':
                    $compatible_groups = ['O-'];
                    break;
            }
            
            if (!empty($compatible_groups)) {
                $compatible_groups_str = "'" . implode("','", $compatible_groups) . "'";
                $donor_query = "SELECT dp.user_id 
                               FROM donor_profiles dp 
                               JOIN user_roles ur ON dp.user_id = ur.user_id
                               WHERE dp.is_available = 1 
                               AND ur.is_donor = 1
                               AND dp.blood_group IN ($compatible_groups_str)";
                $donor_result = mysqli_query($conn, $donor_query);
                
                // Update notifications table to include emergency_id if it doesn't exist
                $check_column_query = "SHOW COLUMNS FROM notifications LIKE 'emergency_id'";
                $column_result = mysqli_query($conn, $check_column_query);
                if (mysqli_num_rows($column_result) == 0) {
                    $alter_table_query = "ALTER TABLE notifications ADD COLUMN emergency_id INT NULL";
                    mysqli_query($conn, $alter_table_query);
                }
                
                // Notify all compatible donors
                while ($donor = mysqli_fetch_assoc($donor_result)) {
                    $donor_notification = "[EMERGENCY] $requester_name needs $blood_group blood urgently at $location. Please contact: $contact if you can help.";
                    $donor_notification_query = "INSERT INTO notifications (user_id, content, emergency_id) VALUES ({$donor['user_id']}, '$donor_notification', $emergency_id)";
                    mysqli_query($conn, $donor_notification_query);
                }
            }
            
            // Notify admin
            $admin_query = "SELECT user_id FROM users WHERE user_type = 'admin' LIMIT 1";
            $admin_result = mysqli_query($conn, $admin_query);
            if (mysqli_num_rows($admin_result) > 0) {
                $admin_id = mysqli_fetch_assoc($admin_result)['user_id'];
                $admin_notification = "[EMERGENCY] $requester_name has posted an emergency blood request for $blood_group at $location.";
                $admin_notification_query = "INSERT INTO notifications (user_id, content, emergency_id) VALUES ($admin_id, '$admin_notification', $emergency_id)";
                mysqli_query($conn, $admin_notification_query);
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            $success_message = "Emergency alert has been posted successfully! All compatible donors have been notified.";
            
            // Redirect to avoid form resubmission
            header("Location: emergency_alert.php?alert_sent=true");
            exit();
        } catch (Exception $e) {
            // Rollback transaction
            mysqli_rollback($conn);
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}

// Check if alert was just sent (prevents popup issue)
$alert_sent = isset($_GET['alert_sent']) && $_GET['alert_sent'] == 'true';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h2 class="mb-0"><i class="fas fa-exclamation-triangle mr-2"></i> Emergency Blood Alert</h2>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle mr-2"></i> Use this form ONLY for emergency blood requirements. This will send immediate notifications to all compatible donors.
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($alert_sent): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle mr-2"></i> Emergency alert has been posted successfully! All compatible donors have been notified.
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        
                        <script>
                            // Auto-dismiss success message after 10 seconds
                            setTimeout(function() {
                                $('.alert-success').alert('close');
                            }, 10000);
                        </script>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" onsubmit="return validateForm('emergencyForm')" id="emergencyForm">
                        <div class="form-group">
                            <label for="blood_group">Blood Group Needed*</label>
                            <select class="form-control" id="blood_group" name="blood_group" required>
                                <option value="">Select Blood Group</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="location">Location/Hospital*</label>
                            <input type="text" class="form-control" id="location" name="location" placeholder="Enter hospital or location details" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="contact">Contact Number*</label>
                            <input type="text" class="form-control" id="contact" name="contact" placeholder="Enter contact number for immediate response" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="emergency_details">Emergency Details*</label>
                            <textarea class="form-control" id="emergency_details" name="emergency_details" rows="3" placeholder="Describe the emergency situation and any additional details that might help" required></textarea>
                        </div>
                        
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="confirm" required>
                            <label class="form-check-label" for="confirm">
                                I confirm this is a genuine emergency
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-danger btn-lg btn-block">
                            <i class="fas fa-bell mr-2"></i> Send Emergency Alert
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-body text-center">
                    <h5>For Immediate Assistance</h5>
                    <p>Call our 24/7 Emergency Helpline:</p>
                    <a href="tel:+8801234567890" class="btn btn-outline-danger btn-lg">
                        <i class="fas fa-phone-alt mr-2"></i> +880 1234-567890
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom JavaScript for confirmation dialog -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if the URL has alert_sent parameter
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('alert_sent')) {
        // Remove the parameter from URL without refreshing the page to prevent duplicate alerts
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);
    }
    
    // Form validation
    window.validateForm = function(formId) {
        const form = document.getElementById(formId);
        if (!form.checkValidity()) {
            form.reportValidity();
            return false;
        }
        
        return confirm('Are you sure you want to send this emergency alert to all compatible donors?');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>