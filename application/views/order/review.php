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
    <div class="nm-order__totalRow">
      <span>Total</span>
      <strong>Rp <?= number_format((float) ($total ?? 0), 0, ',', '.') ?></strong>
    </div>
    <div class="nm-order__reviewActions">
      <a class="nm-btn nm-btn--ghost" id="nmAddMenu" href="<?= base_url('order/menu') ?>">Tambah menu</a>
      <a class="nm-btn nm-btn--primary" href="<?= base_url('order/pay') ?>">Bayar</a>
    </div>
  </div>

  <?php $this->load->view('templates/member/bottom_nav'); ?>
</div>

<script>
  (function () {
    // Mark step di localStorage supaya scan ulang bisa langsung balik ke tahap ini/pay.
    const MEJA_ID = <?= (int) ($this->session->userdata('order_meja_id') ?? 0) ?>;
    const STEP_KEY = 'nm_order_step_v1_' + String(MEJA_ID || 0);

    try { localStorage.setItem(STEP_KEY, 'review'); } catch (_) {}

    // Tombol "Tambah menu": override step jadi "menu" supaya halaman order tidak auto-resume balik ke review.
    const btn = document.getElementById('nmAddMenu');
    if (btn) {
      btn.addEventListener('click', function () {
        try { localStorage.setItem(STEP_KEY, 'menu'); } catch (_) {}
      });
    }
  })();
</script>
