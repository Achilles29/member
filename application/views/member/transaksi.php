<div class="page-content nm-page">
  <div class="nm-topbar nm-topbar--mini">
    <div>
      <div class="nm-name">Riwayat Transaksi</div>
      <div class="nm-level"><?= html_escape($member['nama'] ?? 'Member') ?></div>
    </div>
    <a class="nm-logout" href="<?= site_url('member') ?>" title="Kembali">
      <i class="f7-icons">chevron_left</i>
    </a>
  </div>

  <div class="nm-card nm-filter-card">
    <form method="get" class="nm-filter-f7">
      <div class="nm-filter-row">
        <div class="nm-field">
          <label>Bulan</label>
          <select name="month">
            <?php for ($i = 0; $i < 12; $i++): ?>
              <?php
                $val = date('Y-m', strtotime("-{$i} month"));
                $label = date('F Y', strtotime($val . '-01'));
              ?>
              <option value="<?= $val ?>" <?= ($month === $val) ? 'selected' : '' ?>>
                <?= html_escape($label) ?>
              </option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="nm-field">
          <label>No Transaksi</label>
          <input type="text" name="search" placeholder="Cari no transaksi..." value="<?= html_escape($search) ?>">
        </div>
      </div>

      <div class="nm-filter-row">
        <div class="nm-field grow">
          <label>Jumlah</label>
          <select name="limit">
            <?php foreach (['10', '20', '50', 'semua'] as $v): ?>
              <option value="<?= $v ?>" <?= ((string)$limit === (string)$v) ? 'selected' : '' ?>><?= ucfirst($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="nm-btn-primary" type="submit">Tampilkan</button>
      </div>
    </form>
  </div>

  <div class="nm-section-head">
    <div>
      <div class="nm-section-title">Daftar Transaksi</div>
      <div class="nm-section-sub"><?= (int)$total_rows ?> transaksi ditemukan</div>
    </div>
  </div>

  <?php if (empty($transaksi)): ?>
    <div class="nm-card nm-empty-card">Belum ada transaksi di periode ini.</div>
  <?php endif; ?>

  <?php foreach ($transaksi as $trx): ?>
    <?php
      $total = (int)($trx['total_pembayaran'] ?: $trx['total_penjualan']);
      $tanggal = $trx['waktu_bayar'] ?: $trx['waktu_order'];
    ?>
    <div class="nm-card nm-trx-row">
      <div class="nm-trx-left">
        <div class="nm-trx-no"><?= html_escape($trx['no_transaksi']) ?></div>
        <div class="nm-trx-date"><?= date('d M Y H:i', strtotime($tanggal)) ?></div>
        <div class="nm-trx-total">Rp <?= number_format($total) ?></div>
      </div>
      <div class="nm-trx-actions">
        <a class="nm-trx-btn" href="<?= site_url('transaksi/detail/' . (int)$trx['id']) ?>">Detail</a>
        <a class="nm-trx-btn is-secondary" href="<?= site_url('transaksi/struk/' . (int)$trx['id']) ?>">Lihat Struk</a>
      </div>
    </div>
  <?php endforeach; ?>

  <?php if ($limit !== 'semua' && $total_pages > 1): ?>
    <div class="nm-pagination nm-pagination-f7">
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a
          class="<?= ((int)$i === (int)$page) ? 'active' : '' ?>"
          href="?month=<?= urlencode($month) ?>&search=<?= urlencode($search) ?>&limit=<?= urlencode($limit) ?>&page=<?= (int)$i ?>">
          <?= (int)$i ?>
        </a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>

<?php $this->load->view('templates/member/bottom_nav'); ?>
