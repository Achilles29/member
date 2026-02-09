<!-- HERO POINT CARD -->
<section class="hero-card">
  <small>Star Balance</small>
  <h1><?= $poin ?> ★</h1>
  <span class="level"><?= $level ?> Member</span>

  <div class="hero-actions">
    <a href="<?= site_url('poin') ?>" class="btn-outline">Detail</a>
    <a href="<?= site_url('redeem') ?>" class="btn-solid">Redeem</a>
  </div>
</section>

<!-- QUICK STATS -->
<section class="quick-stats">
  <div>
    <i class="fas fa-star"></i>
    <strong><?= $poin ?></strong>
    <small>Poin</small>
  </div>

  <div>
    <i class="fas fa-stamp"></i>
    <strong><?= $stamp_list[0]['jumlah_stamp'] ?? 0 ?></strong>
    <small>Stamp</small>
  </div>

  <div>
    <i class="fas fa-ticket-alt"></i>
    <strong><?= count($voucher_aktif ?? []) ?></strong>
    <small>Voucher</small>
  </div>
</section>

<!-- PROMO SLIDER -->
<section class="promo-section">
  <div class="section-header">
    <h3>Promo Spesial</h3>
  </div>

  <?php if (!empty($promos)): ?>
    <div class="promo-wrapper">
      <div class="promo-slider" id="promoSlider">
        <?php foreach ($promos as $p): ?>
          <div class="promo-slide">
            <img src="<?= dashboard_url($p['gambar']) ?>" alt="<?= $p['judul'] ?>">
            <span class="promo-title"><?= $p['judul'] ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="promo-dots" id="promoDots"></div>
  <?php else: ?>
    <div class="empty-state">
      <i class="fas fa-bullhorn"></i>
      <p>Belum ada promo hari ini</p>
    </div>
  <?php endif; ?>
</section>

<!-- NEWS -->
<section class="news-section">
  <div class="section-header">
    <h3>Namua News</h3>
  </div>

  <?php if (!empty($news)): ?>
    <?php foreach ($news as $n): ?>
      <a href="<?= site_url('news/detail/'.$n['id']) ?>" class="news-card">
        <img src="<?= dashboard_url($n['gambar']) ?>" alt="<?= $n['judul'] ?>">
        <div>
          <h4><?= $n['judul'] ?></h4>
          <p><?= word_limiter(strip_tags($n['konten'] ?? ''), 16); ?></p>
        </div>
      </a>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="empty-state">
      <i class="fas fa-newspaper"></i>
      <p>Belum ada berita</p>
    </div>
  <?php endif; ?>
</section>
