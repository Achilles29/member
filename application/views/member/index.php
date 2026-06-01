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
  ?>

  <!-- TOPBAR -->
  <div class="nm-topbar">
    <div>
      <div class="nm-name"><?= html_escape($member['nama'] ?? 'Guest') ?></div>
      <div class="nm-level">Level <?= html_escape($level ?? '-') ?></div>
    </div>

    <a class="nm-logout" href="<?= site_url('member/logout') ?>" title="Logout">
      <i class="f7-icons">rectangle_porous_arrow_right</i>
    </a>
  </div>

  <!-- BALANCE -->
  <div class="nm-balance-card">
    <div class="nm-balance-label">Star Balance</div>
    <div class="nm-balance-value">
      <?= number_format($poin ?? 0) ?> <span class="nm-star">⭐</span>
    </div>

    <div class="nm-balance-sub">
      <div class="nm-chip"><span class="dot"></span> Member Active</div>
      <div class="nm-chip"><?= date('d M Y') ?></div>
    </div>
  </div>

  <!-- QUICK ACTION -->
  <div class="nm-actions">
    <a class="nm-action" href="<?= site_url('redeem') ?>">
      <div class="ico"><i class="f7-icons">gift</i></div>
      <span>Redeem</span>
    </a>
    <a class="nm-action" href="<?= site_url('voucher') ?>">
      <div class="ico"><i class="f7-icons">ticket</i></div>
      <span>Voucher</span>
    </a>
    <a class="nm-action" href="<?= site_url('stamp') ?>">
      <div class="ico"><i class="f7-icons">bookmark</i></div>
      <span>Stamp</span>
    </a>
    <?php if ($self_order_available): ?>
    <a class="nm-action" href="<?= site_url('order') ?>">
      <div class="ico"><i class="f7-icons">doc_text</i></div>
      <span>Order</span>
    </a>
    <?php endif; ?>
    <a class="nm-action" href="<?= site_url('transaksi') ?>">
      <div class="ico"><i class="f7-icons">doc_text</i></div>
      <span>Riwayat</span>
    </a>
  </div>

  <!-- STATS -->
  <div class="nm-stats">
    <a class="nm-stat" href="<?= site_url('poin') ?>">
      <i class="f7-icons">star</i>
      <div class="nm-stat-num"><?= number_format($poin ?? 0) ?></div>
      <div class="nm-stat-label">Poin</div>
    </a>

    <a class="nm-stat" href="<?= site_url('stamp') ?>">
      <i class="f7-icons">bookmark</i>
      <div class="nm-stat-num"><?= number_format($total_stamp) ?></div>
      <div class="nm-stat-label">Stamp</div>
    </a>

    <a class="nm-stat" href="<?= site_url('voucher') ?>">
      <i class="f7-icons">ticket</i>
      <div class="nm-stat-num"><?= is_array($voucher_aktif ?? null) ? count($voucher_aktif) : 0 ?></div>
      <div class="nm-stat-label">Voucher</div>
    </a>
  </div>

  <!-- PROMO -->
  <?php if (!empty($promos)): ?>
    <div class="nm-section-head">
      <div>
        <div class="nm-section-title">Promo</div>
        <div class="nm-section-sub">Jangan sampai kelewatan</div>
      </div>
    </div>

    <div class="nm-promo">
      <?php foreach ($promos as $p): ?>
        <div class="nm-promo-card">
          <img src="<?= dashboard_url($p['gambar']) ?>" alt="<?= html_escape($p['judul']) ?>">
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- NEWS -->
  <?php if (!empty($news)): ?>
    <div class="nm-section-head">
      <div>
        <div class="nm-section-title">Namua News</div>
        <div class="nm-section-sub">Update terbaru untuk kamu</div>
      </div>
    </div>

    <?php foreach ($news as $n): ?>
      <div class="nm-card">
        <div class="nm-news">
          <img class="nm-news-thumb" src="<?= dashboard_url($n['gambar']) ?>" alt="<?= html_escape($n['judul']) ?>">
          <div>
            <div class="nm-news-title"><?= html_escape($n['judul']) ?></div>
            <div class="nm-news-desc">
              <?= word_limiter(strip_tags($n['konten'] ?? ($n['deskripsi'] ?? '')), 18) ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>

<?php $this->load->view('templates/member/bottom_nav'); ?>
