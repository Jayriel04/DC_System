<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
} else {
    // Code for deletion
    if (isset($_GET['delid'])) {
        $rid = intval($_GET['delid']);
        $sql = "DELETE FROM tblservice WHERE number = :rid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':rid', $rid, PDO::PARAM_INT);
        $query->execute();
        echo "<script>alert('Service deleted successfully');</script>";
        echo "<script>window.location.href = 'manage-service.php'</script>";
    }

    // Initialize search variable
    $search = '';

    if (isset($_GET['search_query'])) {
        $search = trim($_GET['search_query']);
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Services</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/manage-service.css">
        
    
</head>
<body>
    <div class="container-scroller">
        <?php include_once('includes/header.php'); ?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once('includes/sidebar.php'); ?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="header">
                        <div class="header-content">
                            <h1>Services</h1>
                            <p>Manage your dental services</p>
                        </div>
                        <a href="add-service.php" class="add-btn" id="addServiceBtn">
                            <i class="fas fa-heartbeat"></i>
                             Add New Service
                        </a>
                    </div>

                    <div class="search-filter-section">
                        <form method="GET" class="search-form" id="searchForm">
                            <div class="search-box">
                                <input type="text" id="searchInput" name="search_query" placeholder="Search for services..." value="<?php echo htmlentities($search); ?>">
                            </div>
                        </form>
                    </div>

                    <div class="services-grid" id="servicesGrid">
                        <?php
                        if (isset($_GET['pageno'])) {
                            $pageno = $_GET['pageno'];
                        } else {
                            $pageno = 1;
                        }
                        $no_of_records_per_page = 10;
                        $offset = ($pageno - 1) * $no_of_records_per_page;

                        $count_sql = "SELECT COUNT(*) FROM tblservice WHERE 1=1";
                        $params = [];
                        if ($search) {
                            $count_sql .= " AND (name LIKE :search OR description LIKE :search)";
                            $params[':search'] = "%$search%";
                        }
                        $query1 = $dbh->prepare($count_sql);
                        $query1->execute($params);
                        $total_rows = $query1->fetchColumn();
                        $total_pages = ceil($total_rows / $no_of_records_per_page);

                        $sql = "SELECT * FROM tblservice WHERE 1=1";
                        if ($search) {
                            $sql .= " AND (name LIKE :search OR description LIKE :search)";
                        }
                        $sql .= " ORDER BY name ASC LIMIT :offset, :limit";
                        $query = $dbh->prepare($sql);
                        $query->bindParam(':offset', $offset, PDO::PARAM_INT);
                        $query->bindParam(':limit', $no_of_records_per_page, PDO::PARAM_INT);
                        if ($search) {
                            $query->bindParam(':search', $params[':search']);
                        }
                        $query1->execute();
                        $query->execute();
                        $results = $query->fetchAll(PDO::FETCH_OBJ);

                        if ($query->rowCount() > 0) {
                            foreach ($results as $row) {
                        ?>
                        <div class="service-card">
                            <?php if (!empty($row->image)): ?>
                                <img src="<?php echo htmlentities($row->image); ?>" alt="<?php echo htmlentities($row->name); ?>" class="service-image">
                            <?php endif; ?>
                            <div class="service-header">
                                <h3 class="service-title"><?php echo htmlentities($row->name); ?></h3>
                                <div class="service-actions">
                                    <a href="edit-service.php?editid=<?php echo htmlentities($row->number); ?>" class="action-btn edit-btn" title="Edit"
                                        data-id="<?php echo htmlentities($row->number); ?>"
                                        data-name="<?php echo htmlentities($row->name); ?>"
                                        data-description="<?php echo htmlentities($row->description); ?>"
                                    >✏️</a>
                                    <a href="manage-service.php?delid=<?php echo ($row->number); ?>" class="action-btn" title="Delete" onclick="return confirm('Do you really want to Delete?');">🗑️</a>
                                </div>
                            </div>
                            <p class="service-description"><?php echo htmlentities($row->description); ?></p>
                        </div>
                        <?php
                            }
                        } else {
                        ?>
                        <div class="col-12">
                            <p style="color:red; text-align:center;">No Services Found</p>
                        </div>
                        <?php } ?>
                    </div>

                    <div align="left" class="mt-4">
                        <?php
                            // Build the query string for pagination links
                            $query_params = [];
                            if ($search) $query_params['search_query'] = $search;
                        ?>
                        <ul class="pagination">
                            <li><a href="?pageno=1<?php echo http_build_query($query_params) ? '&' . http_build_query($query_params) : ''; ?>"><strong>First</strong></a></li>
                            <li class="<?php if ($pageno <= 1) echo 'disabled'; ?>">
                                <a href="<?php if ($pageno <= 1) echo '#'; else echo "?pageno=" . ($pageno - 1) . (http_build_query($query_params) ? '&' . http_build_query($query_params) : ''); ?>"><strong>Prev</strong></a>
                            </li>
                            <li class="<?php if ($pageno >= $total_pages) echo 'disabled'; ?>">
                                <a href="<?php if ($pageno >= $total_pages) echo '#'; else echo "?pageno=" . ($pageno + 1) . (http_build_query($query_params) ? '&' . http_build_query($swap_params) : ''); ?>"><strong>Next</strong></a>
                            </li>
                            <li><a href="?pageno=<?php echo $total_pages; ?><?php echo http_build_query($query_params) ? '&' . http_build_query($query_params) : ''; ?>"><strong>Last</strong></a></li>
                        </ul>
                    </div>
                </div>
                <?php include_once('includes/footer.php'); ?>
            </div>
        </div>
    </div>

    <!-- Modal for Add/Edit Service -->
    <div class="modal" id="serviceModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Service</h2>
                <button class="close-btn">&times;</button>
            </div>
            <!-- The form will be submitted to either add-service.php or edit-service.php -->
            <form id="serviceForm" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" id="serviceId">
                <div class="form-group">
                    <label for="serviceName">Service Name</label>
                    <input type="text" name="sname" id="serviceName" required>
                </div>
                <div class="form-group">
                    <label for="serviceDescription">Description</label>
                    <textarea name="sdesc" id="serviceDescription" required></textarea>
                </div>
                <div class="form-group">
                    <label for="serviceImage">Service Image</label>
                    <input type="file" name="image" id="serviceImage" accept="image/*">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-danger">Cancel</button>
                    <button type="submit" name="submit" class="btn-success">Save Service</button>
                </div>
            </form>
        </div>
    </div>

    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <script src="js/manage-service.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchInput');
            let debounceTimeout;

            searchInput.addEventListener('input', function () {
                // Clear the previous timeout
                clearTimeout(debounceTimeout);

                // Set a new timeout
                debounceTimeout = setTimeout(() => {
                    const form = document.getElementById('searchForm');
                    // Programmatically submit the form which now uses GET
                    form.submit();
                }, 500); // Wait for 500ms after the user stops typing
            });
        });
    </script>
</body>
</html>
<?php } ?>