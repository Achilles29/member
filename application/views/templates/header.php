<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    
    <title><?= isset($title) ? $title : 'Admin Panel' ?></title> <!-- Title dinamis -->
    
    <!-- Custom fonts for this template-->
    <link href="<?php echo base_url(); ?>assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="<?php echo base_url(); ?>assets/css/sb-admin-2.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial; background: #fff8f0; color: #4b2c20; margin: 0; padding-bottom: 80px; }
        .header { background: #8b1c1c; color: #fff; padding: 15px; display: flex; justify-content: space-between; align-items: center; }
        .header h2 { margin: 0; font-size: 18px; }
        .section { padding: 15px; }

        .voucher-card {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .voucher-code { font-size: 14px; font-weight: bold; color: #8b1c1c; }
        .voucher-desc { font-size: 13px; margin-top: 5px; }
        .voucher-footer { font-size: 12px; margin-top: 10px; color: #777; }
        .voucher-status { padding: 4px 10px; border-radius: 20px; font-size: 11px; float: right; }

        .aktif { background: #28a745; color: #fff; }
        .habis { background: #ffc107; color: #4b2c20; }
        .kadaluarsa { background: #dc3545; color: #fff; }

        .bottom-nav {
            position: fixed; bottom: 0; left: 0; right: 0;
            background: #8b1c1c;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            z-index: 99;
        }
        .bottom-nav a {
            color: #fff;
            font-size: 12px;
            text-decoration: none;
            text-align: center;
        }
        .bottom-nav i { display: block; font-size: 18px; }
    </style>
</head>


<body>

<div class="header">
    <h2><?= $member['nama']; ?></h2>
    <a href="<?= site_url('member/logout') ?>" title="Logout" style="color:#fff;"><i class="fas fa-sign-out-alt"></i></a>
</div>
