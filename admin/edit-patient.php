<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
    exit();
}

if (!isset($_GET['number'])) {
    echo "<script>alert('No patient selected');window.location.href='manage-patient.php';</script>";
    exit();
}

$number = intval($_GET['number']);

// Fetch patient data
$sql = "SELECT * FROM tblpatient WHERE number = :number";
$query = $dbh->prepare($sql);
$query->bindParam(':number', $number, PDO::PARAM_INT);
$query->execute();
$patient = $query->fetch(PDO::FETCH_OBJ);

if (!$patient) {
    echo "<script>alert('Patient not found');window.location.href='manage-patient.php';</script>";
    exit();
}

// Handle form submission
if (isset($_POST['submit'])) {
    $firstname = trim($_POST['firstname']);
    $surname = trim($_POST['surname']);
    $date_of_birth = $_POST['date_of_birth'];
    $age = intval($_POST['age']);
    $sex = $_POST['sex'];
    $status = $_POST['status'];
    $contact_number = trim($_POST['contact_number']);
    $address = trim($_POST['address']);
    $occupation = trim($_POST['occupation']);

    $updateSql = "UPDATE tblpatient SET firstname=:firstname, surname=:surname, date_of_birth=:date_of_birth, age=:age, sex=:sex, status=:status, contact_number=:contact_number, address=:address, occupation=:occupation WHERE number=:number";
    $updateQuery = $dbh->prepare($updateSql);
    $updateQuery->bindParam(':firstname', $firstname, PDO::PARAM_STR);
    $updateQuery->bindParam(':surname', $surname, PDO::PARAM_STR);
    $updateQuery->bindParam(':date_of_birth', $date_of_birth, PDO::PARAM_STR);
    $updateQuery->bindParam(':age', $age, PDO::PARAM_INT);
    $updateQuery->bindParam(':sex', $sex, PDO::PARAM_STR);
    $updateQuery->bindParam(':status', $status, PDO::PARAM_STR);
    $updateQuery->bindParam(':contact_number', $contact_number, PDO::PARAM_STR);
    $updateQuery->bindParam(':address', $address, PDO::PARAM_STR);
    $updateQuery->bindParam(':occupation', $occupation, PDO::PARAM_STR);
    $updateQuery->bindParam(':number', $number, PDO::PARAM_INT);

    if ($updateQuery->execute()) {
        echo "<script>alert('Patient updated successfully');window.location.href='manage-patient.php';</script>";
        exit();
    } else {
        echo "<script>alert('Update failed. Please try again.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Patient</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
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
                    <h3 class="page-title">Edit Patient</h3>
                </div>
                <div class="row">
                    <div class="col-md-8 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <form method="POST">
                                    <div class="form-group">
                                        <label for="firstname">First Name</label>
                                        <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo htmlentities($patient->firstname); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="surname">Surname</label>
                                        <input type="text" class="form-control" id="surname" name="surname" value="<?php echo htmlentities($patient->surname); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="date_of_birth">Date of Birth</label>
                                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?php echo htmlentities($patient->date_of_birth); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="age">Age</label>
                                        <input type="number" class="form-control" id="age" name="age" value="<?php echo htmlentities($patient->age); ?>" readonly>
                                    </div>
                                        <div class="form-group">
                                            <label for="sex">Sex</label>
                                            <select class="form-control" id="sex" name="sex" disabled>
                                                <option value="Male" <?php if($patient->sex=="Male") echo "selected"; ?>>Male</option>
                                                <option value="Female" <?php if($patient->sex=="Female") echo "selected"; ?>>Female</option>
                                                <option value="Other" <?php if($patient->sex=="Other") echo "selected"; ?>>Other</option>
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
                                        <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?php echo htmlentities($patient->contact_number); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="address">Address</label>
                                        <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlentities($patient->address); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="occupation">Occupation</label>
                                        <input type="text" class="form-control" id="occupation" name="occupation" value="<?php echo htmlentities($patient->occupation); ?>" >
                                    </div>
                                    <button type="submit" name="submit" class="btn btn-primary">Update Patient</button>
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