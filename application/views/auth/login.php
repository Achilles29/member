<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Member - Namua</title>
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
                linear-gradient(to bottom, rgba(139, 28, 28, 0.7), rgba(248, 241, 229, 0.9)),
                url('<?= base_url('uploads/kopi.jpg') ?>') no-repeat center center / cover;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px 25px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            width: 90%;
            max-width: 350px;
            text-align: center;
        }

        .login-container img.logo {
            width: 100px;
            margin-bottom: 15px;
        }

        .login-container h2 {
            margin-bottom: 20px;
            font-size: 20px;
            color: #8b1c1c;
        }

        .login-container input[type="text"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
        }

        .login-container button {
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

        .error-message {
            color: red;
            font-size: 13px;
            margin-bottom: 10px;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 25px 20px;
            }

            .login-container h2 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>

    <div class="login-container">
        <img src="<?= base_url('uploads/logo.png') ?>" alt="Namua Logo" class="logo">
        <h2>Login Member</h2>

        <?php if ($this->session->flashdata('error')): ?>
            <div class="error-message"><?= $this->session->flashdata('error'); ?></div>
        <?php endif; ?>

        <form method="post" action="<?= site_url('login/do_login') ?>">
            <input type="text" name="telepon" placeholder="Masukkan Nomor HP" required>
            <button type="submit">Login</button>
        </form>

        <p style="margin-top: 15px; font-size: 14px;">
            Belum punya akun? <a href="<?= site_url('register') ?>" style="color: #8b1c1c; font-weight: bold;">Daftar di sini</a>
        </p>
    </div>

</body>
</html>
