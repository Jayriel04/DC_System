<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
error_reporting(0);
if (strlen($_SESSION['sturecmsaid']==0)) {
  header('location:logout.php');
  } else{
if(isset($_POST['submit']))
{
    $adminid = $_SESSION['sturecmsaid'];
    $current_password_input = $_POST['currentpassword'];
    $new_password_input = $_POST['newpassword'];

    $sql = "SELECT Password FROM tbladmin WHERE ID=:adminid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':adminid', $adminid, PDO::PARAM_STR);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_OBJ);

    // Verify current password and update to new password
    if ($result && (password_verify($current_password_input, $result->Password) || md5($current_password_input) === $result->Password)) {
        $new_hashed_password = password_hash($new_password_input, PASSWORD_DEFAULT);
        
        $con = "UPDATE tbladmin SET Password=:newpassword WHERE ID=:adminid";
        $chngpwd1 = $dbh->prepare($con);
        $chngpwd1->bindParam(':adminid', $adminid, PDO::PARAM_STR);
        $chngpwd1->bindParam(':newpassword', $new_hashed_password, PDO::PARAM_STR);
        $chngpwd1->execute();

        echo '<script>alert("Your password successfully changed. You will be redirected to the dashboard."); window.location.href="dashboard.php";</script>';
        exit();
    } else {
        echo '<script>alert("Your current password is wrong. Please try again."); window.location.href="change-password.php";</script>';
        exit();
    }
}
  ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta charset="utf-8">
    <title>Change Password</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/select2/select2.min.css">
    <link rel="stylesheet" href="vendors/select2-bootstrap-theme/select2-bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/stylev2.css" >
    <link rel="stylesheet" href="css/sidebar.css" />
    <link rel="stylesheet" href="css/dashboard.css">
  </head>
  <body>
    <div class="container-scroller">
     <?php include_once('includes/header.php');?>
      <div class="container-fluid page-body-wrapper">
      <?php include_once('includes/sidebar.php');?>
        <div class="main-panel">
          <div class="content-wrapper">
            
            <div class="change-password-container">
                <div class="change-password-card">
                    <div class="change-password-header">
                        <div class="lock-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        </div>
                        <div class="header-text">
                            <h1>Change Password</h1>
                            <p>Update password for enhanced account security.</p>
                        </div>
                    </div>
            
                    <form class="forms-sample" name="changepassword" method="post" onsubmit="return checkpass();">
                        <div class="form-group">
                            <label for="currentpassword">Current Password <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <input type="password" id="currentpassword" name="currentpassword" class="form-control" placeholder="••••••••••" required="true" oninput="validateForm()">
                                <button type="button" class="toggle-password" onclick="togglePassword('currentpassword')">
                                    <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </button>
                            </div>
                        </div>
            
                        <div class="form-group">
                            <label for="newpassword">New Password <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <input type="password" id="newpassword" name="newpassword" class="form-control" placeholder="••••••••••" required="true" oninput="validateForm()">
                                <button type="button" class="toggle-password" onclick="togglePassword('newpassword')">
                                    <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </button>
                            </div>
                        </div>
            
                        <div class="form-group">
                            <label for="confirmpassword">Confirm New Password <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <input type="password" id="confirmpassword" name="confirmpassword" class="form-control" placeholder="••••••••••" required="true" oninput="validateForm()">
                                <button type="button" class="toggle-password" onclick="togglePassword('confirmpassword')">
                                    <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </button>
                            </div>
                        </div>
            
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div class="bar" id="bar1"></div>
                                <div class="bar" id="bar2"></div>
                                <div class="bar" id="bar3"></div>
                            </div>
                            <div class="strength-text" id="strengthText">Your new password must contain:</div>
                            <ul class="requirements">
                                <li id="uppercase">At least 1 uppercase letter</li>
                                <li id="number">At least 1 number</li>
                                <li id="length">At least 8 characters</li>
                            </ul>
                        </div>
            
                        <div class="button-group">
                            <button type="button" class="btn-discard" onclick="discardChanges()">Discard</button>
                            <button type="submit" name="submit" class="btn-apply" id="applyBtn" disabled>Apply Changes</button>
                        </div>
                    </form>
                </div>
            </div>
          </div>

         <?php include_once('includes/footer.php');?>
        </div>
      </div>
    </div>
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="vendors/select2/select2.min.js"></script>
    <script src="vendors/typeahead.js/typeahead.bundle.min.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <script src="js/typeahead.js"></script>
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.closest('.input-wrapper').querySelector('.toggle-password');
            const eyeOpenIcon = '<svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
            const eyeClosedIcon = '<svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';

            if (field.type === 'password') {
                field.type = 'text';
                button.innerHTML = eyeClosedIcon;
            } else {
                field.type = 'password';
                button.innerHTML = eyeOpenIcon;
            }
        }

        function discardChanges() {
            window.location.href = 'dashboard.php';
        }

        function checkpass() {
            if (document.changepassword.newpassword.value !== document.changepassword.confirmpassword.value) {
                alert('New Password and Confirm Password field does not match');
                document.changepassword.confirmpassword.focus();
                return false;
            }
            return true;
        }

        function validateForm() {
            const password = document.getElementById('newpassword').value;
            const confirm = document.getElementById('confirmpassword').value;
            
            const hasUppercase = /[A-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasLength = password.length >= 8;
            
            document.getElementById('uppercase').classList.toggle('met', hasUppercase);
            document.getElementById('number').classList.toggle('met', hasNumber);
            document.getElementById('length').classList.toggle('met', hasLength);
            
            const metCount = [hasUppercase, hasNumber, hasLength].filter(Boolean).length;
            
            const bar1 = document.getElementById('bar1');
            const bar2 = document.getElementById('bar2');
            const bar3 = document.getElementById('bar3');
            const strengthText = document.getElementById('strengthText');
            
            ['active', 'medium', 'strong', 'weak'].forEach(c => {
                bar1.classList.remove(c); bar2.classList.remove(c); bar3.classList.remove(c);
            });
            
            if (metCount === 1) {
                bar1.classList.add('active', 'weak');
                strengthText.textContent = 'Weak password. Must contain:';
            } else if (metCount === 2) {
                bar1.classList.add('active', 'medium');
                bar2.classList.add('active', 'medium');
                strengthText.textContent = 'Medium password. Could be stronger.';
            } else if (metCount === 3) {
                bar1.classList.add('active', 'strong'); bar2.classList.add('active', 'strong'); bar3.classList.add('active', 'strong');
                strengthText.textContent = 'Strong password!';
            }
            
            const currentPassword = document.getElementById('currentpassword').value;
            document.getElementById('applyBtn').disabled = !(currentPassword && password && confirm && password === confirm && metCount === 3);
        }
        (function($) {
            'use strict';

            if ($(".js-example-basic-single").length) {
                $(".js-example-basic-single").select2();
            }
            if ($(".js-example-basic-multiple").length) {
                $(".js-example-basic-multiple").select2();
            }
            })(jQuery);
    </script>
  </body>
</html><?php }  ?>