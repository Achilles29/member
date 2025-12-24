<h1>Stempel Member</h1>
<p><strong>Nama:</strong> <?php echo $member['name']; ?></p>
<p><strong>Nomor HP:</strong> <?php echo $member['phone']; ?></p>
<p><strong>Jumlah Stamp:</strong> <?php echo count($stamps); ?>/5</p>


<h2>Transaksi</h2>
<table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 20px;">
    <thead>
        <tr style="background: #4a90e2; color: white;">
            <th style="padding: 10px;">Tanggal Transaksi</th>
            <th style="padding: 10px;">Jam</th>
            <th style="padding: 10px;">Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($stamps as $stamp): ?>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #ddd;"><?php echo date('d M Y', strtotime($stamp['stamp_date'])); ?></td>
                <td style="padding: 10px; border-bottom: 1px solid #ddd;"><?php echo date('H:i', strtotime($stamp['stamp_date'])); ?></td>
                <td style="padding: 10px; border-bottom: 1px solid #ddd;">
                    <a href="<?php echo site_url('admin/delete_transaction/' . $stamp['id']); ?>" 
                       style="color: red; text-decoration: none;" 
                       onclick="return confirm('Anda yakin ingin menghapus transaksi ini?');">
                       Hapus
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($stamps)): ?>
            <tr>
                <td colspan="3" style="padding: 10px; text-align: center; border-bottom: 1px solid #ddd;">Tidak ada transaksi.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>


<h2>Aksi</h2>
<a href="<?php echo site_url('admin/add_stamp_detail/' . $member['id']); ?>">Tambah Stempel</a> |
<a href="<?php echo site_url('admin/remove_stamp_detail/' . $member['id']); ?>">Kurangi Stempel</a> |
<a href="<?php echo site_url('admin/reset_stamp_detail/' . $member['id']); ?>">Reset Stempel</a>
<?php if ($this->session->flashdata('error')): ?>
    <div style="color: red; margin-bottom: 20px;">
        <?php echo $this->session->flashdata('error'); ?>
    </div>
<?php endif; ?>
