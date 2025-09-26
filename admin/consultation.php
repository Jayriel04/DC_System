<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
} else {
    if (isset($_POST['submit'])) {
        
        $firstname = $_POST['firstname'];
        $surname = $_POST['surname'];
        $date_of_birth = $_POST['date_of_birth'];
        $dateOfBirth = new DateTime($date_of_birth);
        $today = new DateTime();
        $age = $today->diff($dateOfBirth)->y;
        $sex = $_POST['sex'];              
        $status = $_POST['status'];
        $contact_number = $_POST['contact_number'];
        $address = $_POST['address'];
        $occupation = $_POST['occupation'];

        $sql = "INSERT INTO tblpatient (firstname, surname, date_of_birth, age, sex, status, contact_number, address, occupation) 
                VALUES (:firstname, :surname, :date_of_birth, :age, :sex, :status, :contact_number, :address, :occupation)";
        
        $query = $dbh->prepare($sql);
        $query->bindParam(':firstname', $firstname, PDO::PARAM_STR);
        $query->bindParam(':surname', $surname, PDO::PARAM_STR);
        $query->bindParam(':date_of_birth', $date_of_birth, PDO::PARAM_STR);
        $query->bindParam(':age', $age, PDO::PARAM_INT);
        $query->bindParam(':sex', $sex, PDO::PARAM_STR);               // Bind sex
        $query->bindParam(':status', $status, PDO::PARAM_STR);         // Bind status
        $query->bindParam(':contact_number', $contact_number, PDO::PARAM_STR);
        $query->bindParam(':address', $address, PDO::PARAM_STR);
        $query->bindParam(':occupation', $occupation, PDO::PARAM_STR); // Bind occupation

        if ($query->execute()) {
            echo "<script>alert('Patient record added successfully');</script>";
            echo "<script>window.location.href = 'manage-patient.php';</script>";
        } else {
            echo "<script>alert('Something went wrong. Please try again.');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Add Patient</title>
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php'); ?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php'); ?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title">Examination Record</h3>
                    </div>
                    <div class="row">
                        <div class="col-md-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="form-group">
                                            <label for="firstname">First Name</label>
                                            <input type="text" class="form-control" id="firstname" name="firstname" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="surname">Surname</label>
                                            <input type="text" class="form-control" id="surname" name="surname" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="date_of_birth">Date of Birth</label>
                                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                                        </div>                                       
                                        <div class="form-group">
                                            <label for="sex">Sex</label>
                                            <select class="form-control" id="sex" name="sex" required>
                                                <option value=""></option>
                                                <option value="Female">Female</option>
                                                <option value="Male">Male</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="status">Status</label>
                                            <select class="form-control" id="status" name="status" required>
                                                <option value=""></option>
                                                <option value="Single">Single</option>
                                                <option value="Married">Married</option>
                                                <option value="Widowed">Widowed</option>
                                                <option value="Separated">Separated</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="contact_number">Contact Number</label>
                                            <input type="text" class="form-control" id="contact_number" name="contact_number" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="address">Address</label>
                                            <textarea class="form-control" id="address" name="address" required></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="occupation">Occupation</label>
                                            <input type="text" class="form-control" id="occupation" name="occupation" required>
                                        </div>
                                        <button type="submit" name="submit" class="btn btn-primary">Add Patient</button>
                                        <a href="manage-patient.php" class="btn btn-secondary">Cancel</a>
                                    </form>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php include_once('includes/footer.php'); ?>
            </div>
        </div>
    </div>
    <script src="vendors/js/vendor.bundle.base.js"></script>
</body>

</html>