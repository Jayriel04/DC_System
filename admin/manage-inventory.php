<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsaid'])==0) {
    header('location:logout.php');
} else { 
    if (isset($_POST['add_inventory'])) {
        $name = ucfirst(trim($_POST['name']));
        $brand = ucfirst(trim($_POST['brand']));
        $category = ucfirst(trim($_POST['category']));
        $quantity = $_POST['quantity'];
        $expiration_date = $_POST['expiration_date'];
        $admin_id = $_SESSION['sturecmsaid'];

        $sql_insert = "INSERT INTO tblinventory (name, brand, category, quantity, expiration_date) VALUES (:name, :brand, :category, :quantity, :exp_date)";
        $query_insert = $dbh->prepare($sql_insert);
        $query_insert->execute([
            ':name' => $name, ':brand' => $brand, ':category' => $category, ':quantity' => $quantity, ':exp_date' => $expiration_date
        ]);
        
        if ($query_insert) {
            $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'New product added successfully.'];

            // notify if new item is already low or out of stock
            if ($quantity == 0) {
                $notif_message = "New item '" . htmlentities($name) . "' was added and is out of stock.";
                $sql_notif = "INSERT INTO tblnotif (recipient_id, recipient_type, message, url) VALUES (:rid, 'admin', :msg, 'manage-inventory.php')";
                $dbh->prepare($sql_notif)->execute([':rid' => $admin_id, ':msg' => $notif_message]);
            } elseif ($quantity <= 2) {
                $notif_message = "New item '" . htmlentities($name) . "' was added with low stock (" . $quantity . ").";
                $sql_notif = "INSERT INTO tblnotif (recipient_id, recipient_type, message, url) VALUES (:rid, 'admin', :msg, 'manage-inventory.php')";
                $dbh->prepare($sql_notif)->execute([':rid' => $admin_id, ':msg' => $notif_message]);
            }

            header('Location: manage-inventory.php');
        } else {
            $_SESSION['toast_message'] = ['type' => 'danger', 'message' => 'An error occurred while adding the product.'];
            header('Location: manage-inventory.php');
        }
        exit();
    }
    // inventory update from modal
    if (isset($_POST['update_inventory'])) {
        $inventory_id = $_POST['inventory_id'];
        $name = ucfirst(trim($_POST['name']));
        $brand = ucfirst(trim($_POST['brand']));
        $category = ucfirst(trim($_POST['category']));
        $quantity = $_POST['quantity'];
        $expiration_date = $_POST['expiration_date'];
        $admin_id = $_SESSION['sturecmsaid'];

        // Get old quantity to check if status changed
        $sql_old_qty = "SELECT quantity FROM tblinventory WHERE number=:id";
        $query_old_qty = $dbh->prepare($sql_old_qty);
        $query_old_qty->execute([':id' => $inventory_id]);
        $old_quantity = $query_old_qty->fetchColumn();

        $low_stock_threshold = 2;


        $sql_update = "UPDATE tblinventory SET name=:name, brand=:brand, category=:category, quantity=:quantity, expiration_date=:exp_date WHERE number=:id";
        $query_update = $dbh->prepare($sql_update);
        $query_update->execute([
            ':name' => $name, ':brand' => $brand, ':category' => $category, ':quantity' => $quantity, 
            ':exp_date' => $expiration_date, ':id' => $inventory_id
        ]);

        if ($query_update) {
            $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'Inventory item updated successfully.'];

            // notify if stock becomes low or out of stock
            $notif_message = null;
            if ($quantity == 0 && $old_quantity > 0) {
                $notif_message = "Item '" . htmlentities($name) . "' is now out of stock.";
            } elseif ($quantity > 0 && $quantity <= $low_stock_threshold && $old_quantity > $low_stock_threshold) {
                $notif_message = "Item '" . htmlentities($name) . "' is running low on stock (" . $quantity . ").";
            }

            if ($notif_message) {
                $sql_notif = "INSERT INTO tblnotif (recipient_id, recipient_type, message, url) VALUES (:rid, 'admin', :msg, 'manage-inventory.php')";
                $query_notif = $dbh->prepare($sql_notif);
                $query_notif->execute([':rid' => $admin_id, ':msg' => $notif_message]);
            }

            header('Location: manage-inventory.php');
        } else {
            $_SESSION['toast_message'] = ['type' => 'danger', 'message' => 'An error occurred while updating the item.'];
            header('Location: manage-inventory.php');
        }
        exit();
    }
    
    $search = '';
    $category_filter = '';

    
    if (isset($_POST['search_query'])) { 
        $search = $_POST['search_query'];
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
        $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'Data deleted.'];
        header('Location: manage-inventory.php');
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta charset="utf-8">
    <title>Inventory</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="vendors/chartist/chartist.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/mas-modal.css">
    <link rel="stylesheet" href="css/stylev2.css">
    <link rel="stylesheet" href="css/toast.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

