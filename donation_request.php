<?php
require_once '../includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

// Check if user is a donor
$is_donor = isUserDonor($user_id, $conn);
$has_donor_profile = hasUserDonorProfile($user_id, $conn);

if (!$is_donor || !$has_donor_profile) {
    $_SESSION['error_message'] = "You need to register as a donor first to access this page.";
    header("Location: donate_blood.php");
    exit();
}

// Get user's donor profile
$profile_query = "SELECT * FROM donor_profiles WHERE user_id = $user_id";
$profile_result = mysqli_query($conn, $profile_query);
$donor_profile = mysqli_fetch_assoc($profile_result);

// Calculate if donor is eligible to donate
$is_eligible = true;
$days_until_eligible = 0;

if (!empty($donor_profile['last_donation_date'])) {
    $last_donation = new DateTime($donor_profile['last_donation_date']);
    $now = new DateTime();
    $diff = $now->diff($last_donation);
    
    // Check if 56 days (8 weeks) have passed since the last donation
    if ($diff->days < 56) {
        $is_eligible = false;
        $days_until_eligible = 56 - $diff->days;
    }
}

// Process donation request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check eligibility
    if (!$is_eligible) {
        $errors[] = "You are not eligible to donate at this time. Please wait $days_until_eligible more days.";
    } else {
        $quantity = trim($_POST['quantity']);
        
        // Validate quantity
        if (!is_numeric($quantity) || $quantity < 300 || $quantity > 500) {
            $errors[] = "Quantity must be between 300ml and 500ml.";
        }
        
        // If no errors, submit request
        if (empty($errors)) {
            $blood_group = $donor_profile['blood_group'];
            
            // Insert donation request
            $query = "INSERT INTO donation_requests (user_id, blood_group, quantity_ml, status) 
                      VALUES ($user_id, '$blood_group', $quantity, 'pending')";
            
            if (mysqli_query($conn, $query)) {
                // Create notification for admin
                $admin_query = "SELECT user_id FROM users WHERE user_type = 'admin' LIMIT 1";
                $admin_result = mysqli_query($conn, $admin_query);
                
                if (mysqli_num_rows($admin_result) > 0) {
                    $admin_id = mysqli_fetch_assoc($admin_result)['user_id'];
                    
                    $notification_content = "New blood donation request received: {$_SESSION['full_name']} wants to donate {$quantity}ml of {$blood_group} blood.";
                    $notification_query = "INSERT INTO notifications (user_id, content) VALUES ($admin_id, '$notification_content')";
                    mysqli_query($conn, $notification_query);
                }
                
                // Create notification for the donor
                $notification_content = "Your blood donation request has been submitted. You will be notified when it is approved.";
                $notification_query = "INSERT INTO notifications (user_id, content) VALUES ($user_id, '$notification_content')";
                mysqli_query($conn, $notification_query);
                
                $success_message = "Your donation request has been submitted successfully! You will be notified when it is approved.";
            } else {
                $errors[] = "Error submitting donation request: " . mysqli_error($conn);
            }
        }
    }
}

// Get pending donation requests
$pending_query = "SELECT * FROM donation_requests WHERE user_id = $user_id AND status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$has_pending_request = mysqli_num_rows($pending_result) > 0;
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h2 class="mb-0"><i class="fas fa-hand-holding-heart mr-2"></i> Blood Donation Request</h2>
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
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle mr-2"></i> <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5 class="card-title text-danger">Your Blood Group</h5>
                                    <h2 class="display-4 text-danger"><?php echo $donor_profile['blood_group']; ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5 class="card-title text-danger">Last Donation</h5>
                                    <?php if (!empty($donor_profile['last_donation_date'])): ?>
                                        <h4><?php echo date('F j, Y', strtotime($donor_profile['last_donation_date'])); ?></h4>
                                    <?php else: ?>
                                        <h4>No previous donations</h4>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5 class="card-title text-danger">Donation Status</h5>
                                    <?php if ($is_eligible): ?>
                                        <h4 class="text-success">Eligible to Donate</h4>
                                    <?php else: ?>
                                        <h4 class="text-warning">Not Eligible</h4>
                                        <small class="text-muted">Please wait <?php echo $days_until_eligible; ?> more days</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($has_pending_request): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-circle mr-2"></i> You already have a pending donation request. Please wait for it to be processed.
                        </div>
                        <?php elseif ($is_eligible): ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="form-group">
                                <label for="quantity">Blood Quantity (ml)</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" min="300" max="500" value="350" required>
                                <small class="form-text text-muted">Standard blood donation is between 350-450 ml. Maximum allowed is 500 ml.</small>
                            </div>
                            
                            <div class="form-group form-check">
                                <input type="checkbox" class="form-check-input" id="confirm" required>
                                <label class="form-check-label" for="confirm">
                                    I confirm that I am in good health and meet the eligibility criteria for blood donation.
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-danger btn-block">
                                <i class="fas fa-paper-plane mr-2"></i> Submit Donation Request
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i> You are not eligible to donate blood at this time. Please wait <?php echo $days_until_eligible; ?> more days after your last donation.
                            
                            <div class="mt-3">
                                <p><strong>Next eligible date:</strong> <?php echo date('F j, Y', strtotime($donor_profile['last_donation_date'] . ' + 56 days')); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Donation Guidelines -->
                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Blood Donation Guidelines</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-danger">Before Donation</h6>
                                    <ul>
                                        <li>Get a good night's sleep</li>
                                        <li>Have a healthy meal before donating</li>
                                        <li>Drink plenty of water</li>
                                        <li>Avoid fatty foods before donation</li>
                                        <li>Bring ID and donor card if you have one</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-danger">After Donation</h6>
                                    <ul>
                                        <li>Rest for at least 10-15 minutes</li>
                                        <li>Drink extra fluids</li>
                                        <li>Avoid strenuous physical activity for 24 hours</li>
                                        <li>Keep the bandage on for at least 4 hours</li>
                                        <li>Eat a healthy meal</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Donation History -->
                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Your Recent Donation Requests</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            // Get donation requests for this user
                            $history_query = "SELECT * FROM donation_requests WHERE user_id = $user_id ORDER BY request_date DESC LIMIT 5";
                            $history_result = mysqli_query($conn, $history_query);
                            
                            if (mysqli_num_rows($history_result) > 0):
                            ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Request Date</th>
                                                <th>Blood Group</th>
                                                <th>Quantity</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($request = mysqli_fetch_assoc($history_result)): ?>
                                                <tr>
                                                    <td><?php echo date('M j, Y', strtotime($request['request_date'])); ?></td>
                                                    <td><span class="badge badge-danger"><?php echo $request['blood_group']; ?></span></td>
                                                    <td><?php echo $request['quantity_ml']; ?> ml</td>
                                                    <td>
                                                        <?php if ($request['status'] == 'pending'): ?>
                                                            <span class="badge badge-warning">Pending</span>
                                                        <?php elseif ($request['status'] == 'approved'): ?>
                                                            <span class="badge badge-success">Approved</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-danger">Rejected</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i> You haven't made any donation requests yet.
                                </div>
                            <?php endif; ?>
                            
                            <div class="text-center mt-3">
                                <a href="donation_history.php" class="btn btn-outline-danger">
                                    <i class="fas fa-history mr-1"></i> View Complete History
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>