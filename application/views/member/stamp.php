<?php $this->load->helper('url'); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Stamp Saya - Namua</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fff8f0;
            margin: 0;
            padding-bottom: 80px;
            color: #4b2c20;
        }
        .header {
            background: #8b1c1c;
            color: #fff8f0;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h2 {
            margin: 0;
            font-size: 18px;
        }
        .container {
            padding: 20px;
        }
        .stamp-card {
            background: #fff;
            border-radius: 10px;
            margin-bottom: 15px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        }
        .stamp-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #8b1c1c;
        }
        .stamp-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .stamp-logo {
            width: 32px;
            height: 32px;
            object-fit: contain;
        }
        .stamp-item {
            background: #fceee4;
            padding: 5px;
            border-radius: 6px;
        }
        .empty-message {
            text-align: center;
            font-size: 14px;
            margin-top: 50px;
            color: #888;
        }
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #8b1c1c;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            color: #fff8f0;
            z-index: 99;
        }
        .bottom-nav a {
            text-align: center;
            color: #fff8f0;
            text-decoration: none;
            font-size: 12px;
        }
        .bottom-nav i {
            display: block;
            font-size: 18px;
        }
        .bottom-nav .active {
            color: #ffc107;
        }
    </style>
</head>
<body>


<div class="container">
    <h3>Stamp Saya</h3>

    <?php if (!empty($stamp_list)): ?>
        <?php foreach ($stamp_list as $stamp): ?>
            <div class="stamp-card">
                <div class="stamp-title"><?= $stamp['nama_promo'] ?></div>
                <div class="stamp-list">
                    <?php for ($i = 1; $i <= $stamp['total_stamp_target']; $i++): ?>
                        <div class="stamp-item">
                            <?php if ($i <= $stamp['jumlah_stamp']): ?>
                                <img src="<?= base_url('uploads/logo.png') ?>" class="stamp-logo" alt="stamp">
                            <?php else: ?>
                                <img src="<?= base_url('uploads/logo.png') ?>" class="stamp-logo" style="opacity: 0.2;" alt="stamp">
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-message">
            Belum ada stamp yang dikumpulkan. Yuk mulai transaksi dan kumpulkan cap-mu! ☕✨
        </div>
    <?php endif; ?>
</div>

</body>
</html>
