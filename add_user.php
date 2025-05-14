<?php
require_once '../includes/header.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

$errors = [];
$success_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $contact_number = trim($_POST['contact_number']);
    $nid_number = trim($_POST['nid_number']);
    $address = trim($_POST['address']);
    $user_type = $_POST['user_type'];
    $is_donor = isset($_POST['is_donor']) ? 1 : 0;
    $is_recipient = isset($_POST['is_recipient']) ? 1 : 0;
    
    // Basic validation
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
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
    
    // Check if email already exists
    $email = mysqli_real_escape_string($conn, $email);
    $email_check_query = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $email_check_query);
    $user = mysqli_fetch_assoc($result);
    
    if ($user) {
        $errors[] = "Email already exists";
    }
    
    // Check if NID already exists
    $nid_number = mysqli_real_escape_string($conn, $nid_number);
    $nid_check_query = "SELECT * FROM users WHERE nid_number = '$nid_number' LIMIT 1";
    $result = mysqli_query($conn, $nid_check_query);
    $user = mysqli_fetch_assoc($result);
    
    if ($user) {
        $errors[] = "NID number already exists";
    }
    
    // If no errors, register user
    if (empty($errors)) {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Sanitize inputs
            $full_name = mysqli_real_escape_string($conn, $full_name);
            $contact_number = mysqli_real_escape_string($conn, $contact_number);
            $address = mysqli_real_escape_string($conn, $address);
            
            // Insert user data
            $query = "INSERT INTO users (full_name, email, password, contact_number, nid_number, address, user_type) 
                    VALUES ('$full_name', '$email', '$hashed_password', '$contact_number', '$nid_number', '$address', '$user_type')";
            
            if (!mysqli_query($conn, $query)) {
                throw new Exception(mysqli_error($conn));
            }
            
            // Get the user_id of the newly registered user
            $user_id = mysqli_insert_id($conn);
            
            // Insert user roles
            $role_query = "INSERT INTO user_roles (user_id, is_donor, is_recipient) 
                          VALUES ($user_id, $is_donor, $is_recipient)";
            
            if (!mysqli_query($conn, $role_query)) {
                throw new Exception(mysqli_error($conn));
            }
            
            // Create a welcome notification
            $notification_content = "Welcome to the Blood Bank Management System! Your account has been created by an administrator.";
            $notification_query = "INSERT INTO notifications (user_id, content) VALUES ($user_id, '$notification_content')";
            
            if (!mysqli_query($conn, $notification_query)) {
                throw new Exception(mysqli_error($conn));
            }
            
            // Commit the transaction
            mysqli_commit($conn);
            
            $success_message = "User created successfully!";
            
            // Clear form data after successful submission
            $full_name = $email = $contact_number = $nid_number = $address = '';
            $user_type = 'user';
            $is_donor = $is_recipient = 0;
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
                        <i class="fas fa-user-plus mr-2"></i> Add New User
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
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="full_name">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <small class="form-text text-muted">Password must be at least 6 characters long.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contact_number">Contact Number</label>
                                    <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?php echo isset($contact_number) ? htmlspecialchars($contact_number) : ''; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="nid_number">NID Number</label>
                                    <input type="text" class="form-control" id="nid_number" name="nid_number" value="<?php echo isset($nid_number) ? htmlspecialchars($nid_number) : ''; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3" required><?php echo isset($address) ? htmlspecialchars($address) : ''; ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="user_type">User Type</label>
                                    <select class="form-control" id="user_type" name="user_type" required>
                                        <option value="user" <?php if (isset($user_type) && $user_type == 'user') echo 'selected'; ?>>Regular User</option>
                                        <option value="admin" <?php if (isset($user_type) && $user_type == 'admin') echo 'selected'; ?>>Admin</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>User Roles</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_donor" name="is_donor" value="1" <?php if (isset($is_donor) && $is_donor) echo 'checked'; ?>>
                                        <label class="form-check-label" for="is_donor">
                                            Donor
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_recipient" name="is_recipient" value="1" <?php if (isset($is_recipient) && $is_recipient) echo 'checked'; ?>>
                                        <label class="form-check-label" for="is_recipient">
                                            Recipient
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-user-plus mr-1"></i> Create User
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>