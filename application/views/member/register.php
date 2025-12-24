<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Member</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #6d4c41, #a6825e);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: white;
        }

        .register-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            width: 400px;
            padding: 30px;
            text-align: center;
            color: #6d4c41;
        }

        .register-container h1 {
            margin: 0 0 20px;
            font-size: 24px;
        }

        .register-container p {
            font-size: 14px;
            color: #a6825e;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 5px;
            outline: none;
        }

        .form-group input:focus {
            border-color: #a6825e;
        }

        .register-button {
            width: 100%;
            padding: 10px;
            background: #6d4c41;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .register-button:hover {
            background: #a6825e;
        }

        .footer-text {
            margin-top: 20px;
            font-size: 12px;
            color: #a6825e;
        }

        .footer-text a {
            color: #6d4c41;
            text-decoration: none;
        }

        .footer-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h1>Daftar Member</h1>
        <p>Silakan daftar untuk membuat kartu digital Anda</p>
        <form method="post" action="<?php echo site_url('member/process_register'); ?>">
            <div class="form-group">
                <label for="name">Nama</label>
                <input type="text" id="name" name="name" placeholder="Masukkan nama Anda" required>
            </div>
            <div class="form-group">
                <label for="phone">Nomor HP</label>
                <input type="text" id="phone" name="phone" placeholder="Masukkan nomor HP Anda" required>
            </div>
            <button type="submit" class="register-button">Daftar Sekarang</button>
        </form>
        <div class="footer-text">
            Sudah memiliki akun? <a href="login.php">Login di sini</a>
        </div>
    </div>
</body>
</html>


