<div class="page-content nm-page">

  <div class="nm-page-hero nm-page-hero--trx">
    <div class="nm-page-hero__nav">
      <a href="<?= site_url('member') ?>" class="nm-hero-back"><i class="f7-icons">chevron_left</i></a>
      <span class="nm-page-hero__label">Riwayat Transaksi</span>
      <a href="<?= site_url('member/logout') ?>" class="nm-logout"><i class="f7-icons">rectangle_porous_arrow_right</i></a>
    </div>
    <div class="nm-page-hero__center" style="padding-bottom:4px;">
      <div class="nm-page-hero__emoji">🧾</div>
      <div class="nm-page-hero__big"><?= (int)$total_rows ?></div>
      <div class="nm-page-hero__sub">Transaksi ditemukan · <?= html_escape($member['nama'] ?? 'Member') ?></div>
    </div>
  </div>

  <!-- FILTER -->
  <div class="nm-card nm-inline-filter">
    <form method="get">
      <div class="nm-inline-filter__row">
        <div class="nm-field">
          <label>Bulan</label>
          <select name="month">
            <?php for ($i = 0; $i < 12; $i++): ?>
              <?php $val = date('Y-m', strtotime("-{$i} month")); $lbl = date('M Y', strtotime($val . '-01')); ?>
              <option value="<?= $val ?>" <?= ($month === $val) ? 'selected' : '' ?>><?= html_escape($lbl) ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="nm-field">
          <label>Cari No</label>
          <input type="text" name="search" placeholder="Nomor transaksi" value="<?= html_escape($search) ?>">
        </div>
        <div class="nm-field" style="max-width:80px;">
          <label>Limit</label>
          <select name="limit">
            <?php foreach (['10','20','50','semua'] as $v): ?>
              <option value="<?= $v ?>" <?= ((string)$limit===$v)?'selected':'' ?>><?= ucfirst($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="nm-inline-filter__go" type="submit"><i class="f7-icons">arrow_right_circle_fill</i></button>
      </div>
    </form>
  </div>

  <?php if (empty($transaksi)): ?>
    <div class="nm-empty-state nm-card">
      <div class="nm-empty-state__ico">🧾</div>
      <div class="nm-empty-state__txt">Belum ada transaksi di periode ini.</div>
    </div>
  <?php endif; ?>

  <?php foreach ($transaksi as $trx): ?>
    <?php
      $total   = (int)($trx['total_pembayaran'] ?: $trx['total_penjualan']);
      $tanggal = $trx['waktu_bayar'] ?: $trx['waktu_order'];
    ?>
    <div class="nm-card nm-trx-card">
      <div class="nm-trx-card__top">
        <div class="nm-trx-card__date-pill"><?= date('d M', strtotime($tanggal)) ?></div>
        <div class="nm-trx-card__no"><?= html_escape($trx['no_transaksi']) ?></div>
        <div class="nm-trx-card__total">Rp <?= number_format($total) ?></div>
      </div>
      <div class="nm-trx-card__foot">
        <span class="nm-trx-card__time"><?= date('H:i', strtotime($tanggal)) ?> · <?= date('Y', strtotime($tanggal)) ?></span>
        <div class="nm-trx-card__actions">
          <a class="nm-trx-card__btn" href="<?= site_url('transaksi/detail/' . (int)$trx['id']) ?>">Detail</a>
          <a class="nm-trx-card__btn nm-trx-card__btn--ghost" href="<?= site_url('transaksi/struk/' . (int)$trx['id']) ?>">Struk</a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <?php if ($limit !== 'semua' && $total_pages > 1): ?>
    <div class="nm-pagination nm-pagination-f7">
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a class="<?= ((int)$i===(int)$page)?'active':'' ?>"
           href="?month=<?= urlencode($month) ?>&search=<?= urlencode($search) ?>&limit=<?= urlencode($limit) ?>&page=<?= (int)$i ?>"><?= (int)$i ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>
<?php $this->load->view('templates/member/bottom_nav'); ?>
