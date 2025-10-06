<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <ul class="nav">
        <li class="nav-item nav-profile">
            <a href="#" class="nav-link">
                <div class="profile-image">
                    <?php
                    // Assuming you are using sturecmsnumber as a session variable
                    $uid = $_SESSION['sturecmsnumber'];
                    $sql = "SELECT * FROM tblpatient WHERE number = :uid"; // Updated query for tblpatient
                    
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':uid', $uid, PDO::PARAM_STR);
                    $query->execute();
                    $results = $query->fetchAll(PDO::FETCH_OBJ);

                    if ($query->rowCount() > 0) {
                        foreach ($results as $row) { ?>
                            <img class="img-xs rounded-circle" src="../admin/images/<?php echo htmlentities($row->image); ?>"
                                alt="Profile image">
                            <div class="dot-indicator bg-success"></div>
                        <?php }
                    } else { ?>
                        <img class="img-xs rounded-circle" src="../admin/images/default-profile.png"
                            alt="Default Profile image"> <!-- Default image if user has no profile image -->
                        <div class="dot-indicator bg-danger"></div>
                    <?php } ?>
                </div>
                <div class="text-wrapper">
                    <?php
                    // Display user name and username
                    if ($query->rowCount() > 0) {
                        foreach ($results as $row) { ?>
                            <p class="profile-name"><?php echo htmlentities($row->firstname . ' ' . $row->surname); ?></p>
                            <p class="designation"><?php echo htmlentities($row->username); ?></p> <!-- Displaying username -->
                        <?php }
                    } ?>
                </div>
            </a>
        </li>
        <p>&nbsp;</p>

        <li class="nav-item">
            <a class="nav-link" href="dashboard.php">
                <span class="menu-title">Dashboard</span>
                <i class="icon-screen-desktop menu-icon"></i>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#ui-basic-staff" aria-expanded="false"
                aria-controls="ui-basic-staff">
                <span class="menu-title">Apointment</span>
                <i class="icon-clock menu-icon"></i>
            </a>
            <div class="collapse" id="ui-basic-staff">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item"><a class="nav-link" href="vac.php">Consulation</a></li>
                    <li class="nav-item"><a class="nav-link" href="vas.php">Service</a></li>
                </ul>
            </div>
        </li>
        <!-- <li class="nav-item">
            <a class="nav-link" href="calendar.php">
                <span class="menu-title">Calendar</span>
                <i class="icon-calendar menu-icon"></i>
            </a>
        </li> -->
    </ul>
</nav>