<div class="page-content nm-page nm-order">
  <div class="nm-topbar nm-topbar--mini">
    <div>
      <div class="nm-name">Pesanan Terkirim</div>
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
    <div class="nm-success">
      <div class="nm-success__title">Berhasil</div>
      <?php if (!empty($pending_order)): ?>
        <div class="nm-success__meta">
          Order ID <strong>#<?= (int) ($pending_order['id'] ?? 0) ?></strong>
        </div>
      <?php endif; ?>

      <?php $pm = strtoupper((string) ($payment_method ?? '')); ?>
      <?php if ($pm === 'QRIS'): ?>
        <div class="nm-success__desc">Pembayaran QRIS terdeteksi. Pesanan masuk antrian setelah tervalidasi.</div>
      <?php else: ?>
        <div class="nm-success__desc">Pesanan masuk antrian kasir. Silakan bayar di kasir ya.</div>
      <?php endif; ?>
    </div>
  </div>

  <div class="nm-card">
    <a class="nm-btn nm-btn--primary nm-btn--block" href="<?= base_url('order') ?>">Order lagi</a>
  </div>

  <?php $this->load->view('templates/member/bottom_nav'); ?>
</div>

<script>
  (function () {
    // Setelah order sukses, bersihkan cart localStorage untuk meja ini.
    const MEJA_ID = <?= (int) ($meja_id ?? 0) ?>;
    const CART_KEY = 'nm_order_cart_v1_' + String(MEJA_ID || 0);
    const STEP_KEY = 'nm_order_step_v1_' + String(MEJA_ID || 0);
    try {
      localStorage.removeItem(CART_KEY);
      localStorage.removeItem(STEP_KEY);
    } catch (_) {}
  })();
</script>

