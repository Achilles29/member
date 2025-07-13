<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Redeem Poin/Stamp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial; background: #fff8f0; color: #4b2c20; margin: 0; padding-bottom: 80px; }
        .header { background: #8b1c1c; color: #fff; padding: 15px; display: flex; justify-content: space-between; align-items: center; }
        .header h2 { margin: 0; font-size: 18px; }
        .section { padding: 15px; }

        .redeem-card {
            background: #fff;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            transition: transform 0.3s;
            position: relative;
        }
        .redeem-card:hover { transform: scale(1.02); }

        .redeem-card .badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ffc107;
            color: #4b2c20;
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 20px;
        }
        .redeem-card img.redeem-img {
            width: 100%;
            height: 130px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .redeem-title { font-size: 16px; font-weight: bold; margin-bottom: 6px; }
        .redeem-desc { font-size: 13px; color: #555; margin-bottom: 8px; }

        .redeem-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .redeem-btn {
            background: #8b1c1c;
            color: white;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 13px;
            text-decoration: none;
        }
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

        .stamp-img { width: 20px; height: 20px; vertical-align: middle; }

        /* Loader overlay */
        #loading {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255,255,255,0.8);
            z-index: 9999;
            text-align: center;
        }
        #loading div {
            margin-top: 50%;
            font-size: 18px;
            color: #8b1c1c;
        }
    </style>
</head>
<body>

<div id="loading">
    <div><i class="fas fa-spinner fa-spin fa-2x"></i><br>Memproses...</div>
</div>

<div class="section">

<?php if ($this->session->flashdata('success')): ?>
    <div style="background:#d4edda;color:#155724;padding:10px;border-radius:6px;margin-bottom:15px;">
        <?= $this->session->flashdata('success') ?>
    </div>
<?php elseif ($this->session->flashdata('error')): ?>
    <div style="background:#f8d7da;color:#721c24;padding:10px;border-radius:6px;margin-bottom:15px;">
        <?= $this->session->flashdata('error') ?>
    </div>
<?php endif; ?>


    <h3 style="margin-bottom: 15px;">Voucher dari Poin</h3>
    <?php if (!empty($redeem_poin)): ?>
        <?php foreach ($redeem_poin as $item): ?>
        <!-- Di dalam loop -->
        <div class="redeem-card">
            <div class="badge"><?= ucfirst($item['jenis']) ?></div>

            <div class="redeem-title"><?= $item['nama_redeem'] ?></div>

            <?php if ($item['jenis_voucher'] == 'produk' && !empty($item['produk_id'])):
                $produk = $this->db->get_where('pr_produk', ['id' => $item['produk_id']])->row_array();
                ?>
                <div class="redeem-desc">Produk: <?= $produk['nama_produk'] ?? 'Produk tidak ditemukan' ?></div>
            <?php elseif ($item['jenis_voucher'] == 'diskon'): ?>
                <div class="redeem-desc">
                    <?= $item['tipe_diskon'] == 'persentase'
                        ? $item['nilai_voucher'] . '% (max Rp ' . number_format($item['max_diskon'], 0, ',', '.') . ')'
                        : 'Diskon Rp ' . number_format($item['nilai_voucher'], 0, ',', '.') ?>
                </div>
            <?php endif; ?>

            <div class="redeem-desc">
                Butuh <?= $item['jumlah_dibutuhkan'] ?>
                <?= $item['jenis'] == 'poin' ? '⭐' : '<img src="' . base_url('uploads/logo.png') . '" class="stamp-img" alt="Stamp">' ?>
            </div>

            <div class="redeem-footer">
                <span>
                    <?= $item['jenis'] == 'poin' ? 'Poin Anda: ' . $poin : 'Stamp Anda: ' . array_sum(array_column($stamp, 'jumlah_stamp')) ?>
                </span>
                <?php
                    $cukup = true;
            if ($item['jenis'] == 'poin' && $poin < $item['jumlah_dibutuhkan']) {
                $cukup = false;
            }
            if ($item['jenis'] == 'stamp' && $stamp_total < $item['jumlah_dibutuhkan']) {
                $cukup = false;
            }
            ?>
                    <?php if ($cukup): ?>
                        <a href="<?= site_url('redeem/process/' . $item['id']) ?>" class="redeem-btn redeem-trigger">Redeem</a>
                    <?php else: ?>
                        <span class="redeem-btn" style="background:#ccc; cursor: not-allowed;">Tidak Cukup</span>
                    <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="text-align:center;">Belum ada yang bisa diredeem dengan poin.</p>
    <?php endif; ?>

    <h3 style="margin:30px 0 15px;">Voucher dari Stamp</h3>
    <?php if (!empty($redeem_stamp)): ?>
        <?php
            $stamp_total = array_sum(array_column($stamp, 'jumlah_stamp'));
        ?>
        <?php foreach ($redeem_stamp as $item): ?>
        <!-- Di dalam loop -->
        <div class="redeem-card">
            <div class="badge"><?= ucfirst($item['jenis']) ?></div>

            <div class="redeem-title"><?= $item['nama_redeem'] ?></div>

            <?php if ($item['jenis_voucher'] == 'produk' && !empty($item['produk_id'])):
                $produk = $this->db->get_where('pr_produk', ['id' => $item['produk_id']])->row_array();
                ?>
                <div class="redeem-desc">Produk: <?= $produk['nama_produk'] ?? 'Produk tidak ditemukan' ?></div>
            <?php elseif ($item['jenis_voucher'] == 'diskon'): ?>
                <div class="redeem-desc">
                    <?= $item['tipe_diskon'] == 'persentase'
                        ? $item['nilai_voucher'] . '% (max Rp ' . number_format($item['max_diskon'], 0, ',', '.') . ')'
                        : 'Diskon Rp ' . number_format($item['nilai_voucher'], 0, ',', '.') ?>
                </div>
            <?php endif; ?>

            <div class="redeem-desc">
                Butuh <?= $item['jumlah_dibutuhkan'] ?>
                <?= $item['jenis'] == 'poin' ? '⭐' : '<img src="' . base_url('uploads/logo.png') . '" class="stamp-img" alt="Stamp">' ?>
            </div>

            <div class="redeem-footer">
                <span>
                    <?= $item['jenis'] == 'poin' ? 'Poin Anda: ' . $poin : 'Stamp Anda: ' . array_sum(array_column($stamp, 'jumlah_stamp')) ?>
                </span>
                <?php
                    $cukup = true;
            if ($item['jenis'] == 'poin' && $poin < $item['jumlah_dibutuhkan']) {
                $cukup = false;
            }
            if ($item['jenis'] == 'stamp' && $stamp_total < $item['jumlah_dibutuhkan']) {
                $cukup = false;
            }
            ?>
                    <?php if ($cukup): ?>
                        <a href="<?= site_url('redeem/process/' . $item['id']) ?>" class="redeem-btn redeem-trigger">Redeem</a>
                    <?php else: ?>
                        <span class="redeem-btn" style="background:#ccc; cursor: not-allowed;">Tidak Cukup</span>
                    <?php endif; ?>


            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="text-align:center;">Belum ada yang bisa diredeem dengan stamp.</p>
    <?php endif; ?>
</div>


<script>

document.querySelectorAll('.redeem-trigger').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('Apakah Anda yakin ingin menukar poin/stamp untuk voucher ini?')) {
            document.getElementById('loading').style.display = 'block';
            window.location.href = this.href;
        }
    });
});

    
</script>

</body>
</html>
