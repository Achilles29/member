<?php
$order_base_path = $order_base_path ?? 'order';
$order_storage_suffix = $order_storage_suffix ?? ('self_' . (int) ($this->session->userdata('order_meja_id') ?? 0));
$is_online_order = !empty($is_online_order);
?>
<div class="page-content nm-page nm-order">
  <div class="nm-topbar nm-topbar--mini">
    <div>
      <div class="nm-name"><?= html_escape($title ?? 'Review Order') ?></div>
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
    <div class="nm-order__reviewList">
      <?php foreach (($produk_list ?? []) as $item): ?>
        <div class="nm-reviewitem">
          <div class="nm-reviewitem__left">
            <div class="nm-reviewitem__name"><?= html_escape((string) ($item['nama'] ?? '')) ?></div>
            <div class="nm-reviewitem__sub">
              <span><?= (int) ($item['jumlah'] ?? 0) ?>x</span>
              <?php if (!empty($item['extra'])): ?>
                <span class="nm-reviewitem__dot">•</span>
                <span class="nm-reviewitem__extras">+ <?= html_escape(implode(', ', array_column($item['extra'], 'nama'))) ?></span>
              <?php endif; ?>
            </div>
            <?php if (!empty($item['catatan'])): ?>
              <div class="nm-reviewitem__sub" style="margin-top:4px;">
                <span>Catatan: <?= html_escape((string) $item['catatan']) ?></span>
              </div>
            <?php endif; ?>
          </div>
          <div class="nm-reviewitem__right">
            <div class="nm-reviewitem__price">Rp <?= number_format((float) ($item['subtotal'] ?? 0), 0, ',', '.') ?></div>
            <?php if (!empty($item['extra'])): ?>
              <div class="nm-reviewitem__extraPrice">
                +Rp <?= number_format((float) (array_sum(array_column($item['extra'], 'harga')) * (int) ($item['jumlah'] ?? 0)), 0, ',', '.') ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="nm-card">
    <?php if ($is_online_order): ?>
      <div class="nm-order__hint" id="nmReviewLocationStatus" style="margin-bottom:10px;">Lokasi wajib aktif untuk online order.</div>
      <button type="button" class="nm-btn nm-btn--ghost nm-btn--block" id="nmReviewEnableLocation" style="margin-bottom:10px;">Aktifkan Lokasi</button>
    <?php endif; ?>
    <div class="nm-order__totalRow">
      <span>Total</span>
      <strong>Rp <?= number_format((float) ($total ?? 0), 0, ',', '.') ?></strong>
    </div>
    <div class="nm-order__reviewActions">
      <a class="nm-btn nm-btn--ghost" id="nmAddMenu" href="<?= base_url($order_base_path . '/menu') ?>">Tambah menu</a>
      <a class="nm-btn nm-btn--primary" id="nmReviewPay" href="<?= base_url($order_base_path . '/pay') ?>">Bayar</a>
    </div>
  </div>

  <?php $this->load->view('templates/member/bottom_nav'); ?>
  <?php if ($is_online_order) $this->load->view('order/_online_whatsapp_float'); ?>
</div>

<script>
  (function () {
    // Mark step di localStorage supaya scan ulang bisa langsung balik ke tahap ini/pay.
    const STORAGE_SUFFIX = <?= json_encode((string) $order_storage_suffix) ?>;
    const IS_ONLINE_ORDER = <?= $is_online_order ? 'true' : 'false' ?>;
    const STEP_KEY = 'nm_order_step_v1_' + STORAGE_SUFFIX;
    const LOCATION_KEY = 'nm_order_location_v1_' + STORAGE_SUFFIX;

    try { localStorage.setItem(STEP_KEY, 'review'); } catch (_) {}

    // Tombol "Tambah menu": override step jadi "menu" supaya halaman order tidak auto-resume balik ke review.
    const btn = document.getElementById('nmAddMenu');
    if (btn) {
      btn.addEventListener('click', function () {
        try { localStorage.setItem(STEP_KEY, 'menu'); } catch (_) {}
      });
    }

    function hasLocation() {
      if (!IS_ONLINE_ORDER) return true;
      try {
        const loc = JSON.parse(localStorage.getItem(LOCATION_KEY) || 'null');
        return Number.isFinite(Number(loc && loc.lat)) && Number.isFinite(Number(loc && loc.lng));
      } catch (_) {
        return false;
      }
    }
    function updateLocationUi() {
      const status = document.getElementById('nmReviewLocationStatus');
      const button = document.getElementById('nmReviewEnableLocation');
      if (!IS_ONLINE_ORDER || !status || !button) return;
      const ok = hasLocation();
      status.textContent = ok ? 'Lokasi sudah aktif untuk online order.' : 'Lokasi wajib aktif untuk online order.';
      button.style.display = ok ? 'none' : '';
    }
    function requestLocation(done) {
      const status = document.getElementById('nmReviewLocationStatus');
      if (!navigator.geolocation) {
        if (status) status.textContent = 'Browser tidak mendukung lokasi. Gunakan browser lain untuk online order.';
        if (done) done(false);
        return;
      }
      if (status) status.textContent = 'Mengambil lokasi kamu...';
      navigator.geolocation.getCurrentPosition(function (pos) {
        try {
          localStorage.setItem(LOCATION_KEY, JSON.stringify({
            lat: pos.coords.latitude,
            lng: pos.coords.longitude,
            accuracy: pos.coords.accuracy || 0,
            at: new Date().toISOString()
          }));
        } catch (_) {}
        updateLocationUi();
        if (done) done(true);
      }, function () {
        if (status) status.textContent = 'Lokasi wajib aktif. Izinkan akses lokasi lalu coba lagi.';
        if (done) done(false);
      }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 });
    }
    updateLocationUi();
    const locBtn = document.getElementById('nmReviewEnableLocation');
    if (locBtn) locBtn.addEventListener('click', function () { requestLocation(); });
    const payBtn = document.getElementById('nmReviewPay');
    if (payBtn) {
      payBtn.addEventListener('click', function (event) {
        if (!IS_ONLINE_ORDER || hasLocation()) return;
        event.preventDefault();
        requestLocation(function (ok) {
          if (ok) window.location.href = payBtn.href;
        });
      });
    }
  })();
</script>
