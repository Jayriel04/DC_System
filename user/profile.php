<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Patient Profile</title>
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
  <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
  <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="./vendors/daterangepicker/daterangepicker.css">
  <link rel="stylesheet" href="./vendors/chartist/chartist.min.css">
    <link href="../css/bootstrap.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .profile-header {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px #f0f4fa;
            padding: 24px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 24px;
            margin-top: 30px;
        }
        .profile-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #e5e7eb;
            object-fit: cover;
            margin-right: 24px;
        }
        .profile-info {
            flex: 1;
        }
        .profile-actions {
            display: flex;
            gap: 12px;
        }
        .profile-status {
            display: inline-block;
            background: #e6f7ec;
            color: #1fa463;
            font-size: 13px;
            border-radius: 12px;
            padding: 2px 12px;
            margin-bottom: 6px;
        }
        .profile-tabs {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 4px #f0f4fa;
            margin-bottom: 24px;
        }
        .nav-tabs .nav-link {
            border: none;
            color: #555;
            font-weight: 500;
            padding: 12px 24px;
        }
        .nav-tabs .nav-link.active {
            color: #2563eb;
            border-bottom: 2px solid #2563eb;
            background: transparent;
        }
    </style>
</head>
<body>
    <?php include_once(__DIR__ . '/../includes/header.php'); ?>
<div class="container mt-4">
    <div class="profile-header">
        <img src="../images/avatar-placeholder.png" alt="Avatar" class="profile-avatar">
        <div class="profile-info">
            <h4 style="margin-bottom: 4px;"></h4>
            <div style="font-size:15px; color:#555; margin-bottom: 6px;">Patient ID: </div>
            <span class="profile-status"></span>
            <div class="row" style="margin-top:10px;">
                <div class="col-auto" style="min-width:180px;">
                    <div style="font-size:13px; color:#888;">Email:</div>
                    <div style="font-size:15px;"></div>
                </div>
                <div class="col-auto" style="min-width:140px;">
                    <div style="font-size:13px; color:#888;">Phone:</div>
                    <div style="font-size:15px;"></div>
                </div>
                <div class="col-auto" style="min-width:140px;">
                    <div style="font-size:13px; color:#888;">Date of Birth:</div>
                    <div style="font-size:15px;"></div>
                </div>
            </div>
        </div>
        <div class="profile-actions">
            <button class="btn btn-outline-primary" style="font-weight:500;">
                <i class="fa-solid fa-pen-to-square"></i> Edit Profile
            </button>
            <button class="btn btn-primary" style="font-weight:500;">
                <i class="fa-solid fa-calendar-week"></i> Schedule Appointment
            </button>
        </div>
    </div>
    <div class="profile-tabs">
        <ul class="nav nav-tabs" id="profileTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link " id="overview-tab" data-toggle="tab" href="#overview" role="tab">
                    <i class="fa-solid fa-list"></i> Overview
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="medical-tab" data-toggle="tab" href="#medical" role="tab">
                    <i class="fa-solid fa-calendar-days"></i> Appointments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="vitals-tab" data-toggle="tab" href="#vitals" role="tab">
                    <i class="fa-solid fa-notes-medical"></i> Medication Records
                </a>
            </li>
        </ul>

    </div>
</div>
<script src="../js/jquery-1.11.0.min.js"></script>
<script src="../js/bootstrap.js"></script>
</body>
</html>