<?php
session_start();
include('includes/dbconnection.php');

// Updated session check for tblpatient
if (strlen($_SESSION['sturecmsnumber']) == 0) {
    header('location:logout.php');
} else {
    // Fetch current user data
    $number = $_SESSION['sturecmsnumber'];
    $sql = "SELECT firstname, surname, date_of_birth, contact_number, address, username, image FROM tblpatient WHERE number = :number";
    $query = $dbh->prepare($sql);
    $query->bindParam(':number', $number, PDO::PARAM_STR);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_OBJ);
    $age = '';

    // Calculate age based on date of birth
    if ($result) {
        $dob = new DateTime($result->date_of_birth);
        $today = new DateTime();
        $age = $today->diff($dob)->y; // Calculate age in years
    }

    if (isset($_POST['update'])) {
        // Handle the form submission
        $firstname = $_POST['firstname'];
        $surname = $_POST['surname'];
        $date_of_birth = $_POST['date_of_birth'];
        $contact_number = $_POST['contact_number'];
        $address = $_POST['address'];
        $username = $_POST['username'];
        $image = $_FILES['image']['name'];

        // Handle image upload
        if (!empty($image)) {
            $target_dir = "../admin/images/";
            $target_file = $target_dir . basename($image);
            move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
        } else {
            $image = $result->image; // Keep the old image if no new one is uploaded
        }

        // Update the database
        $updateSql = "UPDATE tblpatient SET 
            firstname = :firstname, 
            surname = :surname, 
            date_of_birth = :date_of_birth, 
            contact_number = :contact_number, 
            address = :address, 
            username = :username, 
            image = :image 
            WHERE number = :number";
        
        $updateQuery = $dbh->prepare($updateSql);
        $updateQuery->bindParam(':firstname', $firstname, PDO::PARAM_STR);
        $updateQuery->bindParam(':surname', $surname, PDO::PARAM_STR);
        $updateQuery->bindParam(':date_of_birth', $date_of_birth, PDO::PARAM_STR);
        $updateQuery->bindParam(':contact_number', $contact_number, PDO::PARAM_STR);
        $updateQuery->bindParam(':address', $address, PDO::PARAM_STR);
        $updateQuery->bindParam(':username', $username, PDO::PARAM_STR);
        $updateQuery->bindParam(':image', $image, PDO::PARAM_STR);
        $updateQuery->bindParam(':number', $number, PDO::PARAM_STR);

        if ($updateQuery->execute()) {
            echo "<script>alert('Profile updated successfully.');</script>";
            echo "<script>window.location.href='view-profile.php';</script>";
        } else {
            echo "<script>alert('Error updating profile. Please try again.');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Management System || Update Profile</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .update-container {
            max-width: 800px;
            margin: auto;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .update-header {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-scroller">
        <!-- Navbar -->
        <?php include_once('includes/header.php'); ?>
        <!-- Sidebar -->
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php'); ?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="update-container">
                        <div class="update-header">
                            <h3>Update Profile</h3>
                        </div>
                        <form method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="firstname">First Name</label>
                                <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo htmlentities($result->firstname); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="surname">Surname</label>
                                <input type="text" class="form-control" id="surname" name="surname" value="<?php echo htmlentities($result->surname); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?php echo htmlentities($result->date_of_birth); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Age</label>
                                <input type="text" class="form-control" value="<?php echo $age; ?>" disabled>
                                <small class="form-text text-muted">Age will be calculated automatically based on date of birth.</small>
                            </div>
                            <div class="form-group">
                                <label for="contact_number">Contact Number</label>
                                <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?php echo htmlentities($result->contact_number); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea class="form-control" id="address" name="address" required><?php echo htmlentities($result->address); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlentities($result->username); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="image">Profile Picture</label>
                                <input type="file" class="form-control" id="image" name="image">
                                <small class="form-text text-muted">Leave blank to keep the current image.</small>
                            </div>
                            <button type="submit" name="update" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>
                <!-- Footer -->
                <?php include_once('includes/footer.php'); ?>
            </div>
        </div>
    </div>
    <script src="vendors/js/vendor.bundle.base.js"></script>
</body>
</html>