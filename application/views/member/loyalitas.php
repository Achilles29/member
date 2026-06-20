<?php
// ─── POIN ────────────────────────────────────────────────────────────────────
$poin_aktif       = (int)($poin_aktif ?? 0);
$poin_digunakan   = (int)(($poin ?? [])['digunakan']         ?? 0);
$poin_kedaluwarsa = (int)(($poin ?? [])['kedaluwarsa']       ?? 0);
$poin_akan        = (int)(($poin ?? [])['akan_kedaluwarsa']  ?? 0);

// ─── STAMP ───────────────────────────────────────────────────────────────────
$stamp_col    = (int)($stamp_total_col ?? 0);
$stamp_tar    = (int)($stamp_total_tar ?? 0);
$stamp_pct    = ($stamp_tar > 0) ? min(100, round($stamp_col / $stamp_tar * 100)) : 0;
$stamp_dash   = round(2 * M_PI * 36);
$stamp_offset = $stamp_dash - round($stamp_pct / 100 * $stamp_dash);
$stamp_programs = count($stamp_list ?? []);

// ─── VOUCHER ─────────────────────────────────────────────────────────────────
$cnt_aktif      = count($voucher_aktif      ?? []);
$cnt_digunakan  = count($voucher_digunakan  ?? []);
$cnt_kadaluarsa = count($voucher_kadaluarsa ?? []);

