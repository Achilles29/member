<div class="page-content nm-page">

  <div class="nm-page-hero nm-page-hero--voucher">
    <div class="nm-page-hero__nav">
      <a href="<?= site_url('member') ?>" class="nm-hero-back"><i class="f7-icons">chevron_left</i></a>
      <span class="nm-page-hero__label">Voucher Kadaluarsa</span>
      <a href="<?= site_url('member/logout') ?>" class="nm-logout"><i class="f7-icons">rectangle_porous_arrow_right</i></a>
    </div>
    <div class="nm-page-hero__center" style="padding-bottom:4px;">
      <div class="nm-page-hero__emoji">⌛</div>
      <div class="nm-page-hero__big"><?= is_array($voucher_kadaluarsa ?? null) ? count($voucher_kadaluarsa) : 0 ?></div>
      <div class="nm-page-hero__sub">Voucher masa berlaku habis</div>
    </div>
  </div>

  <div class="nm-vtabs">
    <a class="nm-vtab" href="<?= site_url('voucher') ?>">Aktif</a>
    <a class="nm-vtab" href="<?= site_url('voucher/digunakan') ?>">Digunakan</a>
    <a class="nm-vtab is-active" href="<?= site_url('voucher/kadaluarsa') ?>">Kadaluarsa</a>
  </div>

  <?php if (!empty($voucher_kadaluarsa)): ?>
    <?php foreach ($voucher_kadaluarsa as $v): ?>
      <div class="nm-ticket nm-ticket--expired">
        <div class="nm-ticket__left">
          <div class="nm-ticket__badge-wrap"><span class="nm-badge danger">Kadaluarsa</span></div>
          <div class="nm-ticket__icon" style="opacity:.4;filter:grayscale(1);">🎟</div>
        </div>
        <div class="nm-ticket__sep"></div>
        <div class="nm-ticket__body">
          <div class="nm-ticket__code" style="opacity:.6;"><?= html_escape($v['kode_voucher'] ?? '-') ?></div>
          <div class="nm-ticket__desc"><?= ($v['jenis'] ?? '') === 'produk' ? 'Gratis produk' : 'Diskon' ?></div>
          <div class="nm-ticket__date">
            <i class="f7-icons" style="font-size:12px;">clock</i>
            Masa berlaku telah habis
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!empty($pagination_links)): ?>
      <div class="nm-pagination nm-pagination-f7"><?= $pagination_links ?></div>
    <?php endif; ?>
  <?php else: ?>
    <div class="nm-empty-state nm-card">
      <div class="nm-empty-state__ico">⌛</div>
      <div class="nm-empty-state__txt">Tidak ada voucher kadaluarsa.</div>
    </div>
  <?php endif; ?>

</div>
<?php $this->load->view('templates/member/bottom_nav'); ?>
