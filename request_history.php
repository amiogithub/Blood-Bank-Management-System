<?php
require_once '../includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all blood requests for this user
$requests_query = "SELECT br.*, 
                  u.full_name as donor_name
                  FROM blood_requests br
                  LEFT JOIN users u ON br.donor_id = u.user_id
                  WHERE br.recipient_id = $user_id
                  ORDER BY br.request_date DESC";
$requests_result = mysqli_query($conn, $requests_query);

// Get recipient profile information
$profile_query = "SELECT * FROM recipient_profiles WHERE user_id = $user_id";
$profile_result = mysqli_query($conn, $profile_query);
$recipient_profile = mysqli_fetch_assoc($profile_result);
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="card">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><i class="fas fa-history mr-2"></i> My Blood Request History</h2>
                    <a href="blood_request.php" class="btn btn-light">
                        <i class="fas fa-plus mr-1"></i> New Request
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($recipient_profile): ?>
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-danger">Your Blood Group</h5>
                                        <h2 class="display-4 text-danger"><?php echo $recipient_profile['blood_group']; ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title text-danger">Blood Request Summary</h5>
                                        <?php
                                        // Calculate request statistics
                                        $total_requests = mysqli_num_rows($requests_result);
                                        
                                        // Reset pointer
                                        mysqli_data_seek($requests_result, 0);
                                        
                                        $approved_requests = 0;
                                        $pending_requests = 0;
                                        $rejected_requests = 0;
                                        $total_blood_received = 0;
                                        
                                        while ($request = mysqli_fetch_assoc($requests_result)) {
                                            if ($request['status'] == 'approved') {
                                                $approved_requests++;
                                                $total_blood_received += $request['quantity_ml'];
                                            } elseif ($request['status'] == 'pending') {
                                                $pending_requests++;
                                            } else {
                                                $rejected_requests++;
                                            }
                                        }
                                        
                                        // Reset pointer again for the main loop
                                        mysqli_data_seek($requests_result, 0);
                                        ?>
                                        
                                        <div class="row mt-3">
                                            <div class="col-md-4 text-center">
                                                <h3 class="text-danger"><?php echo $total_requests; ?></h3>
                                                <p class="text-muted">Total Requests</p>
                                            </div>
                                            <div class="col-md-4 text-center">
                                                <h3 class="text-success"><?php echo $approved_requests; ?></h3>
                                                <p class="text-muted">Approved</p>
                                            </div>
                                            <div class="col-md-4 text-center">
                                                <h3 class="text-danger"><?php echo $total_blood_received; ?> ml</h3>
                                                <p class="text-muted">Total Received</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Request History Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Request Date</th>
                                    <th>Blood Group</th>
                                    <th>Source</th>
                                    <th>Quantity</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($requests_result) > 0): ?>
                                    <?php while ($request = mysqli_fetch_assoc($requests_result)): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y', strtotime($request['request_date'])); ?></td>
                                            <td><span class="badge badge-danger"><?php echo $request['blood_group']; ?></span></td>
                                            <td>
                                                <?php if ($request['donor_name']): ?>
                                                    <span class="badge badge-info">Individual Donor</span>
                                                    <?php echo htmlspecialchars($request['donor_name']); ?>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Blood Bank</span>
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
                                            <td>
                                                <?php if ($request['status'] == 'approved' && $request['donor_id']): ?>
                                                    <a href="chat.php?user_id=<?php echo $request['donor_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-comment-alt mr-1"></i> Contact Donor
                                                    </a>
                                                <?php elseif ($request['status'] == 'approved'): ?>
                                                    <button class="btn btn-sm btn-success" disabled>
                                                        <i class="fas fa-check-circle mr-1"></i> Approved
                                                    </button>
                                                <?php elseif ($request['status'] == 'pending'): ?>
                                                    <span class="text-muted">Awaiting approval</span>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-danger" disabled>
                                                        <i class="fas fa-times-circle mr-1"></i> Rejected
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No blood requests found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        <a href="blood_request.php" class="btn btn-danger">
                            <i class="fas fa-tint mr-1"></i> Request Blood
                        </a>
                        <a href="dashboard.php" class="btn btn-outline-secondary ml-2">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
                        </a>
                    </div>
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

<?php require_once '../includes/footer.php'; ?>