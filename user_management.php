<?php
require_once '../includes/header.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    
    // Don't allow admin to delete themselves
    if ($user_id == $_SESSION['user_id']) {
        header("Location: user_management.php?status=error&message=You+cannot+delete+your+own+account");
        exit();
    }
    
    // Start transaction to ensure all related records are deleted
    mysqli_begin_transaction($conn);
    
    try {
        // The foreign key constraints with ON DELETE CASCADE will automatically
        // delete related records from these tables
        
        // Delete the user
        $delete_query = "DELETE FROM users WHERE user_id = $user_id";
        if (!mysqli_query($conn, $delete_query)) {
            throw new Exception(mysqli_error($conn));
        }
        
        // Commit the transaction
        mysqli_commit($conn);
        
        // Redirect with success message
        header("Location: user_management.php?status=success&message=User+deleted+successfully");
        exit();
    } catch (Exception $e) {
        // Rollback the transaction if any query fails
        mysqli_rollback($conn);
        
        // Redirect with error message
        header("Location: user_management.php?status=error&message=" . urlencode($e->getMessage()));
        exit();
    }
}

// Get user type filter
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';

// Build query based on filter
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM donation_logs WHERE user_id = u.user_id) as donations_count,
          (SELECT COUNT(*) FROM blood_request_logs WHERE recipient_id = u.user_id) as requests_count,
          ur.is_donor, ur.is_recipient
          FROM users u
          LEFT JOIN user_roles ur ON u.user_id = ur.user_id";

if ($type_filter == 'donor') {
    $query .= " WHERE ur.is_donor = 1";
} elseif ($type_filter == 'recipient') {
    $query .= " WHERE ur.is_recipient = 1";
} elseif ($type_filter == 'admin') {
    $query .= " WHERE u.user_type = 'admin'";
} elseif ($type_filter == 'user') {
    $query .= " WHERE u.user_type = 'user'";
}

$query .= " ORDER BY u.registration_date DESC";

$result = mysqli_query($conn, $query);

// Check for query errors
if (!$result) {
    $error_msg = "Error executing query: " . mysqli_error($conn);
    // You can handle this better, like storing in session and displaying later
}
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
                    <a href="user_management.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-users-cog mr-2"></i> User Management
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
                    <h2 class="mb-0"><i class="fas fa-users-cog mr-2"></i> User Management</h2>
                    <a href="add_user.php" class="btn btn-light">
                        <i class="fas fa-user-plus mr-2"></i> Add New User
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['status'])): ?>
                        <?php if ($_GET['status'] == 'success'): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="fas fa-check-circle mr-2"></i> <?php echo $_GET['message']; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        <?php elseif ($_GET['status'] == 'error'): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $_GET['message']; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if (isset($error_msg)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_msg; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Filters -->
                    <div class="mb-4">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="form-inline">
                            <div class="form-group mr-3">
                                <label for="type" class="mr-2">User Type:</label>
                                <select class="form-control" id="type" name="type" onchange="this.form.submit()">
                                    <option value="all" <?php if ($type_filter == 'all') echo 'selected'; ?>>All Users</option>
                                    <option value="user" <?php if ($type_filter == 'user') echo 'selected'; ?>>Regular Users</option>
                                    <option value="admin" <?php if ($type_filter == 'admin') echo 'selected'; ?>>Admins</option>
                                    <option value="donor" <?php if ($type_filter == 'donor') echo 'selected'; ?>>Donors</option>
                                    <option value="recipient" <?php if ($type_filter == 'recipient') echo 'selected'; ?>>Recipients</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Users Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Roles</th>
                                    <th>Activity</th>
                                    <th>Registered On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo $row['user_id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td>
                                                <?php if ($row['user_type'] == 'admin'): ?>
                                                    <span class="badge badge-danger">Admin</span>
                                                <?php else: ?>
                                                    <span class="badge badge-info">User</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($row['is_donor']): ?>
                                                    <span class="badge badge-success mr-1">Donor</span>
                                                <?php endif; ?>
                                                <?php if ($row['is_recipient']): ?>
                                                    <span class="badge badge-primary">Recipient</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small>
                                                    <i class="fas fa-tint text-danger mr-1"></i> <?php echo $row['donations_count']; ?> Donations<br>
                                                    <i class="fas fa-hand-holding-heart text-primary mr-1"></i> <?php echo $row['requests_count']; ?> Requests
                                                </small>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($row['registration_date'])); ?></td>
                                            <td>
                                                <a href="view_user.php?id=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-info mb-1" data-toggle="tooltip" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_user.php?id=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-warning mb-1" data-toggle="tooltip" title="Edit User">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <!-- Delete button with confirmation -->
                                                <?php if ($row['user_id'] != $_SESSION['user_id'] && $row['user_type'] != 'admin'): ?>
                                                    <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal<?php echo $row['user_id']; ?>" data-toggle="tooltip" title="Delete User">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                    
                                                    <!-- Delete Confirmation Modal -->
                                                    <div class="modal fade" id="deleteModal<?php echo $row['user_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog" role="document">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-danger text-white">
                                                                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                                                                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>Are you sure you want to delete the user <strong><?php echo htmlspecialchars($row['full_name']); ?></strong>?</p>
                                                                    <p class="text-danger"><strong>Warning:</strong> This action cannot be undone. All user data including donation history, requests, posts, and messages will be permanently deleted.</p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                                                        <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                                                        <button type="submit" name="delete_user" class="btn btn-danger">Delete User</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No users found.</td>
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

<!-- Initialize tooltips -->
<script>
$(function () {
    $('[data-toggle="tooltip"]').tooltip();
});
</script>

<?php require_once '../includes/footer.php'; ?>