<link rel="stylesheet" href="css/header.css">
<header class="header">
  <div class="header-container">
    <div class="header-content">
      <div class="logo-section">
        <div class="logo-container">
         
          <img src="images/Jf logo.png" alt="JF Dental Care Logo" class="logo-img" style="height:40px; margin-right:10px;">
          <span class="logo-text">JF DENTAL CARE</span>
        </div>
      </div>
      <?php
        $current = basename($_SERVER['PHP_SELF']);
      ?>
      <nav class="desktop-nav">
        <a href="index.php" class="nav-link<?php if($current == 'index.php') echo ' active'; ?>">Home</a>
        <a href="about.php" class="nav-link<?php if($current == 'about.php') echo ' active'; ?>">About Us</a>
        <a href="services.php" class="nav-link<?php if($current == 'services.php') echo ' active'; ?>">Services</a>
        <a href="contact.php" class="nav-link<?php if($current == 'contact.php') echo ' active'; ?>">Contact Us</a>
      </nav>
      <div class="action-buttons">
        <a href="user/login.php" class="login-btn">LOGIN</a>
        <a href="user/create_account.php" class="signup-btn">SIGN UP</a>
      </div>
    </div>
  </div>
</header>
