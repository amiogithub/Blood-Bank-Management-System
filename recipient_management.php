<?php
require_once '../includes/header.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Create recipient_profiles table if it doesn't exist
$check_table_query = "SHOW TABLES LIKE 'recipient_profiles'";
$table_result = mysqli_query($conn, $check_table_query);

if (mysqli_num_rows($table_result) == 0) {
    $create_table_query = "CREATE TABLE recipient_profiles (
        profile_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
        medical_history TEXT,
        preferred_hospital VARCHAR(255),
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    
    if (!mysqli_query($conn, $create_table_query)) {
        $_SESSION['error_message'] = "Error creating recipient_profiles table: " . mysqli_error($conn);
    }
}

// Get filter parameters
$blood_group_filter = isset($_GET['blood_group']) ? $_GET['blood_group'] : 'all';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query to get all recipients
$query = "SELECT u.user_id, u.full_name, u.email, u.contact_number, u.address, 
         rp.profile_id, rp.blood_group, rp.medical_history, rp.preferred_hospital,
         (SELECT COUNT(*) FROM blood_requests WHERE recipient_id = u.user_id) as request_count
         FROM users u
         JOIN user_roles ur ON u.user_id = ur.user_id
         LEFT JOIN recipient_profiles rp ON u.user_id = rp.user_id
         WHERE ur.is_recipient = 1";

if ($blood_group_filter != 'all' && !empty($blood_group_filter)) {
    $query .= " AND rp.blood_group = '$blood_group_filter'";
}

if (!empty($search_term)) {
    $query .= " AND (u.full_name LIKE '%$search_term%' OR u.email LIKE '%$search_term%' OR u.contact_number LIKE '%$search_term%' OR u.address LIKE '%$search_term%')";
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
                    <a href="donor_management.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-plus mr-2"></i> Donor Management
                    </a>
                    <a href="recipient_management.php" class="list-group-item list-group-item-action active">
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
                    <h2 class="mb-0"><i class="fas fa-user mr-2"></i> Recipient Management</h2>
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
                                <div class="col-md-4">
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
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="search">Search</label>
                                        <input type="text" class="form-control" id="search" name="search" placeholder="Name, email, phone, or address" value="<?php echo htmlspecialchars($search_term); ?>">
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
                    
                    <!-- Recipients Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Name</th>
                                    <th>Contact Information</th>
                                    <th>Blood Group</th>
                                    <th>Medical History</th>
                                    <th>Preferred Hospital</th>
                                    <th>Request Count</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                            <td>
                                                <i class="fas fa-phone-alt text-muted mr-1"></i> <?php echo htmlspecialchars($row['contact_number']); ?><br>
                                                <i class="fas fa-envelope text-muted mr-1"></i> <?php echo htmlspecialchars($row['email']); ?><br>
                                                <i class="fas fa-map-marker-alt text-muted mr-1"></i> <?php echo htmlspecialchars($row['address']); ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($row['blood_group'])): ?>
                                                    <span class="badge badge-danger"><?php echo $row['blood_group']; ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">Not specified</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($row['medical_history'])): ?>
                                                    <div style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($row['medical_history']); ?>">
                                                        <?php echo htmlspecialchars($row['medical_history']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Not provided</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($row['preferred_hospital'])): ?>
                                                    <?php echo htmlspecialchars($row['preferred_hospital']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not specified</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-info"><?php echo $row['request_count']; ?></span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="view_recipient.php?id=<?php echo $row['user_id']; ?>" class="btn btn-info" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_recipient.php?id=<?php echo $row['user_id']; ?>" class="btn btn-warning" title="Edit Recipient">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="recipient_requests.php?id=<?php echo $row['user_id']; ?>" class="btn btn-primary" title="View Requests">
                                                        <i class="fas fa-list"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No recipients found matching your criteria.</td>
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