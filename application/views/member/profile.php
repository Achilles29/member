<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profil Saya - Namua Member</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Arial', sans-serif;
            background-color: #fff8f0;
            color: #4b2c20;
        }
        .header {
            background-color: #8b1c1c;
            color: #fff8f0;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header h2 {
            margin: 0;
            font-size: 18px;
        }
        .profile-container {
            padding: 20px;
        }
        .profile-box {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            text-align: center;
        }
        .profile-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #8b1c1c;
            margin-bottom: 15px;
        }
        .info-label {
            font-weight: bold;
            margin-top: 10px;
            color: #8b1c1c;
        }
        .info-item {
            margin-bottom: 8px;
        }
        .edit-btn {
            margin-top: 20px;
            background-color: #4b2c20;
            color: white;
        }
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #8b1c1c;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            color: #fff8f0;
            z-index: 99;
        }
        .bottom-nav a {
            text-decoration: none;
            text-align: center;
            color: #fff8f0;
            font-size: 12px;
        }
        .bottom-nav i {
            font-size: 18px;
            display: block;
        }
        .bottom-nav span {
            font-size: 11px;
            display: block;
        }
    </style>
</head>
<body>
<div class="profile-container">
    <div class="profile-box">
    <img src="<?= base_url('uploads/foto_pelanggan/' . $member['foto']) ?>" alt="Foto Profil" class="profile-photo">

        <div class="info-item">
            <div class="info-label"><i class="fas fa-id-badge"></i> Nama</div>
            <div><?= $member['nama'] ?></div>
        </div>

        <div class="info-item">
            <div class="info-label"><i class="fas fa-user-tag"></i> Kode Pelanggan</div>
            <div><?= $member['kode_pelanggan'] ?></div>
        </div>

        <div class="info-item">
            <div class="info-label"><i class="fas fa-venus-mars"></i> Jenis Kelamin</div>
            <div><?= $member['jenis_kelamin'] ?></div>
        </div>

        <div class="info-item">
            <div class="info-label"><i class="fas fa-birthday-cake"></i> Tanggal Lahir</div>
            <?= !empty($member['tanggal_lahir']) ? date('d M Y', strtotime($member['tanggal_lahir'])) : '<span class="text-muted">Belum diisi</span>' ?>

        </div>

        <div class="info-item">
            <div class="info-label"><i class="fas fa-map-marker-alt"></i> Alamat</div>
            <div><?= $member['alamat'] ?></div>
        </div>

        <div class="info-item">
            <div class="info-label"><i class="fas fa-phone"></i> Telepon</div>
            <div><?= $member['telepon'] ?></div>
        </div>

        <div class="info-item">
            <div class="info-label"><i class="fas fa-envelope"></i> Email</div>
            <div><?= $member['email'] ?></div>
        </div>

        <button class="btn edit-btn" data-bs-toggle="modal" data-bs-target="#editModal"><i class="fas fa-edit"></i> Edit Profil</button>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" action="<?= site_url('profile/update') ?>" method="post" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title">Edit Profil</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" value="<?= $member['id'] ?>">
        <div class="mb-2"><input type="text" name="nama" class="form-control" placeholder="Nama" value="<?= $member['nama'] ?>" required></div>
        <div class="mb-2"><input type="text" name="telepon" class="form-control" placeholder="Telepon" value="<?= $member['telepon'] ?>" required></div>
        <div class="mb-2"><input type="email" name="email" class="form-control" placeholder="Email" value="<?= $member['email'] ?>"></div>
        <div class="mb-2"><input type="text" name="alamat" class="form-control" placeholder="Alamat" value="<?= $member['alamat'] ?>"></div>
        <div class="mb-2"><input type="date" name="tanggal_lahir" class="form-control" value="<?= $member['tanggal_lahir'] ?>"></div>
        <div class="mb-2">
            <select name="jenis_kelamin" class="form-control">
                <option value="Laki-laki" <?= $member['jenis_kelamin']=='Laki-laki'?'selected':'' ?>>Laki-laki</option>
                <option value="Perempuan" <?= $member['jenis_kelamin']=='Perempuan'?'selected':'' ?>>Perempuan</option>
            </select>
        </div>
        <div class="mb-2">
            <label for="foto">Ganti Foto (Opsional)</label>
            <input type="file" name="foto" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Simpan</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
      </div>
    </form>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
