    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }

        h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }

        .form-group.full-width {
            grid-template-columns: 1fr;
        }

        .form-group label {
            font-size: 14px;
            color: #555;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }

        textarea {
            resize: none;
            height: 80px;
        }

        input:focus, textarea:focus, select:focus {
            border-color: #4a90e2;
            outline: none;
        }

        .form-actions {
            text-align: center;
            margin-top: 20px;
        }

        .form-actions button {
            background: #4a90e2;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .form-actions button:hover {
            background: #357abd;
        }
    </style>
    
        <div class="container">
        <h1>Edit Voucher</h1>
        <form method="post" action="<?php echo site_url('voucher/update/' . $voucher['id']); ?>">
            <div class="form-group">
                <label for="code">Kode Voucher</label>
                <input type="text" id="code" name="code" value="<?php echo $voucher['code']; ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Keterangan</label>
                <textarea id="description" name="description" required><?php echo $voucher['description']; ?></textarea>
            </div>
            <div class="form-group">
                <label for="discount_type">Tipe Diskon</label>
                <select id="discount_type" name="discount_type">
                    <option value="nominal" <?php echo $voucher['discount_type'] == 'nominal' ? 'selected' : ''; ?>>Nominal</option>
                    <option value="percentage" <?php echo $voucher['discount_type'] == 'percentage' ? 'selected' : ''; ?>>Persentase</option>
                </select>
            </div>
            <div class="form-group">
                <label for="discount_value">Nilai Diskon</label>
                <input type="number" id="discount_value" name="discount_value" value="<?php echo $voucher['discount_value']; ?>" required>
            </div>
            <div class="form-group">
                <label for="start_date">Tanggal Mulai</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $voucher['start_date']; ?>" required>
            </div>
            <div class="form-group">
                <label for="end_date">Tanggal Berakhir</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $voucher['end_date']; ?>" required>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active" <?php echo $voucher['status'] == 'active' ? 'selected' : ''; ?>>Aktif</option>
                    <option value="inactive" <?php echo $voucher['status'] == 'inactive' ? 'selected' : ''; ?>>Tidak Aktif</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit">Simpan</button>
                <a href="<?php echo site_url('voucher'); ?>">Kembali</a>
            </div>
        </form>
    </div>
