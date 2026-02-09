<div class="page-content nm-page">

  <!-- TOPBAR MINI -->
  <div class="nm-topbar nm-topbar--mini">
    <div>
      <div class="nm-name"><?= html_escape($member['nama'] ?? 'Guest') ?></div>
      <div class="nm-level">Akun Saya</div>
    </div>

    <a class="nm-logout" href="<?= site_url('member/logout') ?>" title="Logout">
      <i class="f7-icons">rectangle_porous_arrow_right</i>
    </a>
  </div>

  <?php
    $foto = $member['foto'] ?? '';
    $foto_url = !empty($foto)
      ? base_url('uploads/foto_pelanggan/' . $foto)
      : base_url('uploads/logo.png'); // fallback
  ?>

  <!-- PROFILE CARD -->
  <div class="nm-card nm-profile-card">
    <div class="nm-profile-head">
      <div class="nm-avatar">
        <img src="<?= $foto_url ?>" alt="Foto Profil" loading="lazy">
      </div>

      <div class="nm-profile-ident">
        <div class="nm-profile-name"><?= html_escape($member['nama'] ?? '-') ?></div>
        <div class="nm-profile-sub">
          <span class="nm-badge neutral">Kode: <?= html_escape($member['kode_pelanggan'] ?? '-') ?></span>
          <?php if (!empty($member['level'])): ?>
            <span class="nm-badge success">Level <?= html_escape($member['level']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <button type="button" class="nm-profile-editbtn" id="btnToggleEdit">
        <i class="f7-icons">pencil</i>
      </button>
    </div>

    <div class="nm-profile-grid">
      <div class="nm-profile-item">
        <div class="nm-profile-label"><i class="f7-icons">person</i> Jenis Kelamin</div>
        <div class="nm-profile-value"><?= html_escape($member['jenis_kelamin'] ?? '-') ?></div>
      </div>

      <div class="nm-profile-item">
        <div class="nm-profile-label"><i class="f7-icons">calendar</i> Tanggal Lahir</div>
        <div class="nm-profile-value">
          <?php if (!empty($member['tanggal_lahir'])): ?>
            <?= date('d M Y', strtotime($member['tanggal_lahir'])) ?>
          <?php else: ?>
            <span class="nm-muted">Belum diisi</span>
          <?php endif; ?>
        </div>
      </div>

      <div class="nm-profile-item">
        <div class="nm-profile-label"><i class="f7-icons">map</i> Alamat</div>
        <div class="nm-profile-value"><?= !empty($member['alamat']) ? html_escape($member['alamat']) : '<span class="nm-muted">Belum diisi</span>' ?></div>
      </div>

      <div class="nm-profile-item">
        <div class="nm-profile-label"><i class="f7-icons">phone</i> Telepon</div>
        <div class="nm-profile-value"><?= !empty($member['telepon']) ? html_escape($member['telepon']) : '<span class="nm-muted">Belum diisi</span>' ?></div>
      </div>

      <div class="nm-profile-item">
        <div class="nm-profile-label"><i class="f7-icons">envelope</i> Email</div>
        <div class="nm-profile-value"><?= !empty($member['email']) ? html_escape($member['email']) : '<span class="nm-muted">Belum diisi</span>' ?></div>
      </div>
    </div>
  </div>

  <!-- EDIT FORM (inline drawer) -->
  <div class="nm-card nm-profile-edit" id="editBox" style="display:none;">
    <div class="nm-profile-edit-title">
      <div>
        <div class="nm-section-title">Edit Profil</div>
        <div class="nm-section-sub">Perbarui data akun kamu</div>
      </div>
      <button type="button" class="nm-profile-close" id="btnCloseEdit">
        <i class="f7-icons">xmark</i>
      </button>
    </div>

    <form action="<?= site_url('profile/update') ?>" method="post" enctype="multipart/form-data" class="nm-form">
      <input type="hidden" name="id" value="<?= (int)($member['id'] ?? 0) ?>">

      <div class="nm-form-grid">
        <div class="nm-field">
          <label>Nama</label>
          <input type="text" name="nama" value="<?= html_escape($member['nama'] ?? '') ?>" required>
        </div>

        <div class="nm-field">
          <label>Telepon</label>
          <input type="text" name="telepon" value="<?= html_escape($member['telepon'] ?? '') ?>" required>
        </div>

        <div class="nm-field">
          <label>Email</label>
          <input type="email" name="email" value="<?= html_escape($member['email'] ?? '') ?>">
        </div>

        <div class="nm-field">
          <label>Jenis Kelamin</label>
          <select name="jenis_kelamin">
            <option value="Laki-laki" <?= ($member['jenis_kelamin'] ?? '') === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
            <option value="Perempuan" <?= ($member['jenis_kelamin'] ?? '') === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
          </select>
        </div>

        <div class="nm-field">
          <label>Tanggal Lahir</label>
          <input type="date" name="tanggal_lahir" value="<?= html_escape($member['tanggal_lahir'] ?? '') ?>">
        </div>

        <div class="nm-field" style="grid-column:1/-1;">
          <label>Alamat</label>
          <input type="text" name="alamat" value="<?= html_escape($member['alamat'] ?? '') ?>">
        </div>

        <div class="nm-field" style="grid-column:1/-1;">
          <label>Ganti Foto (opsional)</label>
          <input type="file" name="foto" accept="image/png,image/jpeg">
          <div class="nm-help">Format: JPG/PNG, max 12MB</div>
        </div>
      </div>

      <div class="nm-form-actions">
        <button type="submit" class="nm-btn-primary">Simpan</button>
        <button type="button" class="nm-btn-ghost" id="btnCancelEdit">Batal</button>
      </div>
    </form>
  </div>

</div>

<?php $this->load->view('templates/member/bottom_nav'); ?>

<script>
  const editBox = document.getElementById('editBox');
  const btnToggle = document.getElementById('btnToggleEdit');
  const btnClose = document.getElementById('btnCloseEdit');
  const btnCancel = document.getElementById('btnCancelEdit');

  function openEdit(){
    editBox.style.display = 'block';
    editBox.scrollIntoView({behavior:'smooth', block:'start'});
  }
  function closeEdit(){
    editBox.style.display = 'none';
  }

  btnToggle?.addEventListener('click', openEdit);
  btnClose?.addEventListener('click', closeEdit);
  btnCancel?.addEventListener('click', closeEdit);
</script>
