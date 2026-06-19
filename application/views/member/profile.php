<?php
$foto     = $member['foto'] ?? '';
$foto_url = !empty($foto) ? base_url('uploads/foto_pelanggan/' . $foto) : base_url('uploads/logo.png');
$level    = $member['level'] ?? 'Silver';
$lvl_cls  = 'nm-level-badge--' . strtolower($level);
?>
<div class="page-content nm-page">

  <!-- HERO PROFILE -->
  <div class="nm-page-hero nm-page-hero--profile">
    <div class="nm-page-hero__nav">
      <a href="<?= site_url('member') ?>" class="nm-hero-back"><i class="f7-icons">chevron_left</i></a>
      <span class="nm-page-hero__label">Akun Saya</span>
      <a href="<?= site_url('member/logout') ?>" class="nm-logout"><i class="f7-icons">rectangle_porous_arrow_right</i></a>
    </div>
    <div class="nm-profile-hero">
      <div class="nm-profile-avatar-ring">
        <img class="nm-profile-avatar-img" src="<?= $foto_url ?>" alt="Foto Profil" loading="lazy">
      </div>
      <div class="nm-profile-hero__name"><?= html_escape($member['nama'] ?? '-') ?></div>
      <div class="nm-profile-hero__meta">
        <span class="nm-level-badge <?= $lvl_cls ?>"><?= html_escape($level) ?></span>
        <span style="opacity:.7;font-size:12px;margin-left:8px;"><?= html_escape($member['kode_pelanggan'] ?? '-') ?></span>
      </div>
      <button type="button" class="nm-profile-editbtn-hero" id="btnToggleEdit">
        <i class="f7-icons">pencil</i> Edit Profil
      </button>
    </div>
  </div>

  <!-- INFO CARD -->
  <div class="nm-card nm-profile-info">
    <?php
      $fields = [
        ['f7-icons', 'person',   'Jenis Kelamin', $member['jenis_kelamin'] ?? null],
        ['f7-icons', 'calendar', 'Tanggal Lahir',  !empty($member['tanggal_lahir']) ? date('d M Y', strtotime($member['tanggal_lahir'])) : null],
        ['f7-icons', 'phone',    'Telepon',        $member['telepon'] ?? null],
        ['f7-icons', 'envelope', 'Email',          $member['email'] ?? null],
        ['f7-icons', 'map',      'Alamat',         $member['alamat'] ?? null],
      ];
    ?>
    <?php foreach ($fields as [$itype, $icon, $label, $val]): ?>
      <div class="nm-profile-row">
        <div class="nm-profile-row__ico"><i class="f7-icons"><?= $icon ?></i></div>
        <div class="nm-profile-row__body">
          <div class="nm-profile-row__lbl"><?= $label ?></div>
          <div class="nm-profile-row__val <?= empty($val) ? 'nm-profile-row__val--empty' : '' ?>">
            <?= !empty($val) ? html_escape($val) : 'Belum diisi' ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- EDIT FORM -->
  <div class="nm-card nm-profile-edit-card" id="editBox" style="display:none;">
    <div class="nm-profile-edit-head">
      <div class="nm-section-title">Edit Profil</div>
      <button type="button" class="nm-iconbtn" id="btnCloseEdit"><i class="f7-icons">xmark</i></button>
    </div>
    <form action="<?= site_url('profile/update') ?>" method="post" enctype="multipart/form-data" class="nm-form">
      <input type="hidden" name="id" value="<?= (int)($member['id'] ?? 0) ?>">
      <div class="nm-form-grid">
        <div class="nm-field"><label>Nama</label><input type="text" name="nama" value="<?= html_escape($member['nama'] ?? '') ?>" required></div>
        <div class="nm-field"><label>Telepon</label><input type="text" name="telepon" value="<?= html_escape($member['telepon'] ?? '') ?>" required></div>
        <div class="nm-field"><label>Email</label><input type="email" name="email" value="<?= html_escape($member['email'] ?? '') ?>"></div>
        <div class="nm-field">
          <label>Jenis Kelamin</label>
          <select name="jenis_kelamin">
            <option value="Laki-laki" <?= ($member['jenis_kelamin'] ?? '') === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
            <option value="Perempuan" <?= ($member['jenis_kelamin'] ?? '') === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
          </select>
        </div>
        <div class="nm-field"><label>Tanggal Lahir</label><input type="date" name="tanggal_lahir" value="<?= html_escape($member['tanggal_lahir'] ?? '') ?>"></div>
        <div class="nm-field" style="grid-column:1/-1;"><label>Alamat</label><input type="text" name="alamat" value="<?= html_escape($member['alamat'] ?? '') ?>"></div>
        <div class="nm-field" style="grid-column:1/-1;"><label>Ganti Foto</label><input type="file" name="foto" accept="image/png,image/jpeg"><div class="nm-help">JPG/PNG, max 12MB</div></div>
      </div>
      <div class="nm-form-actions">
        <button type="submit" class="nm-btn nm-btn--primary nm-btn--block">Simpan Perubahan</button>
        <button type="button" class="nm-btn nm-btn--ghost nm-btn--block" id="btnCancelEdit" style="margin-top:8px;">Batal</button>
      </div>
    </form>
  </div>

</div>
<?php $this->load->view('templates/member/bottom_nav'); ?>
<script>
(function(){
  var box=document.getElementById('editBox');
  function open(){box.style.display='block';box.scrollIntoView({behavior:'smooth',block:'start'});}
  function close(){box.style.display='none';}
  document.getElementById('btnToggleEdit')?.addEventListener('click',open);
  document.getElementById('btnCloseEdit')?.addEventListener('click',close);
  document.getElementById('btnCancelEdit')?.addEventListener('click',close);
})();
</script>