// ─── HELPERS ─────────────────────────────────────────────────────────────────
function lty_ico(array $v): string {
    $j = $v['jenis_voucher'] ?? $v['jenis'] ?? '';
    return $j === 'produk' ? '🛍️' : ($j === 'diskon' ? '🏷️' : '🎁');
}
function lty_badge(array $v): string {
    $j = $v['jenis_voucher'] ?? $v['jenis'] ?? '';
    $t = $j === 'diskon' ? 'success' : 'neutral';
    $l = $j === 'produk' ? 'Produk' : ($j === 'diskon' ? 'Diskon' : 'Reward');
    return "<span class=\"nm-badge $t\">$l</span>";
}
?>
<div class="page-content nm-page">

  <!-- ═══ HERO ═══════════════════════════════════════════════════════════════ -->
  <div class="nm-page-hero nm-lty-hero lh-poin" id="ltyHero">
    <div class="nm-page-hero__nav">
      <a href="<?= site_url('member') ?>" class="nm-hero-back"><i class="f7-icons">chevron_left</i></a>
      <span class="nm-page-hero__label">Loyalty Saya</span>
      <a href="<?= site_url('member/logout') ?>" class="nm-logout"><i class="f7-icons">rectangle_porous_arrow_right</i></a>
    </div>

    <div class="nm-lty-stats">

      <!-- POIN card -->
      <div class="nm-lty-stat" data-target="poin">
        <div class="nm-lty-stat__ico"><i class="f7-icons">star_fill</i></div>
        <div class="nm-lty-stat__val"><?= number_format($poin_aktif) ?></div>
        <div class="nm-lty-stat__lbl">Poin</div>
        <div class="nm-lty-stat__sub">Level <?= html_escape($level ?? 'Silver') ?></div>
      </div>

      <!-- STAMP card -->
      <div class="nm-lty-stat" data-target="stamp">
        <div class="nm-lty-stat__ico"><i class="f7-icons">bookmark_fill</i></div>
        <div class="nm-lty-stat__val"><?= $stamp_col ?><span class="nm-lty-stat__frac">/<?= $stamp_tar ?: '0' ?></span></div>
        <div class="nm-lty-stat__lbl">Stamp</div>
        <div class="nm-lty-stat__sub"><?= $stamp_pct ?>% progress</div>
      </div>

      <!-- VOUCHER card -->
      <div class="nm-lty-stat" data-target="voucher">
        <div class="nm-lty-stat__ico"><i class="f7-icons">ticket_fill</i></div>
        <div class="nm-lty-stat__val"><?= $cnt_aktif ?></div>
        <div class="nm-lty-stat__lbl">Voucher</div>
        <div class="nm-lty-stat__sub">
          <?= $cnt_aktif > 0 ? 'siap dipakai' : 'belum ada' ?>
        </div>
      </div>

    </div><!-- /nm-lty-stats -->

    <?php if ($this->session->flashdata('success')): ?>
      <div class="nm-alert success" style="margin:0 16px 12px;">
        <?= html_escape($this->session->flashdata('success')) ?>
      </div>
    <?php elseif ($this->session->flashdata('error')): ?>
      <div class="nm-alert danger" style="margin:0 16px 12px;">
        <?= html_escape($this->session->flashdata('error')) ?>
      </div>
    <?php endif; ?>
  </div><!-- /hero -->

  <!-- ═══ PILL TABS ══════════════════════════════════════════════════════════ -->
  <div class="nm-pill-tabs" id="ltyPillTabs">
    <button class="nm-pill-tab" data-tab="poin" type="button">
      <i class="f7-icons">star_fill</i> Poin
    </button>
    <button class="nm-pill-tab" data-tab="stamp" type="button">
      <i class="f7-icons">bookmark_fill</i> Stamp
    </button>
    <button class="nm-pill-tab" data-tab="voucher" type="button">
      <i class="f7-icons">ticket_fill</i> Voucher
    </button>
    <div class="nm-pill-tabs__slider" id="ltySlider"></div>
  </div>

  <!-- ═══════════════════════════════════════════════════════════════════════
       PANEL: POIN
  ════════════════════════════════════════════════════════════════════════════ -->
  <?php
    // Label kontekstual untuk transaksi tanpa no_transaksi
    $poin_trx_labels = [
      'aktif'       => 'Poin Masuk',
      'digunakan'   => 'Pengunaan / Redeem',
      'kedaluwarsa' => 'Poin Kadaluarsa',
    ];
    // Apakah filter sudah diubah dari default?
    $filter_default = ($start === date('Y-m-01') && $end === date('Y-m-d') && $limit == 10);
  ?>
  <div id="panel-poin" class="nm-lty-panel">

    <!-- ── Stat Grid ───────────────────────────────────────────────────── -->
    <div class="nm-poin-statgrid">
      <div class="nm-poin-stat">
        <div class="nm-poin-stat__ico"><i class="f7-icons">cart_fill</i></div>
        <div class="nm-poin-stat__val"><?= number_format($poin_digunakan) ?></div>
        <div class="nm-poin-stat__lbl">Digunakan</div>
      </div>
      <div class="nm-poin-stat nm-poin-stat--amber">
        <div class="nm-poin-stat__ico"><i class="f7-icons">clock_fill</i></div>
        <div class="nm-poin-stat__val"><?= number_format($poin_akan) ?></div>
        <div class="nm-poin-stat__lbl">Akan Habis</div>
      </div>
      <div class="nm-poin-stat nm-poin-stat--red">
        <div class="nm-poin-stat__ico"><i class="f7-icons">xmark_circle_fill</i></div>
        <div class="nm-poin-stat__val"><?= number_format($poin_kedaluwarsa) ?></div>
        <div class="nm-poin-stat__lbl">Kadaluarsa</div>
      </div>
    </div>

    <!-- ── Filter Collapsible ─────────────────────────────────────────── -->
    <div class="nm-card nm-poin-filter">

      <!-- Summary bar (always visible) -->
      <div class="nm-poin-filter__bar" id="poinFilterBar">
        <div class="nm-poin-filter__info">
          <i class="f7-icons">calendar</i>
          <span class="nm-poin-filter__period">
            <?= date('d M Y', strtotime($start)) ?>
            <span class="nm-poin-filter__sep">–</span>
            <?= date('d M Y', strtotime($end)) ?>
          </span>
          <?php if ($limit !== 'semua'): ?>
            <span class="nm-poin-filter__limchip"><?= $limit ?> data</span>
          <?php else: ?>
            <span class="nm-poin-filter__limchip">Semua</span>
          <?php endif; ?>
          <?php if (!$filter_default): ?>
            <span class="nm-badge success" style="font-size:9px;padding:2px 6px;">Aktif</span>
          <?php endif; ?>
        </div>
        <i class="f7-icons nm-poin-filter__arrow" id="poinFilterArrow">chevron_down</i>
      </div>

      <!-- Expandable form -->
      <div class="nm-poin-filter__body" id="poinFilterBody">
        <form method="get" action="<?= site_url('loyalitas') ?>">
          <input type="hidden" name="tab" value="poin">
          <div class="nm-poin-filter__fields">
            <div class="nm-poin-filter__field">
              <label>Dari</label>
              <input type="date" name="start" value="<?= html_escape($start) ?>">
            </div>
            <div class="nm-poin-filter__field">
              <label>Sampai</label>
              <input type="date" name="end" value="<?= html_escape($end) ?>">
            </div>
            <div class="nm-poin-filter__field nm-poin-filter__field--sm">
              <label>Tampil</label>
              <select name="limit">
                <?php foreach ([10, 30, 50, 'semua'] as $lv): ?>
                  <option value="<?= $lv ?>" <?= ($limit == $lv) ? 'selected' : '' ?>><?= ucfirst($lv) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <button class="nm-poin-filter__submit" type="submit">
              <i class="f7-icons">arrow_right_circle_fill</i>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ── Section Header ─────────────────────────────────────────────── -->
    <div class="nm-poin-head">
      <div class="nm-poin-head__title">Riwayat Poin</div>
      <div class="nm-poin-head__meta">
        <?= (int)($total_rows ?? 0) ?> transaksi
      </div>
    </div>

    <!-- ── Timeline ───────────────────────────────────────────────────── -->
    <?php if (empty($riwayat)): ?>
      <div class="nm-empty-state nm-card">
        <div class="nm-empty-state__ico">⭐</div>
        <div class="nm-empty-state__txt">Belum ada riwayat poin di periode ini.</div>
      </div>
    <?php else: ?>
      <div class="nm-poin-timeline">
        <?php foreach ($riwayat as $r): ?>
          <?php
            $st   = strtolower($r['status'] ?? '');
            $pts  = (int)($r['jumlah_poin'] ?? 0);
            $sgn  = $st === 'aktif' ? '+' : '-';
            $trx  = trim($r['no_transaksi'] ?? '');
            $label = ($trx && $trx !== '-')
                      ? '#' . $trx
                      : ($poin_trx_labels[$st] ?? 'Transaksi Poin');
            $bdg_cls = $st === 'aktif' ? 'success' : ($st === 'digunakan' ? 'warning' : 'danger');
            $pts_cls = $st === 'aktif' ? 'poin-plus' : ($st === 'digunakan' ? 'poin-minus' : 'poin-exp');
            $dot_ico = $st === 'aktif' ? 'star_fill' : ($st === 'digunakan' ? 'cart_fill' : 'clock_badge_xmark_fill');
            $dot_cls = $st === 'aktif' ? 'nm-ptl-dot--plus' : ($st === 'digunakan' ? 'nm-ptl-dot--minus' : 'nm-ptl-dot--exp');
          ?>
          <div class="nm-ptl-row">
            <!-- dot -->
            <div class="nm-ptl-dot <?= $dot_cls ?>">
              <i class="f7-icons"><?= $dot_ico ?></i>
            </div>
            <!-- connector -->
            <div class="nm-ptl-line"></div>
            <!-- card -->
            <div class="nm-card nm-ptl-card">
              <div class="nm-ptl-card__top">
                <span class="nm-badge <?= $bdg_cls ?>"><?= ucfirst($st ?: '-') ?></span>
                <span class="nm-ptl-pts nm-ptl-pts--<?= $pts_cls ?>">
                  <?= $sgn . number_format($pts) ?>&nbsp;⭐
                </span>
              </div>
              <div class="nm-ptl-card__label"><?= html_escape($label) ?></div>
              <div class="nm-ptl-card__date">
                <i class="f7-icons">calendar</i>
                <?= date('d M Y', strtotime($r['created_at'])) ?>
                <span class="nm-ptl-card__time"><?= date('H:i', strtotime($r['created_at'])) ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($limit !== 'semua' && (int)($total_pages ?? 1) > 1): ?>
        <div class="nm-pagination nm-pagination-f7">
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a class="<?= $i == $page ? 'active' : '' ?>"
               href="<?= site_url('loyalitas') ?>?tab=poin&start=<?= urlencode($start) ?>&end=<?= urlencode($end) ?>&limit=<?= urlencode($limit) ?>&page=<?= $i ?>"><?= $i ?></a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>

  </div><!-- /panel-poin -->

  <!-- ═══════════════════════════════════════════════════════════════════════
       PANEL: STAMP
  ════════════════════════════════════════════════════════════════════════════ -->
  <div id="panel-stamp" class="nm-lty-panel">

    <!-- stamp summary card -->
    <div class="nm-card nm-lty-stamp-card">
      <div class="nm-lty-stamp-left">
        <div class="nm-lty-stamp-lbl">Total Stamp</div>
        <div class="nm-lty-stamp-val">
          <?= $stamp_col ?><span class="nm-lty-stamp-tar">/<?= $stamp_tar ?: '0' ?></span>
        </div>
        <div style="margin-top:12px;display:flex;gap:8px;">
          <div class="nm-phero-chip">
            <span class="nm-phero-chip__n"><?= $stamp_programs ?></span>
            <span class="nm-phero-chip__l">Program</span>
          </div>
          <div class="nm-phero-chip nm-phero-chip--amber">
            <span class="nm-phero-chip__n"><?= $stamp_pct ?>%</span>
            <span class="nm-phero-chip__l">Progress</span>
          </div>
        </div>
      </div>
      <!-- SVG ring -->
      <div class="nm-stamp-ring nm-lty-stamp-ring">
        <svg width="88" height="88" viewBox="0 0 88 88">
          <circle cx="44" cy="44" r="36" fill="none" stroke="rgba(139,28,28,.1)" stroke-width="7"/>
          <circle cx="44" cy="44" r="36" fill="none" stroke="var(--primary)" stroke-width="7"
            stroke-linecap="round"
            stroke-dasharray="<?= $stamp_dash ?>"
            stroke-dashoffset="<?= $stamp_offset ?>"
            transform="rotate(-90 44 44)"/>
        </svg>
        <div class="nm-stamp-ring__pct nm-lty-ring-pct">
          <b><?= $stamp_pct ?>%</b><small>total</small>
        </div>
      </div>
    </div>

    <!-- program cards -->
    <?php if (empty($stamp_list)): ?>
      <div class="nm-empty-state nm-card">
        <div class="nm-empty-state__ico">☕</div>
        <div class="nm-empty-state__txt">Belum ada stamp. Yuk mulai transaksi!</div>
      </div>
    <?php else: ?>
      <?php foreach ($stamp_list as $stamp): ?>
        <?php
          $sn   = html_escape($stamp['nama_promo'] ?? 'Promo');
          $sc   = (int)($stamp['jumlah_stamp'] ?? 0);
          $st   = (int)($stamp['total_stamp_target'] ?? 0);
          $sp   = ($st > 0) ? min(100, round($sc / $st * 100)) : 0;
          $done = ($st > 0 && $sc >= $st);
        ?>
        <div class="nm-card nm-stamp-prog-card">
          <div class="nm-stamp-prog-head">
            <div>
              <div class="nm-stamp-prog-name"><?= $sn ?></div>
              <div class="nm-stamp-prog-meta"><?= $sc ?>/<?= $st ?> stamp &bull; <?= $sp ?>% lengkap</div>
            </div>
            <?php if ($done): ?>
              <span class="nm-badge success">Selesai ✓</span>
            <?php else: ?>
              <span class="nm-badge neutral"><?= max(0, $st - $sc) ?> lagi</span>
            <?php endif; ?>
          </div>
          <div class="nm-prog-bar" style="margin:12px 0 14px;">
            <div class="nm-prog-bar__fill <?= $done ? 'nm-prog-bar__fill--done' : '' ?>" style="width:<?= $sp ?>%"></div>
          </div>
          <div class="nm-stamp-dots" role="list">
            <?php for ($i = 1; $i <= $st; $i++): ?>
              <?php $on = ($i <= $sc); ?>
              <div class="nm-sdot <?= $on ? 'nm-sdot--on' : 'nm-sdot--off' ?>" role="listitem">
                <?php if ($on): ?>
                  <img src="<?= base_url('uploads/logo.png') ?>" alt="stamp" loading="lazy">
                  <i class="f7-icons nm-sdot__chk">checkmark_circle_fill</i>
                <?php else: ?>
                  <span class="nm-sdot__num"><?= $i ?></span>
                <?php endif; ?>
              </div>
            <?php endfor; ?>
          </div>
          <?php if ($done): ?>
            <div class="nm-stamp-done-banner">🎉 Klaim hadiahmu di kasir!</div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </div><!-- /panel-stamp -->

  <!-- ═══════════════════════════════════════════════════════════════════════
       PANEL: VOUCHER
  ════════════════════════════════════════════════════════════════════════════ -->
  <div id="panel-voucher" class="nm-lty-panel">

    <!-- voucher sub-tabs -->
    <div class="nm-vtabs">
      <button class="nm-vtab" data-subtab="aktif" type="button">
        Aktif <span class="nm-vtab-badge"><?= $cnt_aktif ?></span>
      </button>
      <button class="nm-vtab" data-subtab="digunakan" type="button">
        Digunakan <span class="nm-vtab-badge"><?= $cnt_digunakan ?></span>
      </button>
      <button class="nm-vtab" data-subtab="kadaluarsa" type="button">
        Kadaluarsa <span class="nm-vtab-badge"><?= $cnt_kadaluarsa ?></span>
      </button>
    </div>

    <!-- sub-panel: aktif -->
    <div id="subtab-aktif" class="nm-subtab-panel">
      <?php if (!empty($voucher_aktif)): ?>
        <?php foreach ($voucher_aktif as $v): ?>
          <?php
            $kode  = $v['kode_voucher'] ?? '-';
            $mulai = !empty($v['tanggal_mulai'])    ? date('d M Y', strtotime($v['tanggal_mulai']))    : '-';
            $akhir = !empty($v['tanggal_berakhir']) ? date('d M Y', strtotime($v['tanggal_berakhir'])) : '-';
          ?>
          <div class="nm-ticket nm-ticket--active">
            <div class="nm-ticket__left">
              <span class="nm-badge success">Aktif</span>
              <div class="nm-ticket__lico"><?= lty_ico($v) ?></div>
              <?= lty_badge($v) ?>
            </div>
            <div class="nm-ticket__vline"></div>
            <div class="nm-ticket__body">
              <div class="nm-ticket__code"><?= html_escape($kode) ?></div>
              <div class="nm-ticket__desc"><?= $v['description'] ?? '-' ?></div>
              <div class="nm-ticket__date">
                <i class="f7-icons">calendar</i><?= $mulai ?> – <?= $akhir ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="nm-empty-state nm-card">
          <div class="nm-empty-state__ico">🎟</div>
          <div class="nm-empty-state__txt">Belum ada voucher aktif saat ini.</div>
        </div>
      <?php endif; ?>
    </div><!-- /subtab-aktif -->

    <!-- sub-panel: digunakan -->
    <div id="subtab-digunakan" class="nm-subtab-panel">
      <?php if (!empty($voucher_digunakan)): ?>
        <?php foreach ($voucher_digunakan as $v): ?>
          <?php
            $kode  = $v['kode_voucher'] ?? '-';
            $tgl_ts = $v['used_at'] ?? $v['updated_at'] ?? '';
            $tgl   = $tgl_ts ? date('d M Y', strtotime($tgl_ts)) : '-';
          ?>
          <div class="nm-ticket nm-ticket--used">
            <div class="nm-ticket__left nm-ticket__left--used">
              <span class="nm-badge warning">Dipakai</span>
              <div class="nm-ticket__lico" style="opacity:.4;filter:grayscale(1);"><?= lty_ico($v) ?></div>
              <?= lty_badge($v) ?>
            </div>
            <div class="nm-ticket__vline nm-ticket__vline--used"></div>
            <div class="nm-ticket__body">
              <div class="nm-ticket__code" style="opacity:.6;"><?= html_escape($kode) ?></div>
              <div class="nm-ticket__desc" style="opacity:.7;"><?= $v['description'] ?? '-' ?></div>
              <div class="nm-ticket__date nm-ticket__date--used">
                <i class="f7-icons">checkmark_circle</i>Digunakan <?= $tgl ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="nm-empty-state nm-card">
          <div class="nm-empty-state__ico">🛒</div>
          <div class="nm-empty-state__txt">Belum ada voucher yang digunakan.</div>
        </div>
      <?php endif; ?>
    </div><!-- /subtab-digunakan -->

    <!-- sub-panel: kadaluarsa -->
    <div id="subtab-kadaluarsa" class="nm-subtab-panel">
      <?php if (!empty($voucher_kadaluarsa)): ?>
        <?php foreach ($voucher_kadaluarsa as $v): ?>
          <?php
            $kode  = $v['kode_voucher'] ?? '-';
            $akhir = !empty($v['tanggal_berakhir']) ? date('d M Y', strtotime($v['tanggal_berakhir'])) : '-';
          ?>
          <div class="nm-ticket nm-ticket--expired">
            <div class="nm-ticket__left nm-ticket__left--expired">
              <span class="nm-badge danger">Kadaluarsa</span>
              <div class="nm-ticket__lico" style="opacity:.3;filter:grayscale(1);">🎟</div>
            </div>
            <div class="nm-ticket__vline nm-ticket__vline--expired"></div>
            <div class="nm-ticket__body">
              <div class="nm-ticket__code" style="opacity:.55;"><?= html_escape($kode) ?></div>
              <div class="nm-ticket__desc" style="opacity:.65;"><?= $v['description'] ?? '-' ?></div>
              <div class="nm-ticket__date nm-ticket__date--exp">
                <i class="f7-icons">clock</i>Berakhir <?= $akhir ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="nm-empty-state nm-card">
          <div class="nm-empty-state__ico">⌛</div>
          <div class="nm-empty-state__txt">Tidak ada voucher kadaluarsa.</div>
        </div>
      <?php endif; ?>
    </div><!-- /subtab-kadaluarsa -->

  </div><!-- /panel-voucher -->

