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
$blood_group = ''; // Initialize blood_group variable here to fix undefined variable issue

// Check if success message is set
if (isset($_SESSION['blood_request_success'])) {
    $success_message = $_SESSION['blood_request_success'];
    unset($_SESSION['blood_request_success']);
}

// Check if user is already registered as recipient
$recipient_query = "SELECT * FROM recipient_profiles WHERE user_id = $user_id";
$recipient_result = mysqli_query($conn, $recipient_query);
$is_recipient = mysqli_num_rows($recipient_result) > 0;

// Process recipient registration form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_recipient'])) {
    // Form processing handled by recipient_registration_handler.php
} 

// Process blood request form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_blood'])) {
    if (!isset($_POST['confirm'])) {
        $errors[] = "You must confirm that you have a valid medical reason to request blood.";
    } else {
        $donor_id = $_POST['donor_id'];
        $blood_group = $_POST['blood_group'];
        $quantity = $_POST['quantity'];
        
        // Validate inputs
        if (empty($blood_group)) {
            $errors[] = "Blood group is required";
        }
        
        if (!is_numeric($quantity) || $quantity < 100 || $quantity > 1000) {
            $errors[] = "Quantity must be between 100ml and 1000ml";
        }
        
        // If no errors, proceed with request
        if (empty($errors)) {
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Insert blood request
                $query = "INSERT INTO blood_requests (recipient_id, donor_id, blood_group, quantity_ml, status) 
                         VALUES ($user_id, $donor_id, '$blood_group', $quantity, 'pending')";
                
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
                    
                    $notification_content = "New blood request: $user_name needs {$quantity}ml of $blood_group blood.";
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
                
                // Also notify donor
                // Get donor name and recipient name
                $donor_query = "SELECT full_name FROM users WHERE user_id = $donor_id";
                $donor_result = mysqli_query($conn, $donor_query);
                $donor_name = mysqli_fetch_assoc($donor_result)['full_name'];
                
                $recipient_query = "SELECT full_name FROM users WHERE user_id = $user_id";
                $recipient_result = mysqli_query($conn, $recipient_query);
                $recipient_name = mysqli_fetch_assoc($recipient_result)['full_name'];
                
                $donor_notification = "You have received a blood request from $recipient_name for {$quantity}ml of $blood_group blood. An admin will review this request.";
                $donor_notification_query = "INSERT INTO notifications (user_id, content) VALUES ($donor_id, '$donor_notification')";
                
                if (!mysqli_query($conn, $donor_notification_query)) {
                    throw new Exception(mysqli_error($conn));
                }
                
                // Commit the transaction
                mysqli_commit($conn);
                
                $_SESSION['blood_request_success'] = "Blood request has been sent to admin for approval.";
                header("Location: looking_for_blood.php");
                exit();
            } catch (Exception $e) {
                // Rollback the transaction if any query fails
                mysqli_rollback($conn);
                $errors[] = "Error: " . $e->getMessage();
            }
        }
    }
}

// Get available donors
$donors_query = "SELECT dp.*, u.full_name, u.user_id, u.contact_number, u.address
                FROM donor_profiles dp
                JOIN users u ON dp.user_id = u.user_id
                JOIN user_roles ur ON dp.user_id = ur.user_id
                WHERE dp.is_available = 1 
                AND ur.is_donor = 1
                ORDER BY dp.last_donation_date DESC";
$donors_result = mysqli_query($conn, $donors_query);

// Get all blood groups for filter
$blood_groups_query = "SELECT DISTINCT blood_group FROM blood_stock ORDER BY blood_group";
$blood_groups_result = mysqli_query($conn, $blood_groups_query);

// Get recipient profile if exists
if ($is_recipient) {
    mysqli_data_seek($recipient_result, 0);
    $recipient_profile = mysqli_fetch_assoc($recipient_result);
    
    // Ensure blood_group is set (this is the fix for the undefined variable)
    if (isset($recipient_profile['blood_group'])) {
        $blood_group = $recipient_profile['blood_group'];
    }
}

// Handle errors and success from recipient registration
if (isset($_SESSION['recipient_registered']) && $_SESSION['recipient_registered'] === true) {
    $success_message = "You have successfully registered as a recipient. You can now request blood!";
    unset($_SESSION['recipient_registered']);
    $is_recipient = true;
    
    // Refresh recipient profile data
    $recipient_query = "SELECT * FROM recipient_profiles WHERE user_id = $user_id";
    $recipient_result = mysqli_query($conn, $recipient_query);
    if (mysqli_num_rows($recipient_result) > 0) {
        $recipient_profile = mysqli_fetch_assoc($recipient_result);
        if (isset($recipient_profile['blood_group'])) {
            $blood_group = $recipient_profile['blood_group'];
        }
    }
}

