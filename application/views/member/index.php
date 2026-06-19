<div class="page-content nm-page">
  <?php
    $total_stamp = 0;
    $ci = get_instance();
    $self_order_available = $ci->db->table_exists('crm_member')
      && $ci->db->table_exists('mst_product')
      && $ci->db->table_exists('pos_order')
      && $ci->db->table_exists('pos_order_line')
      && $ci->db->table_exists('pos_payment');

    if (!empty($stamp_list) && is_array($stamp_list)) {
      foreach ($stamp_list as $s) {
        $total_stamp += (int)($s['jumlah_stamp'] ?? 0);
      }
    }

    $voucher_count = is_array($voucher_aktif ?? null) ? count($voucher_aktif) : 0;

    // Level progress calculation
    $thresholds = ['Silver' => 0, 'Gold' => 200, 'Platinum' => 500, 'Diamond' => 1000];
    $next_levels = ['Silver' => ['Gold', 200], 'Gold' => ['Platinum', 500], 'Platinum' => ['Diamond', 1000], 'Diamond' => null];
    $cur_level   = $level ?? 'Silver';
    $cur_floor   = $thresholds[$cur_level] ?? 0;
    $next_info   = $next_levels[$cur_level] ?? null;
    if ($next_info) {
      list($next_name, $next_target) = $next_info;
      $progress_pct = min(100, (int) round(max(0, ($poin - $cur_floor)) / max(1, $next_target - $cur_floor) * 100));
      $poin_left    = max(0, $next_target - $poin);
    } else {
      $next_name    = 'Diamond';
      $next_target  = 1000;
      $progress_pct = 100;
      $poin_left    = 0;
    }

    // Stamp target from first campaign (fallback to 10)
    $stamp_target = 10;
    if ($ci->db->table_exists('pos_stamp_campaign')) {
      $sc = $ci->db->select('redeem_required_stamp')->where('is_active', 1)->order_by('id','ASC')->limit(1)->get('pos_stamp_campaign')->row_array();
      if ($sc && !empty($sc['redeem_required_stamp'])) $stamp_target = (int)$sc['redeem_required_stamp'];
    }
    $stamp_display = min($total_stamp, $stamp_target);
  ?>

  <!-- ── GRADIENT HEADER ─────────────────────── -->
  <div class="nm-topbar nm-home-topbar">
    <div>
      <div class="nm-greeting">Selamat datang 👋</div>
      <div class="nm-name"><?= html_escape($member['nama'] ?? 'Guest') ?></div>
    </div>
    <a class="nm-logout" href="<?= site_url('member/logout') ?>" title="Logout">
      <i class="f7-icons">rectangle_porous_arrow_right</i>
    </a>
  </div>

  <!-- ── MEMBER CARD ─────────────────────────── -->
  <div class="nm-mcard">
    <div class="nm-avatar"><?= html_escape($member['initials'] ?? '?') ?></div>

    <div class="nm-mcard__info">
      <div class="nm-mcard__name"><?= html_escape($member['nama'] ?? 'Guest') ?></div>
      <div class="nm-mcard__no"><?= html_escape($member['kode_pelanggan'] ?? '-') ?></div>
      <span class="nm-level-badge nm-level-badge--<?= strtolower($cur_level) ?>"><?= $cur_level ?></span>
    </div>

    <div class="nm-mcard__right">
      <div class="nm-mcard__balance-label">STAR BALANCE</div>
      <div class="nm-mcard__balance"><?= number_format($poin ?? 0) ?></div>
      <div class="nm-mcard__star">⭐</div>
    </div>
  </div>

  <!-- ── LEVEL PROGRESS ─────────────────────── -->
  <div class="nm-level-progress">
    <div class="nm-level-progress__row">
      <span class="nm-level-progress__label">Level <?= $cur_level ?></span>
      <?php if ($cur_level !== 'Diamond'): ?>
        <span><?= number_format($poin_left) ?> poin lagi menuju <?= $next_name ?></span>
      <?php else: ?>
        <span>💎 Level Tertinggi</span>
      <?php endif; ?>
    </div>
    <div class="nm-level-progress__bar">
      <div class="nm-level-progress__fill" style="width:<?= $progress_pct ?>%"></div>
    </div>
  </div>

  <!-- ── QUICK ACTIONS ──────────────────────── -->
  <div class="nm-actions">
    <a class="nm-action" href="<?= site_url('poin') ?>">
      <div class="ico ico--gold-star" style="background:rgba(245,158,11,.12);color:#d97706;">
        <i class="f7-icons">star</i>
      </div>
      <span>Poin</span>
    </a>
    <a class="nm-action" href="<?= site_url('voucher') ?>">
      <div class="ico ico--orange"><i class="f7-icons">ticket</i></div>
      <span>Voucher</span>
      <?php if ($voucher_count > 0): ?>
        <div class="nm-action-badge"><?= $voucher_count ?></div>
      <?php endif; ?>
    </a>
    <a class="nm-action" href="<?= site_url('stamp') ?>">
      <div class="ico ico--blue"><i class="f7-icons">bookmark</i></div>
      <span>Stamp</span>
    </a>
    <?php if ($self_order_available): ?>
    <a class="nm-action" href="<?= site_url('order') ?>">
      <div class="ico ico--purple"><i class="f7-icons">cart</i></div>
      <span>Order</span>
    </a>
    <?php endif; ?>
    <a class="nm-action" href="<?= site_url('redeem') ?>">
      <div class="ico ico--green"><i class="f7-icons">gift</i></div>
      <span>Redeem</span>
    </a>
    <a class="nm-action" href="<?= site_url('transaksi') ?>">
      <div class="ico ico--teal"><i class="f7-icons">doc_text</i></div>
      <span>Riwayat</span>
    </a>
  </div>

  <!-- ── STAT PILLS ─────────────────────────── -->
  <div class="nm-stat-row">
    <a class="nm-stat-pill" href="<?= site_url('poin') ?>">
      <div class="nm-stat-pill__icon">⭐</div>
      <div class="nm-stat-pill__num"><?= number_format($poin ?? 0) ?></div>
      <div class="nm-stat-pill__label">Poin Aktif</div>
    </a>
    <a class="nm-stat-pill" href="<?= site_url('stamp') ?>">
      <div class="nm-stat-pill__icon">☕</div>
      <div class="nm-stat-pill__num"><?= number_format($total_stamp) ?></div>
      <div class="nm-stat-pill__label">Stamp</div>
    </a>
    <a class="nm-stat-pill" href="<?= site_url('voucher') ?>">
      <div class="nm-stat-pill__icon">🎟</div>
      <div class="nm-stat-pill__num"><?= $voucher_count ?></div>
      <div class="nm-stat-pill__label">Voucher</div>
    </a>
  </div>

  <!-- ── STAMP CARD PREVIEW ─────────────────── -->
  <?php if ($stamp_target > 0): ?>
  <div class="nm-section-head">
    <div>
      <div class="nm-section-title">Stamp Card</div>
      <div class="nm-section-sub"><?= $stamp_display ?>/<?= $stamp_target ?> cap — kumpulkan sampai penuh!</div>
    </div>
    <a href="<?= site_url('stamp') ?>" style="font-size:12px;font-weight:900;color:var(--primary);text-decoration:none;">Lihat</a>
  </div>
  <div class="nm-card">
    <div class="nm-stamp-grid">
      <?php for ($i = 1; $i <= $stamp_target; $i++): ?>
        <div class="nm-stamp-dot <?= $i <= $stamp_display ? 'is-filled' : '' ?>"></div>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── PROMO ──────────────────────────────── -->
  <?php if (!empty($promos)): ?>
    <div class="nm-section-head">
      <div>
        <div class="nm-section-title">Promo Spesial</div>
        <div class="nm-section-sub">Jangan sampai kelewatan</div>
      </div>
    </div>
    <div class="nm-promo">
      <?php foreach ($promos as $p): ?>
        <div class="nm-promo-card">
          <img src="<?= dashboard_url($p['gambar']) ?>" alt="<?= html_escape($p['judul']) ?>">
          <div class="nm-promo-label"><?= html_escape($p['judul']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- ── NEWS ───────────────────────────────── -->
  <?php if (!empty($news)): ?>
    <div class="nm-section-head">
      <div>
        <div class="nm-section-title">Namua News</div>
        <div class="nm-section-sub">Update terbaru untuk kamu</div>
      </div>
    </div>
    <?php foreach ($news as $n): ?>
      <div class="nm-card">
        <div class="nm-news-card">
          <img
            class="nm-news-card__thumb"
            src="<?= dashboard_url($n['gambar']) ?>"
            alt="<?= html_escape($n['judul']) ?>"
          >
          <div>
            <div class="nm-news-card__title"><?= html_escape($n['judul']) ?></div>
            <div class="nm-news-card__desc">
              <?= word_limiter(strip_tags($n['konten'] ?? ($n['deskripsi'] ?? '')), 18) ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>

<?php $this->load->view('templates/member/bottom_nav'); ?>
