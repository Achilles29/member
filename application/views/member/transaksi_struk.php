<?php
$tanggal = $transaksi['waktu_bayar'] ?: $transaksi['waktu_order'];
$total   = (int)($transaksi['total_pembayaran'] ?: $transaksi['total_penjualan']);
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

  <!-- DIGITAL RECEIPT -->
  <div class="nm-receipt">
    <!-- Header -->
    <div class="nm-receipt__head">
      <?php if (!empty($outlet['logo'])): ?>
        <img class="nm-receipt__logo" src="<?= base_url('uploads/' . $outlet['logo']) ?>" alt="Logo">
      <?php else: ?>
        <img class="nm-receipt__logo" src="<?= base_url('uploads/logo.png') ?>" alt="Logo">
      <?php endif; ?>
      <div class="nm-receipt__outlet"><?= html_escape($outlet['nama_outlet'] ?? 'Namua Coffee') ?></div>
      <?php if (!empty($outlet['alamat'])): ?>
        <div class="nm-receipt__addr"><?= nl2br(html_escape($outlet['alamat'])) ?></div>
      <?php endif; ?>
      <?php if (!empty($outlet['no_telepon'])): ?>
        <div class="nm-receipt__addr"><?= html_escape($outlet['no_telepon']) ?></div>
      <?php endif; ?>
    </div>

    <div class="nm-receipt__sep">· · · · · · · · · · · · · · · · · · · ·</div>

    <!-- Meta -->
    <div class="nm-receipt__meta">
      <div class="nm-receipt__meta-row"><span>No</span><span><?= html_escape($transaksi['no_transaksi']) ?></span></div>
      <div class="nm-receipt__meta-row"><span>Tanggal</span><span><?= date('d M Y H:i', strtotime($tanggal)) ?></span></div>
      <div class="nm-receipt__meta-row"><span>Status</span><span><?= html_escape($transaksi['status_pembayaran'] ?? '-') ?></span></div>
      <div class="nm-receipt__meta-row"><span>Metode</span><span><?= html_escape($transaksi['metode_pembayaran'] ?? '-') ?></span></div>
    </div>

    <div class="nm-receipt__sep">· · · · · · · · · · · · · · · · · · · ·</div>

    <!-- Items -->
    <?php foreach ($items as $it): ?>
      <?php $sub = (int)$it['jumlah'] * (int)$it['harga']; ?>
      <div class="nm-receipt__item">
        <div class="nm-receipt__item-top">
          <span class="nm-receipt__item-name"><?= html_escape($it['nama_produk'] ?? 'Produk') ?></span>
          <span class="nm-receipt__item-sub">Rp <?= number_format($sub) ?></span>
        </div>
        <div class="nm-receipt__item-qty"><?= (int)$it['jumlah'] ?> × Rp <?= number_format((int)$it['harga']) ?></div>
        <?php if (!empty($it['catatan'])): ?>
          <div class="nm-receipt__item-note">Ket: <?= html_escape($it['catatan']) ?></div>
        <?php endif; ?>
        <?php foreach ($it['extras'] ?? [] as $ex): ?>
          <div class="nm-receipt__extra">
            <span>+ <?= html_escape($ex['nama_extra']) ?> ×<?= (int)$ex['jumlah'] ?></span>
            <span>Rp <?= number_format((int)$ex['subtotal']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>

    <div class="nm-receipt__sep">· · · · · · · · · · · · · · · · · · · ·</div>

    <div class="nm-receipt__total">
      <span>TOTAL</span>
      <strong>Rp <?= number_format($total) ?></strong>
    </div>

    <?php if (!empty($outlet['custom_footer'])): ?>
      <div class="nm-receipt__footer"><?= nl2br(html_escape($outlet['custom_footer'])) ?></div>
    <?php endif; ?>

    <div class="nm-receipt__footer" style="margin-top:16px;">
      <div class="nm-receipt__thankyou">Terima kasih sudah mengunjungi kami ☕</div>
      <div class="nm-receipt__brand">Namua Coffee & Eatery</div>
    </div>
  </div>

  <div class="nm-detail-actions nm-print-hide">
    <a class="nm-btn nm-btn--ghost" href="<?= site_url('transaksi/detail/' . (int)$transaksi['id']) ?>">Kembali</a>
    <button class="nm-btn nm-btn--primary" type="button" onclick="window.print()">
      <i class="f7-icons">printer</i> Print
    </button>
  </div>
</div>
<?php $this->load->view('templates/member/bottom_nav'); ?>
