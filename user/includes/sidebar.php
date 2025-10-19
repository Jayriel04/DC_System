<link rel="stylesheet" href="css/finset-sidebar.css">
<nav class="finset-sidebar">
    <div class="profile">
        <?php
        $uid = $_SESSION['sturecmsnumber'];
        $sql = "SELECT * FROM tblpatient WHERE number = :uid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':uid', $uid, PDO::PARAM_STR);
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_OBJ);
        if ($query->rowCount() > 0) {
            foreach ($results as $row) {
                $img = !empty($row->image) ? '../admin/images/' . htmlentities($row->image) : '../admin/images/default-profile.png';
                $name = htmlentities($row->firstname . ' ' . $row->surname);
                $email = htmlentities($row->username);
                echo '<img src="' . $img . '" alt="Profile">';
                echo '<div class="name">' . $name . '</div>';
                echo '<div class="email">' . $email . '</div>';
            }
        } else {
            echo '<img src="../admin/images/default-profile.png" alt="Default Profile">';
            echo '<div class="name">Guest</div>';
            echo '<div class="email">guest@email.com</div>';
        }
        ?>
    </div>
    <ul class="nav">
        <li class="nav-item">
            <a class="nav-link " href="dashboard.php">
                <i class="c"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link" href="#" onclick="document.getElementById('appointment-menu').classList.toggle('show'); return false;">
                <i class="icon-clock"></i>
                <span>Appointment</span>
                <i class="icon-arrow-down" style="margin-left:auto;"></i>
            </a>
            <div class="dropdown-menu" id="appointment-menu" style="display:none;">
                <a class="dropdown-item" href="vac.php">Consultation</a>
                <a class="dropdown-item" href="vas.php">Service</a>
            </div>
        </li>
    </ul>
    
</nav>
<script>
// Simple dropdown toggle for Appointment
document.querySelectorAll('.nav-link').forEach(function(link) {
    link.addEventListener('click', function(e) {
        if (link.parentElement.classList.contains('dropdown')) {
            e.preventDefault();
            var menu = link.parentElement.querySelector('.dropdown-menu');
            if (menu.style.display === 'block') {
                menu.style.display = 'none';
            } else {
                menu.style.display = 'block';
            }
        }
    });
});
</script>

