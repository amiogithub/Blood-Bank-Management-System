<?php
require_once '../includes/header.php';

// amra first e check korbo je user adou admin kinah? #nashid 

//ceikhane amra check kortesu user either already logged in kinah or jodi shey bhul user id or email dei tahole take abar redirect to login page

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// handeling approval request or rejeect. first e recipent blood chaile oita validate korlam then
// approve/reject er udilo erpor dekhlam je stock e blood ase naki jodi thake tahole apprive dibo table update korbo 
//transanction er moton erpor jodi stock e na thake tahole aar error khaile shob undo


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_id']) && isset($_POST['action'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    
    if ($action == 'approve' || $action == 'reject') {
        $status = ($action == 'approve') ? 'approved' : 'rejected';
        
        // Get request details
        $request_query = "SELECT * FROM blood_requests WHERE request_id = $request_id";
        $request_result = mysqli_query($conn, $request_query);
        
        if (mysqli_num_rows($request_result) > 0) {
            $request = mysqli_fetch_assoc($request_result);
            
            $recipient_id = $request['recipient_id'];
            $donor_id = $request['donor_id'];
            $blood_group = $request['blood_group'];
            $quantity = $request['quantity_ml'];
            
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Update request status
                $update_query = "UPDATE blood_requests SET status = '$status' WHERE request_id = $request_id";
                
                if (!mysqli_query($conn, $update_query)) {
                    throw new Exception(mysqli_error($conn));
                }
                
                // approved hoile blood stock update kore ditam


                if ($action == 'approve') {
                    // Check if sufficient blood is available
                    $stock_query = "SELECT quantity_ml FROM blood_stock WHERE blood_group = '$blood_group'";
                    $stock_result = mysqli_query($conn, $stock_query);
                    $stock = mysqli_fetch_assoc($stock_result);
                    
                    
                    //jodi stock thake taile database e conn diye connect korbo naile korbona error diye dai
                    
                    if ($stock['quantity_ml'] >= $quantity) {
                        // Update blood stock
                        $stock_update_query = "UPDATE blood_stock SET quantity_ml = quantity_ml - $quantity, last_updated = CURRENT_TIMESTAMP WHERE blood_group = '$blood_group'";
                        if (!mysqli_query($conn, $stock_update_query)) {
                            throw new Exception(mysqli_error($conn));
                        }
                        
                        // Create log entry for blood request
                        $log_query = "INSERT INTO blood_request_logs (recipient_id, donor_id, blood_group, quantity_ml, request_date) 
                                    VALUES ($recipient_id, " . ($donor_id ? $donor_id : "NULL") . ", '$blood_group', $quantity, CURRENT_DATE())";
                        if (!mysqli_query($conn, $log_query)) {
                            throw new Exception(mysqli_error($conn));
                        }
                    } else {
                        throw new Exception("insufficient_stock");
                    }
                }
                
                // Create notification for recipient action approve hoile

                $notification_content = "Your blood request #$request_id has been " . $status . ".";
                if ($action == 'approve') {
                    $notification_content .= " Please visit our center to collect your blood.";
                }
                
                
                #jei notification ta ashlo sheta database e insert korlam 
                
                $notification_query = "INSERT INTO notifications (user_id, content) VALUES ($recipient_id, '$notification_content')";
                if (!mysqli_query($conn, $notification_query)) {
                    throw new Exception(mysqli_error($conn));
                }
                
                // Also notify donor if the request was directly to a donor
                if ($donor_id && $action == 'approve') {
                    // Get recipient name
                    $recipient_query = "SELECT full_name FROM users WHERE user_id = $recipient_id";
                    $recipient_result = mysqli_query($conn, $recipient_query);
                    $recipient_name = mysqli_fetch_assoc($recipient_result)['full_name'];
                    
                    $donor_notification = "Blood request from " . $recipient_name . " for " . $quantity . "ml of " . $blood_group . " has been approved. Please contact the recipient or the blood bank for coordination.";
                    $donor_notification_query = "INSERT INTO notifications (user_id, content) VALUES ($donor_id, '$donor_notification')";
                    if (!mysqli_query($conn, $donor_notification_query)) {
                        throw new Exception(mysqli_error($conn));
                    }
                }
                
                // Commit the transaction
                mysqli_commit($conn);
                
                // Redirect to prevent form resubmission
                header("Location: blood_requests.php?status=success&action=$action");
                exit();
            } catch (Exception $e) {
                // Rollback the transaction
                mysqli_rollback($conn);
                
                if ($e->getMessage() == "insufficient_stock") {
                    header("Location: blood_requests.php?status=error&message=insufficient_stock");
                } else {
                    header("Location: blood_requests.php?status=error&message=" . urlencode($e->getMessage()));
                }
                exit();
            }
        } else {
            header("Location: blood_requests.php?status=error&message=request_not_found");
            exit();
        }
    }
}


