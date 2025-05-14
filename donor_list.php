<?php
require_once '../includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle search and filter
$search_blood_group = isset($_GET['blood_group']) ? $_GET['blood_group'] : '';
$search_location = isset($_GET['location']) ? $_GET['location'] : '';

// Build the query
$donor_query = "SELECT dp.*, u.full_name, u.address, u.user_id, u.contact_number
                FROM donor_profiles dp 
                JOIN users u ON dp.user_id = u.user_id 
                JOIN user_roles ur ON dp.user_id = ur.user_id
                WHERE dp.is_available = 1 
                AND ur.is_donor = 1";

if (!empty($search_blood_group)) {
    $donor_query .= " AND dp.blood_group = '" . mysqli_real_escape_string($conn, $search_blood_group) . "'";
}

if (!empty($search_location)) {
    $donor_query .= " AND u.address LIKE '%" . mysqli_real_escape_string($conn, $search_location) . "%'";
}

$donor_query .= " ORDER BY dp.last_donation_date DESC";
$donor_result = mysqli_query($conn, $donor_query);

// Get all blood groups for the filter dropdown
$blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

// Handle blood request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['donor_id']) && isset($_POST['quantity'])) {
    $donor_id = mysqli_real_escape_string($conn, $_POST['donor_id']);
    $quantity = mysqli_real_escape_string($conn, $_POST['quantity']);
    $blood_group = mysqli_real_escape_string($conn, $_POST['blood_group']);
    
    // Validate quantity
    if (!is_numeric($quantity) || $quantity < 100 || $quantity > 500) {
        $_SESSION['error_message'] = "Quantity must be between 100ml and 500ml.";
        header("Location: donor_list.php");
        exit();
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Create a blood request
        $request_query = "INSERT INTO blood_requests (recipient_id, donor_id, blood_group, quantity_ml, status) 
                      VALUES ($user_id, $donor_id, '$blood_group', $quantity, 'pending')";
        
        if (!mysqli_query($conn, $request_query)) {
            throw new Exception(mysqli_error($conn));
        }
        
        $request_id = mysqli_insert_id($conn);
        
        // Create notification for the donor
        $recipient_query = "SELECT full_name FROM users WHERE user_id = $user_id";
        $recipient_result = mysqli_query($conn, $recipient_query);
        $requester_name = mysqli_fetch_assoc($recipient_result)['full_name'];
        
        $notification_content = "$requester_name has requested $quantity ml of your blood ($blood_group).";
        $notification_sql = "INSERT INTO notifications (user_id, content) VALUES ($donor_id, '$notification_content')";
        
        if (!mysqli_query($conn, $notification_sql)) {
            throw new Exception(mysqli_error($conn));
        }

        // Create notification for admin
        $admin_query = "SELECT user_id FROM users WHERE user_type = 'admin' LIMIT 1";
        $admin_result = mysqli_query($conn, $admin_query);
        
        if (mysqli_num_rows($admin_result) > 0) {
            $admin_id = mysqli_fetch_assoc($admin_result)['user_id'];
            $admin_notification = "$requester_name has requested $quantity ml of $blood_group blood from a donor.";
            $admin_notification_query = "INSERT INTO notifications (user_id, content) VALUES ($admin_id, '$admin_notification')";
            
            if (!mysqli_query($conn, $admin_notification_query)) {
                throw new Exception(mysqli_error($conn));
            }
        }
        
        // Update user role to be a recipient if not already
        $role_query = "SELECT * FROM user_roles WHERE user_id = $user_id";
        $role_result = mysqli_query($conn, $role_query);
        
        if (mysqli_num_rows($role_result) > 0) {
            $role = mysqli_fetch_assoc($role_result);
            if (!$role['is_recipient']) {
                $role_update = "UPDATE user_roles SET is_recipient = TRUE WHERE user_id = $user_id";
                
                if (!mysqli_query($conn, $role_update)) {
                    throw new Exception(mysqli_error($conn));
                }
            }
        } else {
            $role_insert = "INSERT INTO user_roles (user_id, is_donor, is_recipient) VALUES ($user_id, FALSE, TRUE)";
            
            if (!mysqli_query($conn, $role_insert)) {
                throw new Exception(mysqli_error($conn));
            }
        }
        
        // Check if recipient has a profile, create one if not
        $profile_check_query = "SELECT * FROM recipient_profiles WHERE user_id = $user_id";
        $profile_check_result = mysqli_query($conn, $profile_check_query);
        
        if (mysqli_num_rows($profile_check_result) == 0) {
            // Create recipient profile
            $profile_insert = "INSERT INTO recipient_profiles (user_id, blood_group, medical_history, preferred_hospital) 
                          VALUES ($user_id, '$blood_group', '', '')";
            
            if (!mysqli_query($conn, $profile_insert)) {
                throw new Exception(mysqli_error($conn));
            }
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Set session variable for success message
        $_SESSION['success_message'] = "Your blood request has been sent successfully! You'll be notified when it's approved.";
        
        // Redirect to prevent form resubmission
        header("Location: donor_list.php");
        exit();
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        
        $_SESSION['error_message'] = "Error submitting request: " . $e->getMessage();
        header("Location: donor_list.php");
        exit();
    }
}
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h2 class="mb-0"><i class="fas fa-users mr-2"></i> Available Donors</h2>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle mr-2"></i> <?php echo $_SESSION['success_message']; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $_SESSION['error_message']; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>
                    
                    <!-- Search and Filter Form -->
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="blood_group">Blood Group</label>
                                    <select class="form-control" id="blood_group" name="blood_group">
                                        <option value="">All Blood Groups</option>
                                        <?php foreach ($blood_groups as $group): ?>
                                            <option value="<?php echo $group; ?>" <?php if ($search_blood_group == $group) echo 'selected'; ?>>
                                                <?php echo $group; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="location">Location</label>
                                    <input type="text" class="form-control" id="location" name="location" placeholder="Enter city or address" value="<?php echo htmlspecialchars($search_location); ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-danger btn-block">
                                        <i class="fas fa-search mr-1"></i> Search
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Donors List -->
                    <div class="row">
                        <?php if (mysqli_num_rows($donor_result) > 0): ?>
                            <?php while ($donor = mysqli_fetch_assoc($donor_result)): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card donor-card h-100">
                                        <div class="card-header bg-light">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px;">
                                                    <span><?php echo strtoupper(substr($donor['full_name'], 0, 1)); ?></span>
                                                </div>
                                                <div>
                                                    <h5 class="mb-0"><?php echo htmlspecialchars($donor['full_name']); ?></h5>
                                                    <span class="badge badge-danger"><?php echo $donor['blood_group']; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <p><i class="fas fa-map-marker-alt text-danger mr-2"></i> <?php echo htmlspecialchars($donor['address']); ?></p>
                                            <p><i class="fas fa-phone text-danger mr-2"></i> <?php echo htmlspecialchars($donor['contact_number']); ?></p>
                                            <p><i class="fas fa-smoking-ban text-danger mr-2"></i> Smoker: <?php echo ucfirst($donor['smoker']); ?></p>
                                            <p><i class="fas fa-venus-mars text-danger mr-2"></i> Gender: <?php echo ucfirst($donor['gender']); ?></p>
                                            <?php if ($donor['last_donation_date']): ?>
                                                <p><i class="fas fa-calendar-alt text-danger mr-2"></i> Last Donation: <?php echo date('F j, Y', strtotime($donor['last_donation_date'])); ?></p>
                                            <?php else: ?>
                                                <p><i class="fas fa-calendar-alt text-danger mr-2"></i> No previous donations</p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-footer bg-white">
                                            <?php if ($donor['user_id'] != $user_id): ?>
                                                <div class="d-flex justify-content-between">
                                                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#requestModal<?php echo $donor['user_id']; ?>">
                                                        <i class="fas fa-tint mr-2"></i> Request Blood
                                                    </button>
                                                    <a href="chat.php?user_id=<?php echo $donor['user_id']; ?>" class="btn btn-outline-danger">
                                                        <i class="fas fa-comment-alt"></i> Chat
                                                    </a>
                                                </div>
                                                
                                                <!-- Request Modal -->
                                                <div class="modal fade" id="requestModal<?php echo $donor['user_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="requestModalLabel<?php echo $donor['user_id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-danger text-white">
                                                                <h5 class="modal-title" id="requestModalLabel<?php echo $donor['user_id']; ?>">Request Blood from <?php echo htmlspecialchars($donor['full_name']); ?></h5>
                                                                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="requestForm<?php echo $donor['user_id']; ?>">
                                                                <div class="modal-body">
                                                                <input type="hidden" name="donor_id" value="<?php echo $donor['user_id']; ?>">
                                                                    <input type="hidden" name="blood_group" value="<?php echo $donor['blood_group']; ?>">
                                                                    
                                                                    <div class="form-group">
                                                                        <label for="quantity<?php echo $donor['user_id']; ?>">Quantity (in ml)</label>
                                                                        <input type="number" class="form-control" id="quantity<?php echo $donor['user_id']; ?>" name="quantity" min="100" max="500" step="50" value="350" required>
                                                                        <small class="form-text text-muted">Standard blood donation is between 350-450 ml.</small>
                                                                    </div>
                                                                    
                                                                    <div class="form-group form-check">
                                                                        <input type="checkbox" class="form-check-input" id="confirm<?php echo $donor['user_id']; ?>" required>
                                                                        <label class="form-check-label" for="confirm<?php echo $donor['user_id']; ?>">
                                                                            I confirm that I have a valid medical reason to request blood.
                                                                        </label>
                                                                    </div>
                                                                    
                                                                    <div class="alert alert-info">
                                                                        <i class="fas fa-info-circle mr-2"></i> Your request will be sent to both the donor and the admin for approval.
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-danger">Send Request</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php elseif ($donor['user_id'] == $user_id): ?>
                                                <button class="btn btn-secondary btn-block" disabled>
                                                    <i class="fas fa-user mr-2"></i> This is You
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i> No donors found matching your criteria. Please try a different search.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Blood Compatibility Chart -->
            <div class="card mt-4">
                <div class="card-header bg-danger text-white">
                    <h3 class="mb-0">Blood Compatibility Chart</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Blood Type</th>
                                    <th>Can Donate To</th>
                                    <th>Can Receive From</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="badge badge-danger">A+</span></td>
                                    <td>A+, AB+</td>
                                    <td>A+, A-, O+, O-</td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-danger">A-</span></td>
                                    <td>A+, A-, AB+, AB-</td>
                                    <td>A-, O-</td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-danger">B+</span></td>
                                    <td>B+, AB+</td>
                                    <td>B+, B-, O+, O-</td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-danger">B-</span></td>
                                    <td>B+, B-, AB+, AB-</td>
                                    <td>B-, O-</td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-danger">AB+</span></td>
                                    <td>AB+</td>
                                    <td>All Blood Types</td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-danger">AB-</span></td>
                                    <td>AB+, AB-</td>
                                    <td>A-, B-, AB-, O-</td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-danger">O+</span></td>
                                    <td>A+, B+, AB+, O+</td>
                                    <td>O+, O-</td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-danger">O-</span></td>
                                    <td>All Blood Types</td>
                                    <td>O-</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fix the modal popup issue with a script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ensure only one modal shows at a time
    $('.modal').on('show.bs.modal', function () {
        $('.modal').not($(this)).modal('hide');
    });
    
    // Fix for repeated form submissions
    <?php if (mysqli_num_rows($donor_result) > 0): ?>
        <?php mysqli_data_seek($donor_result, 0); ?>
        <?php while ($donor = mysqli_fetch_assoc($donor_result)): ?>
            const form<?php echo $donor['user_id']; ?> = document.getElementById('requestForm<?php echo $donor['user_id']; ?>');
            if (form<?php echo $donor['user_id']; ?>) {
                form<?php echo $donor['user_id']; ?>.addEventListener('submit', function(e) {
                    // Check if the checkbox is checked
                    const checkbox = document.getElementById('confirm<?php echo $donor['user_id']; ?>');
                    if (!checkbox.checked) {
                        e.preventDefault();
                        alert('Please confirm that you have a valid medical reason for requesting blood.');
                        return false;
                    }
                    
                    // Disable the submit button after click to prevent double submission
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sending...';
                    }
                });
            }
        <?php endwhile; ?>
    <?php endif; ?>
});
</script>

<style>
/* Hover effect for donor cards */
.donor-card {
    transition: all 0.3s ease;
}
.donor-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}
</style>

<?php require_once '../includes/footer.php'; ?>