<div class="page-content nm-page">

  <!-- TOPBAR MINI -->
  <div class="nm-topbar nm-topbar--mini">
    <div>
      <div class="nm-name"><?= html_escape($member['nama'] ?? 'Guest') ?></div>
      <div class="nm-level">Redeem Voucher</div>
    </div>

    <a class="nm-logout" href="<?= site_url('member/logout') ?>" title="Logout">
      <i class="f7-icons">rectangle_porous_arrow_right</i>
    </a>
  </div>

  <?php
    $stamp_total = 0;
    if (!empty($stamp) && is_array($stamp)) {
      $stamp_total = array_sum(array_column($stamp, 'jumlah_stamp'));
    }
  ?>

  <!-- SALDO CARD -->
  <div class="nm-redeem-hero">
    <div class="nm-redeem-hero-card">
      <div class="nm-redeem-hero-title">Saldo Kamu</div>

      <div class="nm-redeem-hero-row">
        <div class="nm-redeem-balance">
          <div class="nm-redeem-balance-label">Poin</div>
          <div class="nm-redeem-balance-value"><?= number_format((int)$poin) ?> <span>⭐</span></div>
        </div>

        <div class="nm-redeem-balance">
          <div class="nm-redeem-balance-label">Stamp</div>
          <div class="nm-redeem-balance-value"><?= number_format((int)$stamp_total) ?> <span>🟤</span></div>
        </div>
      </div>

      <?php if ($this->session->flashdata('success')): ?>
        <div class="nm-alert success"><?= html_escape($this->session->flashdata('success')) ?></div>
      <?php elseif ($this->session->flashdata('error')): ?>
        <div class="nm-alert danger"><?= html_escape($this->session->flashdata('error')) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- TABS -->
  <div class="nm-redeem-tabs">
    <button class="nm-rtab is-active" type="button" data-tab="poin">
      Dari Poin <span class="nm-rbadge"><?= is_array($redeem_poin ?? null) ? count($redeem_poin) : 0 ?></span>
    </button>
    <button class="nm-rtab" type="button" data-tab="stamp">
      Dari Stamp <span class="nm-rbadge"><?= is_array($redeem_stamp ?? null) ? count($redeem_stamp) : 0 ?></span>
    </button>
  </div>

  <!-- LIST POIN -->
  <div id="tab-poin" class="nm-redeem-list is-show">
    <div class="nm-section-head">
      <div>
        <div class="nm-section-title">Voucher dari Poin</div>
        <div class="nm-section-sub">Tukarkan poin jadi voucher</div>
      </div>
    </div>

    <?php if (!empty($redeem_poin)): ?>
      <?php foreach ($redeem_poin as $item): ?>
        <?php
          $need = (int)($item['jumlah_dibutuhkan'] ?? 0);
          $cukup = ((int)$poin >= $need);

          // Deskripsi voucher
          $desc = '';
          if (($item['jenis_voucher'] ?? '') === 'produk' && !empty($item['produk_id'])) {
            $produk = $this->db->get_where('pr_produk', ['id' => $item['produk_id']])->row_array();
            $desc = 'Produk: ' . html_escape($produk['nama_produk'] ?? 'Produk tidak ditemukan');
          } elseif (($item['jenis_voucher'] ?? '') === 'diskon') {
            if (($item['tipe_diskon'] ?? '') === 'persentase') {
              $max = number_format((int)($item['max_diskon'] ?? 0), 0, ',', '.');
              $desc = 'Diskon ' . (int)($item['nilai_voucher'] ?? 0) . '% (max Rp ' . $max . ')';
            } else {
              $desc = 'Diskon Rp ' . number_format((int)($item['nilai_voucher'] ?? 0), 0, ',', '.');
            }
          }
        ?>

        <div class="nm-card nm-redeem-card">
          <div class="nm-redeem-head">
            <div class="nm-redeem-title"><?= html_escape($item['nama_redeem'] ?? 'Redeem') ?></div>
            <span class="nm-badge neutral">Poin</span>
          </div>

          <?php if ($desc): ?>
            <div class="nm-redeem-desc"><?= $desc ?></div>
          <?php endif; ?>

          <div class="nm-redeem-need">
            <span>Butuh</span>
            <b><?= number_format($need) ?> ⭐</b>
          </div>

          <div class="nm-redeem-foot">
            <div class="nm-redeem-owned">Poin kamu: <b><?= number_format((int)$poin) ?></b></div>

            <?php if ($cukup): ?>
              <a href="<?= site_url('redeem/process/' . (int)$item['id']) ?>" class="nm-btn-primary nm-redeem-btn redeem-trigger">
                Redeem
              </a>
            <?php else: ?>
              <span class="nm-redeem-btn-disabled">Tidak Cukup</span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="nm-card nm-empty-card">Belum ada voucher yang bisa diredeem dengan poin.</div>
    <?php endif; ?>
  </div>

  <!-- LIST STAMP -->
  <div id="tab-stamp" class="nm-redeem-list">
    <div class="nm-section-head">
      <div>
        <div class="nm-section-title">Voucher dari Stamp</div>
        <div class="nm-section-sub">Tukarkan stamp jadi voucher</div>
      </div>
    </div>

    <?php if (!empty($redeem_stamp)): ?>
      <?php foreach ($redeem_stamp as $item): ?>
        <?php
          $need = (int)($item['jumlah_dibutuhkan'] ?? 0);
          $cukup = ((int)$stamp_total >= $need);

          $desc = '';
          if (($item['jenis_voucher'] ?? '') === 'produk' && !empty($item['produk_id'])) {
            $produk = $this->db->get_where('pr_produk', ['id' => $item['produk_id']])->row_array();
            $desc = 'Produk: ' . html_escape($produk['nama_produk'] ?? 'Produk tidak ditemukan');
          } elseif (($item['jenis_voucher'] ?? '') === 'diskon') {
            if (($item['tipe_diskon'] ?? '') === 'persentase') {
              $max = number_format((int)($item['max_diskon'] ?? 0), 0, ',', '.');
              $desc = 'Diskon ' . (int)($item['nilai_voucher'] ?? 0) . '% (max Rp ' . $max . ')';
            } else {
              $desc = 'Diskon Rp ' . number_format((int)($item['nilai_voucher'] ?? 0), 0, ',', '.');
            }
          }
        ?>

        <div class="nm-card nm-redeem-card">
          <div class="nm-redeem-head">
            <div class="nm-redeem-title"><?= html_escape($item['nama_redeem'] ?? 'Redeem') ?></div>
            <span class="nm-badge neutral">Stamp</span>
          </div>

          <?php if ($desc): ?>
            <div class="nm-redeem-desc"><?= $desc ?></div>
          <?php endif; ?>

          <div class="nm-redeem-need">
            <span>Butuh</span>
            <b><?= number_format($need) ?> Stamp</b>
          </div>

          <div class="nm-redeem-foot">
            <div class="nm-redeem-owned">Stamp kamu: <b><?= number_format((int)$stamp_total) ?></b></div>

            <?php if ($cukup): ?>
              <a href="<?= site_url('redeem/process/' . (int)$item['id']) ?>" class="nm-btn-primary nm-redeem-btn redeem-trigger">
                Redeem
              </a>
            <?php else: ?>
              <span class="nm-redeem-btn-disabled">Tidak Cukup</span>
            <?php endif; ?>
          </div>
        </div>

      <?php endforeach; ?>
    <?php else: ?>
      <div class="nm-card nm-empty-card">Belum ada voucher yang bisa diredeem dengan stamp.</div>
    <?php endif; ?>
  </div>

