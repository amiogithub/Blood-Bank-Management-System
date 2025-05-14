<footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="fas fa-heartbeat mr-2"></i>Blood Bank</h5>
                    <p>Saving lives through blood donation.</p>
                    <p>
                        <i class="fas fa-envelope mr-2"></i> contact@bloodbank.com<br>
                        <i class="fas fa-phone mr-2"></i> Emergency: +880 1234-567890
                    </p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a class="text-white" href="/BloodBankSystem/index.php">Home</a></li>
                        <li><a class="text-white" href="/BloodBankSystem/pages/about.php">About Us</a></li>
                        <li><a class="text-white" href="/BloodBankSystem/pages/looking_for_blood.php">Looking for Blood</a></li>
                        <li><a class="text-white" href="/BloodBankSystem/pages/donate_blood.php">Want to Donate Blood</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Our Services</h5>
                    <ul class="list-unstyled">
                        <li><a class="text-white" href="/BloodBankSystem/pages/blood_stock.php">Blood Availability Search</a></li>
                        <li><a class="text-white" href="/BloodBankSystem/pages/donor_list.php">Blood Bank Directory</a></li>
                        <li><a class="text-white" href="#">Blood Donation Camps</a></li>
                        <li><a class="text-white" href="/BloodBankSystem/pages/register.php">Register Voluntary Blood Camp</a></li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>© <?php echo date('Y'); ?> Blood Bank Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="/BloodBankSystem/js/script.js"></script>
</body>
</html>
<?php
ob_end_flush(); // Send the contents of the output buffer
?>