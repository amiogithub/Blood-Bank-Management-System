<?php 
require_once '../includes/header.php';

// Initialize variables
$full_name = $email = $contact_number = $nid_number = $address = '';
$errors = [];

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
    $email_check_query = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $email_check_query);
    $user = mysqli_fetch_assoc($result);
    
    if ($user) {
        $errors[] = "Email already exists";
    }
    
    // Check if NID already exists
    $nid_check_query = "SELECT * FROM users WHERE nid_number = '$nid_number' LIMIT 1";
    $result = mysqli_query($conn, $nid_check_query);
    $user = mysqli_fetch_assoc($result);
    
    if ($user) {
        $errors[] = "NID number already exists";
    }
    
    // If no errors, register user
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user data - set user_type to 'user' instead of donor/recipient
        $query = "INSERT INTO users (full_name, email, password, contact_number, nid_number, address, user_type) 
                VALUES ('$full_name', '$email', '$hashed_password', '$contact_number', '$nid_number', '$address', 'user')";
        
        if (mysqli_query($conn, $query)) {
            // Get the user_id of the newly registered user
            $user_id = mysqli_insert_id($conn);
            
            // Create a welcome notification
            $notification_content = "Welcome to the Blood Bank Management System! Thank you for registering.";
            $notification_query = "INSERT INTO notifications (user_id, content) VALUES ('$user_id', '$notification_content')";
            mysqli_query($conn, $notification_query);
            
            // Set session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $email;
            $_SESSION['user_type'] = 'user';
            
            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            $errors[] = "Database error: " . mysqli_error($conn);
        }
    }
}
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="form-container">
                <h2 class="text-center text-danger mb-4">Create an Account</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" onsubmit="return validateForm('registrationForm')" id="registrationForm">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="form-text text-muted">Password must be at least 6 characters long.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($contact_number); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="nid_number">NID Number</label>
                        <input type="text" class="form-control" id="nid_number" name="nid_number" value="<?php echo htmlspecialchars($nid_number); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Home Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($address); ?></textarea>
                    </div>
                    
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="terms" required>
                        <label class="form-check-label" for="terms">I agree to the terms and conditions</label>
                    </div>
                    
                    <button type="submit" class="btn btn-danger btn-block">Register</button>
                </form>
                
                <div class="text-center mt-3">
                    <p>Already have an account? <a href="login.php" class="text-danger">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>