<?php
$poin        = (int) ($poin ?? 0);
$stamp_total = isset($stamp_total) ? (int) $stamp_total : 0;
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
        <span class="nm-phero-chip__n"><?= number_format($poin) ?></span>
        <span class="nm-phero-chip__l">Poin</span>
      </div>
      <div class="nm-phero-chip nm-phero-chip--lg nm-phero-chip--amber">
        <span class="nm-phero-chip__ico">☕</span>
        <span class="nm-phero-chip__n"><?= number_format($stamp_total) ?></span>
        <span class="nm-phero-chip__l">Stamp</span>
      </div>
    </div>
    <?php if ($this->session->flashdata('success')): ?>
      <div class="nm-alert success" style="margin:12px 0 0;">
        <?= html_escape($this->session->flashdata('success')) ?>
      </div>
    <?php elseif ($this->session->flashdata('error')): ?>
      <div class="nm-alert danger" style="margin:12px 0 0;">
        <?= html_escape($this->session->flashdata('error')) ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- TABS -->
  <div class="nm-vtabs">
    <button class="nm-vtab is-active" type="button" data-tab="poin">
      Dari Poin <span class="nm-vtab-badge"><?= count($redeem_poin ?? []) ?></span>
    </button>
    <button class="nm-vtab" type="button" data-tab="stamp">
      Dari Stamp <span class="nm-vtab-badge"><?= count($redeem_stamp ?? []) ?></span>
    </button>
  </div>

  <!-- POIN TAB -->
  <div id="tab-poin" class="nm-tab-panel is-show">
    <?php if (!empty($redeem_poin)): ?>
      <?php foreach ($redeem_poin as $item): ?>
        <?php
          $need  = (int) ($item['jumlah_dibutuhkan'] ?? 0);
          $cukup = ($poin >= $need);
          $desc  = nm_redeem_desc($item);
          $pct   = $need > 0 ? min(100, round($poin / $need * 100)) : 100;
        ?>
        <div class="nm-card nm-redeem-card <?= $cukup ? '' : 'nm-redeem-card--dim' ?>">
          <div class="nm-redeem-card__head">
            <div class="nm-redeem-card__icon"><?= nm_redeem_icon($item) ?></div>
            <div style="flex:1;min-width:0;">
              <div class="nm-redeem-card__name"><?= html_escape($item['nama_redeem']) ?></div>
              <?php if ($desc): ?><div class="nm-redeem-card__desc"><?= $desc ?></div><?php endif; ?>
            </div>
            <span class="nm-badge neutral">Poin</span>
          </div>
          <div class="nm-redeem-card__foot">
            <div class="nm-redeem-card__cost">
              <span>Butuh</span>
              <strong><?= number_format($need) ?> ⭐</strong>
              <span class="nm-redeem-card__own">/ kamu <?= number_format($poin) ?></span>
            </div>
            <?php if ($cukup): ?>
              <a href="<?= site_url('redeem/process/' . (int) $item['id']) ?>"
                 class="nm-redeem-card__btn redeem-trigger"
                 data-nama="<?= html_escape($item['nama_redeem']) ?>"
                 data-desc="<?= html_escape($desc) ?>"
                 data-cost="<?= number_format($need) ?>"
                 data-cost-label="⭐ Poin"
                 data-saldo="<?= number_format($poin) ?>"
                 data-ico="<?= nm_redeem_icon($item) ?>">Redeem</a>
            <?php else: ?>
              <span class="nm-redeem-card__btn nm-redeem-card__btn--off">Kurang</span>
            <?php endif; ?>
          </div>
          <div class="nm-redeem-card__prog">
            <div style="width:<?= $cukup ? 100 : $pct ?>%;"></div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="nm-empty-state nm-card">
        <div class="nm-empty-state__ico">⭐</div>
        <div class="nm-empty-state__txt">Belum ada reward yang bisa diredeem dengan poin.</div>
      </div>
    <?php endif; ?>
  </div>

  <!-- STAMP TAB -->
  <div id="tab-stamp" class="nm-tab-panel">
    <?php if (!empty($redeem_stamp)): ?>
      <?php foreach ($redeem_stamp as $item): ?>
        <?php
          $need  = (int) ($item['jumlah_dibutuhkan'] ?? 0);
          $cukup = ($stamp_total >= $need);
          $desc  = nm_redeem_desc($item);
          $pct   = $need > 0 ? min(100, round($stamp_total / $need * 100)) : 100;
        ?>
        <div class="nm-card nm-redeem-card <?= $cukup ? '' : 'nm-redeem-card--dim' ?>">
          <div class="nm-redeem-card__head">
            <div class="nm-redeem-card__icon"><?= nm_redeem_icon($item) ?></div>
            <div style="flex:1;min-width:0;">
              <div class="nm-redeem-card__name"><?= html_escape($item['nama_redeem']) ?></div>
              <?php if ($desc): ?><div class="nm-redeem-card__desc"><?= $desc ?></div><?php endif; ?>
            </div>
            <span class="nm-badge neutral">Stamp</span>
          </div>
          <div class="nm-redeem-card__foot">
            <div class="nm-redeem-card__cost">
              <span>Butuh</span>
              <strong><?= number_format($need) ?> ☕</strong>
              <span class="nm-redeem-card__own">/ kamu <?= number_format($stamp_total) ?></span>
            </div>
            <?php if ($cukup): ?>
              <a href="<?= site_url('redeem/process/' . (int) $item['id']) ?>"
                 class="nm-redeem-card__btn redeem-trigger"
                 data-nama="<?= html_escape($item['nama_redeem']) ?>"
                 data-desc="<?= html_escape($desc) ?>"
                 data-cost="<?= number_format($need) ?>"
                 data-cost-label="☕ Stamp"
                 data-saldo="<?= number_format($stamp_total) ?>"
                 data-ico="<?= nm_redeem_icon($item) ?>">Redeem</a>
            <?php else: ?>
              <span class="nm-redeem-card__btn nm-redeem-card__btn--off">Kurang</span>
            <?php endif; ?>
          </div>
          <div class="nm-redeem-card__prog">
            <div style="width:<?= $cukup ? 100 : $pct ?>%;"></div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="nm-empty-state nm-card">
        <div class="nm-empty-state__ico">☕</div>
        <div class="nm-empty-state__txt">Belum ada reward yang bisa diredeem dengan stamp.</div>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php $this->load->view('templates/member/bottom_nav'); ?>

