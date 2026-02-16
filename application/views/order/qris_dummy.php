<div class="page-content nm-page nm-order">
  <div class="nm-topbar nm-topbar--mini">
    <div>
      <div class="nm-name"><?= html_escape($title ?? 'QRIS (Dummy)') ?></div>
      <div class="nm-level">
        <?php if (!empty($nomor_meja)): ?>
          Meja <?= html_escape($nomor_meja) ?>
        <?php endif; ?>
      </div>
    </div>
    <a class="nm-logout" href="<?= site_url('member/logout') ?>" title="Logout">
      <i class="f7-icons">rectangle_porous_arrow_right</i>
    </a>
  </div>

  <div class="nm-card" style="margin-top:-22px;">
    <div class="nm-order__totalRow">
      <span>Total</span>
      <strong>Rp <?= number_format((float) ($order['total_penjualan'] ?? 0), 0, ',', '.') ?></strong>
    </div>
    <div class="nm-order__hint">
      QRIS dinamis belum aktif. Ini halaman dummy untuk persiapan integrasi.
    </div>
    <div class="nm-order__hint">
      Order ID: <strong>#<?= (int) ($order['id'] ?? 0) ?></strong>
    </div>
  </div>

  <div class="nm-card">
    <a class="nm-btn nm-btn--primary nm-btn--block" href="<?= base_url('order/qris_simulate_paid/' . (int) ($order['id'] ?? 0)) ?>">
      Simulasikan: Pembayaran Berhasil
    </a>
    <a class="nm-btn nm-btn--ghost nm-btn--block" href="<?= base_url('order/pay') ?>">Kembali</a>
  </div>

  <?php $this->load->view('templates/member/bottom_nav'); ?>
</div>