// Count pending requests for notification badge
$pending_count_query = "SELECT COUNT(*) as pending_count FROM blood_requests WHERE status = 'pending'";
$pending_count_result = mysqli_query($conn, $pending_count_query);
$pending_count = mysqli_fetch_assoc($pending_count_result)['pending_count'];

// Get filter parameters
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';
$blood_group_filter = isset($_GET['blood_group_filter']) ? $_GET['blood_group_filter'] : 'all';


//basically eikhane blood requests table theke jar blood lagbe jarta lagbe oder details anbo users table theke. 
//this will only return rows where the recipient exists in the users table.

$query = "SELECT br.*, 
         u1.full_name as recipient_name, u1.contact_number as recipient_contact,
         u2.full_name as donor_name 
         FROM blood_requests br
         JOIN users u1 ON br.recipient_id = u1.user_id    
         LEFT JOIN users u2 ON br.donor_id = u2.user_id";



$where_clauses = [];

if ($status_filter != 'all') {
    $where_clauses[] = "br.status = '$status_filter'";
}

if ($blood_group_filter != 'all') {
    $where_clauses[] = "br.blood_group = '$blood_group_filter'";
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " ORDER BY br.request_date DESC";

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
                    
                    <a href="user_management.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-plus mr-2"></i> User Management
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
                    <a href="donation_requests.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-hand-holding-heart mr-2"></i> Donation Requests
                    </a>
                    <a href="blood_requests.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-procedures mr-2"></i> Blood Requests
                        <?php if ($pending_count > 0): ?>
                            <span class="badge badge-danger ml-1"><?php echo $pending_count; ?></span>
                        <?php endif; ?>
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
                    <h2 class="mb-0">
                        <i class="fas fa-procedures mr-2"></i> Blood Requests
                        <?php if ($pending_count > 0): ?>
                            <span class="badge badge-light ml-1"><?php echo $pending_count; ?> pending</span>
                        <?php endif; ?>
                    </h2>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
                        <div class="alert alert-success">
                            <?php if ($_GET['action'] == 'approve'): ?>
                                <i class="fas fa-check-circle mr-2"></i> Blood request has been approved successfully. Blood stock has been updated.
                            <?php else: ?>
                                <i class="fas fa-times-circle mr-2"></i> Blood request has been rejected.
                                <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['status']) && $_GET['status'] == 'error'): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <?php if (isset($_GET['message']) && $_GET['message'] == 'insufficient_stock'): ?>
                                Insufficient blood stock to fulfill this request.
                            <?php else: ?>
                                An error occurred while processing the request: <?php echo htmlspecialchars($_GET['message']); ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Filters -->
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
                                    <th>Recipient</th>
                                    <th>Contact</th>
                                    <th>Donor</th>
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
                                        <tr class="<?php echo ($row['status'] == 'pending') ? 'bg-light' : ''; ?>">
                                            <td><?php echo $row['request_id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['recipient_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['recipient_contact']); ?></td>
                                            <td>
                                                <?php if ($row['donor_name']): ?>
                                                    <?php echo htmlspecialchars($row['donor_name']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">From Blood Bank</span>
                                                <?php endif; ?>
                                            </td>
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
                                                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to approve this blood request? This will reduce the blood stock.')">
                                                                <i class="fas fa-check"></i> Approve
                                                            </button>
                                                        </form>
                                                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                                            <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                                                            <input type="hidden" name="action" value="reject">
                                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to reject this blood request?')">
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
                                        <td colspan="9" class="text-center">No blood requests found.</td>
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

<script>
$(document).ready(function() {
    // Add a pulsing effect to the notification badge for attention
    $('.badge-danger').addClass('pulse');
    
    // Highlight the rows with pending status
    $('tr.bg-light').hover(
        function() {
            $(this).addClass('bg-danger-light');
        },
        function() {
            $(this).removeClass('bg-danger-light');
        }
    );
});
</script>

<style>
.pulse {
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
    }
}

.bg-danger-light {
    background-color: rgba(220, 53, 69, 0.1) !important;
}
</style>

<?php require_once '../includes/footer.php'; ?>