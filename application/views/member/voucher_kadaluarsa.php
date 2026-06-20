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
      <?php
        $akhir = !empty($v['tanggal_berakhir']) ? date('d M Y', strtotime($v['tanggal_berakhir'])) : '-';
        $desc  = $v['description'] ?? '-';
      ?>
      <div class="nm-ticket nm-ticket--expired">
        <div class="nm-ticket__left nm-ticket__left--expired">
          <span class="nm-badge danger">Kadaluarsa</span>
          <div class="nm-ticket__lico" style="opacity:.3;filter:grayscale(1);">🎟</div>
        </div>
        <div class="nm-ticket__vline nm-ticket__vline--expired"></div>
        <div class="nm-ticket__body">
          <div class="nm-ticket__code" style="opacity:.55;"><?= html_escape($v['kode_voucher'] ?? '-') ?></div>
          <div class="nm-ticket__desc" style="opacity:.65;"><?= $desc ?></div>
          <div class="nm-ticket__date nm-ticket__date--exp">
            <i class="f7-icons">clock</i>Berakhir <?= $akhir ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!empty($pagination_links)): ?>
      <div class="nm-pagination-f7"><?= $pagination_links ?></div>
    <?php endif; ?>
  <?php else: ?>
    <div class="nm-empty-state nm-card">
      <div class="nm-empty-state__ico">⌛</div>
      <div class="nm-empty-state__txt">Tidak ada voucher kadaluarsa.</div>
    </div>
  <?php endif; ?>

</div>
<?php $this->load->view('templates/member/bottom_nav'); ?>
