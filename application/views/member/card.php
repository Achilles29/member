<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Namua Member</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        body {
            margin: 0;
            font-family: 'Arial', sans-serif;
            background-color: #fff8f0;
            color: #4b2c20;
        }
        .header {
            background-color: #8b1c1c;
            color: #fff8f0;
            padding: 20px;
            border-bottom: 3px solid #fff8f0;
        }
        .header h2 {
            margin: 0;
            font-size: 20px;
        }
        .level {
            color: #b5f2c2;
            font-weight: bold;
        }
        .top-icons {
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            background: #fff;
            border-bottom: 1px solid #ccc;
        }
        .top-icons a {
            text-decoration: none;
            color: #4b2c20;
            font-size: 14px;
            text-align: center;
        }
        .top-icons i {
            font-size: 20px;
            display: block;
            margin-bottom: 5px;
        }
        .poin-section {
            text-align: center;
            padding: 30px 20px;
            background: #fceee4;
            border-bottom: 1px solid #eee;
        }
        .poin-section h1 {
            font-size: 48px;
            margin: 0;
            color: #8b1c1c;
        }
        .poin-section small {
            display: block;
            margin-top: 5px;
        }
        .track-line {
            margin: 20px auto 10px;
            width: 90%;
            height: 6px;
            background: #ddd;
            border-radius: 10px;
            position: relative;
        }
        .track-progress {
            background: #4CAF50;
            height: 100%;
            border-radius: 10px;
        }
        .track-point {
            position: absolute;
            top: -6px;
            width: 12px;
            height: 12px;
            background: #fff;
            border: 2px solid #4CAF50;
            border-radius: 50%;
        }
        .track-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
            padding: 0 20px;
            font-size: 12px;
        }
        .cta-buttons {
            margin-top: 15px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .cta-buttons a {
            padding: 8px 15px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: bold;
        }
        .details-btn {
            background: #fff;
            border: 2px solid #8b1c1c;
            color: #8b1c1c;
        }
        .redeem-btn {
            background: #4b2c20;
            color: #fff;
        }
        .promo-slider {
            margin: 20px;
        }
        .promo-slider img {
            width: 100%;
            border-radius: 10px;
        }
        .news-section {
            padding: 10px 20px 70px;
        }
        .news-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 10px;
        }
        .news-item {
            display: flex;
            margin-bottom: 15px;
            background: #fff;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .news-item img {
            width: 60px;
            height: 60px;
            border-radius: 6px;
            margin-right: 10px;
        }
        .news-item div {
            flex: 1;
        }
        .news-item h4 {
            margin: 0;
            font-size: 14px;
            color: #8b1c1c;
        }
        .news-item p {
            margin: 3px 0 0;
            font-size: 12px;
            color: #555;
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
            text-decoration: none;
            text-align: center;
            color: #fff8f0;
            font-size: 12px;
        }
        .bottom-nav i {
            font-size: 18px;
            display: block;
        }
    </style>
</head>
<body>

<div class="header">
    <h2>Good Morning, <?= $member['nama']; ?> <span class="level"> - <?= $level; ?> Level</span></h2>
</div>

<div class="top-icons">
    <a href="#"><i class="fas fa-user-circle"></i>Profile</a>
    <a href="#"><i class="fas fa-envelope"></i>Inbox</a>
    <a href="#"><i class="fas fa-ticket-alt"></i>Voucher</a>
</div>

<div class="poin-section">
    <div>
        <h1><?= $poin; ?>★</h1>
        <small>Star Balance</small>
    </div>
    <div class="track-line">
        <div class="track-progress" style="width: <?= min(($poin / 400) * 100, 100); ?>%"></div>
        <?php foreach ([30, 60, 120, 240, 400] as $point): ?>
            <div class="track-point" style="left: <?= ($point / 400) * 100; ?>%;"></div>
        <?php endforeach; ?>
    </div>
    <div class="track-labels">
        <span>30</span><span>60</span><span>120</span><span>240</span><span>400</span>
    </div>
    <div class="cta-buttons">
        <a href="#" class="details-btn"><i class="fas fa-info-circle"></i> Details</a>
        <a href="#" class="redeem-btn"><i class="fas fa-star"></i> Redeem</a>
    </div>
</div>

<div class="promo-slider">
    <img src="<?= base_url('assets/img/promo1.jpg') ?>" alt="Promo 1">
</div>

<div class="news-section">
    <div class="news-title">Namua News</div>
    <?php for ($i = 1; $i <= 10; $i++): ?>
        <div class="news-item">
            <img src="<?= base_url("assets/img/news{$i}.jpg") ?>" alt="News <?= $i ?>">
            <div>
                <h4>Promo Spesial <?= $i ?></h4>
                <p>Dapatkan diskon menarik hanya untuk member setia Namua!</p>
            </div>
        </div>
    <?php endfor; ?>
</div>

<div class="bottom-nav">
    <a href="#"><i class="fas fa-home"></i>Home</a>
    <a href="#"><i class="fas fa-id-card"></i>Card</a>
    <a href="#"><i class="fas fa-mug-hot"></i>Order</a>
    <a href="#"><i class="fas fa-gift"></i>Reward</a>
    <a href="#"><i class="fas fa-store"></i>Store</a>
</div>

</body>
</html>
