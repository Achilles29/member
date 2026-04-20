<?php
  $total = (int)($transaksi['total_pembayaran'] ?: $transaksi['total_penjualan']);
  $tanggal = $transaksi['waktu_bayar'] ?: $transaksi['waktu_order'];
?>
<div class="page-content nm-page">
  <div class="nm-topbar nm-topbar--mini">
    <div>
      <div class="nm-name">Detail Transaksi</div>
      <div class="nm-level"><?= html_escape($transaksi['no_transaksi']) ?></div>
    </div>
    <a class="nm-logout" href="<?= site_url('transaksi') ?>" title="Kembali">
      <i class="f7-icons">chevron_left</i>
    </a>
  </div>

  <div class="nm-card">
    <div class="nm-history-date"><?= date('d M Y H:i', strtotime($tanggal)) ?></div>
    <div class="nm-history-trx">Status: <?= html_escape($transaksi['status_pembayaran'] ?? '-') ?></div>
    <div class="nm-history-trx">Metode Bayar: <?= html_escape($transaksi['metode_pembayaran'] ?? '-') ?></div>
    <div class="nm-history-point">Total: Rp <?= number_format($total) ?></div>
  </div>

  <div class="nm-section-head">
    <div>
      <div class="nm-section-title">Item Transaksi</div>
      <div class="nm-section-sub"><?= count($items) ?> item</div>
    </div>
  </div>

  <?php if (empty($items)): ?>
    <div class="nm-card nm-empty-card">Detail item tidak ditemukan.</div>
  <?php else: ?>
    <div class="nm-card">
      <?php foreach ($items as $it): ?>
        <?php $subtotal = (int)$it['jumlah'] * (int)$it['harga']; ?>
        <div class="nm-trx-item">
          <div class="nm-trx-item-main">
            <div class="nm-trx-item-name"><?= html_escape($it['nama_produk'] ?? 'Produk') ?></div>
            <div class="nm-trx-item-meta">
              <?= (int)$it['jumlah'] ?> x Rp <?= number_format((int)$it['harga']) ?>
              <?php if (!empty($it['status'])): ?>
                <span class="nm-trx-item-status">(<?= html_escape($it['status']) ?>)</span>
              <?php endif; ?>
            </div>
            <?php if (!empty($it['catatan'])): ?>
              <div class="nm-trx-item-note"><?= html_escape($it['catatan']) ?></div>
            <?php endif; ?>
            <?php if (!empty($it['extras'])): ?>
              <div class="nm-trx-extra-wrap">
                <?php foreach ($it['extras'] as $ex): ?>
                  <div class="nm-trx-extra-line">
                    <span>+ <?= html_escape($ex['nama_extra']) ?> (<?= (int)$ex['jumlah'] ?> x Rp <?= number_format((int)$ex['harga']) ?>)</span>
                    <b>Rp <?= number_format((int)$ex['subtotal']) ?></b>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="nm-trx-item-subtotal">Rp <?= number_format($subtotal) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="nm-trx-detail-actions">
    <a class="nm-trx-btn is-secondary" href="<?= site_url('transaksi') ?>">Kembali</a>
    <a class="nm-trx-btn" href="<?= site_url('transaksi/struk/' . (int)$transaksi['id']) ?>">Lihat Struk</a>
  </div>
</div>

<?php $this->load->view('templates/member/bottom_nav'); ?>
