<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Member - Namua</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background: 
                linear-gradient(to bottom, rgba(139, 28, 28, 0.7), rgba(248, 241, 229, 0.95)),
                url('<?= base_url('uploads/kopi.jpg') ?>') no-repeat center center / cover;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .form-box {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px 25px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            width: 90%;
            max-width: 380px;
            text-align: center;
        }

        .form-box img.logo {
            width: 100px;
            margin-bottom: 10px;
        }

        .form-box h2 {
            margin-bottom: 20px;
            font-size: 20px;
            color: #8b1c1c;
        }

        input, select {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #8b1c1c;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .back-link {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }

        .error-message {
            color: red;
            font-size: 13px;
            margin-bottom: 10px;
        }

        @media (max-width: 480px) {
            .form-box {
                padding: 25px 20px;
            }

            .form-box h2 {
                font-size: 18px;
            }
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

    </style>
</head>
<body>

<div class="form-box">
    <h2 style="text-align:center; color:#8b1c1c;">Daftar Member</h2>
    <form method="post" action="<?= site_url('register/process') ?>">

        <div class="form-group">
            <label for="nama">Nama Lengkap</label>
            <input type="text" id="nama" name="nama" required>
        </div>

        <div class="form-group">
            <label for="telepon">Nomor HP</label>
            <input type="text" id="telepon" name="telepon" required>
        </div>

        <div class="form-group">
            <label for="alamat">Alamat</label>
            <input type="text" id="alamat" name="alamat">
        </div>

        <div class="form-group">
            <label for="jenis_kelamin">Jenis Kelamin</label>
            <select id="jenis_kelamin" name="jenis_kelamin">
                <option value="">Pilih Jenis Kelamin</option>
                <option value="Laki-laki">Laki-laki</option>
                <option value="Perempuan">Perempuan</option>
            </select>
        </div>

        <div class="form-group">
            <label for="tanggal_lahir">Tanggal Lahir</label>
            <input type="date" id="tanggal_lahir" name="tanggal_lahir">
        </div>

        <button type="submit">Daftar</button>
    </form>

    <div class="back-link">
            <a href="<?= site_url('login') ?>" style="color:#8b1c1c; font-weight: bold;">← Kembali ke Login</a>
        </div>

</div>

</body>
</html>
