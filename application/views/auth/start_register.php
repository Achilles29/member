<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Daftar Cepat - Namua</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

  <style>
    :root{
      --primary:#8b1c1c;
      --primary2:#b12a2a;
      --text:#111827;
      --muted:#6b7280;
      --shadow:0 18px 40px rgba(0,0,0,.18);
    }

    *{box-sizing:border-box}
    html,body{height:100%}

    body{
      margin:0;
      font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;
      background:
        linear-gradient(135deg, rgba(139,28,28,.75), rgba(248,241,229,.92)),
        url('<?= base_url('uploads/kopi.jpg') ?>') no-repeat center / cover;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:18px;
      color:var(--text);
    }

    .nm-auth{width:100%;max-width:420px}
    .nm-card{
      background:rgba(255,255,255,.96);
      border-radius:24px;
      box-shadow:var(--shadow);
      padding:26px 22px;
      text-align:center;
      backdrop-filter: blur(2px);
    }

    .nm-logo{
      width:96px;height:96px;border-radius:22px;margin:0 auto 14px;
      background:#fff;display:flex;align-items:center;justify-content:center;
      box-shadow:0 10px 22px rgba(0,0,0,.12);overflow:hidden;
    }
    .nm-logo img{width:100%;height:100%;object-fit:contain}

    .nm-title{font-size:20px;font-weight:1000;color:var(--primary);margin:0}
    .nm-sub{margin-top:6px;font-size:13px;color:var(--muted);font-weight:800}
    .nm-pill{
      display:inline-block;
      margin-top:10px;
      padding:6px 10px;
      border-radius:999px;
      background:rgba(139,28,28,.08);
      color:var(--primary);
      font-size:12px;
      font-weight:1000;
    }
    .nm-info{
      margin:14px 0 0;
      padding:10px 12px;
      border-radius:14px;
      background:rgba(16,185,129,.12);
      color:#065f46;
      font-size:13px;
      font-weight:900;
      text-align:left;
    }
    .nm-error{
      margin:14px 0 0;
      padding:10px 12px;
      border-radius:14px;
      background:rgba(239,68,68,.12);
      color:#b91c1c;
      font-size:13px;
      font-weight:900;
      text-align:left;
    }

    .nm-field{margin-top:16px;display:flex;flex-direction:column;gap:6px;text-align:left}
    .nm-field label{font-size:12px;font-weight:900;color:var(--muted)}
    .nm-field input{
      height:48px;border-radius:16px;border:1px solid #e5e7eb;padding:0 14px;
      font-size:14px;outline:none;background:#fff;
    }
    .nm-field input:focus{
      border-color:rgba(139,28,28,.35);
      box-shadow:0 0 0 4px rgba(139,28,28,.12);
    }
    .nm-field input[readonly]{background:#f9fafb}

    .nm-btn{
      margin-top:18px;width:100%;height:48px;border:none;border-radius:16px;
      background:linear-gradient(135deg,var(--primary),var(--primary2));
      color:#fff;font-weight:1000;font-size:15px;cursor:pointer;
      box-shadow:0 14px 24px rgba(139,28,28,.28);
    }
    .nm-btn:active{transform:scale(.99)}

    .nm-link{
      margin-top:12px;
      font-size:13px;
      font-weight:900;
      color:var(--muted);
      text-align:center;
    }
    .nm-link a{
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

      <h1 class="nm-title">Daftar Cepat</h1>
      <div class="nm-sub">Nomor belum terdaftar. Isi nama untuk lanjut order.</div>

      <?php if (!empty($nomor_meja)): ?>
        <div class="nm-pill">Meja <?= html_escape($nomor_meja) ?></div>
      <?php endif; ?>

      <div class="nm-info">
        Nomor HP: <b><?= html_escape($telepon ?? '') ?></b>
      </div>

      <?php if ($this->session->flashdata('error')): ?>
        <div class="nm-error">
          <?= html_escape($this->session->flashdata('error')) ?>
        </div>
      <?php endif; ?>

      <form method="post" action="<?= site_url('start/register') ?>">
        <input type="hidden" name="redirect_to" value="<?= isset($redirect_to) ? html_escape($redirect_to) : '' ?>">
        <div class="nm-field">
          <label for="telepon">Nomor HP</label>
          <input
            id="telepon"
            type="tel"
            name="telepon"
            value="<?= html_escape($telepon ?? '') ?>"
            inputmode="numeric"
            autocomplete="tel"
            readonly
            required
          >
        </div>

        <div class="nm-field">
          <label for="nama">Nama</label>
          <input
            id="nama"
            type="text"
            name="nama"
            placeholder="Nama kamu"
            autocomplete="name"
            required
          >
        </div>

        <button type="submit" class="nm-btn">Daftar dan Lanjut</button>
      </form>

      <?php
        $rt = isset($redirect_to) ? (string) $redirect_to : '';
        $startUrl = site_url('start' . ($rt !== '' ? '?redirect_to=' . urlencode($rt) : ''));
      ?>
      <div class="nm-link">
        Salah nomor? <a href="<?= $startUrl ?>">Ubah nomor HP</a>
      </div>
    </div>
  </div>

  <script>
    const nama = document.getElementById('nama');
    if (nama) nama.focus();
  </script>
</body>
</html>
