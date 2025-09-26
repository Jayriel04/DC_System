<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsaid'])==0) {
    header('location:logout.php');
} else {
    // Initialize search and filter variables
    $search = '';
    $category_filter = '';

    // Handle the search and category filter
    if (isset($_POST['search'])) {
        $search = $_POST['search'];
    }
    if (isset($_POST['category_filter'])) {
        $category_filter = $_POST['category_filter'];
    }

    // Code for deletion
    if (isset($_GET['delid'])) {
        $rid = intval($_GET['delid']);
        $sql = "DELETE FROM tblinventory WHERE number = :rid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':rid', $rid, PDO::PARAM_STR);
        $query->execute();
        echo "<script>alert('Data deleted');</script>";
        echo "<script>window.location.href = 'manage-inventory.php'</script>";
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Inventory</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="vendors/chartist/chartist.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script>
        function autoSubmit() {
            document.getElementById("filterForm").submit();
        }
    </script>
</head>
<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php');?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php');?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title">Manage Inventory</h3>
                    </div>

                    <?php
                    // Low stock alert for Supply
                    $lowStockSql = "SELECT name, quantity FROM tblinventory WHERE category = 'Supply' AND quantity <= 2";
                    $lowStockQuery = $dbh->prepare($lowStockSql);
                    $lowStockQuery->execute();
                    $lowStockItems = $lowStockQuery->fetchAll(PDO::FETCH_OBJ);
                    if (count($lowStockItems) > 0) {
                        echo '<div class="alert alert-danger" style="font-weight:bold;">';
                        echo '<span style="color:#721c24;">&#9888; <strong>Low Supply Stock Alert!</strong> The following supplies are low on stock: ';
                        $names = [];
                        foreach ($lowStockItems as $item) {
                            $names[] = htmlentities($item->name) . " (Qty: " . htmlentities($item->quantity) . ")";
                        }
                        echo implode(', ', $names);
                        echo '</span></div>';
                    }

                    // Low stock alert for Medicine
                    $lowMedSql = "SELECT name, quantity FROM tblinventory WHERE category = 'Medicine' AND quantity <= 2";
                    $lowMedQuery = $dbh->prepare($lowMedSql);
                    $lowMedQuery->execute();
                    $lowMedItems = $lowMedQuery->fetchAll(PDO::FETCH_OBJ);
                    if (count($lowMedItems) > 0) {
                        echo '<div class="alert alert-danger" style="font-weight:bold;">';
                        echo '<span style="color:#721c24;">&#9888; <strong>Low Medicine Stock Alert!</strong> The following medicines are low on stock: ';
                        $medNames = [];
                        foreach ($lowMedItems as $item) {
                            $medNames[] = htmlentities($item->name) . " (Qty: " . htmlentities($item->quantity) . ")";
                        }
                        echo implode(', ', $medNames);
                        echo '</span></div>';
                    }
                    ?>

                    <div class="row">
                        <div class="col-md-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-sm-flex align-items-center mb-4">
                                        <a href="add-inventory.php" class="btn btn-primary ml-auto mb-3 mb-sm-0">Add Inventory</a>
                                    </div>
                                    <form method="POST" class="form-inline mb-4" id="filterForm" onsubmit="return false;">
                                        <div class="form-group mr-3">
                                                <input type="text" class="form-control" name="search" placeholder="Search..." value="<?php echo htmlentities($search); ?>" onkeyup="if(event.key === 'Enter') autoSubmit()">
                                            </div>
                                            <div class="form-group mr-3">
                                            <select class="form-control" name="category_filter" onchange="autoSubmit()">
                                                <option value="">All Categories</option>
                                                <option value="Medicine" <?php if ($category_filter == "Medicine") echo 'selected'; ?>>Medicine</option>
                                                <option value="Supply" <?php if ($category_filter == "Supply") echo 'selected'; ?>>Supply</option>
                                            </select>
                                        </div>
                                    </form>

                                    <div class="table-responsive border rounded p-1">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th class="font-weight-bold">No</th>
                                                    <th class="font-weight-bold">Name</th>
                                                    <th class="font-weight-bold">Brand</th>
                                                    <th class="font-weight-bold">Expiration Date</th>
                                                    <th class="font-weight-bold">Quantity</th>
                                                    <th class="font-weight-bold">Category</th>
                                                    <th class="font-weight-bold">Status</th>
                                                    <th class="font-weight-bold">Stock Level</th>
                                                    <th class="font-weight-bold">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                if (isset($_GET['pageno'])) {
                                                    $pageno = $_GET['pageno'];
                                                } else {
                                                    $pageno = 1;
                                                }
                                                // Formula for pagination
                                                $no_of_records_per_page = 15;
                                                $offset = ($pageno - 1) * $no_of_records_per_page;

                                                // Build the query based on search and category filter
                                                $sql = "SELECT * FROM tblinventory WHERE 1=1";
                                                if ($search) {
                                                    $sql .= " AND (name LIKE :search OR brand LIKE :search)";
                                                }
                                                if ($category_filter) {
                                                    $sql .= " AND category = :category_filter";
                                                }
                                                $sql .= " LIMIT $offset, $no_of_records_per_page";

                                                $query = $dbh->prepare($sql);
                                                if ($search) {
                                                    $like_search = "%$search%";
                                                    $query->bindParam(':search', $like_search, PDO::PARAM_STR);
                                                }
                                                if ($category_filter) {
                                                    $query->bindParam(':category_filter', $category_filter, PDO::PARAM_STR);
                                                }
                                                $query->execute();
                                                $results = $query->fetchAll(PDO::FETCH_OBJ);

                                                // Get total rows for pagination
                                                $ret = "SELECT number FROM tblinventory WHERE 1=1";
                                                if ($search) {
                                                    $ret .= " AND (name LIKE :search OR brand LIKE :search)";
                                                }
                                                if ($category_filter) {
                                                    $ret .= " AND category = :category_filter";
                                                }
                                                $query1 = $dbh->prepare($ret);
                                                if ($search) {
                                                    $query1->bindParam(':search', $like_search, PDO::PARAM_STR);
                                                }
                                                if ($category_filter) {
                                                    $query1->bindParam(':category_filter', $category_filter, PDO::PARAM_STR);
                                                }
                                                $query1->execute();
                                                $total_rows = $query1->rowCount();
                                                $total_pages = ceil($total_rows / $no_of_records_per_page);

                                                $cnt = 1;
                                                if ($query->rowCount() > 0) {
                                                    foreach ($results as $row) { ?>   
                                                        <tr
                                                            <?php
                                                            // Highlighting logic for stock levels
                                                            if ($row->quantity == 0) {
                                                                echo 'style="background-color: #f8d7da;"'; // Red for out of stock
                                                            } elseif ($row->category == "Supply" && $row->quantity <= 2) {
                                                                echo 'style="background-color: #fff3cd;"'; // Yellow for low stock
                                                            } elseif ($row->category == "Medicine" && $row->quantity <= 2) {
                                                                echo 'style="background-color: #fff3cd;"'; // Yellow for low stock
                                                            }
                                                            ?>
                                                        >
                                                            <td><?php echo htmlentities($cnt);?></td>
                                                            <td><?php echo htmlentities($row->name);?></td>
                                                            <td><?php echo htmlentities($row->brand);?></td>
                                                            <td><?php echo htmlentities($row->expiration_date);?></td>
                                                            <td><?php echo htmlentities($row->quantity);?></td>
                                                            <td><?php echo htmlentities($row->category);?></td>
                                                            <td>
                                                                <?php
                                                                if ($row->quantity == 0) {
                                                                    echo '<span style="color:#d9534f;font-weight:bold;">Unavailable</span>';
                                                                } else {
                                                                    echo htmlentities($row->status);
                                                                }
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                if ($row->quantity == 0) {
                                                                    echo '<span style="color:#d9534f;font-weight:bold;">&#10060; Out of Stock</span>';
                                                                } elseif (($row->category == "Supply" && $row->quantity <= 2) || ($row->category == "Medicine" && $row->quantity <= 2)) {
                                                                    echo '<span style="color:#d9534f;font-weight:bold;" title="Low Stock">&#9888; Low Stock!</span>';
                                                                }
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <div>
                                                                    <a href="edit-inventory.php?editid=<?php echo htmlentities($row->number); ?>" class="btn btn-info btn-xs">Edit</a>
                                                                    <a href="manage-inventory.php?delid=<?php echo ($row->number); ?>" onclick="return confirm('Do you really want to Delete ?');" class="btn btn-danger btn-xs">Delete</a>
                                                                </div>
                                                            </td> 
                                                        </tr>
                                                        <?php $cnt = $cnt + 1;
                                                    }
                                                } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div align="left" class="mt-4">
                                        <ul class="pagination">
                                            <li><a href="?pageno=1"><strong>First</strong></a></li>
                                            <li class="<?php if ($pageno <= 1) { echo 'disabled'; } ?>">
                                                <a href="<?php if ($pageno <= 1) { echo '#'; } else { echo "?pageno=" . ($pageno - 1); } ?>"><strong>Prev</strong></a>
                                            </li>
                                            <li class="<?php if ($pageno >= $total_pages) { echo 'disabled'; } ?>">
                                                <a href="<?php if ($pageno >= $total_pages) { echo '#'; } else { echo "?pageno=" . ($pageno + 1); } ?>"><strong>Next</strong></a>
                                            </li>
                                            <li><a href="?pageno=<?php echo $total_pages; ?>"><strong>Last</strong></a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php include_once('includes/footer.php');?>
            </div>
        </div>
    </div>
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="vendors/chart.js/Chart.min.js"></script>
    <script src="vendors/moment/moment.min.js"></script>
    <script src="vendors/daterangepicker/daterangepicker.js"></script>
    <script src="vendors/chartist/chartist.min.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>
<?php } ?>