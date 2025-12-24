<?php $this->load->helper('url'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Namua Member</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

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
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h2 {
            margin: 0;
            font-size: 18px;
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
            padding: 25px 20px;
            background: #fceee4;
            border-bottom: 1px solid #eee;
        }

        .poin-section h1 {
            font-size: 42px;
            margin: 0;
            color: #8b1c1c;
        }

        .poin-section small {
            display: block;
            margin-top: 5px;
            font-size: 13px;
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

        .status-cards {
            display: flex;
            justify-content: space-around;
            margin: 20px 10px;
        }

        .status-cards div {
            text-align: center;
        }


        .promo-slider img {
            width: 100%;
            border-radius: 10px;
        }

        .voucher-slider {
            overflow-x: auto;
            white-space: nowrap;
            padding: 10px;
        }

        .voucher-slider > div {
            display: inline-block;
            width: 200px;
            background: #fff;
            border-radius: 8px;
            padding: 10px;
            margin-right: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .news-section {
            padding: 10px 20px 70px;
        }

        .news-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .promo-title {
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

        .bottom-nav .active {
            color: #ffd700;
        }

        .bottom-nav span {
            font-size: 11px;
            display: block;
        }
        img.responsive-img {
            max-width: 100%;
            height: auto;
            display: block;
            border-radius: 10px;
            margin-bottom: 15px;
            object-fit: cover;
        }

        .promo-title {
            font-weight: bold;
            margin-bottom: 8px;
        }


        .promo-slide {
            min-width: 100%;
            transition: transform 0.5s ease-in-out;
            flex-shrink: 0;
        }

        .promo-slide img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 10px;
            display: block;
        }
        .promo-slider-wrapper {
            padding: 20px;
            background: #f9e8e0; /* warna lembut, bisa diganti sesuai brand */
            border-top: 1px solid #e0ccc0;
            border-bottom: 1px solid #e0ccc0;
        }
        .promo-slider {
    display: flex;
    transition: transform 0.8s ease-in-out;
    width: 100%;
}

#sliderWrapper {
    width: 100%;
    overflow: hidden;
    border-radius: 10px;
}
.status-cards h3 {
    margin-bottom: 5px;
    font-weight: bold;
}


    </style>
</head>
<body>

<!-- <div class="header">
    <h2><?= $member['nama']; ?></h2>
    <a href="<?= site_url('member/logout') ?>" title="Logout" style="color:#fff;"><i class="fas fa-sign-out-alt"></i></a>
</div> -->

<div class="top-icons">
    <a href="<?= site_url('profile') ?>"><i class="fas fa-user-circle"></i>Profile</a>
    <a href="#"><i class="fas fa-envelope"></i>Inbox</a>
    <a href="/voucher"><i class="fas fa-ticket-alt"></i>Voucher</a>
</div>

<div class="poin-section">
    <h1><?= $poin; ?>★</h1>
    <small>Star Balance</small>

    <div class="cta-buttons">
        <a href="#" class="details-btn"><i class="fas fa-info-circle"></i> Details</a>
        <a href="/redeem" class="redeem-btn"><i class="fas fa-star"></i> Redeem</a>
    </div>
</div>

<div class="status-cards">
    <div>
        <h3 style="color:#8b1c1c;"><?= $poin ?>★</h3>
        <small>Poin</small>
    </div>

    <?php if (!empty($stamp_list)): ?>
    <?php foreach ($stamp_list as $s): ?>
        <div>
            <h3><?= $s['jumlah_stamp'] ?> / <?= $s['total_stamp_target'] ?></h3>
            <small><?= $s['nama_promo'] ?></small>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div>
        <h3>🚫</h3>
        <small>Stamp</small>
    </div>
<?php endif; ?>


<div>
    <h3 style="color:#ff9800;">
        <?= count($voucher_aktif ?? []) ?>
    </h3>
    <small>Voucher</small>
</div>
</div>
<div class="promo-slider-wrapper">
    <div class="promo-title">Our Promo</div>
    <div id="sliderWrapper">
        <div class="promo-slider" id="promoSlider">
            <?php foreach ($promos as $p): ?>
                <div class="promo-slide">
                    <img src="<?= dashboard_url($p['gambar']) ?>" alt="<?= $p['judul'] ?>">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>


<div class="news-section">
    <div class="news-title">Namua News</div>
    <?php foreach ($news as $n): ?>
        <div class="news-item">
            <img src="<?= dashboard_url($n['gambar']) ?>" alt="<?= $n['judul'] ?>" class="responsive-img" style="width: 60px; height: 60px; object-fit: cover;">
            <div>
                <h4><?= $n['judul'] ?></h4>
                <?php $konten = isset($n['konten']) ? $n['konten'] : ($n['deskripsi'] ?? ''); ?>
                <p><?= word_limiter(strip_tags($konten), 150); ?></p>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<!-- 
<div class="bottom-nav">
    <a href="#" class="active"><i class="fas fa-home"></i><span>Home</span></a>
    <a href="<?= site_url('poin') ?>"><i class="fas fa-star"></i><span>Poin</span></a>
    <a href="<?= site_url('stamp') ?>"><i class="fas fa-stamp"></i><span>Stamp</span></a>
    <a href="#"><i class="fas fa-ticket-alt"></i><span>Voucher</span></a>
    <a href="#"><i class="fas fa-user"></i><span>Akun</span></a>
</div> -->
<script>
let currentSlide = 0;
const slides = document.querySelectorAll("#promoSlider .promo-slide");
const totalSlides = slides.length;

function showNextSlide() {
    currentSlide = (currentSlide + 1) % totalSlides;
    document.getElementById("promoSlider").style.transform = `translateX(-${currentSlide * 100}%)`;
}

if (slides.length > 1) {
    setInterval(showNextSlide, 4000);
}

</script>

</body>
</html>
