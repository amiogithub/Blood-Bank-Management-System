<?php
require_once '../includes/header.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: user_management.php");
    exit();
}

$user_id = $_GET['id'];

// Get user information
$user_query = "SELECT u.*, ur.is_donor, ur.is_recipient 
               FROM users u
               LEFT JOIN user_roles ur ON u.user_id = ur.user_id
               WHERE u.user_id = $user_id";
$user_result = mysqli_query($conn, $user_query);

if (mysqli_num_rows($user_result) === 0) {
    header("Location: user_management.php?status=error&message=User+not+found");
    exit();
}

$user = mysqli_fetch_assoc($user_result);

// Get donor profile if exists
$donor_profile = null;
if ($user['is_donor']) {
    $profile_query = "SELECT * FROM donor_profiles WHERE user_id = $user_id";
    $profile_result = mysqli_query($conn, $profile_query);
    
    if (mysqli_num_rows($profile_result) > 0) {
        $donor_profile = mysqli_fetch_assoc($profile_result);
    }
}

// Get donation history
$donations_query = "SELECT * FROM donation_logs WHERE user_id = $user_id ORDER BY donation_date DESC";
$donations_result = mysqli_query($conn, $donations_query);

// Get blood request history
$requests_query = "SELECT br.*, u.full_name as donor_name 
                  FROM blood_requests br
                  LEFT JOIN users u ON br.donor_id = u.user_id
                  WHERE br.recipient_id = $user_id 
                  ORDER BY br.request_date DESC";
$requests_result = mysqli_query($conn, $requests_query);

// Get user badges
$badges_query = "SELECT b.*, ub.earned_date 
                FROM badges b
                JOIN user_badges ub ON b.badge_id = ub.badge_id
                WHERE ub.user_id = $user_id
                ORDER BY ub.earned_date DESC";
$badges_result = mysqli_query($conn, $badges_query);

// Get profile picture
$picture_query = "SELECT * FROM profile_pictures WHERE user_id = $user_id";
$picture_result = mysqli_query($conn, $picture_query);
$has_profile_picture = mysqli_num_rows($picture_result) > 0;

if ($has_profile_picture) {
    $picture = mysqli_fetch_assoc($picture_result);
    $profile_picture_path = $picture['file_path'];
}

// Get user posts
$posts_query = "SELECT * FROM posts WHERE user_id = $user_id ORDER BY post_date DESC LIMIT 5";
$posts_result = mysqli_query($conn, $posts_query);
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
                    <h2 class="mb-0">
                        <i class="fas fa-user mr-2"></i> User Details
                    </h2>
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h2 class="mb-0">
                        <i class="fas fa-user mr-2"></i> User Details
                    </h2>
                    <div>
                        <a href="edit_user.php?id=<?php echo $user_id; ?>" class="btn btn-light mr-2">
                            <i class="fas fa-edit mr-1"></i> Edit
                        </a>
                        <a href="user_management.php" class="btn btn-light">
                            <i class="fas fa-arrow-left mr-1"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- User Profile Section -->
                        <div class="col-md-4 text-center mb-4">
                            <?php if ($has_profile_picture): ?>
                                <img src="<?php echo $profile_picture_path; ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 150px; height: 150px; font-size: 4rem;">
                                    <span><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                            
                            <div class="mb-3">
                                <?php if ($user['user_type'] == 'admin'): ?>
                                    <span class="badge badge-danger">Admin</span>
                                <?php else: ?>
                                    <span class="badge badge-info">User</span>
                                <?php endif; ?>
                                
                                <?php if ($user['is_donor']): ?>
                                    <span class="badge badge-success">Donor</span>
                                <?php endif; ?>
                                
                                <?php if ($user['is_recipient']): ?>
                                    <span class="badge badge-primary">Recipient</span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Badges Display -->
                            <?php if (mysqli_num_rows($badges_result) > 0): ?>
                                <div class="mt-3">
                                    <h5>Badges</h5>
                                    <div class="d-flex flex-wrap justify-content-center">
                                        <?php while ($badge = mysqli_fetch_assoc($badges_result)): ?>
                                            <div class="badge-icon m-1" title="<?php echo $badge['badge_name'] . ': ' . $badge['badge_description']; ?>" data-toggle="tooltip">
                                                <i class="<?php echo $badge['badge_icon']; ?> fa-2x"></i>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- User Details Section -->
                        <div class="col-md-8">
                            <h4 class="border-bottom pb-2 mb-3">Personal Information</h4>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($user['contact_number']); ?></p>
                                    <p><strong>NID Number:</strong> <?php echo htmlspecialchars($user['nid_number']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Address:</strong> <?php echo htmlspecialchars($user['address']); ?></p>
                                    <p><strong>Registration Date:</strong> <?php echo date('F j, Y', strtotime($user['registration_date'])); ?></p>
                                </div>
                            </div>
                            
                            <?php if ($donor_profile): ?>
                                <h4 class="border-bottom pb-2 mb-3">Donor Information</h4>
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <p><strong>Blood Group:</strong> <span class="badge badge-danger"><?php echo $donor_profile['blood_group']; ?></span></p>
                                        <p><strong>Gender:</strong> <?php echo ucfirst($donor_profile['gender']); ?></p>
                                        <p><strong>Smoker:</strong> <?php echo ucfirst($donor_profile['smoker']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Last Donation:</strong> <?php echo $donor_profile['last_donation_date'] ? date('F j, Y', strtotime($donor_profile['last_donation_date'])) : 'No records'; ?></p>
                                        <p><strong>Is Available:</strong> <?php echo $donor_profile['is_available'] ? 'Yes' : 'No'; ?></p>
                                        <p><strong>Medical Info:</strong> <?php echo htmlspecialchars($donor_profile['medical_info'] ?: 'No information provided'); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Activity Section -->
                    <div class="row mt-4">
                        <!-- Donation History -->
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-tint mr-2"></i> Donation History</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (mysqli_num_rows($donations_result) > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Blood Group</th>
                                                        <th>Quantity</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($donation = mysqli_fetch_assoc($donations_result)): ?>
                                                        <tr>
                                                            <td><?php echo date('M j, Y', strtotime($donation['donation_date'])); ?></td>
                                                            <td><span class="badge badge-danger"><?php echo $donation['blood_group']; ?></span></td>
                                                            <td><?php echo $donation['quantity_ml']; ?> ml</td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted text-center">No donation records found.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Blood Requests -->
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-procedures mr-2"></i> Blood Request History</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (mysqli_num_rows($requests_result) > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Blood</th>
                                                        <th>Quantity</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($request = mysqli_fetch_assoc($requests_result)): ?>
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
                                        <p class="text-muted text-center">No blood request records found.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Posts -->
                    <div class="card mt-2">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-comments mr-2"></i> Recent Posts</h5>
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($posts_result) > 0): ?>
                                <div class="list-group">
                                    <?php while ($post = mysqli_fetch_assoc($posts_result)): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <p class="mb-1"><?php echo htmlspecialchars(substr($post['content'], 0, 150)) . (strlen($post['content']) > 150 ? '...' : ''); ?></p>
                                                <small class="text-muted"><?php echo date('M j, Y', strtotime($post['post_date'])); ?></small>
                                            </div>
                                            <a href="../pages/view_post.php?id=<?php echo $post['post_id']; ?>" class="btn btn-sm btn-outline-info mt-2">View Post</a>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center">No posts found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.badge-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background-color: #f8f9fa;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
</style>

<!-- Initialize tooltips -->
<script>
$(function () {
    $('[data-toggle="tooltip"]').tooltip();
});
</script>

<?php require_once '../includes/footer.php'; ?>