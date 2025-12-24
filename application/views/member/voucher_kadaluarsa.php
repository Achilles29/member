<!-- voucher_kadaluarsa.php -->
<div class="section">
    <a href="<?= site_url('voucher') ?>" style="display:inline-block; margin-bottom:15px; background:#8b1c1c; color:white; padding:6px 12px; border-radius:5px; text-decoration:none;">
        <i class="fas fa-arrow-left"></i> Kembali ke Voucher Aktif
    </a>

    <?php foreach ($voucher_kadaluarsa as $v): ?>
        <div class="voucher-card">
            <div class="voucher-code"><?= $v['kode_voucher'] ?>
                <span class="voucher-status kadaluarsa">Kadaluarsa</span>
            </div>
            <div class="voucher-desc"><?= $v['jenis'] === 'produk' ? 'Gratis produk' : 'Diskon' ?></div>
            <div class="voucher-footer">Masa berlaku telah habis</div>
        </div>
    <?php endforeach; ?>
</div>
