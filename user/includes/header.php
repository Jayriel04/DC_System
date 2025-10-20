<nav class="navbar default-layout-navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
    <div class="navbar-brand-wrapper d-flex align-items-center">
        <a class="navbar-brand brand-logo" href="dashboard.php">
            <strong style="color: white;">JF DENTAL CARE </strong>
        </a>
    </div>

    <?php
    $uid = $_SESSION['sturecmsnumber'];
    $sql_header = "SELECT firstname, surname, username, Image FROM tblpatient WHERE number = :uid";
    $query = $dbh->prepare($sql_header);
    $query->bindParam(':uid', $uid, PDO::PARAM_STR);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_OBJ);

    if ($query->rowCount() > 0) {
        foreach ($results as $row) { ?>
            <div class="navbar-menu-wrapper d-flex align-items-center flex-grow-1">
                <h5 class="mb-0 font-weight-medium d-none d-lg-flex">
                    <?php echo htmlentities($row->firstname . ' ' . $row->surname); ?>, welcome to the dashboard!</h5>
                <ul class="navbar-nav navbar-nav-right ml-auto">
                    <li class="nav-item dropdown d-none d-xl-inline-flex user-dropdown">
                        <a class="nav-link dropdown-toggle" id="UserDropdown" href="#" data-toggle="dropdown"
                            aria-expanded="false">
                            <?php $image_path = !empty($row->Image) ? '/dental-clinic/admin/images/' . htmlentities($row->Image) : '/dental-clinic/admin/images/avatar.png'; ?>
                            <img class="img-xs rounded-circle ml-2" src="<?php echo $image_path; ?>"
                                alt="Profile image">
                            <span class="font-weight-normal"><?php echo htmlentities($row->firstname); ?></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
                            <div class="dropdown-header text-center">
                                <img class="img-md rounded-circle" src="<?php echo $image_path; ?>" style="object-fit: cover;"
                                    alt="Profile image">
                                <p class="mb-1 mt-3"><?php echo htmlentities($row->firstname . ' ' . $row->surname); ?></p>
                                <p class="font-weight-light text-muted mb-0"><?php echo htmlentities($row->username); ?></p>
                            </div>
                            <a class="dropdown-item" href="profile.php"><i
                                    class="dropdown-item-icon icon-user text-primary"></i> My Profile</a>
                            <a class="dropdown-item" href="change-password.php"><i
                                    class="dropdown-item-icon icon-energy text-primary"></i> Change Password</a>
                            <a class="dropdown-item" href="logout.php"><i
                                    class="dropdown-item-icon icon-power text-primary"></i>Sign
                                Out</a>
                        </div>
                    </li>
                </ul>
                <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button"
                    data-toggle="offcanvas">
                    <span class="icon-menu"></span>
                </button>
            </div>
        <?php }
    } else { ?>
        <div class="navbar-menu-wrapper d-flex align-items-center flex-grow-1">
            <h5 class="mb-0 font-weight-medium d-none d-lg-flex">Welcome to the dashboard!</h5>
            <ul class="navbar-nav navbar-nav-right ml-auto">
                <li class="nav-item dropdown d-none d-xl-inline-flex user-dropdown">
                    <a class="nav-link dropdown-toggle" id="UserDropdown" href="#" data-toggle="dropdown"
                        aria-expanded="false">
                        <img class="img-xs rounded-circle ml-2" src="path/to/default-profile.png"
                            alt="Default Profile image">
                        <span class="font-weight-normal">Guest</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
                        <div class="dropdown-header text-center">
                            <img class="img-md rounded-circle" src="path/to/default-profile.png"
                                alt="Default Profile image">
                            <p class="mb-1 mt-3">Guest</p>
                        </div>
                        <a class="dropdown-item" href="login.php"><i class="dropdown-item-icon icon-user text-primary"></i>
                            Login</a>
                    </div>
                </li>
            </ul>
        </div>
    <?php } ?>
</nav>