</head>
<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php');?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php');?>
            <div class="main-panel">
                <div id="toast-container"></div>
                    <?php
                    if (isset($_SESSION['toast_message'])) {
                        echo "<script>document.addEventListener('DOMContentLoaded', function() { showToast('{$_SESSION['toast_message']['message']}', '{$_SESSION['toast_message']['type']}'); });</script>";
                        unset($_SESSION['toast_message']);
                    }
                    ?>
                <div class="content-wrapper" style="padding: 0.75rem 1.5rem 0;">
                    <div class="inventory-container">
                        <div class="header">
                            <div class="header-content">
                                <h1>Inventory</h1>
                                <p>Track and manage your product inventory</p>
                            </div>
                            <button class="btn-add" id="addProductBtn">
                                <i class="fas fa-pills"></i>
                                Add Product
                            </button>
                        </div>

                        <?php
                        $low_stock_items_check = [];
                        $out_of_stock_items_check = [];
                        $all_items_query = $dbh->query("SELECT name, quantity FROM tblinventory");
                        $all_items = $all_items_query->fetchAll(PDO::FETCH_OBJ);
                        foreach ($all_items as $item) {
                            if ($item->quantity == 0) {
                                $out_of_stock_items_check[] = $item->name;
                            } elseif ($item->quantity <= 2) {
                                $low_stock_items_check[] = $item->name;
                            }
                        }

                        if (!empty($low_stock_items_check) || !empty($out_of_stock_items_check)) {
                            $alert_message_text = (!empty($out_of_stock_items_check) ? "You have items that are out of stock. " : "") . (!empty($low_stock_items_check) ? "Some items are running low. " : "") . "Please top up your inventory.";
                        ?>
                        <div class="inventory-alert-banner" id="inventoryAlertBanner">
                            <div class="alert-content">
                                <span class="alert-icon">⚠️</span>
                                <div class="alert-text">
                                    <strong>Inventory Alert</strong>
                                    <p><?php echo $alert_message_text; ?></p>
                                </div>
                            </div>
                            <button class="alert-close-btn" id="alertCloseBtn">&times;</button>
                        </div>
                        <?php } ?>

                        <?php
                        $total_products = $dbh->query("SELECT COUNT(*) FROM tblinventory")->fetchColumn();
                        // In Stock (quantity > 2)
                        $in_stock = $dbh->query("SELECT COUNT(*) FROM tblinventory WHERE quantity > 2")->fetchColumn();
                        // Low Stock (quantity > 0 and <= 2)
                        $low_stock = $dbh->query("SELECT COUNT(*) FROM tblinventory WHERE quantity > 0 AND quantity <= 2")->fetchColumn();
                        // Out of Stock (quantity = 0)
                        $out_of_stock = $dbh->query("SELECT COUNT(*) FROM tblinventory WHERE quantity = 0")->fetchColumn();
                        ?>

                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-info">
                                    <h3>Total Products</h3>
                                    <div class="number" id="totalProducts"><?php echo $total_products; ?></div>
                                </div>
                                <div class="stat-icon icon-blue">☰</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-info">
                                    <h3>In Stock</h3>
                                    <div class="number" id="inStock"><?php echo $in_stock; ?></div>
                                </div>
                                <div class="stat-icon icon-green">✓</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-info">
                                    <h3>Low Stock</h3>
                                    <div class="number" id="lowStock"><?php echo $low_stock; ?></div>
                                </div>
                                <div class="stat-icon icon-yellow">⚠</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-info">
                                    <h3>Out of Stock</h3>
                                    <div class="number" id="outOfStock"><?php echo $out_of_stock; ?></div>
                                </div>
                                <div class="stat-icon icon-red">✕</div>
                            </div>
                        </div>

                        <form method="POST">
                        <div class="search-filter-bar">
                            <div class="search-box">
                                <span class="search-icon"></span>
                                <input type="text" class="search-input" name="search_query" placeholder="Search for products ..." value="<?php echo htmlentities($search); ?>" id="searchInput">
                            </div>
                            
                        </div>
                    </form>

                        <div class="table-container">
                            <table id="inventoryTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Material</th>
                                        <th>Brand</th>
                                        <th>Category</th>
                                        <th>Quantity</th>
                                        <th>Expiration Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBody">
                                    <?php
                                    
                                    $low_stock_items = [];
                                    $out_of_stock_items = [];

                                    $no_of_records_per_page = 15;
                                    $pageno = isset($_GET['pageno']) ? intval($_GET['pageno']) : 1;
                                    $offset = ($pageno - 1) * $no_of_records_per_page;

                                    
                                    $sql = "SELECT * FROM tblinventory WHERE 1=1";
                                    $count_sql = "SELECT COUNT(*) FROM tblinventory WHERE 1=1";
                                    $params = [];

                                    if ($search) {
                                        $sql .= " AND (name LIKE :search OR brand LIKE :search)";
                                        $count_sql .= " AND (name LIKE :search OR brand LIKE :search)";
                                        $params[':search'] = "%$search%";
                                    }
                                    if ($category_filter) {
                                        $sql .= " AND category = :category_filter";
                                        $count_sql .= " AND category = :category_filter";
                                        $params[':category_filter'] = $category_filter;
                                    }

                                    $query1 = $dbh->prepare($count_sql);
                                    $query1->execute($params);
                                    $total_rows = (int) $query1->fetchColumn();
                                    $total_pages = ceil($total_rows / $no_of_records_per_page);

                                    $sql .= " ORDER BY number ASC LIMIT :offset, :limit";
                                    $query = $dbh->prepare($sql);
                                    $query->bindParam(':offset', $offset, PDO::PARAM_INT);
                                    $query->bindParam(':limit', $no_of_records_per_page, PDO::PARAM_INT);
                                    foreach ($params as $key => &$val) {
                                        $query->bindParam($key, $val);
                                    }
                                    $query->execute();
                                    $results = $query->fetchAll(PDO::FETCH_OBJ);

                                    if ($query->rowCount() > 0) {
                                        foreach ($results as $row) {
                                            $status = 'in-stock';
                                            $statusText = 'In Stock';
                                            if ($row->quantity == 0) {
                                                $status = 'out-of-stock';
                                                $statusText = 'Out of stock';
                                                $out_of_stock_items[] = $row->name;
                                            } elseif ($row->quantity <= 2) {
                                                $status = 'low-stock';
                                                $statusText = 'Low Stock';
                                                $low_stock_items[] = $row->name;
                                            }
                                    ?>
                                    <tr data-id="<?php echo htmlentities($row->number); ?>">
                                        <td><?php echo htmlentities($row->number); ?></td>
                                        <td><strong><?php echo htmlentities($row->name); ?></strong></td>
                                        <td><?php echo htmlentities($row->brand); ?></td>
                                        <td><?php echo htmlentities($row->category); ?></td>
                                        <td><strong><?php echo htmlentities($row->quantity); ?></strong></td>
                                        <td><?php echo htmlentities(date('M d, Y', strtotime($row->expiration_date))); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $status; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <button class="action-btn btn-edit" title="Edit"
                                                    data-id="<?php echo htmlentities($row->number); ?>"
                                                    data-name="<?php echo htmlentities($row->name); ?>"
                                                    data-brand="<?php echo htmlentities($row->brand); ?>"
                                                    data-category="<?php echo htmlentities($row->category); ?>"
                                                    data-quantity="<?php echo htmlentities($row->quantity); ?>"
                                                    data-expiration="<?php echo htmlentities($row->expiration_date); ?>"
                                                    style="background:none; border:none; cursor:pointer; padding:0; font-size: 1rem;"
                                                ><i class="fas fa-edit" style="color:#007bffe3 ;"></i></button>
                                                <a href="manage-inventory.php?delid=<?php echo ($row->number); ?>" onclick="return confirm('Do you really want to Delete ?');" class="action-btn btn-delete" title="Delete"><i class="fas fa-trash-alt" style="color:red;"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php }
                                    } else { ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center;">No products found.</td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <div align="left" class="mt-4">
                            <?php
                            
                            $query_params = [];
                            if ($search)
                                $query_params['search_query'] = $search;
                            if ($category_filter)
                                $query_params['category_filter'] = $category_filter;
                            ?>
                            <ul class="pagination">
                                <li><a
                                        href="?pageno=1<?php echo http_build_query($query_params) ? '&' . http_build_query($query_params) : ''; ?>"><strong>First</strong></a>
                                </li>
                                <li class="<?php if ($pageno <= 1)
                                    echo 'disabled'; ?>">
                                    <a
                                        href="<?php if ($pageno <= 1)
                                            echo '#';
                                        else
                                            echo "?pageno=" . ($pageno - 1) . (http_build_query($query_params) ? '&' . http_build_query($query_params) : ''); ?>"><strong>Prev</strong></a>
                                </li>
                                <li class="<?php if ($pageno >= $total_pages)
                                    echo 'disabled'; ?>">
                                    <a
                                        href="<?php if ($pageno >= $total_pages)
                                            echo '#';
                                        else
                                            echo "?pageno=" . ($pageno + 1) . (http_build_query($query_params) ? '&' . http_build_query($query_params) : ''); ?>"><strong>Next</strong></a>
                                </li>
                                <li><a
                                        href="?pageno=<?php echo $total_pages; ?><?php echo http_build_query($query_params) ? '&' . http_build_query($query_params) : ''; ?>"><strong>Last</strong></a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div id="addInventoryModal" class="modal-container" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Product</h2>
                <button class="close-button">&times;</button>
            </div>
            <form id="addInventoryForm" method="POST">
                <div class="modal-body">
                    <div class="form-group"><label for="add_name">Material Name</label><input type="text" id="add_name" name="name" required></div>
                    <div class="form-row">
                        <div class="form-group"><label for="add_brand">Brand</label><input type="text" id="add_brand" name="brand"></div>
                        <div class="form-group"><label for="add_category">Category</label><input type="text" id="add_category" name="category"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="add_quantity">Quantity</label><input type="number" id="add_quantity" name="quantity" min="0"></div>
                        <div class="form-group"><label for="add_expiration_date">Expiration Date</label><input type="date" id="add_expiration_date" name="expiration_date" ></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel">Cancel</button>
                    <button type="submit" name="add_inventory" class="btn btn-schedule">Add Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Inventory Modal -->
    <div id="editInventoryModal" class="modal-container" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Inventory Item</h2>
                <button class="close-button">&times;</button>
            </div>
            <form id="editInventoryForm" method="POST">
                <input type="hidden" name="inventory_id" id="edit_inventory_id">
                <div class="modal-body">
                    <div class="form-group"><label for="edit_name">Material Name</label><input type="text" id="edit_name" name="name" required></div>
                    <div class="form-row">
                        <div class="form-group"><label for="edit_brand">Brand</label><input type="text" id="edit_brand" name="brand"></div>
                        <div class="form-group"><label for="edit_category">Category</label><input type="text" id="edit_category" name="category"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="edit_quantity">Quantity</label><input type="number" id="edit_quantity" name="quantity" min="0"></div>
                        <div class="form-group"><label for="edit_expiration_date">Expiration Date</label><input type="date" id="edit_expiration_date" name="expiration_date" ></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel">Cancel</button>
                    <button type="submit" name="update_inventory" class="btn btn-update">Update Item</button>
                </div>
            </form>
        </div>
    </div>
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="vendors/chart.js/Chart.min.js"></script>
    <script src="vendors/moment/moment.min.js"></script>
    <script src="vendors/daterangepicker/daterangepicker.js"></script>
    <script src="vendors/chartist/chartist.min.js"></script>
    <script src="js/toast.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <script src="js/dashboard.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const addModal = document.getElementById('addInventoryModal');
        const addOpenBtn = document.getElementById('addProductBtn');
        const addCloseBtn = addModal.querySelector('.close-button');
        const addCancelBtn = addModal.querySelector('.btn-cancel');

        addOpenBtn.addEventListener('click', () => { addModal.style.display = 'flex'; });
        addCloseBtn.addEventListener('click', () => { addModal.style.display = 'none'; });
        addCancelBtn.addEventListener('click', () => { addModal.style.display = 'none'; });

        window.addEventListener('click', function (event) {
            if (event.target === addModal) {
                addModal.style.display = 'none';
            }
        });


        // --- Edit Inventory Modal ---
        const editModal = document.getElementById('editInventoryModal');
        const editCloseBtn = editModal.querySelector('.close-button');
        const editCancelBtn = editModal.querySelector('.btn-cancel');

        function closeEditModal() {
            editModal.style.display = 'none';
        }

        editCloseBtn.addEventListener('click', closeEditModal);
        editCancelBtn.addEventListener('click', closeEditModal);

        window.addEventListener('click', function (event) {
            if (event.target === editModal) {
                closeEditModal();
            }
        });

        document.querySelectorAll('.btn-edit').forEach(button => {
            button.addEventListener('click', function() {
                const dataset = this.dataset;
                document.getElementById('edit_inventory_id').value = dataset.id;
                document.getElementById('edit_name').value = dataset.name;
                document.getElementById('edit_brand').value = dataset.brand;
                document.getElementById('edit_category').value = dataset.category;
                document.getElementById('edit_quantity').value = dataset.quantity;
                document.getElementById('edit_expiration_date').value = dataset.expiration;

                editModal.style.display = 'flex';
            });
        });

        // --- Inventory Alert  ---
        const alertBanner = document.getElementById('inventoryAlertBanner');
        const alertCloseBtn = document.getElementById('alertCloseBtn');

        if (alertBanner && alertCloseBtn) {
            alertCloseBtn.addEventListener('click', () => {
                alertBanner.style.display = 'none';
            });
        }

        function capitalizeFirstLetter(inputId) {
            const input = document.getElementById(inputId);
            if (input) {
                input.addEventListener('input', function() {
                    if (this.value.length > 0) {
                        this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);
                    }
                });
            }
        }
        capitalizeFirstLetter('add_name');
        capitalizeFirstLetter('add_brand');
        capitalizeFirstLetter('add_category');
        capitalizeFirstLetter('edit_name');
        capitalizeFirstLetter('edit_brand');
        capitalizeFirstLetter('edit_category');
    });
    </script>
</body>
</html>
<?php } ?>