<?php
$aktif        = $poin['aktif'] ?? 0;
$digunakan    = $poin['digunakan'] ?? 0;
$kedaluwarsa  = $poin['kedaluwarsa'] ?? 0;
$akan         = $poin['akan_kedaluwarsa'] ?? 0;
?>
<div class="page-content nm-page">

  <!-- HERO -->
  <div class="nm-page-hero">
    <div class="nm-page-hero__nav">
      <a href="<?= site_url('member') ?>" class="nm-hero-back"><i class="f7-icons">chevron_left</i></a>
      <span class="nm-page-hero__label">Poin Saya</span>
      <a href="<?= site_url('member/logout') ?>" class="nm-logout"><i class="f7-icons">rectangle_porous_arrow_right</i></a>
    </div>
    <div class="nm-page-hero__center">
      <div class="nm-page-hero__emoji">⭐</div>
      <div class="nm-page-hero__big"><?= number_format($aktif) ?></div>
      <div class="nm-page-hero__sub">Poin Aktif &middot; Level <?= html_escape($level ?? '-') ?></div>
    </div>
    <div class="nm-phero-chips">
      <div class="nm-phero-chip">
        <span class="nm-phero-chip__n"><?= number_format($digunakan) ?></span>
        <span class="nm-phero-chip__l">Digunakan</span>
      </div>
      <div class="nm-phero-chip nm-phero-chip--amber">
        <span class="nm-phero-chip__n"><?= number_format($akan) ?></span>
        <span class="nm-phero-chip__l">Akan Habis</span>
      </div>
      <div class="nm-phero-chip nm-phero-chip--red">
        <span class="nm-phero-chip__n"><?= number_format($kedaluwarsa) ?></span>
        <span class="nm-phero-chip__l">Kedaluwarsa</span>
      </div>
    </div>
  </div>

  <!-- FILTER -->
  <div class="nm-card nm-inline-filter">
    <form method="get">
      <div class="nm-inline-filter__row">
        <div class="nm-field"><label>Dari</label><input type="date" name="start" value="<?= html_escape($start) ?>"></div>
        <div class="nm-field"><label>Sampai</label><input type="date" name="end" value="<?= html_escape($end) ?>"></div>
        <div class="nm-field" style="max-width:80px;">
          <label>Limit</label>
          <select name="limit">
            <?php foreach ([10, 30, 50, 'semua'] as $v): ?>
              <option value="<?= $v ?>" <?= ($limit == $v) ? 'selected' : '' ?>><?= ucfirst($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="nm-inline-filter__go" type="submit"><i class="f7-icons">arrow_right_circle_fill</i></button>
      </div>
    </form>
  </div>

  <!-- TIMELINE -->
  <div class="nm-section-head" style="margin-top:16px;">
    <div>
      <div class="nm-section-title">Riwayat Poin</div>
      <div class="nm-section-sub">Periode terpilih</div>
    </div>
  </div>

  <?php if (empty($riwayat)): ?>
    <div class="nm-empty-state nm-card">
      <div class="nm-empty-state__ico">⭐</div>
      <div class="nm-empty-state__txt">Belum ada riwayat poin.</div>
    </div>
  <?php else: ?>
    <div class="nm-timeline">
      <?php foreach ($riwayat as $r): ?>
        <?php
          $status = strtolower($r['status'] ?? '');
          $cls    = $status === 'aktif' ? 'plus' : ($status === 'digunakan' ? 'minus' : 'exp');
          $ico    = $status === 'aktif' ? '⭐' : ($status === 'digunakan' ? '🛒' : '⌛');
          $sign   = $status === 'aktif' ? '+' : '-';
          $badge  = $status === 'aktif' ? 'success' : ($status === 'digunakan' ? 'warning' : 'danger');
        ?>
        <div class="nm-timeline__row">
          <div class="nm-timeline__dot nm-tl-<?= $cls ?>"><?= $ico ?></div>
          <div class="nm-card nm-timeline__card">
            <div class="nm-timeline__head">
              <div>
                <div class="nm-timeline__trx"><?= html_escape($r['no_transaksi'] ?? '-') ?></div>
                <div class="nm-timeline__date"><?= date('d M Y', strtotime($r['created_at'])) ?></div>
              </div>
              <div style="text-align:right;">
                <div class="nm-timeline__pts nm-tlpts-<?= $cls ?>"><?= $sign . number_format($r['jumlah_poin'] ?? 0) ?> ⭐</div>
                <span class="nm-badge <?= $badge ?>"><?= ucfirst($status ?: '-') ?></span>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($limit !== 'semua' && $total_pages > 1): ?>
    <div class="nm-pagination nm-pagination-f7">
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a class="<?= $i == $page ? 'active' : '' ?>"
           href="?start=<?= urlencode($start) ?>&end=<?= urlencode($end) ?>&limit=<?= urlencode($limit) ?>&page=<?= $i ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>

</div>
<?php $this->load->view('templates/member/bottom_nav'); ?>
