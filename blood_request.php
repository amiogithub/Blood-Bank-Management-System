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

// Check if user is a recipient
$is_recipient = isUserRecipient($user_id, $conn);

if (!$is_recipient) {
    $_SESSION['error_message'] = "You need to register as a recipient first to access this page.";
    header("Location: looking_for_blood.php");
    exit();
}

// Get user's recipient profile and blood type
$profile_query = "SELECT * FROM recipient_profiles WHERE user_id = $user_id";
$profile_result = mysqli_query($conn, $profile_query);

if (mysqli_num_rows($profile_result) == 0) {
    $_SESSION['error_message'] = "You need to complete your recipient profile first.";
    header("Location: looking_for_blood.php");
    exit();
}

$recipient_profile = mysqli_fetch_assoc($profile_result);
$blood_group = $recipient_profile['blood_group'];

// Process blood request form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $quantity = trim($_POST['quantity']);
    $request_blood_group = $_POST['blood_group'];
    $request_type = $_POST['request_type']; // From bank or from donor
    $donor_id = null;
    
    // Validation
    if (!is_numeric($quantity) || $quantity < 100 || $quantity > 1000) {
        $errors[] = "Quantity must be between 100ml and 1000ml.";
    }
    
    if (empty($request_blood_group)) {
        $errors[] = "Blood group is required.";
    }
    
    // If the request is from a specific donor, validate donor
    if ($request_type == 'from_donor' && isset($_POST['donor_id'])) {
        $donor_id = $_POST['donor_id'];
        
        // Check if donor exists and is available
        $donor_query = "SELECT dp.*, u.full_name
                        FROM donor_profiles dp
                        JOIN users u ON dp.user_id = u.user_id
                        JOIN user_roles ur ON dp.user_id = ur.user_id
                        WHERE dp.user_id = $donor_id 
                        AND dp.is_available = 1 
                        AND ur.is_donor = 1";
        $donor_result = mysqli_query($conn, $donor_query);
        
        if (mysqli_num_rows($donor_result) == 0) {
            $errors[] = "Selected donor is not available.";
        } else {
            $donor = mysqli_fetch_assoc($donor_result);
            
            // Check if donor's blood is compatible
            if (!areBloodTypesCompatible($donor['blood_group'], $request_blood_group)) {
                $errors[] = "Selected donor's blood type ({$donor['blood_group']}) is not compatible with the requested blood type ($request_blood_group).";
            }
        }
    }
    
    // If no errors, submit request
    if (empty($errors)) {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert blood request
            $query = "INSERT INTO blood_requests (recipient_id, donor_id, blood_group, quantity_ml, status) 
                     VALUES ($user_id, " . ($donor_id ? $donor_id : "NULL") . ", '$request_blood_group', $quantity, 'pending')";
            
            if (!mysqli_query($conn, $query)) {
                throw new Exception(mysqli_error($conn));
            }
            
            $request_id = mysqli_insert_id($conn);
            
            // Create notification for admin
            $admin_query = "SELECT user_id FROM users WHERE user_type = 'admin' LIMIT 1";
            $admin_result = mysqli_query($conn, $admin_query);
            
            if (mysqli_num_rows($admin_result) > 0) {
                $admin_id = mysqli_fetch_assoc($admin_result)['user_id'];
                
                // Get user name
                $user_query = "SELECT full_name FROM users WHERE user_id = $user_id";
                $user_result = mysqli_query($conn, $user_query);
                $user_name = mysqli_fetch_assoc($user_result)['full_name'];
                
                $notification_content = "New blood request: $user_name needs {$quantity}ml of $request_blood_group blood.";
                $notification_query = "INSERT INTO notifications (user_id, content) VALUES ($admin_id, '$notification_content')";
                
                if (!mysqli_query($conn, $notification_query)) {
                    throw new Exception(mysqli_error($conn));
                }
            }
            
            // Create notification for recipient
            $notification_content = "Your blood request has been submitted successfully. Request ID: #$request_id. You will be notified when it is approved.";
            $notification_query = "INSERT INTO notifications (user_id, content) VALUES ($user_id, '$notification_content')";
            
            if (!mysqli_query($conn, $notification_query)) {
                throw new Exception(mysqli_error($conn));
            }
            
            // Commit the transaction
            mysqli_commit($conn);
            
            // Set success message and redirect
            $_SESSION['blood_request_success'] = "Blood request has been sent to admin for approval.";
            header("Location: blood_request.php");
            exit();
        } catch (Exception $e) {
            // Rollback the transaction if any query fails
            mysqli_rollback($conn);
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}

