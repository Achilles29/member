<div class="page-content nm-page">

  <div class="nm-topbar nm-topbar--mini">
    <div>
      <div class="nm-name"><?= html_escape($member['nama'] ?? 'Guest') ?></div>
      <div class="nm-level">Voucher Saya</div>
    </div>

    <a class="nm-logout" href="<?= site_url('member/logout') ?>" title="Logout">
      <i class="f7-icons">rectangle_porous_arrow_right</i>
    </a>
  </div>

  <div class="nm-section-head">
    <div>
      <div class="nm-section-title">Voucher Aktif</div>
      <div class="nm-section-sub">Gunakan saat transaksi</div>
    </div>
  </div>

  <!-- quick filter buttons -->
  <div class="nm-voucher-tabs">
    <a class="nm-vtab is-active" href="<?= site_url('voucher') ?>">
      Aktif
      <span class="nm-vtab-badge"><?= is_array($voucher_aktif ?? null) ? count($voucher_aktif) : 0 ?></span>
    </a>
    <a class="nm-vtab" href="<?= site_url('voucher/digunakan') ?>">
      Digunakan
      <span class="nm-vtab-badge"><?= is_array($voucher_digunakan ?? null) ? count($voucher_digunakan) : 0 ?></span>
    </a>
    <a class="nm-vtab" href="<?= site_url('voucher/kadaluarsa') ?>">
      Kadaluarsa
      <span class="nm-vtab-badge"><?= is_array($voucher_kadaluarsa ?? null) ? count($voucher_kadaluarsa) : 0 ?></span>
    </a>
  </div>

  <?php if (!empty($voucher_aktif)): ?>
    <?php foreach ($voucher_aktif as $v): ?>
      <?php
        $kode = html_escape($v['kode_voucher'] ?? '-');
        $jenis = $v['jenis'] ?? '';
        $mulai = !empty($v['tanggal_mulai']) ? date('d M Y', strtotime($v['tanggal_mulai'])) : '-';
        $akhir = !empty($v['tanggal_berakhir']) ? date('d M Y', strtotime($v['tanggal_berakhir'])) : '-';

        // desc
        $desc = 'Voucher';
        if ($jenis === 'produk') {
          $desc = 'Gratis produk: ' . html_escape($v['produk_nama'] ?? 'Produk tidak ditemukan');
          $badge = 'Produk';
        } elseif ($jenis === 'diskon') {
          $badge = 'Diskon';
          if (isset($v['tipe_diskon']) && $v['tipe_diskon'] === 'persentase') {
            $max = isset($v['max_diskon']) ? number_format($v['max_diskon'], 0, ',', '.') : '0';
            $desc = 'Diskon ' . (int)$v['nilai'] . '% (Max Rp' . $max . ')';
          } else {
            $desc = 'Diskon Rp' . number_format((int)$v['nilai'], 0, ',', '.');
          }
        } else {
          $badge = 'Aktif';
        }
      ?>

      <div class="nm-card nm-voucher-card">
        <div class="nm-voucher-head">
          <div class="nm-voucher-code"><?= $kode ?></div>
          <span class="nm-badge success">Aktif</span>
        </div>

        <div class="nm-voucher-desc"><?= $desc ?></div>

        <div class="nm-voucher-meta">
          <span class="nm-badge neutral"><?= html_escape($badge) ?></span>
          <span class="nm-voucher-date">
            <i class="f7-icons">calendar</i>
            <?= $mulai ?> - <?= $akhir ?>
          </span>
        </div>
      </div>

    <?php endforeach; ?>
  <?php else: ?>
    <div class="nm-card nm-empty-card">
      Belum ada voucher aktif saat ini. ✨
    </div>
  <?php endif; ?>

</div>

<?php $this->load->view('templates/member/bottom_nav'); ?>
