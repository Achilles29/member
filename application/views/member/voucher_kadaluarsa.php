<div class="page-content nm-page">

  <div class="nm-topbar nm-topbar--mini">
    <div>
      <div class="nm-name"><?= html_escape($member['nama'] ?? 'Guest') ?></div>
      <div class="nm-level">Voucher Kadaluarsa</div>
    </div>

    <a class="nm-logout" href="<?= site_url('member/logout') ?>" title="Logout">
      <i class="f7-icons">rectangle_porous_arrow_right</i>
    </a>
  </div>

  <div class="nm-voucher-tabs">
    <a class="nm-vtab" href="<?= site_url('voucher') ?>">Aktif</a>
    <a class="nm-vtab" href="<?= site_url('voucher/digunakan') ?>">Digunakan</a>
    <a class="nm-vtab is-active" href="<?= site_url('voucher/kadaluarsa') ?>">Kadaluarsa</a>
  </div>
<?php if (!empty($voucher_kadaluarsa)): ?>
  <?php foreach ($voucher_kadaluarsa as $v): ?>
    <div class="nm-card nm-voucher-card">
      <div class="nm-voucher-head">
        <div class="nm-voucher-code"><?= html_escape($v['kode_voucher'] ?? '-') ?></div>
        <span class="nm-badge danger">Kadaluarsa</span>
      </div>

      <div class="nm-voucher-desc">
        <?= ($v['jenis'] ?? '') === 'produk' ? 'Gratis produk' : 'Diskon' ?>
      </div>

      <div class="nm-voucher-meta">
        <span class="nm-voucher-date">
          <i class="f7-icons">clock</i>
          Masa berlaku telah habis
        </span>
      </div>
    </div>
  <?php endforeach; ?>

  <?php if (!empty($pagination_links)): ?>
    <?= $pagination_links ?>
  <?php endif; ?>

<?php else: ?>
  <div class="nm-card nm-empty-card">
    Tidak ada voucher kadaluarsa.
  </div>
<?php endif; ?>


</div>

<?php $this->load->view('templates/member/bottom_nav'); ?>
