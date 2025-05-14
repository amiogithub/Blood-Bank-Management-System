<?php
require_once '../includes/header.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle donor status toggle (available/unavailable)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_status'])) {
    $profile_id = $_POST['profile_id'];
    $current_status = $_POST['current_status'];
    $new_status = $current_status == '1' ? 0 : 1;
    
    $update_query = "UPDATE donor_profiles SET is_available = $new_status WHERE profile_id = $profile_id";
    
    if (mysqli_query($conn, $update_query)) {
        $_SESSION['success_message'] = "Donor status updated successfully";
    } else {
        $_SESSION['error_message'] = "Error updating donor status: " . mysqli_error($conn);
    }
    
    // Redirect to prevent form resubmission
    header("Location: donor_management.php");
    exit();
}

// Get filter parameters
$blood_group_filter = isset($_GET['blood_group']) ? $_GET['blood_group'] : 'all';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query based on filters
$query = "SELECT dp.*, u.full_name, u.contact_number, u.email, u.address,
         (SELECT COUNT(*) FROM donation_logs WHERE user_id = dp.user_id) as donation_count
         FROM donor_profiles dp
         JOIN users u ON dp.user_id = u.user_id";

$where_clauses = [];

if ($blood_group_filter != 'all') {
    $where_clauses[] = "dp.blood_group = '$blood_group_filter'";
}

if ($status_filter != 'all') {
    $where_clauses[] = "dp.is_available = " . ($status_filter == 'available' ? "1" : "0");
}

if (!empty($search_term)) {
    $where_clauses[] = "(u.full_name LIKE '%$search_term%' OR u.contact_number LIKE '%$search_term%' OR u.address LIKE '%$search_term%')";
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " ORDER BY u.full_name";

$result = mysqli_query($conn, $query);

// Get all blood groups for filter dropdown
$blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
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
                        <i class="fas fa-users-cog mr-2"></i> User Management
                    </a>
                    <a href="donor_management.php" class="list-group-item list-group-item-action active">
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
                    <h2 class="mb-0"><i class="fas fa-user-plus mr-2"></i> Donor Management</h2>
                    <a href="add_user.php" class="btn btn-light">
                        <i class="fas fa-user-plus mr-1"></i> Add New User
                    </a>
                </div>
                <div class="card-body">
                    <!-- Success/Error Messages -->
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
                    
                    <!-- Filters -->
                    <div class="mb-4">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="mb-3">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="blood_group">Blood Group</label>
                                        <select class="form-control" id="blood_group" name="blood_group">
                                            <option value="all" <?php if ($blood_group_filter == 'all') echo 'selected'; ?>>All Blood Groups</option>
                                            <?php foreach ($blood_groups as $group): ?>
                                                <option value="<?php echo $group; ?>" <?php if ($blood_group_filter == $group) echo 'selected'; ?>>
                                                    <?php echo $group; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="all" <?php if ($status_filter == 'all') echo 'selected'; ?>>All Status</option>
                                            <option value="available" <?php if ($status_filter == 'available') echo 'selected'; ?>>Available</option>
                                            <option value="unavailable" <?php if ($status_filter == 'unavailable') echo 'selected'; ?>>Unavailable</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="search">Search</label>
                                        <input type="text" class="form-control" id="search" name="search" placeholder="Name, phone, or address" value="<?php echo htmlspecialchars($search_term); ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-danger btn-block">
                                            <i class="fas fa-search mr-1"></i> Filter
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Donors Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Name</th>
                                    <th>Blood Group</th>
                                    <th>Contact</th>
                                    <th>Gender</th>
                                    <th>Medical Info</th>
                                    <th>Last Donation</th>
                                    <th>Donations</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                            <td>
                                                <span class="badge badge-danger"><?php echo $row['blood_group']; ?></span>
                                            </td>
                                            <td>
                                                <i class="fas fa-phone-alt text-muted mr-1"></i> <?php echo htmlspecialchars($row['contact_number']); ?><br>
                                                <i class="fas fa-envelope text-muted mr-1"></i> <?php echo htmlspecialchars($row['email']); ?>
                                            </td>
                                            <td><?php echo ucfirst($row['gender']); ?></td>
                                            <td>
                                                <?php if (!empty($row['medical_info'])): ?>
                                                    <div style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($row['medical_info']); ?>">
                                                        <?php echo htmlspecialchars($row['medical_info']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Not provided</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($row['last_donation_date']): ?>
                                                    <?php echo date('M j, Y', strtotime($row['last_donation_date'])); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Never</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-info"><?php echo $row['donation_count']; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($row['is_available']): ?>
                                                    <span class="badge badge-success">Available</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Unavailable</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="view_donor.php?id=<?php echo $row['user_id']; ?>" class="btn btn-info" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_donor.php?id=<?php echo $row['user_id']; ?>" class="btn btn-warning" title="Edit Donor">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="d-inline">
                                                        <input type="hidden" name="profile_id" value="<?php echo $row['profile_id']; ?>">
                                                        <input type="hidden" name="current_status" value="<?php echo $row['is_available']; ?>">
                                                        <button type="submit" name="toggle_status" class="btn <?php echo $row['is_available'] ? 'btn-secondary' : 'btn-success'; ?>" title="<?php echo $row['is_available'] ? 'Mark as Unavailable' : 'Mark as Available'; ?>" onclick="return confirm('Are you sure you want to change this donor\'s status?')">
                                                            <i class="fas <?php echo $row['is_available'] ? 'fa-toggle-off' : 'fa-toggle-on'; ?>"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No donors found matching your criteria.</td>
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