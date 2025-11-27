<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsaid']) == 0) {
    header('location:logout.php');
} else {
    // Handle new service creation from modal
    if (isset($_POST['add_service'])) {
        $sname = ucfirst(trim($_POST['sname']));
        $sdesc = trim($_POST['sdesc']);
        $category_id = $_POST['category_id']; // Changed from 'category'
        $image_path = '';

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image = $_FILES['image']['name'];
            $image_path = 'images/' . basename($image);
            $target_dir = __DIR__ . '/images/';
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $target_file = $target_dir . basename($image);
            move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
        }

        $sql = "INSERT INTO tblservice (name, description, image, category_id) VALUES (:sname, :sdesc, :image, :category_id)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':sname', $sname, PDO::PARAM_STR);
        $query->bindParam(':sdesc', $sdesc, PDO::PARAM_STR);
        $query->bindParam(':image', $image_path, PDO::PARAM_STR);
        $query->bindParam(':category_id', $category_id, PDO::PARAM_INT);
        $query->execute();

        echo "<script>alert('Service added successfully');</script>";
        echo "<script>window.location.href = 'manage-service.php'</script>";
        exit();
    }


    if (isset($_POST['update_service'])) {
        $sid = $_POST['id'];
        $sname = ucfirst(trim($_POST['sname']));
        $sdesc = trim($_POST['sdesc']);
        $category_id = $_POST['category_id']; // Changed from 'category'
        $image_path = $_POST['existing_image']; // Keep existing image by default

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0 && !empty($_FILES['image']['name'])) {
            $image = $_FILES['image']['name'];
            $image_path = 'images/' . basename($image);
            $target_file = __DIR__ . '/images/' . basename($image);
            move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
        }

        $sql = "UPDATE tblservice SET name=:sname, description=:sdesc, image=:image, category_id=:category_id WHERE number=:sid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':sname', $sname, PDO::PARAM_STR);
        $query->bindParam(':sdesc', $sdesc, PDO::PARAM_STR);
        $query->bindParam(':image', $image_path, PDO::PARAM_STR);
        $query->bindParam(':category_id', $category_id, PDO::PARAM_INT);
        $query->bindParam(':sid', $sid, PDO::PARAM_INT);
        $query->execute();

        echo "<script>alert('Service updated successfully');</script>";
        echo "<script>window.location.href = 'manage-service.php'</script>";
        exit();
    }
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

    // Initialize search and category variables
    $search = '';
    $category_id_filter = '';

    if (isset($_GET['search_query'])) {
        $search = trim($_GET['search_query']);
    }
    if (isset($_GET['category_id'])) {
        $category_id_filter = trim($_GET['category_id']);
    }

    // Fetch all categories for dropdowns
    $sql_cats = "SELECT id, name FROM tblcategory ORDER BY id ASC";
    $query_cats = $dbh->prepare($sql_cats);
    $query_cats->execute();
    $categories_list = $query_cats->fetchAll(PDO::FETCH_OBJ);
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
                            <a href="#" class="add-btn" id="addServiceBtn">
                                <i class="fas fa-heartbeat"></i>
                                Add New Service
                            </a>
                        </div>

                        <div class="search-filter-section">
                            <form method="GET" class="search-form" id="searchForm"
                                style="display: flex; gap: 10px; align-items: center;">
                                <div class="search-box">
                                    <input type="text" id="searchInput" name="search_query"
                                        placeholder="Search for services..." value="<?php echo htmlentities($search); ?>">
                                </div>
                                <div class="category-box"> 
                                    <select name="category_id" id="categoryDropdown" style="height: 37px;">
                                        <option value="">All Categories</option>
                                        <?php if (!empty($categories_list)): ?>
                                            <?php foreach ($categories_list as $cat): ?>
                                                <option value="<?php echo htmlentities($cat->id); ?>" <?php if ($category_id_filter == $cat->id) echo "selected"; ?>>
                                                    <?php echo htmlentities($cat->name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
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

                            $count_sql = "SELECT COUNT(*) FROM tblservice s WHERE 1=1";
                            $params = [];
                            if ($search) {
                                $count_sql .= " AND (s.name LIKE :search OR s.description LIKE :search)";
                                $params[':search'] = "%$search%";
                            }
                            if ($category_id_filter) {
                                $count_sql .= " AND s.category_id = :category_id";
                                $params[':category_id'] = $category_id_filter;
                            }
                            $query1 = $dbh->prepare($count_sql);
                            $query1->execute($params);
                            $total_rows = $query1->fetchColumn();
                            $total_pages = ceil($total_rows / $no_of_records_per_page);

                            $sql = "SELECT s.*, c.name as category_name FROM tblservice s LEFT JOIN tblcategory c ON s.category_id = c.id WHERE 1=1";
                            if ($search) {
                                $sql .= " AND (s.name LIKE :search OR s.description LIKE :search)";
                            }
                            if ($category_id_filter) {
                                $sql .= " AND s.category_id = :category_id";
                            }
                            $sql .= " ORDER BY s.name ASC LIMIT :offset, :limit";
                            $query = $dbh->prepare($sql);
                            $query->bindParam(':offset', $offset, PDO::PARAM_INT);
                            $query->bindParam(':limit', $no_of_records_per_page, PDO::PARAM_INT);
                            if ($search) {
                                // bindValue so the wildcard string is bound correctly
                                $query->bindValue(':search', $params[':search'], PDO::PARAM_STR);
                            }
                            if ($category_id_filter) {
                                $query->bindParam(':category_id', $params[':category_id']);
                            }
                            $query1->execute();
                            $query->execute();
                            $results = $query->fetchAll(PDO::FETCH_OBJ);

                            if ($query->rowCount() > 0) {
                                foreach ($results as $row) {
                                    ?>
                                    <div class="service-card">
                                        <div class="service-actions">
                                            <button class="action-btn edit-btn" title="Edit"
                                                data-id="<?php echo htmlentities($row->number); ?>"
                                                data-name="<?php echo htmlentities($row->name); ?>" 
                                                data-category-id="<?php echo htmlentities($row->category_id); ?>"
                                                data-description="<?php echo htmlentities($row->description); ?>"
                                                data-image="<?php echo htmlentities($row->image); ?>"><i
                                                    class="fas fa-edit"></i></button>
                                            <a href="manage-service.php?delid=<?php echo ($row->number); ?>" class="action-btn"
                                                title="Delete" onclick="return confirm('Do you really want to Delete?');"><i
                                                    class="fas fa-trash"></i></a>
                                        </div>
                                        <?php if (!empty($row->image)): ?>
                                            <img src="<?php echo htmlentities($row->image); ?>"
                                                alt="<?php echo htmlentities($row->name); ?>" class="service-image">
                                        <?php endif; ?>
                                        <div class="service-header">
                                            <h3 class="service-title"><?php echo htmlentities($row->name); ?></h3>
                                        </div>
                                        <p class="service-description"><?php echo htmlentities($row->description); ?></p>
                                        <hr style="margin-top: 1px; margin-bottom: 1px;">
                                        <p style="padding: 10px;">Category: <?php echo htmlentities($row->category_name); ?></p>
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
                            if ($search)
                                $query_params['search_query'] = $search;
                            if ($category_id_filter)
                                $query_params['category_id'] = $category_id_filter;
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
                    <?php include_once('includes/footer.php'); ?>
                </div>
            </div>
        </div>

        <!-- Add Service Modal -->
        <div class="modal" id="addServiceModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Add New Service</h2>
                    <button class="close-button">&times;</button>
                </div>
                <form id="addServiceForm" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="serviceName">Service Name</label>
                        <input type="text" id="serviceName" name="sname" required>
                    </div>
                    <div class="form-group">
                        <label for="add_category_id">Category</label>
                        <select id="add_category_id" name="category_id" required>
                            <option value="">Select a Category</option>
                            <?php if (!empty($categories_list)): ?>
                                <?php foreach ($categories_list as $cat): ?>
                                    <option value="<?php echo htmlentities($cat->id); ?>"><?php echo htmlentities($cat->name); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="serviceDescription">Description</label>
                        <textarea id="serviceDescription" name="sdesc" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="serviceImage">Service Image</label>
                        <input type="file" name="image" accept="image/*">
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn-danger btn-cancel">Cancel</button>
                        <button type="submit" name="add_service" class="btn-success">Add Service</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Service Modal -->
        <div class="modal" id="editServiceModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Edit Service</h2>
                    <button class="close-button">&times;</button>
                </div>
                <form id="editServiceForm" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="edit_service_id">
                    <input type="hidden" name="existing_image" id="edit_existing_image">
                    <div class="form-group">
                        <label for="edit_service_name">Service Name</label>
                        <input type="text" name="sname" id="edit_service_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_category_id">Category</label>
                        <select id="edit_category_id" name="category_id" required>
                            <option value="">Select a Category</option>
                            <?php if (!empty($categories_list)): ?>
                                <?php foreach ($categories_list as $cat): ?>
                                    <option value="<?php echo htmlentities($cat->id); ?>"><?php echo htmlentities($cat->name); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_service_description">Description</label>
                        <textarea name="sdesc" id="edit_service_description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_service_image">Service Image</label>
                        <input type="file" name="image" id="edit_service_image" accept="image/*">
                        <small>Leave blank to keep the current image.</small>
                        <img id="edit_image_preview" src="" alt="Current Image"
                            style="max-width: 100px; margin-top: 10px; display: none;">
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn-danger btn-cancel">Cancel</button>
                        <button type="submit" name="update_service" class="btn-success">Update Service</button>
                    </div>
                </form>
            </div>
        </div>

        <script src="vendors/js/vendor.bundle.base.js"></script>
        <script src="js/off-canvas.js"></script>
        <script src="js/misc.js"></script>
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

                        form.submit();
                    }, 500);
                });

                // --- Add Service Modal ---
                const addModal = document.getElementById('addServiceModal');
                const addBtn = document.getElementById('addServiceBtn');
                const addCloseBtn = addModal.querySelector('.close-button');
                const addCancelBtn = addModal.querySelector('.btn-cancel');

                addBtn.addEventListener('click', (e) => { e.preventDefault(); addModal.style.display = 'flex'; });
                addCloseBtn.addEventListener('click', () => { addModal.style.display = 'none'; });
                addCancelBtn.addEventListener('click', () => { addModal.style.display = 'none'; });
                window.addEventListener('click', (e) => { if (e.target === addModal) addModal.style.display = 'none'; });

                // --- Edit Service Modal ---
                const editModal = document.getElementById('editServiceModal');
                const editCloseBtn = editModal.querySelector('.close-button');
                const editCancelBtn = editModal.querySelector('.btn-cancel');

                function closeEditModal() {
                    editModal.style.display = 'none';
                }

                editCloseBtn.addEventListener('click', closeEditModal);
                editCancelBtn.addEventListener('click', closeEditModal);
                window.addEventListener('click', (e) => { if (e.target === editModal) closeEditModal(); });

                document.querySelectorAll('.edit-btn').forEach(button => {
                    button.addEventListener('click', function () {
                        const dataset = this.dataset;
                        document.getElementById('edit_service_id').value = dataset.id;
                        document.getElementById('edit_service_name').value = dataset.name;
                        document.getElementById('edit_category_id').value = dataset.categoryId;
                        document.getElementById('edit_service_description').value = dataset.description;
                        document.getElementById('edit_existing_image').value = dataset.image;

                        const imagePreview = document.getElementById('edit_image_preview');
                        if (dataset.image) {
                            imagePreview.src = dataset.image;
                            imagePreview.style.display = 'block';
                        } else {
                            imagePreview.style.display = 'none';
                        }

                        // Clear the file input
                        document.getElementById('edit_service_image').value = '';

                        editModal.style.display = 'flex';
                    });
                });

                // Auto-capitalize first letter for new service fields
                function capitalizeFirstLetter(inputId) {
                    const input = document.getElementById(inputId);
                    if (input) {
                        input.addEventListener('input', function () {
                            if (this.value.length > 0) {
                                this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);
                            }
                        });
                    }
                }
                capitalizeFirstLetter('serviceName');
                capitalizeFirstLetter('serviceDescription');
                capitalizeFirstLetter('edit_service_name');
                capitalizeFirstLetter('edit_service_description');

                // Add event listener for category dropdown to auto-submit form
                document.getElementById('categoryDropdown').addEventListener('change', function () {
                    document.getElementById('searchForm').submit();
                });

            });
        </script>
    </body>

    </html>
<?php } ?>