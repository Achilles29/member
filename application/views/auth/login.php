<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login Member - Namua</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

  <style>
    :root{
      --primary:#8b1c1c;
      --primary2:#b12a2a;
      --text:#111827;
      --muted:#6b7280;
      --card:#ffffff;
      --shadow:0 18px 40px rgba(0,0,0,.18);
      --radius:18px;
    }

    *{box-sizing:border-box}

    html,body{
      height:100%;
    }

    body{
      margin:0;
      font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;
      background:
        linear-gradient(
          135deg,
          rgba(139,28,28,.75),
          rgba(248,241,229,.92)
        ),
        url('<?= base_url('uploads/kopi.jpg') ?>') no-repeat center / cover;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:18px;
      color:var(--text);
    }

    /* Frame biar kayak app */
    .nm-auth{
      width:100%;
      max-width:420px;
    }

    .nm-card{
      background:rgba(255,255,255,.96);
      border-radius:24px;
      box-shadow:var(--shadow);
      padding:26px 22px;
      text-align:center;
      backdrop-filter: blur(2px);
    }

    .nm-logo{
      width:96px;
      height:96px;
      border-radius:22px;
      margin:0 auto 14px;
      background:#fff;
      display:flex;
      align-items:center;
      justify-content:center;
      box-shadow:0 10px 22px rgba(0,0,0,.12);
      overflow:hidden;
    }
    .nm-logo img{
      width:100%;
      height:100%;
      object-fit:contain;
    }

    .nm-title{
      font-size:20px;
      font-weight:1000;
      color:var(--primary);
      margin:0;
    }

    .nm-sub{
      margin-top:6px;
      font-size:13px;
      color:var(--muted);
      font-weight:800;
    }

    .nm-error{
      margin:14px 0;
      padding:10px 12px;
      border-radius:14px;
      background:rgba(239,68,68,.12);
      color:#b91c1c;
      font-size:13px;
      font-weight:900;
    }

    .nm-field{
      margin-top:16px;
      display:flex;
      flex-direction:column;
      gap:6px;
      text-align:left;
    }
    .nm-field label{
      font-size:12px;
      font-weight:900;
      color:var(--muted);
    }
    .nm-field input{
      height:48px;
      border-radius:16px;
      border:1px solid #e5e7eb;
      padding:0 14px;
      font-size:14px;
      outline:none;
      background:#fff;
    }
    .nm-field input:focus{
      border-color:rgba(139,28,28,.35);
      box-shadow:0 0 0 4px rgba(139,28,28,.12);
    }

    .nm-btn{
      margin-top:18px;
      width:100%;
      height:48px;
      border:none;
      border-radius:16px;
      background:linear-gradient(135deg,var(--primary),var(--primary2));
      color:#fff;
      font-weight:1000;
      font-size:15px;
      cursor:pointer;
      box-shadow:0 14px 24px rgba(139,28,28,.28);
    }
    .nm-btn:active{transform:scale(.99)}

    .nm-footer{
      margin-top:16px;
      font-size:13px;
      font-weight:800;
      color:var(--muted);
    }
    .nm-footer a{
      color:var(--primary);
      font-weight:1000;
      text-decoration:none;
    }

    @media (max-width:420px){
      .nm-card{padding:24px 18px}
      .nm-title{font-size:18px}
    }
  </style>
</head>

<body>

  <div class="nm-auth">
    <div class="nm-card">

      <div class="nm-logo">
        <img src="<?= base_url('uploads/logo.png') ?>" alt="Namua Logo">
      </div>

      <h1 class="nm-title">Login Member</h1>
      <div class="nm-sub">Masuk menggunakan nomor HP kamu</div>

      <?php if ($this->session->flashdata('error')): ?>
        <div class="nm-error">
          <?= html_escape($this->session->flashdata('error')) ?>
        </div>
      <?php endif; ?>

      <form method="post" action="<?= site_url('login/do_login') ?>">
        <div class="nm-field">
          <label for="telepon">Nomor HP</label>
          <input
            id="telepon"
            type="tel"
            name="telepon"
            placeholder="08xxxxxxxxxx"
            inputmode="numeric"
            autocomplete="tel"
            required
          >
        </div>

        <button type="submit" class="nm-btn">
          Login
        </button>
      </form>

      <div class="nm-footer">
        Belum punya akun?
        <a href="<?= site_url('register') ?>">Daftar di sini</a>
      </div>

    </div>
  </div>

  <script>
    // rapikan input HP (angka saja)
    const tel = document.getElementById('telepon');
    if (tel) {
      tel.focus();
      tel.addEventListener('input', () => {
        tel.value = tel.value.replace(/[^\d]/g,'');
      });
    }
  </script>

</body>
</html>