<!-- REDEEM CONFIRM MODAL -->
<div class="nm-modal-overlay" id="nmRedeemOverlay">
  <div class="nm-modal-sheet" id="nmRedeemSheet">
    <div class="nm-modal-handle"></div>
    <div class="nm-modal-head">
      <div class="nm-modal-head__ico" id="nmModalIco">🎁</div>
      <div>
        <div class="nm-modal-head__title" id="nmModalNama">Reward</div>
        <div class="nm-modal-head__sub" id="nmModalDesc"></div>
      </div>
    </div>
    <div class="nm-modal-body">
      <div class="nm-modal-cost-row">
        <div class="nm-modal-cost-item">
          <div class="nm-modal-cost-item__lbl">Saldo kamu</div>
          <div class="nm-modal-cost-item__val" id="nmModalSaldo">—</div>
        </div>
        <div class="nm-modal-cost-divider">→</div>
        <div class="nm-modal-cost-item">
          <div class="nm-modal-cost-item__lbl">Dipotong</div>
          <div class="nm-modal-cost-item__val nm-modal-cost-item__val--cost" id="nmModalCost">—</div>
        </div>
      </div>
      <p class="nm-modal-note">Pastikan kamu ingin menukarkan reward ini. Tindakan ini tidak dapat dibatalkan.</p>
      <div class="nm-modal-actions">
        <button class="nm-btn nm-btn--ghost" type="button" id="nmModalCancel">Batal</button>
        <a class="nm-btn nm-btn--primary" id="nmModalConfirm" href="#">
          <div class="nm-modal-spin" id="nmModalSpin"></div>
          <span id="nmModalBtnTxt">Ya, Redeem!</span>
        </a>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  // Tab switching
  document.querySelectorAll('.nm-vtab[data-tab]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.nm-vtab[data-tab]').forEach(function (b) { b.classList.remove('is-active'); });
      btn.classList.add('is-active');
      var t = btn.getAttribute('data-tab');
      document.querySelectorAll('.nm-tab-panel').forEach(function (p) { p.classList.remove('is-show'); });
      var el = document.getElementById('tab-' + t);
      if (el) el.classList.add('is-show');
    });
  });

  // Modal elements
  var overlay  = document.getElementById('nmRedeemOverlay');
  var ico      = document.getElementById('nmModalIco');
  var nama     = document.getElementById('nmModalNama');
  var desc     = document.getElementById('nmModalDesc');
  var saldo    = document.getElementById('nmModalSaldo');
  var cost     = document.getElementById('nmModalCost');
  var confirm  = document.getElementById('nmModalConfirm');
  var cancel   = document.getElementById('nmModalCancel');
  var spin     = document.getElementById('nmModalSpin');
  var btnTxt   = document.getElementById('nmModalBtnTxt');
  var targetHref = '';

  function openModal(a) {
    targetHref = a.href;
    ico.textContent   = a.getAttribute('data-ico')        || '🎁';
    nama.textContent  = a.getAttribute('data-nama')       || 'Reward';
    desc.textContent  = a.getAttribute('data-desc')       || '';
    var costLabel     = a.getAttribute('data-cost-label') || '';
    saldo.textContent = a.getAttribute('data-saldo')      || '—';
    cost.textContent  = (a.getAttribute('data-cost')      || '—') + ' ' + costLabel;
    confirm.href = targetHref;
    spin.style.display   = 'none';
    btnTxt.textContent   = 'Ya, Redeem!';
    confirm.style.pointerEvents = 'auto';
    overlay.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    overlay.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  // Open modal when redeem button clicked
  document.querySelectorAll('.redeem-trigger').forEach(function (a) {
    a.addEventListener('click', function (e) {
      e.preventDefault();
      openModal(this);
    });
  });

  // Cancel
  cancel.addEventListener('click', closeModal);

  // Close when clicking backdrop
  overlay.addEventListener('click', function (e) {
    if (e.target === overlay) closeModal();
  });

  // Confirm → show spinner, navigate
  confirm.addEventListener('click', function (e) {
    if (!targetHref) return;
    e.preventDefault();
    spin.style.display   = 'block';
    btnTxt.textContent   = 'Memproses...';
    confirm.style.pointerEvents = 'none';
    setTimeout(function () {
      window.location.href = targetHref;
    }, 200);
  });
})();
</script>

