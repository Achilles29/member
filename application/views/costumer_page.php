<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Pelanggan</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background: #f4f4f9;
            color: #333;
        }

        .container {
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #4a90e2;
            margin-bottom: 20px;
        }

        .customer-info {
            margin-bottom: 30px;
        }

        .customer-info h3 {
            margin: 0;
            font-size: 18px;
            color: #666;
        }

        .customer-info p {
            margin: 5px 0 0;
            font-size: 16px;
            color: #333;
        }

        .stamp-container {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }

        .stamp-box {
            width: 100px;
            height: 100px;
            border-radius: 10px;
            background: #6d4c41;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .stamp-box.empty {
            background: #ddd;
            color: #999;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Data Pelanggan</h1>
        <div class="customer-info">
            <h3>Nama:</h3>
            <p><?php echo $customer['name']; ?></p>
            <h3>Nomor HP:</h3>
            <p><?php echo $customer['phone']; ?></p>
            <h3>Berlaku hingga:</h3>
            <p>
                <?php
                if (!empty($stamps)) {
                    $first_stamp_date = $stamps[0]['stamp_date'];
                    echo date('d M Y', strtotime($first_stamp_date . ' +1 month'));
                } else {
                    echo '-';
                }
            ?>
            </p>
        </div>
        <h2>Stamp yang Dikumpulkan</h2>
        <div class="stamp-container">
            <?php foreach ($stamps as $stamp): ?>
                <div class="stamp-box">
                    <?php echo date('d M', strtotime($stamp['stamp_date'])); ?>
                    <span><?php echo date('H:i', strtotime($stamp['stamp_date'])); ?></span>
                </div>
            <?php endforeach; ?>
            <?php for ($i = count($stamps) + 1; $i <= 5; $i++): ?>
                <div class="stamp-box empty">
                    <span>-</span>
                </div>
            <?php endfor; ?>
        </div>
        <div class="footer">
            © 2024 Coffee Club. All Rights Reserved.
        </div>
    </div>
</body>
</html>