</div>

<?php $this->load->view('templates/member/bottom_nav'); ?>

<!-- Modal Loading -->
<div id="nm-loading" class="nm-loading" style="display:none;">
  <div class="nm-loading-card">
    <div class="nm-loading-spin"></div>
    <div class="nm-loading-text">Memproses...</div>
  </div>
</div>

<script>
  // tab switch
  const tabs = document.querySelectorAll('.nm-rtab');
  const tabPoin = document.getElementById('tab-poin');
  const tabStamp = document.getElementById('tab-stamp');

  tabs.forEach(btn => {
    btn.addEventListener('click', () => {
      tabs.forEach(b => b.classList.remove('is-active'));
      btn.classList.add('is-active');

      const t = btn.getAttribute('data-tab');
      if (t === 'poin') {
        tabPoin.classList.add('is-show');
        tabStamp.classList.remove('is-show');
      } else {
        tabStamp.classList.add('is-show');
        tabPoin.classList.remove('is-show');
      }
    });
  });

  // confirm + loading overlay
  document.querySelectorAll('.redeem-trigger').forEach(a => {
    a.addEventListener('click', function(e){
      e.preventDefault();
      if (confirm('Apakah Anda yakin ingin menukar poin/stamp untuk voucher ini?')) {
        document.getElementById('nm-loading').style.display = 'flex';
        window.location.href = this.href;
      }
    });
  });
</script>