<?php
function nm_redeem_desc(array $item): string
{
  $type  = $item['reward_type'] ?? '';
  $jenis = $item['jenis_voucher'] ?? '';

  if (in_array($type, ['PRODUCT', 'FREE_PRODUCT'])) {
    $nama = !empty($item['produk_nama']) ? $item['produk_nama'] : ($item['reward_notes'] ?: 'Produk gratis');
    return 'Gratis: ' . html_escape($nama);
  }

  if ($jenis === 'diskon') {
    if (($item['tipe_diskon'] ?? '') === 'persentase') {
      $s = 'Diskon ' . (int) ($item['nilai_voucher'] ?? 0) . '%';
      if (!empty($item['max_diskon'])) {
        $s .= ' (max Rp ' . number_format((int) $item['max_diskon'], 0, ',', '.') . ')';
      }
    } else {
      $s = 'Diskon Rp ' . number_format((int) ($item['nilai_voucher'] ?? 0), 0, ',', '.');
    }
    if (!empty($item['min_spend_amount'])) {
      $s .= ' · min. Rp ' . number_format((int) $item['min_spend_amount'], 0, ',', '.');
    }
    return $s;
  }

  if ($type === 'MERCHANDISE') {
    return html_escape($item['reward_notes'] ?? 'Merchandise');
  }

  if (!empty($item['deskripsi'])) {
    return html_escape($item['deskripsi']);
  }

  return '';
}

function nm_redeem_icon(array $item): string
{
  static $map = [
    'DISCOUNT_AMOUNT'  => '🏷️',
    'DISCOUNT_PERCENT' => '🏷️',
    'VOUCHER'          => '🎟️',
    'PRODUCT'          => '🛍️',
    'FREE_PRODUCT'     => '🛍️',
    'MERCHANDISE'      => '🎁',
    'OTHER'            => '✨',
  ];
  return $map[$item['reward_type'] ?? ''] ?? '🎁';
}
?>
