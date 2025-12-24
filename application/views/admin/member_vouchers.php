<h1>Voucher Member: <?php echo $member['name']; ?></h1>

<div style="margin-bottom: 20px;">
    <form method="post" action="<?php echo site_url('admin/add_member_voucher'); ?>">
        <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
        <select name="voucher_id" required style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
            <option value="" disabled selected>Pilih Voucher</option>
            <?php foreach ($all_vouchers as $voucher): ?>
                <option value="<?php echo $voucher['id']; ?>"><?php echo $voucher['code']; ?> - <?php echo $voucher['description']; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" style="padding: 10px 15px; background: #4a90e2; color: white; border: none; border-radius: 5px;">Tambah Voucher</button>
    </form>
</div>

<table style="width: 100%; border-collapse: collapse; font-size: 14px;">
    <thead>
        <tr style="background: #4a90e2; color: white;">
            <th style="padding: 10px;">Kode Voucher</th>
            <th style="padding: 10px;">Keterangan</th>
            <th style="padding: 10px;">Tipe Diskon</th>
            <th style="padding: 10px;">Nilai Diskon</th>
            <th style="padding: 10px;">Masa Berlaku</th>
            <th style="padding: 10px;">Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($vouchers as $voucher): ?>
        <tr>
            <td style="padding: 10px;"><?php echo $voucher['code']; ?></td>
            <td style="padding: 10px;"><?php echo $voucher['description']; ?></td>
            <td style="padding: 10px;"><?php echo $voucher['discount_type']; ?></td>
            <td style="padding: 10px;"><?php echo $voucher['discount_value']; ?></td>
            <td style="padding: 10px;"><?php echo $voucher['start_date'] . ' - ' . $voucher['end_date']; ?></td>
            <td style="padding: 10px;">
                <a href="<?php echo site_url('admin/delete_member_voucher/' . $voucher['member_voucher_id'] . '/' . $member['id']); ?>" 
                   style="color: red;" onclick="return confirm('Yakin ingin menghapus voucher ini?');">Hapus</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
