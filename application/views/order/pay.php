<div class="page-content nm-page nm-order">
  <div class="nm-topbar nm-topbar--mini">
    <div>
      <div class="nm-name"><?= html_escape($title ?? 'Pembayaran') ?></div>
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

  <?php if (!empty($this->session->flashdata('error'))): ?>
    <div class="nm-card" style="margin-top:-22px;">
      <div class="nm-alert nm-alert--danger">
        <?= html_escape((string) $this->session->flashdata('error')) ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="nm-card" style="margin-top:-22px;">
    <div class="nm-order__totalRow">
      <span>Total</span>
      <strong>Rp <?= number_format((float) ($total ?? 0), 0, ',', '.') ?></strong>
    </div>
    <div class="nm-order__hint">
      Pilih metode pembayaran. Default: bayar di kasir. QRIS masih dummy (persiapan integrasi).
    </div>
  </div>

  <form method="post" action="<?= base_url('order/confirm') ?>">
    <div class="nm-card">
      <div class="nm-form">
        <div class="nm-form__label">Metode pembayaran</div>

        <label class="nm-radio">
          <input type="radio" name="payment_method" value="KASIR" checked>
          <span>Bayar di kasir</span>
        </label>

        <label class="nm-radio">
          <input type="radio" name="payment_method" value="QRIS">
          <span>QRIS (dummy)</span>
        </label>

        <div class="nm-form__label" style="margin-top:14px;">Catatan (opsional)</div>
        <textarea name="catatan" rows="3" placeholder="Contoh: tanpa es, kurang manis, dll."></textarea>
      </div>
    </div>

    <div class="nm-card">
      <button type="submit" class="nm-btn nm-btn--primary nm-btn--block">Kirim Pesanan</button>
      <a class="nm-btn nm-btn--ghost nm-btn--block" href="<?= base_url('order/review_session') ?>">Kembali</a>
    </div>
  </form>

  <?php $this->load->view('templates/member/bottom_nav'); ?>
</div>

<script>
  (function () {
    const MEJA_ID = <?= (int) ($this->session->userdata('order_meja_id') ?? 0) ?>;
    const STEP_KEY = 'nm_order_step_v1_' + String(MEJA_ID || 0);
    try { localStorage.setItem(STEP_KEY, 'pay'); } catch (_) {}
  })();
</script>

