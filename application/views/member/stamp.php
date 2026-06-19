<?php
$total_program  = is_array($stamp_list ?? null) ? count($stamp_list) : 0;
$total_collected = 0;
$total_target   = 0;
if (!empty($stamp_list)) {
  foreach ($stamp_list as $s) {
    $total_collected += (int)($s['jumlah_stamp'] ?? 0);
    $total_target    += (int)($s['total_stamp_target'] ?? 0);
  }
}
$pct = ($total_target > 0) ? min(100, round(($total_collected / $total_target) * 100)) : 0;
$dash = round(2 * pi() * 36); // circumference for r=36
$offset = $dash - round($pct / 100 * $dash);
?>
<div class="page-content nm-page">

  <!-- HERO -->
  <div class="nm-page-hero nm-page-hero--stamp">
    <div class="nm-page-hero__nav">
      <a href="<?= site_url('member') ?>" class="nm-hero-back"><i class="f7-icons">chevron_left</i></a>
      <span class="nm-page-hero__label">Stamp Saya</span>
      <a href="<?= site_url('member/logout') ?>" class="nm-logout"><i class="f7-icons">rectangle_porous_arrow_right</i></a>
    </div>
    <div class="nm-stamp-hero-row">
      <div class="nm-stamp-hero-left">
        <div class="nm-stamp-hero-lbl">Stamp Terkumpul</div>
        <div class="nm-stamp-hero-val"><?= number_format($total_collected) ?><span>/<?= number_format($total_target) ?></span></div>
        <div class="nm-phero-chips" style="margin-top:14px;">
          <div class="nm-phero-chip">
            <span class="nm-phero-chip__n"><?= $total_program ?></span>
            <span class="nm-phero-chip__l">Program</span>
          </div>
          <div class="nm-phero-chip nm-phero-chip--amber">
            <span class="nm-phero-chip__n"><?= $pct ?>%</span>
            <span class="nm-phero-chip__l">Progress</span>
          </div>
        </div>
      </div>
      <!-- SVG progress ring -->
      <div class="nm-stamp-ring">
        <svg width="88" height="88" viewBox="0 0 88 88">
          <circle cx="44" cy="44" r="36" fill="none" stroke="rgba(255,255,255,.2)" stroke-width="7"/>
          <circle cx="44" cy="44" r="36" fill="none" stroke="#fff" stroke-width="7"
            stroke-linecap="round"
            stroke-dasharray="<?= $dash ?>"
            stroke-dashoffset="<?= $offset ?>"
            transform="rotate(-90 44 44)"/>
        </svg>
        <div class="nm-stamp-ring__pct">
          <b><?= $pct ?>%</b>
          <small>total</small>
        </div>
      </div>
    </div>
  </div>

  <!-- PROGRAM LIST -->
  <div class="nm-section-head" style="margin-top:16px;">
    <div>
      <div class="nm-section-title">Program Stamp</div>
      <div class="nm-section-sub">Progress per kampanye</div>
    </div>
  </div>

  <?php if (empty($stamp_list)): ?>
    <div class="nm-empty-state nm-card">
      <div class="nm-empty-state__ico">☕</div>
      <div class="nm-empty-state__txt">Belum ada stamp. Yuk mulai transaksi!</div>
    </div>
  <?php else: ?>
    <?php foreach ($stamp_list as $stamp): ?>
      <?php
        $nama     = html_escape($stamp['nama_promo'] ?? 'Promo');
        $col      = (int)($stamp['jumlah_stamp'] ?? 0);
        $tar      = (int)($stamp['total_stamp_target'] ?? 0);
        $p        = ($tar > 0) ? min(100, round(($col / $tar) * 100)) : 0;
        $is_done  = ($tar > 0 && $col >= $tar);
      ?>
      <div class="nm-card nm-stamp-prog-card">
        <div class="nm-stamp-prog-head">
          <div>
            <div class="nm-stamp-prog-name"><?= $nama ?></div>
            <div class="nm-stamp-prog-meta"><?= $col ?>/<?= $tar ?> stamp &bull; <?= $p ?>% lengkap</div>
          </div>
          <?php if ($is_done): ?>
            <span class="nm-badge success">Selesai ✓</span>
          <?php else: ?>
            <span class="nm-badge neutral"><?= max(0, $tar - $col) ?> lagi</span>
          <?php endif; ?>
        </div>

        <!-- Progress bar -->
        <div class="nm-prog-bar" style="margin:12px 0 14px;">
          <div class="nm-prog-bar__fill <?= $is_done ? 'nm-prog-bar__fill--done' : '' ?>" style="width:<?= $p ?>%"></div>
        </div>

        <!-- Stamp grid -->
        <div class="nm-stamp-dots" role="list">
          <?php for ($i = 1; $i <= $tar; $i++): ?>
            <?php $on = ($i <= $col); ?>
            <div class="nm-sdot <?= $on ? 'nm-sdot--on' : 'nm-sdot--off' ?>" role="listitem">
              <?php if ($on): ?>
                <img src="<?= base_url('uploads/logo.png') ?>" alt="stamp" loading="lazy">
                <i class="f7-icons nm-sdot__chk">checkmark_circle_fill</i>
              <?php else: ?>
                <span class="nm-sdot__num"><?= $i ?></span>
              <?php endif; ?>
            </div>
          <?php endfor; ?>
        </div>

        <?php if ($is_done): ?>
          <div class="nm-stamp-done-banner">🎉 Klaim hadiahmu di kasir!</div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>
<?php $this->load->view('templates/member/bottom_nav'); ?>
