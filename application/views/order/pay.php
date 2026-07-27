<?php
$order_base_path = $order_base_path ?? 'order';
$order_storage_suffix = $order_storage_suffix ?? ('self_' . (int) ($this->session->userdata('order_meja_id') ?? 0));
$cash_payment_label = $cash_payment_label ?? 'Bayar di kasir';
$payment_hint = $payment_hint ?? 'Pilih metode pembayaran. Default: bayar di kasir. QRIS via Midtrans.';
$catatan_placeholder = $catatan_placeholder ?? 'Contoh: tanpa es, kurang manis, dll.';
$selected_payment_method = strtoupper((string) ($payment_method ?? 'KASIR'));
$manual_payment_enabled = $manual_payment_enabled ?? true;
$manual_payment_instructions = trim((string) ($manual_payment_instructions ?? ''));
?>
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
      <?= html_escape($payment_hint) ?>
    </div>
  </div>

  <form method="post" action="<?= base_url($order_base_path . '/confirm') ?>">
    <?php if (!empty($is_online_order)): ?>
      <input type="hidden" name="customer_lat" id="customer_lat">
      <input type="hidden" name="customer_lng" id="customer_lng">
      <input type="hidden" name="customer_location_accuracy" id="customer_location_accuracy">
    <?php endif; ?>
    <div class="nm-card">
      <div class="nm-form">
        <div class="nm-form__label">Metode pembayaran</div>

        <?php if (!empty($manual_payment_enabled)): ?>
          <label class="nm-radio">
            <input type="radio" name="payment_method" value="KASIR" <?= $selected_payment_method === 'KASIR' ? 'checked' : '' ?>>
            <span><?= html_escape($cash_payment_label) ?></span>
          </label>
          <?php if ($manual_payment_instructions !== ''): ?>
            <div class="nm-order__hint" style="margin-top:8px;">
              <?= nl2br(html_escape($manual_payment_instructions)) ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($qris_enabled)): ?>
          <label class="nm-radio">
            <input type="radio" name="payment_method" value="QRIS" <?= $selected_payment_method === 'QRIS' ? 'checked' : '' ?>>
            <span>QRIS</span>
          </label>
        <?php else: ?>
          <div class="nm-order__hint" style="margin-top:8px;">
            <?= !empty($is_online_order) ? 'QRIS sedang nonaktif. Silakan konfirmasi manual ke admin.' : 'QRIS sedang nonaktif. Silakan bayar di kasir.' ?>
          </div>
        <?php endif; ?>

        <div class="nm-form__label" style="margin-top:14px;">Catatan (opsional)</div>
        <textarea name="catatan" rows="3" placeholder="<?= html_escape($catatan_placeholder) ?>"></textarea>
      </div>
    </div>

    <div class="nm-card">
      <button type="submit" class="nm-btn nm-btn--primary nm-btn--block">Kirim Pesanan</button>
      <a class="nm-btn nm-btn--ghost nm-btn--block" href="<?= base_url($order_base_path . '/review_session') ?>">Kembali</a>
    </div>
  </form>

  <?php $this->load->view('templates/member/bottom_nav'); ?>
  <?php if (!empty($is_online_order)) $this->load->view('order/_online_whatsapp_float'); ?>
</div>

<script>
  (function () {
    const STORAGE_SUFFIX = <?= json_encode((string) $order_storage_suffix) ?>;
    const IS_ONLINE_ORDER = <?= !empty($is_online_order) ? 'true' : 'false' ?>;
    const STEP_KEY = 'nm_order_step_v1_' + STORAGE_SUFFIX;
    const LOCATION_KEY = 'nm_order_location_v1_' + STORAGE_SUFFIX;
    try { localStorage.setItem(STEP_KEY, 'pay'); } catch (_) {}
    if (!IS_ONLINE_ORDER) return;
    const form = document.querySelector('form');
    const latEl = document.getElementById('customer_lat');
    const lngEl = document.getElementById('customer_lng');
    const accEl = document.getElementById('customer_location_accuracy');
    function fillLocation() {
      try {
        const loc = JSON.parse(localStorage.getItem(LOCATION_KEY) || 'null');
        const lat = Number(loc && loc.lat);
        const lng = Number(loc && loc.lng);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return false;
        latEl.value = String(lat);
        lngEl.value = String(lng);
        accEl.value = String(Number(loc.accuracy || 0));
        return true;
      } catch (_) {
        return false;
      }
    }
    fillLocation();
    if (form) {
      form.addEventListener('submit', function (event) {
        if (fillLocation()) return;
        event.preventDefault();
        alert('Lokasi wajib aktif untuk online order. Kembali ke halaman review/menu lalu aktifkan lokasi.');
      });
    }
  })();
</script>
