<?php
$order_base_path = $order_base_path ?? 'online-order';
$message = trim((string) ($message ?? 'Online order sedang tutup.'));
$next_open_hint = trim((string) ($next_open_hint ?? ''));
?>
<div class="page-content nm-page nm-order">
  <div class="nm-topbar nm-topbar--mini">
    <div>
      <div class="nm-name"><?= html_escape($title ?? 'Online Order Tutup') ?></div>
      <div class="nm-level">Delivery belum tersedia</div>
    </div>
    <a class="nm-logout" href="<?= site_url('member/logout') ?>" title="Logout">
      <i class="f7-icons">rectangle_porous_arrow_right</i>
    </a>
  </div>

  <div class="nm-card" style="margin-top:-22px;">
    <div class="nm-empty" style="text-align:left;">
      <strong><?= html_escape($message) ?></strong>
      <?php if ($next_open_hint !== ''): ?>
        <div class="nm-order__hint" style="margin-top:8px;"><?= html_escape($next_open_hint) ?></div>
      <?php endif; ?>
    </div>
    <a class="nm-btn nm-btn--primary nm-btn--block" href="<?= site_url('member') ?>" style="margin-top:14px;">Kembali</a>
  </div>

  <?php $this->load->view('templates/member/bottom_nav'); ?>
  <?php $this->load->view('order/_online_whatsapp_float'); ?>
</div>
