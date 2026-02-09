<div class="toolbar tabbar tabbar-labels toolbar-bottom nm-tabbar">
  <div class="toolbar-inner">

    <a href="<?= site_url('member') ?>" class="tab-link <?= ($active_menu ?? '') === 'home' ? 'tab-link-active' : '' ?>">
      <i class="f7-icons">house</i>
      <span class="tabbar-label">Home</span>
    </a>

    <a href="<?= site_url('poin') ?>" class="tab-link <?= ($active_menu ?? '') === 'poin' ? 'tab-link-active' : '' ?>">
      <i class="f7-icons">star</i>
      <span class="tabbar-label">Poin</span>
    </a>

    <a href="<?= site_url('stamp') ?>" class="tab-link <?= ($active_menu ?? '') === 'stamp' ? 'tab-link-active' : '' ?>">
      <i class="f7-icons">bookmark</i>
      <span class="tabbar-label">Stamp</span>
    </a>

    <a href="<?= site_url('voucher') ?>" class="tab-link <?= ($active_menu ?? '') === 'voucher' ? 'tab-link-active' : '' ?>">
      <i class="f7-icons">ticket</i>
      <span class="tabbar-label">Voucher</span>
    </a>

    <a href="<?= site_url('redeem') ?>" class="tab-link <?= ($active_menu ?? '') === 'redeem' ? 'tab-link-active' : '' ?>">
      <i class="f7-icons">gift</i>
      <span class="tabbar-label">Redeem</span>
    </a>

    <a href="<?= site_url('profile') ?>" class="tab-link <?= ($active_menu ?? '') === 'akun' ? 'tab-link-active' : '' ?>">
      <i class="f7-icons">person</i>
      <span class="tabbar-label">Akun</span>
    </a>

  </div>
</div>
