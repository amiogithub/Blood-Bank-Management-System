<?php
require_once '../includes/header.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle request approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_id']) && isset($_POST['action'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    
    if ($action == 'approve' || $action == 'reject') {
        $status = ($action == 'approve') ? 'approved' : 'rejected';
        
        // Get donation details
        $request_query = "SELECT * FROM donation_requests WHERE request_id = $request_id";
        $request_result = mysqli_query($conn, $request_query);
        
        if (mysqli_num_rows($request_result) > 0) {
            $request = mysqli_fetch_assoc($request_result);
            
            $donor_id = $request['user_id'];
            $blood_group = $request['blood_group'];
            $quantity = $request['quantity_ml'];
            
            // Update request status
            $update_query = "UPDATE donation_requests SET status = '$status' WHERE request_id = $request_id";
            
            if (mysqli_query($conn, $update_query)) {
                // If approved, update blood stock and donor profile
                if ($action == 'approve') {
                    // Update blood stock
                    $stock_query = "UPDATE blood_stock SET quantity_ml = quantity_ml + $quantity, last_updated = CURRENT_TIMESTAMP WHERE blood_group = '$blood_group'";
                    mysqli_query($conn, $stock_query);
                    
                    // Update donor profile with last donation date
                    $profile_query = "UPDATE donor_profiles SET last_donation_date = CURRENT_DATE() WHERE user_id = $donor_id";
                    mysqli_query($conn, $profile_query);
                    
                    // Create log entry for donation
                    $log_query = "INSERT INTO donation_logs (user_id, blood_group, quantity_ml, donation_date) 
                                VALUES ($donor_id, '$blood_group', $quantity, CURRENT_DATE())";
                    mysqli_query($conn, $log_query);
                }
                
                // Create notification for donor
                $notification_content = "Your blood donation request has been " . $status . ".";
                if ($action == 'approve') {
                    $notification_content .= " Please visit our center to complete your donation.";
                }
                
                $notification_query = "INSERT INTO notifications (user_id, content) VALUES ($donor_id, '$notification_content')";
                mysqli_query($conn, $notification_query);
                
                // Redirect to prevent form resubmission
                header("Location: donation_requests.php?status=success&action=$action");
                exit();
            } else {
                // Error handling
                header("Location: donation_requests.php?status=error&message=" . urlencode(mysqli_error($conn)));
                exit();
            }
        } else {
            header("Location: donation_requests.php?status=error&message=request_not_found");
            exit();
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';
$blood_group_filter = isset($_GET['blood_group_filter']) ? $_GET['blood_group_filter'] : 'all';

// Build query based on filters
$query = "SELECT dr.*, u.full_name, u.contact_number, u.address 
          FROM donation_requests dr
          JOIN users u ON dr.user_id = u.user_id";

$where_clauses = [];

if ($status_filter != 'all') {
    $where_clauses[] = "dr.status = '$status_filter'";
}

if ($blood_group_filter != 'all') {
    $where_clauses[] = "dr.blood_group = '$blood_group_filter'";
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " ORDER BY dr.request_date DESC";

$result = mysqli_query($conn, $query);

// Get all blood groups for filter
$blood_groups_query = "SELECT DISTINCT blood_group FROM blood_stock ORDER BY blood_group";
$blood_groups_result = mysqli_query($conn, $blood_groups_query);
?>

<div class="container-fluid my-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-tachometer-alt mr-2"></i> Admin Panel</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-home mr-2"></i> Dashboard
                    </a>
                    <a href="donor_management.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-plus mr-2"></i> Donor Management
                    </a>
                    <a href="recipient_management.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user mr-2"></i> Recipient Management
                    </a>
                    <a href="blood_stock.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tint mr-2"></i> Blood Stock
                    </a>
                    <a href="donation_requests.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-hand-holding-heart mr-2"></i> Donation Requests
                    </a>
                    <a href="blood_requests.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-procedures mr-2"></i> Blood Requests
                    </a>
                    <a href="posts.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-comments mr-2"></i> Manage Posts
                    </a>
                    <a href="reports.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-bar mr-2"></i> Reports
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><i class="fas fa-hand-holding-heart mr-2"></i> Donation Requests</h2>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
                        <div class="alert alert-success">
                            <?php if ($_GET['action'] == 'approve'): ?>
                                <i class="fas fa-check-circle mr-2"></i> Donation request has been approved successfully. Blood stock has been updated.
                            <?php else: ?>
                                <i class="fas fa-times-circle mr-2"></i> Donation request has been rejected.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['status']) && $_GET['status'] == 'error'): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <?php if (isset($_GET['message'])): ?>
                                Error: <?php echo htmlspecialchars($_GET['message']); ?>
                            <?php else: ?>
                                An error occurred while processing the request.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Filters -->
                    <div class="mb-4">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="form-inline">
                            <div class="form-group mr-3">
                                <label for="status_filter" class="mr-2">Status:</label>
                                <select class="form-control" id="status_filter" name="status_filter">
                                    <option value="all" <?php if ($status_filter == 'all') echo 'selected'; ?>>All</option>
                                    <option value="pending" <?php if ($status_filter == 'pending') echo 'selected'; ?>>Pending</option>
                                    <option value="approved" <?php if ($status_filter == 'approved') echo 'selected'; ?>>Approved</option>
                                    <option value="rejected" <?php if ($status_filter == 'rejected') echo 'selected'; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="form-group mr-3">
                                <label for="blood_group_filter" class="mr-2">Blood Group:</label>
                                <select class="form-control" id="blood_group_filter" name="blood_group_filter">
                                    <option value="all" <?php if ($blood_group_filter == 'all') echo 'selected'; ?>>All</option>
                                    <?php while ($blood_group = mysqli_fetch_assoc($blood_groups_result)): ?>
                                        <option value="<?php echo $blood_group['blood_group']; ?>" <?php if ($blood_group_filter == $blood_group['blood_group']) echo 'selected'; ?>>
                                            <?php echo $blood_group['blood_group']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-danger">Filter</button>
                        </form>
                    </div>
                    
                    <!-- Requests Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Donor Name</th>
                                    <th>Contact</th>
                                    <th>Blood Group</th>
                                    <th>Quantity</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo $row['request_id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                                            <td>
                                                <span class="badge badge-danger"><?php echo $row['blood_group']; ?></span>
                                            </td>
                                            <td><?php echo $row['quantity_ml']; ?> ml</td>
                                            <td><?php echo date('M j, Y', strtotime($row['request_date'])); ?></td>
                                            <td>
                                                <?php if ($row['status'] == 'pending'): ?>
                                                    <span class="badge badge-warning">Pending</span>
                                                <?php elseif ($row['status'] == 'approved'): ?>
                                                    <span class="badge badge-success">Approved</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Rejected</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($row['status'] == 'pending'): ?>
                                                    <div class="btn-group" role="group">
                                                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="mr-1">
                                                            <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to approve this donation request? This will update the blood stock.')">
                                                                <i class="fas fa-check"></i> Approve
                                                            </button>
                                                        </form>
                                                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                                            <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                                                            <input type="hidden" name="action" value="reject">
                                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to reject this donation request?')">
                                                                <i class="fas fa-times"></i> Reject
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-secondary" disabled>Processed</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No donation requests found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>