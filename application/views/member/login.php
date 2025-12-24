<!-- login_view.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Login Member</title>
    <style>
        body {
            margin: 0; padding: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(to bottom, #800000, #f8f1e5);
            height: 100vh; display: flex; justify-content: center; align-items: center;
        }
        .container {
            background: #fff; padding: 30px; border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2); width: 300px;
            text-align: center;
        }
        .container h2 { margin-bottom: 20px; color: #800000; }
        input {
            width: 100%; padding: 10px; margin-bottom: 15px;
            border: 1px solid #ccc; border-radius: 5px;
        }
        button {
            width: 100%; padding: 10px;
            background: #800000; color: #fff; border: none;
            border-radius: 5px; cursor: pointer;
        }
        .error { color: red; margin-bottom: 10px; }
        .register-link {
            margin-top: 10px;
            display: block;
            color: #800000;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Login Member</h2>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="error"><?= $this->session->flashdata('error'); ?></div>
    <?php endif; ?>
    <form method="post" action="<?= site_url('card/do_login') ?>">
        <input type="text" name="phone" placeholder="Nomor HP" required>
        <button type="submit">Login</button>
    </form>
    <a class="register-link" href="<?= site_url('card/register') ?>">Belum punya akun? Daftar sekarang</a>
</div>
</body>
</html>
