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
$errors = [];
$success_message = '';

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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $contact_number = trim($_POST['contact_number']);
    $nid_number = trim($_POST['nid_number']);
    $address = trim($_POST['address']);
    $user_type = $_POST['user_type'];
    $is_donor = isset($_POST['is_donor']) ? 1 : 0;
    $is_recipient = isset($_POST['is_recipient']) ? 1 : 0;
    
    // Check if password should be updated
    $password_update = '';
    if (!empty($_POST['password'])) {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $password_update = ", password = '$hashed_password'";
        }
    }
    
    // Basic validation
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($contact_number)) {
        $errors[] = "Contact number is required";
    }
    
    if (empty($nid_number)) {
        $errors[] = "NID number is required";
    }
    
    if (empty($address)) {
        $errors[] = "Address is required";
    }
    
    // Check if email exists (excluding the current user)
    $email_check_query = "SELECT * FROM users WHERE email = '$email' AND user_id != $user_id LIMIT 1";
    $result = mysqli_query($conn, $email_check_query);
    $user_with_email = mysqli_fetch_assoc($result);
    
    if ($user_with_email) {
        $errors[] = "Email already exists";
    }
    
    // Check if NID number exists (excluding the current user)
    $nid_check_query = "SELECT * FROM users WHERE nid_number = '$nid_number' AND user_id != $user_id LIMIT 1";
    $result = mysqli_query($conn, $nid_check_query);
    $user_with_nid = mysqli_fetch_assoc($result);
    
    if ($user_with_nid) {
        $errors[] = "NID number already exists";
    }
    
    // If no errors, update user
    if (empty($errors)) {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Update user information
            $update_query = "UPDATE users SET 
                           full_name = '$full_name', 
                           email = '$email', 
                           contact_number = '$contact_number', 
                           nid_number = '$nid_number', 
                           address = '$address', 
                           user_type = '$user_type'
                           $password_update
                           WHERE user_id = $user_id";
            
            if (!mysqli_query($conn, $update_query)) {
                throw new Exception(mysqli_error($conn));
            }
            
            // Update user roles
            $role_check_query = "SELECT * FROM user_roles WHERE user_id = $user_id";
            $role_result = mysqli_query($conn, $role_check_query);
            
            if (mysqli_num_rows($role_result) > 0) {
                $role_update_query = "UPDATE user_roles SET 
                                     is_donor = $is_donor, 
                                     is_recipient = $is_recipient 
                                     WHERE user_id = $user_id";
                
                if (!mysqli_query($conn, $role_update_query)) {
                    throw new Exception(mysqli_error($conn));
                }
            } else {
                $role_insert_query = "INSERT INTO user_roles (user_id, is_donor, is_recipient) 
                                     VALUES ($user_id, $is_donor, $is_recipient)";
                
                if (!mysqli_query($conn, $role_insert_query)) {
                    throw new Exception(mysqli_error($conn));
                }
            }
            
            // If user is no longer a donor, update donor profile availability
            if (!$is_donor) {
                $profile_update_query = "UPDATE donor_profiles SET is_available = 0 WHERE user_id = $user_id";
                mysqli_query($conn, $profile_update_query);
            }
            
            // Commit the transaction
            mysqli_commit($conn);
            
            $success_message = "User updated successfully!";
            
            // Refresh user data
            $user_result = mysqli_query($conn, $user_query);
            $user = mysqli_fetch_assoc($user_result);
        } catch (Exception $e) {
            // Rollback the transaction if any query fails
            mysqli_rollback($conn);
            $errors[] = "Error: " . $e->getMessage();
        }
    }
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
                    <h2 class="mb-0">
                        <i class="fas fa-user-edit mr-2"></i> Edit User
                    </h2>
                    <a href="user_management.php" class="btn btn-light">
                        <i class="fas fa-arrow-left mr-1"></i> Back
                    </a>
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
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $user_id); ?>" method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="full_name">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="contact_number">Contact Number</label>
                                    <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($user['contact_number']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="nid_number">NID Number</label>
                                    <input type="text" class="form-control" id="nid_number" name="nid_number" value="<?php echo htmlspecialchars($user['nid_number']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="user_type">User Type</label>
                                    <select class="form-control" id="user_type" name="user_type" required>
                                        <option value="user" <?php if ($user['user_type'] == 'user') echo 'selected'; ?>>Regular User</option>
                                        <option value="admin" <?php if ($user['user_type'] == 'admin') echo 'selected'; ?>>Admin</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>User Roles</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_donor" name="is_donor" value="1" <?php if ($user['is_donor']) echo 'checked'; ?>>
                                        <label class="form-check-label" for="is_donor">
                                            Donor
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_recipient" name="is_recipient" value="1" <?php if ($user['is_recipient']) echo 'checked'; ?>>
                                        <label class="form-check-label" for="is_recipient">
                                            Recipient
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password">New Password (leave blank to keep current password)</label>
                                    <input type="password" class="form-control" id="password" name="password">
                                    <small class="form-text text-muted">Password must be at least 6 characters long.</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="confirm_password">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-save mr-1"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>