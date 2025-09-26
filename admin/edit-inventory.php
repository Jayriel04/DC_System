<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
    exit();
}

if (!isset($_GET['editid'])) {
    echo "<script>alert('No inventory item selected');window.location.href='manage-inventory.php';</script>";
    exit();
}

$number = intval($_GET['editid']);

// Fetch inventory data
$sql = "SELECT * FROM tblinventory WHERE number = :number";
$query = $dbh->prepare($sql);
$query->bindParam(':number', $number, PDO::PARAM_INT);
$query->execute();
$item = $query->fetch(PDO::FETCH_OBJ);

if (!$item) {
    echo "<script>alert('Inventory item not found');window.location.href='manage-inventory.php';</script>";
    exit();
}

// Handle form submission
if (isset($_POST['submit'])) {
    $name = trim($_POST['name']);
    $brand = trim($_POST['brand']);
    $expiration_date = $_POST['expiration_date'];
    $quantity = intval($_POST['quantity']);
    $category = trim($_POST['category']);
    $status = trim($_POST['status']);

    $updateSql = "UPDATE tblinventory SET name=:name, brand=:brand, expiration_date=:expiration_date, quantity=:quantity, category=:category, status=:status WHERE number=:number";
    $updateQuery = $dbh->prepare($updateSql);
    $updateQuery->bindParam(':name', $name, PDO::PARAM_STR);
    $updateQuery->bindParam(':brand', $brand, PDO::PARAM_STR);
    $updateQuery->bindParam(':expiration_date', $expiration_date, PDO::PARAM_STR);
    $updateQuery->bindParam(':quantity', $quantity, PDO::PARAM_INT);
    $updateQuery->bindParam(':category', $category, PDO::PARAM_STR);
    $updateQuery->bindParam(':status', $status, PDO::PARAM_STR);
    $updateQuery->bindParam(':number', $number, PDO::PARAM_INT);

    if ($updateQuery->execute()) {
        echo "<script>alert('Inventory item updated successfully');window.location.href='manage-inventory.php';</script>";
        exit();
    } else {
        echo "<script>alert('Update failed. Please try again.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Inventory</title>
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container-scroller">
    <?php include_once('includes/header.php'); ?>
    <div class="container-fluid page-body-wrapper">
        <?php include_once('includes/sidebar.php'); ?>
        <div class="main-panel">
            <div class="content-wrapper">
                <div class="page-header">
                    <h3 class="page-title">Edit Inventory Item</h3>
                </div>
                <div class="row">
                    <div class="col-md-8 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <form method="POST">
                                    <div class="form-group">
                                        <label for="name">Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlentities($item->name); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="brand">Brand</label>
                                        <input type="text" class="form-control" id="brand" name="brand" value="<?php echo htmlentities($item->brand); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="expiration_date">Expiration Date</label>
                                        <input type="date" class="form-control" id="expiration_date" name="expiration_date" value="<?php echo htmlentities($item->expiration_date); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="quantity">Quantity</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" value="<?php echo htmlentities($item->quantity); ?>" min="0" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="category">Category</label>
                                        <select class="form-control" id="category" name="category" required>
                                            <option value="">Select Category</option>
                                            <option value="Supply" <?php if($item->category=="Supply") echo "selected"; ?>>Supply</option>
                                            <option value="Medicine" <?php if($item->category=="Medicine") echo "selected"; ?>>Medicine</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select class="form-control" id="status" name="status" readonly>
                                            <option value="">Select Status</option>
                                            <option value="Available" <?php if($item->status=="Available") echo "selected"; ?>>Available</option>
                                            <option value="Unavailable" <?php if($item->status=="Unavailable") echo "selected"; ?>>Unavailable</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="submit" class="btn btn-primary">Update Inventory</button>
                                    <a href="manage-inventory.php" class="btn btn-secondary">Cancel</a>
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