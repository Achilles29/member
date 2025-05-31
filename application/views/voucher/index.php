<style>

    h1 {
        font-size: 24px;
        color: #333;
        margin-bottom: 20px;
    }

    .button-container {
        margin-bottom: 20px;
        text-align: left;
    }

    .button-container a {
        padding: 10px 15px;
        background: #4a90e2;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        font-size: 14px;
        transition: background 0.3s ease;
    }

    .button-container a:hover {
        background: #357abd;
    }

    .table-container {
        width: 100%;
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        background: white;
        border: 1px solid #ddd;
    }

    th, td {
        text-align: left;
        padding: 12px;
        border: 1px solid #ddd;
    }

    th {
        background: #4a90e2;
        color: white;
        font-size: 14px;
    }

    td {
        font-size: 14px;
        color: #555;
    }

    tr:nth-child(even) {
        background: #f9f9f9;
    }

    tr:hover {
        background: #e0e0e0;
    }

    a.action-link {
        color: #4a90e2;
        text-decoration: none;
    }

    a.action-link:hover {
        text-decoration: underline;
    }

    a.action-delete {
        color: red;
        text-decoration: none;
    }

    a.action-delete:hover {
        text-decoration: underline;
    }

    .container {
        width: 100%;
        max-width: 1400px; /* Untuk membatasi ukuran maksimal */
        margin: 0 auto;
    }
</style>


<h1>Daftar Voucher</h1>
    <div class="button-container">
        <a href="<?php echo site_url('voucher/add'); ?>">Tambah Voucher</a>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kode Voucher</th>
                    <th>Keterangan</th>
                    <th>Tipe Diskon</th>
                    <th>Nilai Diskon</th>
                    <th>Masa Berlaku</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vouchers as $voucher): ?>
                <tr>
                    <td><?php echo $voucher['id']; ?></td>
                    <td><?php echo $voucher['code']; ?></td>
                    <td><?php echo $voucher['description']; ?></td>
                    <td><?php echo $voucher['discount_type']; ?></td>
                    <td><?php echo $voucher['discount_value']; ?></td>
                    <td><?php echo $voucher['start_date'] . ' - ' . $voucher['end_date']; ?></td>
                    <td><?php echo ucfirst($voucher['status']); ?></td>
                    <td>
                        <a href="<?php echo site_url('voucher/edit/' . $voucher['id']); ?>" class="action-link">Edit</a> |
                        <a href="<?php echo site_url('voucher/delete/' . $voucher['id']); ?>" 
                           class="action-delete" onclick="return confirm('Yakin ingin menghapus voucher ini?');">Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
