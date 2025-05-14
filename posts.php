<?php
require_once '../includes/header.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle post deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_post'])) {
    $post_id = $_POST['post_id'];
    
    // Start transaction for clean deletion
    mysqli_begin_transaction($conn);
    
    try {
        // First delete related comments
        $delete_comments_query = "DELETE FROM comments WHERE post_id = $post_id";
        mysqli_query($conn, $delete_comments_query);
        
        // Delete the post
        $delete_query = "DELETE FROM posts WHERE post_id = $post_id";
        
        if (mysqli_query($conn, $delete_query)) {
            mysqli_commit($conn);
            $_SESSION['success_message'] = "Post deleted successfully";
        } else {
            throw new Exception(mysqli_error($conn));
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Error deleting post: " . $e->getMessage();
    }
    
    // Redirect to prevent form resubmission
    header("Location: posts.php");
    exit();
}

// Get filter parameters
$post_type = isset($_GET['post_type']) ? $_GET['post_type'] : 'all';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query based on filters
$query = "SELECT p.*, u.full_name, u.user_type, 
         (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comment_count
         FROM posts p
         JOIN users u ON p.user_id = u.user_id";

$where_clauses = [];

if ($post_type == 'blood_request') {
    $where_clauses[] = "(p.content LIKE '%need blood%' OR p.content LIKE '%looking for blood%' OR p.content LIKE '%require blood%')";
} elseif ($post_type == 'blood_donation') {
    $where_clauses[] = "(p.content LIKE '%donate blood%' OR p.content LIKE '%willing to donate%' OR p.content LIKE '%can donate%')";
} elseif ($post_type == 'emergency') {
    $where_clauses[] = "p.content LIKE '%EMERGENCY%'";
}

if (!empty($search_term)) {
    $search_term = mysqli_real_escape_string($conn, $search_term);
    $where_clauses[] = "(p.content LIKE '%$search_term%' OR u.full_name LIKE '%$search_term%')";
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " ORDER BY p.post_date DESC";

$result = mysqli_query($conn, $query);

// Check for errors in the query
if (!$result) {
    $_SESSION['error_message'] = "Error executing query: " . mysqli_error($conn);
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
                    <a href="user_management.php" class="list-group-item list-group-item-action">
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
                    <a href="posts.php" class="list-group-item list-group-item-action active">
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
                <div class="card-header bg-danger text-white">
                    <h2 class="mb-0"><i class="fas fa-comments mr-2"></i> Manage Posts</h2>
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
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="form-inline mb-3">
                            <div class="form-group mr-3">
                                <select class="form-control" name="post_type" id="post_type">
                                    <option value="all" <?php if ($post_type == 'all') echo 'selected'; ?>>All Posts</option>
                                    <option value="blood_request" <?php if ($post_type == 'blood_request') echo 'selected'; ?>>Blood Requests</option>
                                    <option value="blood_donation" <?php if ($post_type == 'blood_donation') echo 'selected'; ?>>Blood Donations</option>
                                    <option value="emergency" <?php if ($post_type == 'emergency') echo 'selected'; ?>>Emergency Alerts</option>
                                </select>
                            </div>
                            <div class="form-group mr-3">
                                <input type="text" class="form-control" name="search" id="search" placeholder="Search in content..." value="<?php echo htmlspecialchars($search_term); ?>">
                            </div>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-search mr-1"></i> Filter
                            </button>
                        </form>
                    </div>
                    
                    <!-- Posts Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>User</th>
                                    <th>Content</th>
                                    <th>Posted On</th>
                                    <th>Comments</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                                    <?php while ($post = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td>
                                                <div class="font-weight-bold"><?php echo htmlspecialchars($post['full_name']); ?></div>
                                                <small class="text-muted"><?php echo $post['user_type']; ?></small>
                                            </td>
                                            <td>
                                                <?php 
                                                    $is_emergency = strpos($post['content'], 'EMERGENCY') !== false;
                                                    $content_preview = htmlspecialchars(substr($post['content'], 0, 100)) . (strlen($post['content']) > 100 ? '...' : '');
                                                    if ($is_emergency) {
                                                        echo '<span class="badge badge-danger mr-1">EMERGENCY</span> ';
                                                    }
                                                    echo $content_preview;
                                                ?>
                                            </td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($post['post_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-info"><?php echo $post['comment_count']; ?></span>
                                            </td>
                                            <td>
                                                <a href="../pages/view_post.php?id=<?php echo $post['post_id']; ?>" class="btn btn-sm btn-info mb-1" title="View Post">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="d-inline">
                                                    <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                                    <button type="submit" name="delete_post" class="btn btn-sm btn-danger mb-1" title="Delete Post" onclick="return confirm('Are you sure you want to delete this post? This action cannot be undone.');">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No posts found.</td>
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