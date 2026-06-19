<?php
$stamp_total = 0;
if (!empty($stamp) && is_array($stamp)) {
  $stamp_total = array_sum(array_column($stamp, 'jumlah_stamp'));
}
?>
<div class="page-content nm-page">

  <!-- HERO -->
  <div class="nm-page-hero nm-page-hero--redeem">
    <div class="nm-page-hero__nav">
      <a href="<?= site_url('member') ?>" class="nm-hero-back"><i class="f7-icons">chevron_left</i></a>
      <span class="nm-page-hero__label">Redeem Reward</span>
      <a href="<?= site_url('member/logout') ?>" class="nm-logout"><i class="f7-icons">rectangle_porous_arrow_right</i></a>
    </div>
    <div class="nm-phero-chips">
      <div class="nm-phero-chip nm-phero-chip--lg">
        <span class="nm-phero-chip__ico">⭐</span>
        <span class="nm-phero-chip__n"><?= number_format((int)$poin) ?></span>
        <span class="nm-phero-chip__l">Poin</span>
      </div>
      <div class="nm-phero-chip nm-phero-chip--lg nm-phero-chip--amber">
        <span class="nm-phero-chip__ico">☕</span>
        <span class="nm-phero-chip__n"><?= number_format((int)$stamp_total) ?></span>
        <span class="nm-phero-chip__l">Stamp</span>
      </div>
    </div>
    <?php if ($this->session->flashdata('success')): ?>
      <div class="nm-alert success" style="margin-top:12px;"><?= html_escape($this->session->flashdata('success')) ?></div>
    <?php elseif ($this->session->flashdata('error')): ?>
      <div class="nm-alert danger" style="margin-top:12px;"><?= html_escape($this->session->flashdata('error')) ?></div>
    <?php endif; ?>
  </div>

  <!-- TABS -->
  <div class="nm-vtabs">
    <button class="nm-vtab is-active" type="button" data-tab="poin">
      Dari Poin <span class="nm-vtab-badge"><?= is_array($redeem_poin ?? null) ? count($redeem_poin) : 0 ?></span>
    </button>
    <button class="nm-vtab" type="button" data-tab="stamp">
      Dari Stamp <span class="nm-vtab-badge"><?= is_array($redeem_stamp ?? null) ? count($redeem_stamp) : 0 ?></span>
    </button>
  </div>

  <!-- POIN TAB -->
  <div id="tab-poin" class="nm-tab-panel is-show">
    <?php if (!empty($redeem_poin)): ?>
      <?php foreach ($redeem_poin as $item): ?>
        <?php
          $need  = (int)($item['jumlah_dibutuhkan'] ?? 0);
          $cukup = ((int)$poin >= $need);
          $desc  = '';
          if (($item['jenis_voucher'] ?? '') === 'produk' && !empty($item['produk_id']))
            $desc = 'Gratis: ' . html_escape($item['produk_nama'] ?? 'Produk');
          elseif (($item['jenis_voucher'] ?? '') === 'diskon') {
            if (($item['tipe_diskon'] ?? '') === 'persentase')
              $desc = 'Diskon ' . (int)($item['nilai_voucher'] ?? 0) . '% (max Rp ' . number_format((int)($item['max_diskon'] ?? 0), 0, ',', '.') . ')';
            else
              $desc = 'Diskon Rp ' . number_format((int)($item['nilai_voucher'] ?? 0), 0, ',', '.');
          }
        ?>
        <div class="nm-card nm-redeem-card <?= $cukup ? '' : 'nm-redeem-card--dim' ?>">
          <div class="nm-redeem-card__head">
            <div class="nm-redeem-card__icon">🎁</div>
            <div style="flex:1;min-width:0;">
              <div class="nm-redeem-card__name"><?= html_escape($item['nama_redeem'] ?? 'Redeem') ?></div>
              <?php if ($desc): ?><div class="nm-redeem-card__desc"><?= $desc ?></div><?php endif; ?>
            </div>
            <span class="nm-badge neutral">Poin</span>
          </div>
          <div class="nm-redeem-card__foot">
            <div class="nm-redeem-card__cost">
              <span>Butuh</span>
              <strong><?= number_format($need) ?> ⭐</strong>
              <span class="nm-redeem-card__own">/ kamu <?= number_format((int)$poin) ?></span>
            </div>
            <?php if ($cukup): ?>
              <a href="<?= site_url('redeem/process/' . (int)$item['id']) ?>" class="nm-redeem-card__btn redeem-trigger">Redeem</a>
            <?php else: ?>
              <span class="nm-redeem-card__btn nm-redeem-card__btn--off">Kurang</span>
            <?php endif; ?>
          </div>
          <?php if ($cukup): ?>
            <div class="nm-redeem-card__prog"><div style="width:100%;"></div></div>
          <?php else: ?>
            <div class="nm-redeem-card__prog"><div style="width:<?= min(100, round((int)$poin / max(1, $need) * 100)) ?>%;"></div></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="nm-empty-state nm-card"><div class="nm-empty-state__ico">⭐</div><div class="nm-empty-state__txt">Belum ada voucher yang bisa diredeem dengan poin.</div></div>
    <?php endif; ?>
  </div>

  <!-- STAMP TAB -->
  <div id="tab-stamp" class="nm-tab-panel">
    <?php if (!empty($redeem_stamp)): ?>
      <?php foreach ($redeem_stamp as $item): ?>
        <?php
          $need  = (int)($item['jumlah_dibutuhkan'] ?? 0);
          $cukup = ((int)$stamp_total >= $need);
          $desc  = '';
          if (($item['jenis_voucher'] ?? '') === 'produk' && !empty($item['produk_id']))
            $desc = 'Gratis: ' . html_escape($item['produk_nama'] ?? 'Produk');
          elseif (($item['jenis_voucher'] ?? '') === 'diskon') {
            if (($item['tipe_diskon'] ?? '') === 'persentase')
              $desc = 'Diskon ' . (int)($item['nilai_voucher'] ?? 0) . '% (max Rp ' . number_format((int)($item['max_diskon'] ?? 0), 0, ',', '.') . ')';
            else
              $desc = 'Diskon Rp ' . number_format((int)($item['nilai_voucher'] ?? 0), 0, ',', '.');
          }
        ?>
        <div class="nm-card nm-redeem-card <?= $cukup ? '' : 'nm-redeem-card--dim' ?>">
          <div class="nm-redeem-card__head">
            <div class="nm-redeem-card__icon">☕</div>
            <div style="flex:1;min-width:0;">
              <div class="nm-redeem-card__name"><?= html_escape($item['nama_redeem'] ?? 'Redeem') ?></div>
              <?php if ($desc): ?><div class="nm-redeem-card__desc"><?= $desc ?></div><?php endif; ?>
            </div>
            <span class="nm-badge neutral">Stamp</span>
          </div>
          <div class="nm-redeem-card__foot">
            <div class="nm-redeem-card__cost">
              <span>Butuh</span>
              <strong><?= number_format($need) ?> ☕</strong>
              <span class="nm-redeem-card__own">/ kamu <?= number_format((int)$stamp_total) ?></span>
            </div>
            <?php if ($cukup): ?>
              <a href="<?= site_url('redeem/process/' . (int)$item['id']) ?>" class="nm-redeem-card__btn redeem-trigger">Redeem</a>
            <?php else: ?>
              <span class="nm-redeem-card__btn nm-redeem-card__btn--off">Kurang</span>
            <?php endif; ?>
          </div>
          <div class="nm-redeem-card__prog"><div style="width:<?= min(100, round((int)$stamp_total / max(1, $need) * 100)) ?>%;"></div></div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="nm-empty-state nm-card"><div class="nm-empty-state__ico">☕</div><div class="nm-empty-state__txt">Belum ada voucher yang bisa diredeem dengan stamp.</div></div>
    <?php endif; ?>
  </div>

</div>

<?php $this->load->view('templates/member/bottom_nav'); ?>

<div id="nm-loading" class="nm-loading" style="display:none;">
  <div class="nm-loading-card"><div class="nm-loading-spin"></div><div class="nm-loading-text">Memproses...</div></div>
</div>

<script>
(function(){
  document.querySelectorAll('.nm-vtab[data-tab]').forEach(function(btn){
    btn.addEventListener('click',function(){
      document.querySelectorAll('.nm-vtab[data-tab]').forEach(function(b){b.classList.remove('is-active');});
      btn.classList.add('is-active');
      var t=btn.getAttribute('data-tab');
      document.querySelectorAll('.nm-tab-panel').forEach(function(p){p.classList.remove('is-show');});
      var el=document.getElementById('tab-'+t);
      if(el)el.classList.add('is-show');
    });
  });
  document.querySelectorAll('.redeem-trigger').forEach(function(a){
    a.addEventListener('click',function(e){
      e.preventDefault();
      if(confirm('Tukar poin/stamp untuk voucher ini?')){
        document.getElementById('nm-loading').style.display='flex';
        window.location.href=this.href;
      }
    });
  });
})();
</script>