if (isset($_SESSION['recipient_error'])) {
    $errors[] = $_SESSION['recipient_error'];
    unset($_SESSION['recipient_error']);
}

if (isset($_SESSION['recipient_errors']) && is_array($_SESSION['recipient_errors'])) {
    $errors = array_merge($errors, $_SESSION['recipient_errors']);
    unset($_SESSION['recipient_errors']);
}

// Blood type compatibility map
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

// Only set compatible_types if blood_group is valid
$compatible_types = !empty($blood_group) && isset($compatibility_map[$blood_group]) ? $compatibility_map[$blood_group] : [];
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h2 class="mb-0"><i class="fas fa-tint mr-2"></i> Looking for Blood</h2>
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
                    
                    <?php if (!$is_recipient): ?>
                        <!-- Recipient Registration Form -->
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i> You need to register as a recipient before you can request blood.
                        </div>
                        
                        <div class="form-container">
                            <h3 class="mb-4">Recipient Registration</h3>
                            <form action="recipient_registration_handler.php" method="post">
                                <div class="form-group">
                                    <label for="blood_group">Your Blood Group</label>
                                    <select class="form-control" id="blood_group" name="blood_group" required>
                                        <option value="">Select Your Blood Group</option>
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
                                    <label for="medical_history">Medical History (Optional)</label>
                                    <textarea class="form-control" id="medical_history" name="medical_history" rows="3" placeholder="Any relevant medical conditions, allergies, or previous transfusions..."></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="preferred_hospital">Preferred Hospital/Clinic (Optional)</label>
                                    <input type="text" class="form-control" id="preferred_hospital" name="preferred_hospital" placeholder="Where you usually receive medical care">
                                </div>
                                
                                <button type="submit" name="register_recipient" class="btn btn-danger">
                                    <i class="fas fa-user-plus mr-2"></i> Register as Recipient
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <!-- Blood Request Section -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-danger">Your Blood Group</h5>
                                        <h2 class="display-4 text-danger"><?php echo !empty($blood_group) ? $blood_group : 'Not Set'; ?></h2>
                                        <?php if (empty($blood_group)): ?>
                                            <div class="alert alert-warning mt-2 small">
                                                <i class="fas fa-exclamation-triangle mr-1"></i> Please update your profile with your blood group
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title text-danger">Compatible Blood Types</h5>
                                        <div class="mt-2">
                                            <?php if (!empty($compatible_types)): ?>
                                                <?php foreach ($compatible_types as $type): ?>
                                                    <span class="badge badge-danger mr-2 p-2"><?php echo $type; ?></span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="alert alert-info mb-0">
                                                    <i class="fas fa-info-circle mr-1"></i> Please set your blood group to see compatibility
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($compatible_types)): ?>
                                            <p class="mt-3 small text-muted">These are the blood types that are compatible with your blood type. You can receive blood from any of these types.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <ul class="nav nav-tabs mb-4" id="requestTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="donor-tab" data-toggle="tab" href="#donorList" role="tab" aria-controls="donorList" aria-selected="true">
                                    <i class="fas fa-users mr-1"></i> Available Donors
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="bank-tab" data-toggle="tab" href="#bankRequest" role="tab" aria-controls="bankRequest" aria-selected="false">
                                    <i class="fas fa-hospital-alt mr-1"></i> Request from Blood Bank
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="history-tab" data-toggle="tab" href="#requestHistory" role="tab" aria-controls="requestHistory" aria-selected="false">
                                    <i class="fas fa-history mr-1"></i> Request History
                                </a>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="requestTabContent">
                            <!-- Donor List -->
                            <div class="tab-pane fade show active" id="donorList" role="tabpanel" aria-labelledby="donor-tab">
                                <h4 class="mb-3">Available Donors</h4>
                                
                                <!-- Donor Search and Filters -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <input type="text" id="donorSearch" class="form-control" placeholder="Search donors...">
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <select id="bloodTypeFilter" class="form-control">
                                            <option value="all">All Blood Types</option>
                                            <?php foreach ($compatible_types as $type): ?>
                                                <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Donors List -->
                                <div class="row">
                                    <?php if (mysqli_num_rows($donors_result) > 0): ?>
                                        <?php while ($donor = mysqli_fetch_assoc($donors_result)): ?>
                                            <?php 
                                            $is_compatible = !empty($blood_group) && in_array($donor['blood_group'], $compatible_types);
                                            $card_class = $is_compatible ? '' : 'bg-light text-muted';
                                            ?>
                                            <div class="col-md-6 mb-3 donor-card" data-blood-type="<?php echo $donor['blood_group']; ?>">
                                                <div class="card h-100 <?php echo $card_class; ?>">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                                            <h5 class="card-title"><?php echo htmlspecialchars($donor['full_name']); ?></h5>
                                                            <span class="badge badge-danger"><?php echo $donor['blood_group']; ?></span>
                                                        </div>
                                                        <ul class="list-unstyled">
                                                            <li><i class="fas fa-map-marker-alt mr-2"></i> <?php echo htmlspecialchars($donor['address']); ?></li>
                                                            <li><i class="fas fa-phone mr-2"></i> <?php echo htmlspecialchars($donor['contact_number']); ?></li>
                                                            <li><i class="fas fa-smoking-ban mr-2"></i> Smoker: <?php echo ucfirst($donor['smoker']); ?></li>
                                                            <li><i class="fas fa-venus-mars mr-2"></i> Gender: <?php echo ucfirst($donor['gender']); ?></li>
                                                            <?php if ($donor['last_donation_date']): ?>
                                                                <li><i class="fas fa-calendar-alt mr-2"></i> Last Donation: <?php echo date('F j, Y', strtotime($donor['last_donation_date'])); ?></li>
                                                            <?php else: ?>
                                                                <li><i class="fas fa-calendar-alt mr-2"></i> No previous donations</li>
                                                            <?php endif; ?>
                                                        </ul>
                                                        
                                                        <div class="mt-3">
                                                            <a href="chat.php?user_id=<?php echo $donor['user_id']; ?>" class="btn btn-sm btn-outline-secondary mr-2">
                                                                <i class="fas fa-comments mr-1"></i> Chat
                                                            </a>
                                                            
                                                            <?php if ($is_compatible): ?>
                                                                <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#requestModal<?php echo $donor['user_id']; ?>">
                                                                    <i class="fas fa-tint mr-1"></i> Request Blood
                                                                </button>
                                                            <?php else: ?>
                                                                <button class="btn btn-sm btn-secondary" disabled>
                                                                    <i class="fas fa-exclamation-circle mr-1"></i> Incompatible
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Blood Request Modal -->
                                                <div class="modal fade" id="requestModal<?php echo $donor['user_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="requestModalLabel<?php echo $donor['user_id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-danger text-white">
                                                                <h5 class="modal-title" id="requestModalLabel<?php echo $donor['user_id']; ?>">
                                                                    Request Blood from <?php echo htmlspecialchars($donor['full_name']); ?>
                                                                </h5>
                                                                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form id="requestForm<?php echo $donor['user_id']; ?>" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="donor-request-form">
                                                                    <input type="hidden" name="donor_id" value="<?php echo $donor['user_id']; ?>">
                                                                    <input type="hidden" name="request_blood" value="1">
                                                                    
                                                                    <div class="form-group">
                                                                        <label for="blood_group<?php echo $donor['user_id']; ?>">Blood Group</label>
                                                                        <select class="form-control" id="blood_group<?php echo $donor['user_id']; ?>" name="blood_group" required>
                                                                            <option value="">Select Blood Group</option>
                                                                            <?php foreach ($compatible_types as $type): ?>
                                                                                <?php if ($type == $donor['blood_group']): ?>
                                                                                    <option value="<?php echo $type; ?>" selected><?php echo $type; ?></option>
                                                                                <?php endif; ?>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>
                                                                    
                                                                    <div class="form-group">
                                                                        <label for="quantity<?php echo $donor['user_id']; ?>">Quantity (ml)</label>
                                                                        <input type="number" class="form-control" id="quantity<?php echo $donor['user_id']; ?>" name="quantity" min="100" max="500" value="350" required>
                                                                        <small class="form-text text-muted">Standard blood donation is 350-450 ml.</small>
                                                                    </div>
                                                                    
                                                                    <div class="form-group form-check">
                                                                        <input type="checkbox" class="form-check-input" id="confirm<?php echo $donor['user_id']; ?>" name="confirm" required>
                                                                        <label class="form-check-label" for="confirm<?php echo $donor['user_id']; ?>">
                                                                            I confirm that I have a valid medical reason to request blood.
                                                                        </label>
                                                                    </div>
                                                                    
                                                                    <div class="alert alert-info">
                                                                        <i class="fas fa-info-circle mr-2"></i> Your request will be sent to both the donor and the admin for approval.
                                                                    </div>
                                                                </form>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                <button type="submit" form="requestForm<?php echo $donor['user_id']; ?>" class="btn btn-danger">
                                                                    <i class="fas fa-paper-plane mr-1"></i> Send Request
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <div class="col-12">
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle mr-2"></i> No donors available at the moment.
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Request from Bank -->
                            <div class="tab-pane fade" id="bankRequest" role="tabpanel" aria-labelledby="bank-tab">
                                <h4 class="mb-3">Request Blood from Bank</h4>
                                
                                <form action="blood_request.php" method="post" class="bank-request-form">
                                    <input type="hidden" name="request_type" value="from_bank">
                                    
                                    <div class="form-group">
                                        <label for="bank_blood_group">Blood Group</label>
                                        <select class="form-control" id="bank_blood_group" name="blood_group" required>
                                            <option value="">Select Blood Group</option>
                                            <?php foreach ($compatible_types as $type): ?>
                                                <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="bank_quantity">Quantity (ml)</label>
                                        <input type="number" class="form-control" id="bank_quantity" name="quantity" min="100" max="1000" value="350" required>
                                        <small class="form-text text-muted">Standard blood request is between 350-450 ml. Maximum allowed is 1000 ml.</small>
                                    </div>
                                    
                                    <div class="form-group form-check">
                                        <input type="checkbox" class="form-check-input" id="bank_confirm" name="confirm" required>
                                        <label class="form-check-label" for="bank_confirm">
                                            I confirm that I have a valid medical reason to request blood.
                                        </label>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-paper-plane mr-1"></i> Submit Request
                                    </button>
                                </form>
                            </div>
                            
                            <!-- Request History -->
                            <div class="tab-pane fade" id="requestHistory" role="tabpanel" aria-labelledby="history-tab">
                                <h4 class="mb-3">Your Blood Request History</h4>
                                
                                <?php
                                // Get user's blood request history
                                $history_query = "SELECT br.*, 
                                               u.full_name as donor_name
                                               FROM blood_requests br
                                               LEFT JOIN users u ON br.donor_id = u.user_id
                                               WHERE br.recipient_id = $user_id
                                               ORDER BY br.request_date DESC";
                                $history_result = mysqli_query($conn, $history_query);
                                ?>
                                
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Date</th>
                                                <th>Blood Group</th>
                                                <th>Source</th>
                                                <th>Quantity</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (mysqli_num_rows($history_result) > 0): ?>
                                                <?php while ($request = mysqli_fetch_assoc($history_result)): ?>
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
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No blood requests found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <a href="request_history.php" class="btn btn-outline-danger">
                                        <i class="fas fa-list mr-1"></i> View Complete History
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Blood Request Tips -->
            <div class="card mt-4">
                <div class="card-header bg-danger text-white">
                    <h3 class="mb-0">Blood Request Tips</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="fas fa-check-circle text-success mr-2"></i> Best Practices</h5>
                            <ul>
                                <li>Request blood at least 24-48 hours before you need it</li>
                                <li>Provide accurate information about your medical condition</li>
                                <li>Always specify the correct blood group you need</li>
                                <li>Keep your contact information updated</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                        <h5><i class="fas fa-exclamation-triangle text-warning mr-2"></i> Important Notes</h5>
                            <ul>
                                <li>For emergencies, call our 24/7 hotline</li>
                                <li>You may need to provide medical documents for verification</li>
                                <li>Blood will be provided based on availability and priority</li>
                                <li>Always carry your ID when collecting blood</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle modal form submission
    $('.donor-request-form').submit(function(e) {
        if (!$(this).find('input[name="confirm"]').is(':checked')) {
            e.preventDefault();
            alert('You must confirm that you have a valid medical reason to request blood.');
            return false;
        }
    });
    
    // Blood Type Filter
    $('#bloodTypeFilter').change(function() {
        var selectedType = $(this).val();
        
        if (selectedType === 'all') {
            $('.donor-card').show();
        } else {
            $('.donor-card').hide();
            $('.donor-card[data-blood-type="' + selectedType + '"]').show();
        }
    });
    
    // Donor Search
    $('#donorSearch').keyup(function() {
        var searchText = $(this).val().toLowerCase();
        
        $('.donor-card').each(function() {
            var donorName = $(this).find('.card-title').text().toLowerCase();
            
            if (donorName.indexOf(searchText) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Bank request form validation
    $('.bank-request-form').submit(function(e) {
        if (!$('#bank_confirm').is(':checked')) {
            e.preventDefault();
            alert('You must confirm that you have a valid medical reason to request blood.');
            return false;
        }
    });
    
    // Fix the modal closing issue
    $('.modal').on('shown.bs.modal', function() {
        $(this).find('form').on('submit', function() {
            // Prevent double submission
            var form = $(this);
            if (form.data('submitted') === true) {
                e.preventDefault();
                return false;
            }
            
            form.data('submitted', true);
        });
    });
    
    // Reset form submission flag when modal is hidden
    $('.modal').on('hidden.bs.modal', function() {
        $(this).find('form').data('submitted', false);
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>