// Get blood stock data
$stock_query = "SELECT * FROM blood_stock ORDER BY blood_group";
$stock_result = mysqli_query($conn, $stock_query);

// Get available donors
$donors_query = "SELECT dp.*, u.full_name, u.user_id
                FROM donor_profiles dp
                JOIN users u ON dp.user_id = u.user_id
                JOIN user_roles ur ON dp.user_id = ur.user_id
                WHERE dp.is_available = 1 
                AND ur.is_donor = 1
                ORDER BY dp.last_donation_date DESC";
$donors_result = mysqli_query($conn, $donors_query);

// Get recent blood requests
$requests_query = "SELECT br.*, u.full_name as donor_name
                  FROM blood_requests br
                  LEFT JOIN users u ON br.donor_id = u.user_id
                  WHERE br.recipient_id = $user_id
                  ORDER BY br.request_date DESC
                  LIMIT 5";
$requests_result = mysqli_query($conn, $requests_query);

// Check for session messages
if (isset($_SESSION['blood_request_success'])) {
    $success_message = $_SESSION['blood_request_success'];
    unset($_SESSION['blood_request_success']);
}
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h2 class="mb-0"><i class="fas fa-tint mr-2"></i> Blood Request</h2>
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
                                    <h2 class="display-4 text-danger"><?php echo $blood_group; ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title text-danger">Compatible Blood Types</h5>
                                    <div class="mt-2">
                                        <?php
                                        $compatibility_map = [
                                            'A+' => ['A+', 'A-', 'O+', 'O-'],
                                            'A-' => ['A-', 'O-'],
                                            'B+' => ['B+', 'B-', 'O+', 'O-'],
                                            'B-' => ['B-', 'O-'],
                                            'AB+' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
                                            'AB-' => ['A-', 'B-', 'AB-', 'O-'],
                                            'O+' => ['O+', 'O-'],
                                            'O-' => ['O-']
                                        ];
                                        
                                        $compatible_types = isset($compatibility_map[$blood_group]) ? $compatibility_map[$blood_group] : [];
                                        
                                        foreach ($compatible_types as $type) {
                                            echo '<span class="badge badge-danger mr-2 p-2">' . $type . '</span>';
                                        }
                                        ?>
                                    </div>
                                    <p class="mt-3 small text-muted">These are the blood types that are compatible with your blood type. You can receive blood from any of these types.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <ul class="nav nav-tabs mb-4" id="requestTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="bank-tab" data-toggle="tab" href="#bankRequest" role="tab" aria-controls="bankRequest" aria-selected="true">
                                <i class="fas fa-hospital-alt mr-1"></i> Request from Bank
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="donor-tab" data-toggle="tab" href="#donorRequest" role="tab" aria-controls="donorRequest" aria-selected="false">
                                <i class="fas fa-user-friends mr-1"></i> Request from Donor
                            </a>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="requestTabContent">
                        <!-- Request from Bank -->
                        <div class="tab-pane fade show active" id="bankRequest" role="tabpanel" aria-labelledby="bank-tab">
                            <h4 class="mb-3">Request Blood from Bank</h4>
                            
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                <input type="hidden" name="request_type" value="from_bank">
                                
                                <div class="form-group">
                                    <label for="bank_blood_group">Blood Group</label>
                                    <select class="form-control" id="bank_blood_group" name="blood_group" required>
                                        <option value="">Select Blood Group</option>
                                        <?php
                                        mysqli_data_seek($stock_result, 0);
                                        while ($stock = mysqli_fetch_assoc($stock_result)) {
                                            $is_compatible = in_array($stock['blood_group'], $compatible_types);
                                            if ($is_compatible && $stock['quantity_ml'] > 0) {
                                                echo '<option value="' . $stock['blood_group'] . '">' . $stock['blood_group'] . ' (' . $stock['quantity_ml'] . 'ml available)</option>';
                                            } elseif ($is_compatible) {
                                                echo '<option value="' . $stock['blood_group'] . '" disabled>' . $stock['blood_group'] . ' (Not available)</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="bank_quantity">Quantity (ml)</label>
                                    <input type="number" class="form-control" id="bank_quantity" name="quantity" min="100" max="1000" value="350" required>
                                    <small class="form-text text-muted">Standard blood request is between 350-450 ml. Maximum allowed is 1000 ml.</small>
                                </div>
                                
                                <div class="form-group form-check">
                                    <input type="checkbox" class="form-check-input" id="bank_confirm" required>
                                    <label class="form-check-label" for="bank_confirm">
                                        I confirm that I have a valid medical reason to request blood.
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-paper-plane mr-1"></i> Submit Request
                                </button>
                            </form>
                        </div>
                        
                        <!-- Request from Donor -->
                        <div class="tab-pane fade" id="donorRequest" role="tabpanel" aria-labelledby="donor-tab">
                            <h4 class="mb-3">Request Blood from Specific Donor</h4>
                            
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                <input type="hidden" name="request_type" value="from_donor">
                                
                                <div class="form-group">
                                    <label for="donor_select">Select Donor</label>
                                    <select class="form-control" id="donor_select" name="donor_id" required>
                                        <option value="">Select Donor</option>
                                        <?php
                                        mysqli_data_seek($donors_result, 0);
                                        while ($donor = mysqli_fetch_assoc($donors_result)) {
                                            $is_compatible = in_array($donor['blood_group'], $compatible_types);
                                            if ($is_compatible) {
                                                echo '<option value="' . $donor['user_id'] . '">' . htmlspecialchars($donor['full_name']) . ' (' . $donor['blood_group'] . ')</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                    <small class="form-text text-muted">Only compatible donors are shown.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="donor_blood_group">Blood Group</label>
                                    <select class="form-control" id="donor_blood_group" name="blood_group" required>
                                        <option value="">Select Blood Group</option>
                                        <?php foreach ($compatible_types as $type): ?>
                                            <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="donor_quantity">Quantity (ml)</label>
                                    <input type="number" class="form-control" id="donor_quantity" name="quantity" min="100" max="500" value="350" required>
                                    <small class="form-text text-muted">Standard blood request is between 350-450 ml.</small>
                                </div>
                                
                                <div class="form-group form-check">
                                    <input type="checkbox" class="form-check-input" id="donor_confirm" required>
                                    <label class="form-check-label" for="donor_confirm">
                                        I confirm that I have a valid medical reason to request blood.
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-paper-plane mr-1"></i> Submit Request
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Recent Requests -->
                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Your Recent Blood Requests</h5>
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($requests_result) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Request Date</th>
                                                <th>Blood Group</th>
                                                <th>From</th>
                                                <th>Quantity</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($request = mysqli_fetch_assoc($requests_result)): ?>
                                                <tr>
                                                    <td><?php echo date('M j, Y', strtotime($request['request_date'])); ?></td>
                                                    <td><span class="badge badge-danger"><?php echo $request['blood_group']; ?></span></td>
                                                    <td>
                                                        <?php if ($request['donor_name']): ?>
                                                            <?php echo htmlspecialchars($request['donor_name']); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Blood Bank</span>
                                                        <?php endif; ?>
                                                    </td>
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
                                <div class="text-center mt-3">
                                    <a href="request_history.php" class="btn btn-outline-danger">
                                        <i class="fas fa-history mr-1"></i> View Complete History
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i> You haven't made any blood requests yet.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Update donor blood group dropdown based on selected donor
$(document).ready(function() {
    $('#donor_select').change(function() {
        var selectedOption = $(this).find('option:selected');
        var bloodGroup = selectedOption.text().match(/\(([^)]+)\)/);
        
        if (bloodGroup && bloodGroup[1]) {
            $('#donor_blood_group').val(bloodGroup[1]);
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>