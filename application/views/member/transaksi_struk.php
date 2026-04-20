<?php
  $tanggal = $transaksi['waktu_bayar'] ?: $transaksi['waktu_order'];
  $total = (int)($transaksi['total_pembayaran'] ?: $transaksi['total_penjualan']);
?>
<div class="page-content nm-page">
  <div class="nm-topbar nm-topbar--mini nm-print-hide">
    <div>
      <div class="nm-name">Struk Transaksi</div>
      <div class="nm-level"><?= html_escape($transaksi['no_transaksi']) ?></div>
    </div>
    <a class="nm-logout" href="<?= site_url('transaksi/detail/' . (int)$transaksi['id']) ?>" title="Kembali">
      <i class="f7-icons">chevron_left</i>
    </a>
  </div>

  <div class="nm-card nm-struk-card">
    <div class="nm-struk-head">
      <?php if (!empty($outlet['logo'])): ?>
        <div class="nm-struk-logo-wrap">
          <img class="nm-struk-logo" src="<?= base_url('uploads/' . $outlet['logo']) ?>" alt="Logo outlet">
        </div>
      <?php endif; ?>
      <div class="nm-struk-title"><?= html_escape($outlet['nama_outlet'] ?? 'Nama Outlet') ?></div>
      <?php if (!empty($outlet['alamat'])): ?>
        <div class="nm-struk-line"><?= nl2br(html_escape($outlet['alamat'])) ?></div>
      <?php endif; ?>
      <?php if (!empty($outlet['no_telepon'])): ?>
        <div class="nm-struk-line">Telp: <?= html_escape($outlet['no_telepon']) ?></div>
      <?php endif; ?>
    </div>

    <div class="nm-struk-sep"></div>

    <div class="nm-struk-line">No: <?= html_escape($transaksi['no_transaksi']) ?></div>
    <div class="nm-struk-line">Tanggal: <?= date('d M Y H:i', strtotime($tanggal)) ?></div>
    <div class="nm-struk-line">Status: <?= html_escape($transaksi['status_pembayaran'] ?? '-') ?></div>
    <div class="nm-struk-line">Metode: <?= html_escape($transaksi['metode_pembayaran'] ?? '-') ?></div>

    <div class="nm-struk-sep"></div>

    <?php foreach ($items as $it): ?>
      <?php $subtotal = (int)$it['jumlah'] * (int)$it['harga']; ?>
      <div class="nm-struk-item">
        <div class="nm-struk-item-top">
          <div class="nm-struk-item-name"><?= html_escape($it['nama_produk'] ?? 'Produk') ?></div>
          <div class="nm-struk-item-sub">Rp <?= number_format($subtotal) ?></div>
        </div>
        <div class="nm-struk-item-meta"><?= (int)$it['jumlah'] ?> x <?= number_format((int)$it['harga']) ?></div>
        <?php if (!empty($it['catatan'])): ?>
          <div class="nm-struk-item-note">Catatan: <?= html_escape($it['catatan']) ?></div>
        <?php endif; ?>
        <?php if (!empty($it['extras'])): ?>
          <?php foreach ($it['extras'] as $ex): ?>
            <div class="nm-struk-extra-line">
              <span>+ <?= html_escape($ex['nama_extra']) ?> (<?= (int)$ex['jumlah'] ?> x <?= number_format((int)$ex['harga']) ?>)</span>
              <b>Rp <?= number_format((int)$ex['subtotal']) ?></b>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <div class="nm-struk-sep"></div>

    <div class="nm-struk-total">
      <span>Total</span>
      <b>Rp <?= number_format($total) ?></b>
    </div>

    <?php if (!empty($outlet['custom_footer'])): ?>
      <div class="nm-struk-footer"><?= nl2br(html_escape($outlet['custom_footer'])) ?></div>
    <?php endif; ?>
  </div>

  <div class="nm-trx-detail-actions nm-print-hide">
    <a class="nm-trx-btn is-secondary" href="<?= site_url('transaksi/detail/' . (int)$transaksi['id']) ?>">Kembali</a>
    <button class="nm-trx-btn" type="button" onclick="window.print()">Print</button>
  </div>
</div>

<?php $this->load->view('templates/member/bottom_nav'); ?>
