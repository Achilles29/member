<div class="page-content nm-page">

  <!-- TOPBAR MINI -->
  <div class="nm-topbar nm-topbar--mini">
    <div>
      <div class="nm-name">Poin Saya</div>
      <div class="nm-level">Level <?= html_escape($level ?? '-') ?></div>
    </div>

    <a class="nm-logout" href="<?= site_url('member') ?>" title="Kembali">
      <i class="f7-icons">chevron_left</i>
    </a>
  </div>

  <!-- HERO SUMMARY -->
  <div class="nm-poin-hero">
    <div class="nm-poin-hero-card">
      <div class="nm-poin-hero-label">Poin Aktif</div>
      <div class="nm-poin-hero-value">
        <?= number_format($poin['aktif'] ?? 0) ?> <span>⭐</span>
      </div>

      <div class="nm-poin-chips">
        <div class="nm-pill success">
          <b><?= number_format($poin['digunakan'] ?? 0) ?></b> Digunakan
        </div>
        <div class="nm-pill danger">
          <b><?= number_format($poin['kedaluwarsa'] ?? 0) ?></b> Kedaluwarsa
        </div>
        <div class="nm-pill warning">
          <b><?= number_format($poin['akan_kedaluwarsa'] ?? 0) ?></b> Akan Habis
        </div>
      </div>
    </div>
  </div>

  <!-- FILTER CARD -->
  <div class="nm-card nm-filter-card">
    <form method="get" class="nm-filter-f7">
      <div class="nm-filter-row">
        <div class="nm-field">
          <label>Dari</label>
          <input type="date" name="start" value="<?= html_escape($start) ?>">
        </div>

        <div class="nm-field">
          <label>Sampai</label>
          <input type="date" name="end" value="<?= html_escape($end) ?>">
        </div>
      </div>

      <div class="nm-filter-row">
        <div class="nm-field grow">
          <label>Jumlah</label>
          <select name="limit">
            <?php foreach ([10, 30, 50, 'semua'] as $v): ?>
              <option value="<?= $v ?>" <?= ($limit == $v) ? 'selected' : '' ?>>
                <?= ucfirst($v) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <button class="nm-btn-primary" type="submit">
          Tampilkan
        </button>
      </div>
    </form>
  </div>

  <!-- RIWAYAT -->
  <div class="nm-section-head">
    <div>
      <div class="nm-section-title">Riwayat Poin</div>
      <div class="nm-section-sub">Transaksi poin periode terpilih</div>
    </div>
  </div>

  <?php if (empty($riwayat)): ?>
    <div class="nm-card nm-empty-card">
      Belum ada riwayat poin.
    </div>
  <?php endif; ?>

  <?php foreach ($riwayat as $r): ?>
    <?php
      $status = strtolower($r['status'] ?? '');
      $badgeClass = 'neutral';
      if ($status === 'aktif') $badgeClass = 'success';
      if ($status === 'digunakan') $badgeClass = 'warning';
      if ($status === 'kedaluwarsa') $badgeClass = 'danger';
    ?>
    <div class="nm-card nm-history-card">
      <div class="nm-history-left">
        <div class="nm-history-date">
          <?= date('d M Y', strtotime($r['created_at'])) ?>
        </div>
        <div class="nm-history-trx">
          <?= html_escape($r['no_transaksi'] ?? '-') ?>
        </div>
      </div>

      <div class="nm-history-right">
        <div class="nm-history-point">
          <?= number_format($r['jumlah_poin'] ?? 0) ?> ⭐
        </div>
        <div class="nm-badge <?= $badgeClass ?>">
          <?= ucfirst($status ?: '-') ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <!-- PAGINATION -->
  <?php if ($limit !== 'semua' && $total_pages > 1): ?>
    <div class="nm-pagination nm-pagination-f7">
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a
          class="<?= $i == $page ? 'active' : '' ?>"
          href="?start=<?= urlencode($start) ?>&end=<?= urlencode($end) ?>&limit=<?= urlencode($limit) ?>&page=<?= $i ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>

</div>

<?php $this->load->view('templates/member/bottom_nav'); ?>
