<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Digital</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f4f4f9, #e0d9d0);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-image: url('/assets/kopi.jpg') ;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        .card {
            width: 400px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            background-color: #fff;
            text-align: center;
            position: relative;
        }

        .card-header {
            background: linear-gradient(135deg, #6d4c41, #a6825e);
            color: white;
            padding: 20px;
        }

        .card-header h1 {
            margin: 0;
            font-size: 24px;
        }

        .profile-section {
            margin-top: -50px;
        }

        .profile-picture {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid #ffffff;
            background-color: white; /* Tambahkan background putih */
            overflow: hidden;
            margin: 0 auto;
        }

        .profile-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .info-section {
            padding: 20px;
            background: #f9f9f9;
            margin-top: 10px;
        }

        .stamp-section {
            padding: 15px;
            background: #f4e3d1;
            display: flex;
            flex-wrap: wrap; /* Membolehkan elemen turun ke baris berikutnya */
            justify-content: center;
            align-items: center;
            gap: 5px;
            margin: 20px 0;
        }

        .stamp {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: #6d4c41;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            line-height: 1.2;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 2px solid white;
            flex-shrink: 0;
        }

        .stamp span.date {
            font-size: 15px;
            font-weight: bold;
        }

        .stamp span.time {
            font-size: 15px;
            margin-top: 3px;
        }

        .social-section {
            background: #f9f9f9;
            padding: 10px;
            margin-top: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }

        .social-section .social {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #6d4c41;
        }

        .social-section .social i {
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h1>Namua Coffee & Eatery</h1>
            <div>Loyalty Member Card</div>
            <br><br>
        </div>
        <div class="profile-section">
            <div class="profile-picture">
                <img src="/assets/Logo.png" alt="Profile Picture">
            </div>
            <span><b><?php echo $member['name']; ?></b></span>
            <br>
            <small><b><?php echo $member['phone']; ?></b></small>
            <br>
       <b>  COLLECT 5 STAMPS IN ONE MONTH </b> <br>
        Get free 1 cup of coffee & 1 Snack 
        </div>
        <div id="stamp-section" class="stamp-section">
            <?php foreach ($stamps as $stamp): ?>
                <div class="stamp">
                    <span class="date"><?php echo date('d M', strtotime($stamp['stamp_date'])); ?></span>
                    <span class="time"><?php echo date('H:i', strtotime($stamp['stamp_date'])); ?></span>
                </div>
            <?php endforeach; ?>
            <?php for ($i = count($stamps) + 1; $i <= 5; $i++): ?>
                <div class="stamp" style="background: #d3d3d3; color: #6d6d6d;">
                    <span class="date">-</span>
                    <span class="time">--:--</span>
                </div>
            <?php endfor; ?>
        </div>

        <?php if ($valid_until): ?>
            <div style="font-size: 14px; color: #6d4c41; margin-top: 10px;">
                 <strong>Valid Until: <?php echo date('d M Y', strtotime($valid_until)); ?> </strong>
            </div>
        <?php endif; ?>
<h4>Kode Voucher Anda</h4>
<?php if (!empty($vouchers)): ?>
    <?php foreach ($vouchers as $voucher): ?>
        <p>
            <strong><?php echo htmlspecialchars($voucher['code']); ?></strong> - <?php echo htmlspecialchars($voucher['description']); ?><br>
            Diskon: 
            <?php if ($voucher['discount_type'] == 'nominal'): ?>
                Rp<?php echo number_format($voucher['discount_value'], 0, ',', '.'); ?>
            <?php else: ?>
                <?php echo $voucher['discount_value']; ?>%
            <?php endif; ?><br>
            Berlaku: <strong><?php echo date('d M Y', strtotime($voucher['start_date'])); ?></strong> - 
            <strong><?php echo date('d M Y', strtotime($voucher['end_date'])); ?></strong>
        </p>
    <?php endforeach; ?>
<?php else: ?>
    <p><strong>Belum ada voucher aktif</strong></p>
<?php endif; ?>

            <div class="social-section">
                <div class="social">
                    <a href="https://instagram.com/namuacoffee" target="_blank" style="text-decoration: none; color: #6d4c41;">
                        <i class="icon">📸</i>
                        <span>@namuacoffee</span>
                    </a>
                </div>
                <div class="social">
                    <a href="tel:085150737377" style="text-decoration: none; color: #6d4c41;">
                        <i class="icon">📞</i>
                        <span>0851-5073-7377</span>
                    </a>
                </div>
            </div>

    </div>
</body>
<script>
    function refreshStamps() {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', '<?php echo site_url("member/get_stamps/" . $member["id"]); ?>', true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                document.getElementById('stamp-section').innerHTML = xhr.responseText;
            }
        };
        xhr.send();
    }

    // Refresh bagian stamp setiap 3 detik
    setInterval(refreshStamps, 3000);
    function refreshVoucher() {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', '<?php echo site_url("member/get_voucher/" . $member["id"]); ?>', true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const voucher = JSON.parse(xhr.responseText);
                document.getElementById('voucher-code').innerText = voucher.voucher_code || 'Belum ada kode voucher';
            }
        };
        xhr.send();
    }

    // Refresh voucher setiap 10 detik
    setInterval(refreshVoucher, 10000);
</script>

</html>
