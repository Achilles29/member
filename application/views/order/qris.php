<div class="page-content nm-page nm-order">
  <div class="nm-topbar nm-topbar--mini">
    <div>
      <div class="nm-name"><?= html_escape($title ?? 'QRIS') ?></div>
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

  <?php
    $order_status = strtoupper((string) ($order['status'] ?? 'PENDING'));
    $is_rejected_order = !empty($is_rejected_order);
  ?>
  <?php if (!empty($qris_error)): ?>
    <div class="nm-card" style="margin-top:-22px;">
      <div class="nm-alert nm-alert--danger">
        <?= html_escape((string) $qris_error) ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="nm-card" style="margin-top:-22px;">
    <div class="nm-order__totalRow">
      <span>Total</span>
      <strong>Rp <?= number_format((float) ($order['total_penjualan'] ?? 0), 0, ',', '.') ?></strong>
    </div>
    <div class="nm-order__hint">
      Order ID: <strong>#<?= (int) ($order['id'] ?? 0) ?></strong>
    </div>
    <div class="nm-order__hint">
      Status order: <strong><?= html_escape($order_status) ?></strong>
    </div>
    <div class="nm-order__hint">
      Status pembayaran: <strong id="qris-status"><?= html_escape((string) ($payment_status ?? 'PENDING')) ?></strong>
    </div>
  </div>

  <div class="nm-card">
    <?php $qr_url = $qris['qr_url'] ?? ''; ?>
    <?php $qr_string = $qris['qr_string'] ?? ''; ?>
    <?php $has_qr = !$is_rejected_order && (!empty($qr_url) || !empty($qr_string)); ?>

    <?php if ($is_rejected_order): ?>
      <div class="nm-order__hint">
        Pesanan tidak bisa diproses lebih lanjut. Jika perlu bantuan, hubungi kasir atau outlet.
      </div>
    <?php elseif (!empty($qr_url)): ?>
      <div style="text-align:center;">
        <img src="<?= html_escape($qr_url) ?>" alt="QRIS" style="max-width:240px;width:100%;height:auto;" />
      </div>
      <div class="nm-order__hint" style="text-align:center;margin-top:8px;">
        Scan QRIS di atas untuk pembayaran.
      </div>
      <div class="nm-order__hint" style="text-align:center;margin-top:6px;">
        Status akan diperbarui otomatis setelah pembayaran berhasil.
      </div>
    <?php elseif (!empty($qr_string)): ?>
      <div class="nm-order__hint">
        QR string:
      </div>
      <div class="nm-order__hint" style="word-break:break-all;">
        <?= html_escape($qr_string) ?>
      </div>
      <div class="nm-order__hint" style="text-align:center;margin-top:6px;">
        Status akan diperbarui otomatis setelah pembayaran berhasil.
      </div>
    <?php else: ?>
      <div class="nm-order__hint">
        QRIS belum tersedia. Silakan buat QR baru.
      </div>
    <?php endif; ?>
  </div>

  <div class="nm-card">
    <?php if ($has_qr): ?>
      <a class="nm-btn nm-btn--primary nm-btn--block" href="#" id="qris-check">Cek status pembayaran</a>
    <?php endif; ?>
    <?php if (!$is_rejected_order && (in_array(strtoupper((string) ($payment_status ?? '')), ['EXPIRED', 'FAILED'], true) || !$has_qr)): ?>
      <a class="nm-btn nm-btn--ghost nm-btn--block" href="<?= base_url('order/qris_regenerate/' . (int) ($order['id'] ?? 0)) ?>">Buat QR baru</a>
    <?php endif; ?>
    <a class="nm-btn nm-btn--ghost nm-btn--block" href="<?= base_url($is_rejected_order ? 'order' : 'order/pay') ?>"><?= $is_rejected_order ? 'Kembali ke Menu' : 'Kembali' ?></a>
  </div>

  <?php $this->load->view('templates/member/bottom_nav'); ?>
</div>

<script>
  (function () {
    const orderId = <?= (int) ($order['id'] ?? 0) ?>;
    const isRejectedOrder = <?= !empty($is_rejected_order) ? 'true' : 'false' ?>;
    const statusEl = document.getElementById('qris-status');
    const checkBtn = document.getElementById('qris-check');
    let isChecking = false;

    async function checkStatus() {
      if (isChecking) return;
      isChecking = true;
      try {
        const res = await fetch('<?= base_url('order/qris_status/') ?>' + orderId, { credentials: 'same-origin' });
        const data = await res.json();
        if (data && data.ok) {
          const status = String(data.status || '').toUpperCase();
          if (data.rejected || status === 'REJECTED') {
            window.location.reload();
            return;
          }
          if (statusEl) statusEl.textContent = status || 'PENDING';
          if (status === 'PAID') {
            window.location.href = '<?= base_url('order/selesai') ?>';
            return;
          }
        }
      } catch (_) {}
      isChecking = false;
    }

    if (!isRejectedOrder && checkBtn) {
      checkBtn.addEventListener('click', function (e) {
        e.preventDefault();
        checkStatus();
      });
    }

    if (!isRejectedOrder) {
      setInterval(checkStatus, 5000);
    }
  })();
</script>