</div><!-- /nm-page -->
<?php $this->load->view('templates/member/bottom_nav'); ?>

<script>
(function () {
  'use strict';

  var hero      = document.getElementById('ltyHero');
  var pills     = Array.from(document.querySelectorAll('.nm-pill-tab'));
  var panels    = Array.from(document.querySelectorAll('.nm-lty-panel'));
  var lstats    = Array.from(document.querySelectorAll('.nm-lty-stat'));
  var slider    = document.getElementById('ltySlider');
  var subtabBtns = Array.from(document.querySelectorAll('#panel-voucher .nm-vtab[data-subtab]'));
  var subpanels = Array.from(document.querySelectorAll('.nm-subtab-panel'));

  // ── TAB SWITCH ────────────────────────────────────────────────────────────
  function switchTab(tab) {
    pills.forEach(function(p)  { p.classList.toggle('is-active', p.dataset.tab === tab); });
    panels.forEach(function(p) { p.classList.toggle('is-show', p.id === 'panel-' + tab); });
    lstats.forEach(function(s) { s.classList.toggle('is-active', s.dataset.target === tab); });

    // hero theme
    hero.classList.remove('lh-poin', 'lh-stamp', 'lh-voucher');
    hero.classList.add('lh-' + tab);

    // sliding pill indicator
    var activeBtn = document.querySelector('.nm-pill-tab[data-tab="' + tab + '"]');
    if (activeBtn && slider) {
      slider.style.width = activeBtn.offsetWidth + 'px';
      slider.style.left  = activeBtn.offsetLeft  + 'px';
    }

    // persist in URL without reload
    try {
      var u = new URL(window.location.href);
      u.searchParams.set('tab', tab);
      if (tab !== 'voucher') u.searchParams.delete('subtab');
      history.replaceState(null, '', u.toString());
    } catch(e) {}
  }

  // ── VOUCHER SUB-TAB SWITCH ───────────────────────────────────────────────
  function switchSubtab(sub) {
    subtabBtns.forEach(function(b) { b.classList.toggle('is-active', b.dataset.subtab === sub); });
    subpanels.forEach(function(p)  { p.classList.toggle('is-show', p.id === 'subtab-' + sub); });
    try {
      var u = new URL(window.location.href);
      u.searchParams.set('subtab', sub);
      history.replaceState(null, '', u.toString());
    } catch(e) {}
  }

  // ── EVENTS ───────────────────────────────────────────────────────────────
  pills.forEach(function(btn) {
    btn.addEventListener('click', function() { switchTab(btn.dataset.tab); });
  });

  lstats.forEach(function(card) {
    card.addEventListener('click', function() { switchTab(card.dataset.target); });
  });

  subtabBtns.forEach(function(btn) {
    btn.addEventListener('click', function() { switchSubtab(btn.dataset.subtab); });
  });

  // ── FILTER TOGGLE ────────────────────────────────────────────────────────
  var filterBar   = document.getElementById('poinFilterBar');
  var filterBody  = document.getElementById('poinFilterBody');
  var filterArrow = document.getElementById('poinFilterArrow');

  if (filterBar && filterBody) {
    filterBar.addEventListener('click', function () {
      var open = filterBody.classList.toggle('is-open');
      if (filterArrow) filterArrow.classList.toggle('is-open', open);
    });
    // Auto-open jika filter sudah dimodifikasi dari default
    var p = new URLSearchParams(window.location.search);
    if (p.get('start') || p.get('end') || (p.get('limit') && p.get('limit') !== '10')) {
      filterBody.classList.add('is-open');
      if (filterArrow) filterArrow.classList.add('is-open');
    }
  }

  // ── INIT ─────────────────────────────────────────────────────────────────
  setTimeout(function() {
    var params  = new URLSearchParams(window.location.search);
    var initTab = params.get('tab')    || 'poin';
    var initSub = params.get('subtab') || 'aktif';
    switchTab(initTab);
    switchSubtab(initSub);
  }, 30);

})();
</script>
