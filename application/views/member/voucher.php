<div class="page-content nm-page">

  <div class="nm-page-hero nm-page-hero--voucher">
    <div class="nm-page-hero__nav">
      <a href="<?= site_url('member') ?>" class="nm-hero-back"><i class="f7-icons">chevron_left</i></a>
      <span class="nm-page-hero__label">Voucher Saya</span>
      <a href="<?= site_url('member/logout') ?>" class="nm-logout"><i class="f7-icons">rectangle_porous_arrow_right</i></a>
    </div>
    <div class="nm-page-hero__center" style="padding-bottom:4px;">
      <div class="nm-page-hero__emoji">🎟</div>
      <div class="nm-page-hero__big"><?= is_array($voucher_aktif ?? null) ? count($voucher_aktif) : 0 ?></div>
      <div class="nm-page-hero__sub">Voucher siap dipakai</div>
    </div>
  </div>

  <!-- TABS -->
  <div class="nm-vtabs">
    <a class="nm-vtab is-active" href="<?= site_url('voucher') ?>">
      Aktif <span class="nm-vtab-badge"><?= is_array($voucher_aktif ?? null) ? count($voucher_aktif) : 0 ?></span>
    </a>
    <a class="nm-vtab" href="<?= site_url('voucher/digunakan') ?>">
      Digunakan <span class="nm-vtab-badge"><?= is_array($voucher_digunakan ?? null) ? count($voucher_digunakan) : 0 ?></span>
    </a>
    <a class="nm-vtab" href="<?= site_url('voucher/kadaluarsa') ?>">
      Kadaluarsa <span class="nm-vtab-badge"><?= is_array($voucher_kadaluarsa ?? null) ? count($voucher_kadaluarsa) : 0 ?></span>
    </a>
  </div>

  <?php if (!empty($voucher_aktif)): ?>
    <?php foreach ($voucher_aktif as $v): ?>
      <?php
        $kode  = html_escape($v['kode_voucher'] ?? '-');
        $jenis = $v['jenis'] ?? '';
        $mulai = !empty($v['tanggal_mulai']) ? date('d M Y', strtotime($v['tanggal_mulai'])) : '-';
        $akhir = !empty($v['tanggal_berakhir']) ? date('d M Y', strtotime($v['tanggal_berakhir'])) : '-';
        if ($jenis === 'produk') {
          $desc  = 'Gratis: ' . html_escape($v['produk_nama'] ?? 'Produk');
          $badge = 'Produk'; $bclass = 'neutral';
        } elseif ($jenis === 'diskon') {
          $badge = 'Diskon'; $bclass = 'success';
          if (isset($v['tipe_diskon']) && $v['tipe_diskon'] === 'persentase') {
            $max  = number_format($v['max_diskon'] ?? 0, 0, ',', '.');
            $desc = 'Diskon ' . (int)$v['nilai'] . '% (max Rp' . $max . ')';
          } else {
            $desc = 'Diskon Rp' . number_format((int)$v['nilai'], 0, ',', '.');
          }
        } else { $desc = 'Voucher'; $badge = 'Aktif'; $bclass = 'neutral'; }
      ?>
      <div class="nm-ticket nm-ticket--active">
        <div class="nm-ticket__left">
          <div class="nm-ticket__badge-wrap"><span class="nm-badge success">Aktif</span></div>
          <div class="nm-ticket__icon">🎟</div>
          <span class="nm-badge neutral" style="margin-top:4px;"><?= html_escape($badge) ?></span>
        </div>
        <div class="nm-ticket__sep"></div>
        <div class="nm-ticket__body">
          <div class="nm-ticket__code"><?= $kode ?></div>
          <div class="nm-ticket__desc"><?= $desc ?></div>
          <div class="nm-ticket__date">
            <i class="f7-icons" style="font-size:12px;">calendar</i>
            <?= $mulai ?> – <?= $akhir ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="nm-empty-state nm-card">
      <div class="nm-empty-state__ico">🎟</div>
      <div class="nm-empty-state__txt">Belum ada voucher aktif saat ini.</div>
    </div>
  <?php endif; ?>

</div>
<?php $this->load->view('templates/member/bottom_nav'); ?>
