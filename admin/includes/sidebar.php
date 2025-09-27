<nav class="sidebar" id="sidebar">
  <ul class="nav">
    <li class="nav-item nav-profile">
      <a href="#" class="nav-link">
        <div class="profile-image">
          <img class="img-xs rounded-circle" src="images/faces/face8.jpg" alt="profile image">
          <div class="dot-indicator bg-success"></div>
        </div>
        <div class="text-wrapper">
          <?php
          $aid = $_SESSION['sturecmsaid'];
          $sql = "SELECT * from tbladmin where ID=:aid";

          $query = $dbh->prepare($sql);
          $query->bindParam(':aid', $aid, PDO::PARAM_STR);
          $query->execute();
          $results = $query->fetchAll(PDO::FETCH_OBJ);

          $cnt = 1;
          if ($query->rowCount() > 0) {
            foreach ($results as $row) { ?>
              <p class="profile-name"><?php echo htmlentities($row->AdminName); ?></p>
              <p class="designation"><?php echo htmlentities($row->Email); ?></p><?php $cnt = $cnt + 1;
            }
          } ?>
        </div>

      </a>
    </li>
    <br>
    <br>
    <br>
    <li class="nav-item">
      <a class="nav-link" href="dashboard.php">
        <span class="menu-title">Dashboard</span>
        <i class="icon-home menu-icon"></i>
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
          <li class="nav-item"><a class="nav-link" href="mac.php">Consulation</a></li>
          <li class="nav-item"><a class="nav-link" href="mas.php">Service</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item">
      <a class="nav-link" href="manage-inventory.php">
        <span class="menu-title">Inventory</span>
        <i class="icon-layers menu-icon"></i>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link" href="manage-patient.php">
        <span class="menu-title">Patient</span>
        <i class="icon-notebook menu-icon"></i>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link" href="manage-service.php">
        <span class="menu-title">Service</span>
        <i class="icon-star menu-icon"></i>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link" href="calendar.php">
        <span class="menu-title">Calendar</span>
        <i class="icon-calendar menu-icon"></i>
      </a>
    </li>
    </li>
  </ul>
</nav> 