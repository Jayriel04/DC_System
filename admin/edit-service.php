<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
    exit();
}

if (!isset($_GET['editid'])) {
    echo "<script>alert('No service selected');window.location.href='manage-service.php';</script>";
    exit();
}

$number = intval($_GET['editid']);

// Fetch service data
$sql = "SELECT * FROM tblservice WHERE number = :number";
$query = $dbh->prepare($sql);
$query->bindParam(':number', $number, PDO::PARAM_INT);
$query->execute();
$service = $query->fetch(PDO::FETCH_OBJ);

if (!$service) {
    echo "<script>alert('Service not found');window.location.href='manage-service.php';</script>";
    exit();
}

// Handle form submission
if (isset($_POST['submit'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $image = $service->image;

    // Handle image upload if a new image is provided
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "images/services/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image = $target_dir . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $image);
    }

    $updateSql = "UPDATE tblservice SET name=:name, description=:description, image=:image WHERE number=:number";
    $updateQuery = $dbh->prepare($updateSql);
    $updateQuery->bindParam(':name', $name, PDO::PARAM_STR);
    $updateQuery->bindParam(':description', $description, PDO::PARAM_STR);
    $updateQuery->bindParam(':image', $image, PDO::PARAM_STR);
    $updateQuery->bindParam(':number', $number, PDO::PARAM_INT);

    if ($updateQuery->execute()) {
        echo "<script>alert('Service updated successfully');window.location.href='manage-service.php';</script>";
        exit();
    } else {
        echo "<script>alert('Update failed. Please try again.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Service</title>
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
                    <h3 class="page-title">Edit Service</h3>
                </div>
                <div class="row">
                    <div class="col-md-8 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label for="name">Service Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlentities($service->name); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlentities($service->description); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="image">Image</label><br>
                                        <?php if ($service->image) { ?>
                                            <img src="<?php echo htmlentities($service->image); ?>" alt="Service Image" style="width:100px;height:auto;"><br>
                                        <?php } ?>
                                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                        <small class="form-text text-muted">Leave blank to keep current image.</small>
                                    </div>
                                    <button type="submit" name="submit" class="btn btn-primary">Update Service</button>
                                    <a href="manage-service.php" class="btn btn-secondary">Cancel</a>
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