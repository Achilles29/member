<div class="page-content nm-page">

  <!-- TOPBAR MINI -->
  <div class="nm-topbar nm-topbar--mini">
    <div>
      <div class="nm-name"><?= html_escape($member['nama'] ?? 'Guest') ?></div>
      <div class="nm-level">Stamp Saya</div>
    </div>

    <a class="nm-logout" href="<?= site_url('member/logout') ?>" title="Logout">
      <i class="f7-icons">rectangle_porous_arrow_right</i>
    </a>
  </div>

  <!-- HERO / RINGKASAN -->
  <?php
    // hitung ringkasan
    $total_program = is_array($stamp_list ?? null) ? count($stamp_list) : 0;
    $total_collected = 0;
    $total_target = 0;

    if (!empty($stamp_list)) {
      foreach ($stamp_list as $s) {
        $total_collected += (int)($s['jumlah_stamp'] ?? 0);
        $total_target += (int)($s['total_stamp_target'] ?? 0);
      }
    }

    $pct = ($total_target > 0) ? min(100, round(($total_collected / $total_target) * 100)) : 0;
  ?>

  <div class="nm-stamp-hero">
    <div class="nm-stamp-hero-card">
      <div class="nm-stamp-hero-top">
        <div>
          <div class="nm-stamp-hero-label">Total Stamp Terkumpul</div>
          <div class="nm-stamp-hero-value">
            <?= number_format($total_collected) ?>
            <span>/ <?= number_format($total_target) ?></span>
          </div>
        </div>

        <div class="nm-stamp-ring" aria-label="Progress">
          <div class="nm-stamp-ring__in">
            <b><?= (int)$pct ?>%</b>
            <small>progress</small>
          </div>
        </div>
      </div>

      <div class="nm-stamp-hero-meta">
        <div class="nm-pill success">
          <i class="f7-icons" style="font-size:16px;">bookmark</i>
          <span><b><?= $total_program ?></b> program aktif</span>
        </div>
        <div class="nm-pill warning">
          <i class="f7-icons" style="font-size:16px;">sparkles</i>
          <span>Kumpulkan stamp untuk klaim hadiah</span>
        </div>
      </div>

      <div class="nm-stamp-progress">
        <div class="nm-stamp-progress__bar">
          <div class="nm-stamp-progress__fill" style="width: <?= (int)$pct ?>%;"></div>
        </div>
        <div class="nm-stamp-progress__text">
          <?= (int)$pct ?>% dari total target
        </div>
      </div>
    </div>
  </div>

  <!-- LIST PROGRAM STAMP -->
  <div class="nm-section-head">
    <div>
      <div class="nm-section-title">Program Stamp</div>
      <div class="nm-section-sub">Lihat progress tiap promo</div>
    </div>
  </div>

  <?php if (!empty($stamp_list)): ?>
    <?php foreach ($stamp_list as $stamp): ?>
      <?php
        $nama = html_escape($stamp['nama_promo'] ?? 'Promo');
        $collected = (int)($stamp['jumlah_stamp'] ?? 0);
        $target = (int)($stamp['total_stamp_target'] ?? 0);
        $pct_item = ($target > 0) ? min(100, round(($collected / $target) * 100)) : 0;
        $is_done = ($target > 0 && $collected >= $target);
      ?>

      <div class="nm-card nm-stamp-card">
        <div class="nm-stamp-head">
          <div class="nm-stamp-title"><?= $nama ?></div>

          <?php if ($is_done): ?>
            <span class="nm-badge success">Selesai ✓</span>
          <?php else: ?>
            <span class="nm-badge neutral"><?= $collected ?>/<?= $target ?></span>
          <?php endif; ?>
        </div>

        <div class="nm-stamp-sub">
          <div class="nm-stamp-sub-left">
            <i class="f7-icons">chart_bar</i>
            <span><?= (int)$pct_item ?>% lengkap</span>
          </div>
          <div class="nm-stamp-sub-right">
            <?php if ($is_done): ?>
              <span class="nm-stamp-hint">Siap klaim hadiah 🎁</span>
            <?php else: ?>
              <span class="nm-stamp-hint">Butuh <?= max(0, $target - $collected) ?> stamp lagi</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="nm-stamp-item-progress">
          <div class="nm-stamp-item-progress__fill" style="width: <?= (int)$pct_item ?>%;"></div>
        </div>

        <div class="nm-stamp-grid" role="list">
          <?php for ($i = 1; $i <= $target; $i++): ?>
            <?php $active = ($i <= $collected); ?>
            <div class="nm-stamp-dot <?= $active ? 'is-on' : 'is-off' ?>" role="listitem" aria-label="stamp <?= $i ?>">
              <img
                src="<?= base_url('uploads/logo.png') ?>"
                alt="stamp"
                loading="lazy"
              >
              <?php if ($active): ?>
                <i class="f7-icons nm-stamp-check">checkmark_circle_fill</i>
              <?php endif; ?>
            </div>
          <?php endfor; ?>
        </div>

      </div>
    <?php endforeach; ?>

  <?php else: ?>
    <div class="nm-card nm-empty-card">
      Belum ada stamp yang dikumpulkan.<br>
      Yuk mulai transaksi dan kumpulkan cap-mu! ☕✨
    </div>
  <?php endif; ?>

</div>

<?php $this->load->view('templates/member/bottom_nav'); ?>
