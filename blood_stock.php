<?php
require_once '../includes/header.php';

// Get blood stock data
$stock_query = "SELECT * FROM blood_stock ORDER BY blood_group";
$stock_result = mysqli_query($conn, $stock_query);
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h2 class="mb-0"><i class="fas fa-tint mr-2"></i> Blood Stock Availability</h2>
                </div>
                <div class="card-body">
                    <p class="lead">Current blood inventory at our blood bank:</p>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered blood-stock-table">
                            <thead>
                                <tr>
                                    <th>Blood Group</th>
                                    <th>Quantity Available (ml)</th>
                                    <th>Status</th>
                                    <th>Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($stock = mysqli_fetch_assoc($stock_result)): ?>
                                    <tr>
                                        <td class="font-weight-bold">
                                            <span class="badge badge-danger mr-2"><?php echo $stock['blood_group']; ?></span>
                                            <?php echo $stock['blood_group']; ?>
                                        </td>
                                        <td><?php echo $stock['quantity_ml']; ?> ml</td>
                                        <td>
                                            <?php if ($stock['quantity_ml'] > 1000): ?>
                                                <span class="badge badge-success">Sufficient</span>
                                            <?php elseif ($stock['quantity_ml'] > 500): ?>
                                                <span class="badge badge-warning">Moderate</span>
                                            <?php elseif ($stock['quantity_ml'] > 0): ?>
                                                <span class="badge badge-danger">Low</span>
                                            <?php else: ?>
                                                <span class="badge badge-dark">Not Available</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('F j, Y, g:i a', strtotime($stock['last_updated'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="mt-4">
                            <?php if ($_SESSION['user_type'] == 'donor'): ?>
                                <a href="donation_request.php" class="btn btn-danger">
                                    <i class="fas fa-hand-holding-heart mr-2"></i> Donate Blood
                                </a>
                            <?php else: ?>
                                <a href="blood_request.php" class="btn btn-danger">
                                    <i class="fas fa-tint mr-2"></i> Request Blood
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mt-4">
                            <i class="fas fa-info-circle mr-2"></i> 
                            Please <a href="login.php" class="alert-link">login</a> or <a href="register.php" class="alert-link">register</a> to request or donate blood.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header bg-danger text-white">
                    <h3 class="mb-0">Blood Compatibility Chart</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Blood Type</th>
                                    <th>Can Donate To</th>
                                    <th>Can Receive From</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="badge badge-danger">A+</span></td>
                                    <td>A+, AB+</td>
                                    <td>A+, A-, O+, O-</td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-danger">A-</span></td>
                                    <td>A+, A-, AB+, AB-</td>
                                    <td>A-, O-</td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-danger">B+</span></td>
                                    <td>B+, AB+</td>
                                    <td>B+, B-, O+, O-</td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-danger">B-</span></td>
                                    <td>B+, B-, AB+, AB-</td>
                                    <td>B-, O-</td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-danger">AB+</span></td>
                                    <td>AB+</td>
                                    <td>All Blood Types</td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-danger">AB-</span></td>
                                    <td>AB+, AB-</td>
                                    <td>A-, B-, AB-, O-</td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-danger">O+</span></td>
                                    <td>A+, B+, AB+, O+</td>
                                    <td>O+, O-</td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-danger">O-</span></td>
                                    <td>All Blood Types</td>
                                    <td>O-</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header bg-danger text-white">
                    <h3 class="mb-0">Blood Facts</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <ul>
                                <li>Blood makes up about 7% of your body's weight.</li>
                                <li>There are four main blood types: A, B, AB, and O.</li>
                                <li>One pint of blood can save up to three lives.</li>
                                <li>A single car accident victim can require as many as 100 units of blood.</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul>
                                <li>O- is the universal donor blood type.</li>
                                <li>AB+ is the universal recipient blood type.</li>
                                <li>Red blood cells can be stored for up to 42 days.</li>
                                <li>Platelets must be used within 5 days of donation.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>