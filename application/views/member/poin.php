<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Poin - Namua</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #8b1c1c;
            --background: #fff8f0;
            --accent: #fceee4;
            --text-dark: #4b2c20;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
        }

        body {
            font-family: Arial, sans-serif;
            background: var(--background);
            margin: 0;
            padding-bottom: 80px;
            color: var(--text-dark);
        }

        .header {
            background: var(--primary);
            color: #fff8f0;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h2 {
            margin: 0;
            font-size: 18px;
        }

        .section {
            padding: 20px;
        }

        .summary-cards {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .summary-card {
            background: #fff;
            flex: 1 1 45%;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .summary-card h3 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }

        .summary-card small {
            font-size: 12px;
            color: #777;
        }

        .text-success { color: var(--success); }
        .text-danger { color: var(--danger); }
        .text-warning { color: var(--warning); }
        .text-secondary { color: #888; }

        .history-table {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th {
            background: var(--accent);
            color: var(--primary);
            padding: 10px;
            text-align: center;
        }

        td {
            padding: 8px 10px;
            border-bottom: 1px solid #eee;
        }

        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--primary);
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            color: #fff8f0;
            z-index: 99;
        }

        .bottom-nav a {
            text-align: center;
            color: #fff8f0;
            text-decoration: none;
            font-size: 12px;
        }

        .bottom-nav i {
            display: block;
            font-size: 18px;
        }

        .bottom-nav .active {
            color: var(--warning);
        }
        .form-label {
    font-weight: bold;
    color: var(--text-dark);
    font-size: 13px;
}

.form-select, .form-control {
    border-radius: 6px;
    font-size: 14px;
}


    .filter-modern input:focus,
    .filter-modern select:focus {
        border-color: #8b1c1c;
        outline: none;
        box-shadow: 0 0 0 3px rgba(139, 28, 28, 0.1);
    }




    .filter-modern {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.07);
}

.filter-modern .filter-row {
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    gap: 12px;
    align-items: end;
    padding-bottom: 5px;
    scrollbar-width: thin; /* Firefox */
}


.filter-modern .form-group {
    flex: 0 0 auto;
    min-width: 130px;
}
.filter-modern label {
    font-weight: 600;
    font-size: 13px;
    margin-bottom: 5px;
    color: #8b1c1c;
    display: block;
}

.filter-modern input,
.filter-modern select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
}

.filter-modern button {
    padding: 10px 18px;
    border: none;
    background-color: #8b1c1c;
    color: #fff;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

.filter-modern button:hover {
    background-color: #a52727;
}

@media (max-width: 600px) {
    .filter-modern .filter-row {
        flex-wrap: nowrap;
        justify-content: flex-start;
    }

    .filter-modern .form-group {
        min-width: 150px;
    }

    .filter-modern button {
        white-space: nowrap;
    }
}
    </style>
</head>
<body>

<div class="section">
    <h3 class="text-center">Ringkasan Poin</h3>

    <div class="summary-cards">
        <div class="summary-card">
            <h3 class="text-success"><?= $poin['aktif'] ?>⭐</h3>
            <small>Poin Aktif</small>
        </div>
        <div class="summary-card">
            <h3 class="text-danger"><?= $poin['digunakan'] ?>⭐</h3>
            <small>Poin Digunakan</small>
        </div>
        <div class="summary-card">
            <h3 class="text-secondary"><?= $poin['kedaluwarsa'] ?>⭐</h3>
            <small>Poin Kedaluwarsa</small>
        </div>
        <div class="summary-card">
            <h3 class="text-warning"><?= $poin['akan_kedaluwarsa'] ?>⭐</h3>
            <small>Akan Kedaluwarsa</small>
        </div>
    </div>

    <div class="filter-modern">
    <form method="get">
        <div class="filter-row">
            <div class="form-group">
                <label for="start">Dari</label>
                <input type="date" name="start" id="start" value="<?= $start ?>">
            </div>
            <div class="form-group">
                <label for="end">Sampai</label>
                <input type="date" name="end" id="end" value="<?= $end ?>">
            </div>
            <div class="form-group">
                <label for="limit">Jumlah</label>
                <select name="limit" id="limit">
                    <?php foreach ([10, 30, 50, 'semua'] as $val): ?>
                        <option value="<?= $val ?>" <?= ($limit == $val) ? 'selected' : '' ?>><?= ucfirst($val) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group" style="flex:0;">
                <label>&nbsp;</label>
                <button type="submit">Tampilkan</button>
            </div>
        </div>
    </form>
</div>





    <h4 style="margin-top:30px; margin-bottom:10px;">Riwayat Poin</h4>
    <div class="history-table">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>No Transaksi</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($riwayat as $r): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
                        <td><?= $r['no_transaksi'] ?? '-' ?></td>
                        <td class="text-end"><?= number_format($r['jumlah_poin']) ?>⭐</td>
                        <td class="text-center"><?= ucfirst(strtolower($r['status'])) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>

        <?php if ($limit !== 'semua' && $total_pages > 1): ?>
<div style="text-align:center; margin-top:15px;">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?start=<?= $start ?>&end=<?= $end ?>&limit=<?= $limit ?>&page=<?= $i ?>"
           style="margin:0 4px; text-decoration:none; padding:5px 10px; border:1px solid #ccc; border-radius:4px;
           <?= $i == $page ? 'background:#8b1c1c;color:white;' : '' ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

    </div>
</div>


</body>
</html>
