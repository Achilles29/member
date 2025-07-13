
<div class="section">
    <h3>Voucher Aktif</h3>
    <?php foreach ($voucher_aktif as $v): ?>
        <div class="voucher-card">
            <div class="voucher-code"><?= $v['kode_voucher'] ?>
                <span class="voucher-status aktif">Aktif</span>
            </div>
            <div class="voucher-desc">
                <?php if ($v['jenis'] === 'produk'): ?>
                    Gratis produk:
                    <?php
                        $produk = $this->db->get_where('pr_produk', ['id' => $v['produk_id']])->row('nama_produk');
                    echo $produk ?? 'Produk tidak ditemukan';
                    ?>
                <?php elseif ($v['jenis'] === 'diskon'): ?>
                    <?php if (isset($v['tipe_diskon']) && $v['tipe_diskon'] === 'persentase'): ?>
                        Diskon <?= $v['nilai'] ?>% (Max Rp<?= number_format($v['max_diskon'], 0, ',', '.') ?>)
                    <?php else: ?>
                        Diskon Rp<?= number_format($v['nilai'], 0, ',', '.') ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="voucher-footer">
                Berlaku: <?= date('d M Y', strtotime($v['tanggal_mulai'])) ?> - <?= date('d M Y', strtotime($v['tanggal_berakhir'])) ?>
            </div>
        </div>
    <?php endforeach; ?>
    <div style="margin-bottom: 15px; text-align: center;">
    <a href="<?= site_url('voucher/digunakan') ?>" class="btn btn-sm btn-warning" style="padding: 6px 12px; margin-right: 5px; background: #ffc107; color: #4b2c20; border-radius: 5px; text-decoration: none;">Voucher Digunakan</a>
    <a href="<?= site_url('voucher/kadaluarsa') ?>" class="btn btn-sm btn-danger" style="padding: 6px 12px; background: #dc3545; color: #fff; border-radius: 5px; text-decoration: none;">Voucher Kadaluarsa</a>
</div>
<!-- 
    <h3>Voucher Sudah Digunakan</h3>
    <?php foreach ($voucher_digunakan as $v): ?>

        <div class="voucher-card">
            <div class="voucher-code"><?= $v['kode_voucher'] ?>
                <span class="voucher-status habis">Digunakan</span>
            </div>
            <div class="voucher-desc"><?= $v['jenis'] === 'produk' ? 'Gratis produk' : 'Diskon' ?></div>
            <div class="voucher-footer">Digunakan dalam transaksi</div>
        </div>
    <?php endforeach; ?>

    <h3>Voucher Kadaluarsa</h3>
    <?php foreach ($voucher_kadaluarsa as $v): ?>
        <div class="voucher-card">
            <div class="voucher-code"><?= $v['kode_voucher'] ?>
                <span class="voucher-status kadaluarsa">Kadaluarsa</span>
            </div>
            <div class="voucher-desc"><?= $v['jenis'] === 'produk' ? 'Gratis produk' : 'Diskon' ?></div>
            <div class="voucher-footer">Masa berlaku telah habis</div>
        </div>
    <?php endforeach; ?>
</div> -->


</body>
</html>
