<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Login Member</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

  <style>
    :root{
      --primary:#8b1c1c;
      --primary2:#b12a2a;
      --bg:#f4f6f8;
      --card:#ffffff;
      --text:#111827;
      --muted:#6b7280;
      --shadow:0 12px 28px rgba(0,0,0,.10);
      --radius:18px;
    }

    html,body{height:100%;}
    body{
      margin:0;
      font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;
      background:linear-gradient(135deg, rgba(139,28,28,.20), rgba(244,246,248,1) 55%, rgba(177,42,42,.10));
      display:flex;
      justify-content:center;
      align-items:center;
      padding:18px;
      color:var(--text);
    }

    /* Frame seperti app */
    .nm-auth{
      width:100%;
      max-width:420px;
      background:transparent;
    }

    .nm-auth-card{
      background:var(--card);
      border-radius:24px;
      box-shadow:var(--shadow);
      overflow:hidden;
    }

    .nm-auth-hero{
      padding:18px 16px 16px;
      background:linear-gradient(135deg,var(--primary),var(--primary2));
      color:#fff;
      position:relative;
    }

    .nm-brand{
      display:flex;
      align-items:center;
      gap:10px;
    }

    .nm-logo{
      width:42px;height:42px;
      border-radius:16px;
      background:rgba(255,255,255,.18);
      display:flex;
      align-items:center;
      justify-content:center;
      font-weight:1000;
      letter-spacing:.4px;
    }

    .nm-auth-title{
      font-size:16px;
      font-weight:1000;
      margin:0;
      line-height:1.2;
    }
    .nm-auth-sub{
      margin-top:6px;
      font-size:12px;
      opacity:.92;
      font-weight:700;
    }

    .nm-auth-body{
      padding:16px;
    }

    .nm-alert{
      border-radius:16px;
      padding:10px 12px;
      font-size:12px;
      font-weight:900;
      margin-bottom:12px;
      background:rgba(239,68,68,.10);
      color:#b91c1c;
      border:1px solid rgba(239,68,68,.18);
    }

    .nm-field{
      display:flex;
      flex-direction:column;
      gap:6px;
      margin-bottom:12px;
    }
    .nm-field label{
      font-size:12px;
      color:var(--muted);
      font-weight:900;
    }
    .nm-field input{
      height:46px;
      border-radius:16px;
      border:1px solid #e5e7eb;
      padding:0 12px;
      font-size:14px;
      outline:none;
      background:#fff;
    }
    .nm-field input:focus{
      border-color:rgba(139,28,28,.35);
      box-shadow:0 0 0 4px rgba(139,28,28,.10);
    }

    .nm-btn{
      width:100%;
      height:46px;
      border:none;
      border-radius:16px;
      background:var(--primary);
      color:#fff;
      font-weight:1000;
      font-size:14px;
      cursor:pointer;
      box-shadow:0 12px 20px rgba(139,28,28,.18);
      display:flex;
      align-items:center;
      justify-content:center;
      gap:10px;
    }
    .nm-btn:active{ transform:scale(.99); }

    .nm-meta{
      margin-top:12px;
      display:flex;
      justify-content:center;
      gap:6px;
      font-size:12px;
      color:var(--muted);
      font-weight:800;
    }

    .nm-link{
      color:var(--primary);
      text-decoration:none;
      font-weight:1000;
    }

    .nm-note{
      margin-top:10px;
      text-align:center;
      font-size:11px;
      color:var(--muted);
      font-weight:800;
      line-height:1.4;
    }
  </style>
</head>

<body>
  <div class="nm-auth">
    <div class="nm-auth-card">
      <div class="nm-auth-hero">
        <div class="nm-brand">
          <div class="nm-logo">N</div>
          <div>
            <h1 class="nm-auth-title">Namua Member</h1>
            <div class="nm-auth-sub">Masuk pakai nomor HP kamu</div>
          </div>
        </div>
      </div>

      <div class="nm-auth-body">

        <?php if ($this->session->flashdata('error')): ?>
          <div class="nm-alert"><?= html_escape($this->session->flashdata('error')) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= site_url('card/do_login') ?>">
          <div class="nm-field">
            <label for="phone">Nomor HP</label>
            <input
              id="phone"
              type="tel"
              name="phone"
              inputmode="numeric"
              autocomplete="tel"
              placeholder="contoh: 08xxxxxxxxxx"
              required
            >
          </div>

          <button type="submit" class="nm-btn">
            Masuk
          </button>
        </form>

        <div class="nm-meta">
          <span>Belum punya akun?</span>
          <a class="nm-link" href="<?= site_url('card/register') ?>">Daftar sekarang</a>
        </div>

        <div class="nm-note">
          Dengan masuk, kamu bisa cek poin, stamp, voucher, dan redeem hadiah.
        </div>

      </div>
    </div>
  </div>

  <script>
    // auto focus & rapikan input (opsional)
    const phone = document.getElementById('phone');
    if (phone) {
      phone.focus();
      phone.addEventListener('input', () => {
        // hanya angka + awalan 0 (hapus spasi/dll)
        phone.value = phone.value.replace(/[^\d]/g, '');
      });
    }
  </script>
</body>
</html>
