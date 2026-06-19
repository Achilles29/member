<?php
$total   = (int)($transaksi['total_pembayaran'] ?: $transaksi['total_penjualan']);
$tanggal = $transaksi['waktu_bayar'] ?: $transaksi['waktu_order'];
$status  = strtolower($transaksi['status_pembayaran'] ?? '');
$sbadge  = $status === 'lunas' ? 'success' : ($status === 'pending' ? 'warning' : 'neutral');
?>
<div class="page-content nm-page">

  <div class="nm-page-hero nm-page-hero--trx">
    <div class="nm-page-hero__nav">
      <a href="<?= site_url('transaksi') ?>" class="nm-hero-back"><i class="f7-icons">chevron_left</i></a>
      <span class="nm-page-hero__label">Detail Transaksi</span>
      <a href="<?= site_url('transaksi/struk/' . (int)$transaksi['id']) ?>" class="nm-hero-back" style="font-size:12px;font-weight:1000;width:auto;padding:0 10px;">Struk</a>
    </div>
    <div class="nm-page-hero__center" style="padding-bottom:4px;">
      <div class="nm-page-hero__emoji">🧾</div>
      <div class="nm-page-hero__big">Rp <?= number_format($total) ?></div>
      <div class="nm-page-hero__sub"><?= html_escape($transaksi['no_transaksi']) ?></div>
    </div>
  </div>

  <!-- META CARD -->
  <div class="nm-card nm-trx-meta-card">
    <div class="nm-trx-meta-row">
      <div class="nm-trx-meta-item">
        <div class="nm-trx-meta-lbl"><i class="f7-icons">calendar</i> Tanggal</div>
        <div class="nm-trx-meta-val"><?= date('d M Y H:i', strtotime($tanggal)) ?></div>
      </div>
      <div class="nm-trx-meta-item">
        <div class="nm-trx-meta-lbl"><i class="f7-icons">creditcard</i> Metode</div>
        <div class="nm-trx-meta-val"><?= html_escape($transaksi['metode_pembayaran'] ?? '-') ?></div>
      </div>
      <div class="nm-trx-meta-item">
        <div class="nm-trx-meta-lbl"><i class="f7-icons">checkmark_shield</i> Status</div>
        <div class="nm-trx-meta-val"><span class="nm-badge <?= $sbadge ?>"><?= html_escape($transaksi['status_pembayaran'] ?? '-') ?></span></div>
      </div>
    </div>
  </div>

  <!-- ITEMS -->
  <div class="nm-section-head" style="margin-top:16px;">
    <div>
      <div class="nm-section-title">Item Pesanan</div>
      <div class="nm-section-sub"><?= count($items) ?> item</div>
    </div>
  </div>

  <?php if (empty($items)): ?>
    <div class="nm-empty-state nm-card"><div class="nm-empty-state__ico">🛒</div><div class="nm-empty-state__txt">Detail item tidak ditemukan.</div></div>
  <?php else: ?>
    <div class="nm-card nm-item-list">
      <?php foreach ($items as $it): ?>
        <?php $sub = (int)$it['jumlah'] * (int)$it['harga']; ?>
        <div class="nm-item-row">
          <div class="nm-item-row__main">
            <div class="nm-item-row__name"><?= html_escape($it['nama_produk'] ?? 'Produk') ?></div>
            <div class="nm-item-row__meta">
              <?= (int)$it['jumlah'] ?> × Rp <?= number_format((int)$it['harga']) ?>
              <?php if (!empty($it['status'])): ?> <span class="nm-item-row__status"><?= html_escape($it['status']) ?></span><?php endif; ?>
            </div>
            <?php if (!empty($it['catatan'])): ?><div class="nm-item-row__note">📝 <?= html_escape($it['catatan']) ?></div><?php endif; ?>
            <?php if (!empty($it['extras'])): ?>
              <div class="nm-item-row__extras">
                <?php foreach ($it['extras'] as $ex): ?>
                  <div class="nm-item-extra">
                    <span>+ <?= html_escape($ex['nama_extra']) ?> ×<?= (int)$ex['jumlah'] ?></span>
                    <b>Rp <?= number_format((int)$ex['subtotal']) ?></b>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="nm-item-row__sub">Rp <?= number_format($sub) ?></div>
        </div>
      <?php endforeach; ?>
      <div class="nm-item-total">
        <span>Total</span>
        <strong>Rp <?= number_format($total) ?></strong>
      </div>
    </div>
  <?php endif; ?>

  <div class="nm-detail-actions">
    <a class="nm-btn nm-btn--ghost" href="<?= site_url('transaksi') ?>">Kembali</a>
    <a class="nm-btn nm-btn--primary" href="<?= site_url('transaksi/struk/' . (int)$transaksi['id']) ?>">Lihat Struk</a>
  </div>
</div>
<?php $this->load->view('templates/member/bottom_nav'); ?>
