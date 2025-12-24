<h1>Daftar Members</h1>

<div style="margin-bottom: 20px; text-align: left;">
    <input
        type="text"
        id="search"
        placeholder="Cari nama atau nomor HP..."
        style="padding: 10px; width: 300px; font-size: 14px; border: 1px solid #ddd; border-radius: 5px;">
</div>
<div style="margin-bottom: 20px; text-align: right;">
    <a href="<?php echo site_url('admin/add_member'); ?>" 
       style="padding: 10px 15px; background: #4a90e2; color: white; text-decoration: none; border-radius: 5px;">
       Tambah Member
    </a>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div style="color: green; margin-bottom: 20px;">
        <?php echo $this->session->flashdata('success'); ?>
    </div>
<?php endif; ?>

<div id="member-list">
    <!-- Daftar member -->
    <table style="width: 100%; border-collapse: collapse; font-size: 14px; background: white;">
        <thead>
            <tr style="background: #4a90e2; color: white;">
                <th style="padding: 10px;">ID</th>
                <th style="padding: 10px;">Nama</th>
                <th style="padding: 10px;">Nomor HP</th>
                <th style="padding: 10px;">Jumlah Stamp</th>
                <th style="padding: 10px;">Aksi</th>
                <th style="padding: 10px;">Edit / Hapus</th>
                <th style="padding: 10px;">Card</th>
                <th style="padding: 10px;">Voucher</th>

            </tr>
        </thead>
        <tbody>
            <?php foreach ($members as $index => $member): ?>
            <tr style="text-align: center; <?php echo $index % 2 == 0 ? 'background: #f9f9f9;' : ''; ?>">
                <td style="padding: 10px; border-bottom: 1px solid #ddd;"><?php echo $member['id']; ?></td>
                <td style="padding: 10px; border-bottom: 1px solid #ddd;"><?php echo $member['name']; ?></td>
                <td style="padding: 10px; border-bottom: 1px solid #ddd;"><?php echo $member['phone']; ?></td>
                <td style="padding: 10px; border-bottom: 1px solid #ddd;"><?php echo $member['stamp_count']; ?>/5</td>
                <td style="padding: 10px; border-bottom: 1px solid #ddd;">
                    <a href="<?php echo site_url('admin/add_stamp/' . $member['id']); ?>" style="color: #4a90e2; text-decoration: none;">Tambah</a> |
                    <a href="<?php echo site_url('admin/remove_stamp/' . $member['id']); ?>" style="color: #4a90e2; text-decoration: none;">Kurang</a> |
                    <a href="<?php echo site_url('admin/reset_stamp/' . $member['id']); ?>" style="color: #4a90e2; text-decoration: none;">Reset</a> |
                    <a href="<?php echo site_url('admin/stamps/' . $member['id']); ?>" style="color: #4a90e2; text-decoration: none;">Lihat</a>
                </td>
                <td style="padding: 10px; border-bottom: 1px solid #ddd;">
                    <a href="<?php echo site_url('admin/edit_member/' . $member['id']); ?>" style="color: #4a90e2; text-decoration: none;">Edit</a> |
                    <a href="<?php echo site_url('admin/delete_member/' . $member['id']); ?>" 
                       onclick="return confirm('Anda yakin ingin menghapus member ini?');" 
                       style="color: red; text-decoration: none;">Hapus</a>
                </td>
                <td style="padding: 10px; border-bottom: 1px solid #ddd;">
                    <a href="<?php echo site_url('admin/member_card/' . $member['id']); ?>" 
                    style="color: #4a90e2; text-decoration: none;">Lihat Card</a>
                </td>
                <td style="padding: 10px;">
                    <a href="<?php echo site_url('admin/member_vouchers/' . $member['id']); ?>" style="color: #4a90e2;">
                        Lihat Voucher
                    </a>
                </td>

            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div style="margin-top: 20px; text-align: center;">
    <?php echo $pagination; ?>
</div>


<script>
    document.getElementById('search').addEventListener('keyup', function() {
        const query = this.value;

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo site_url("admin/search_members"); ?>', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                document.getElementById('member-list').innerHTML = xhr.responseText;
            }
        };
        xhr.send('query=' + encodeURIComponent(query));
    });
</script>

<?php if ($this->session->flashdata('error')): ?>
    <div style="color: red; margin-bottom: 20px;">
        <?php echo $this->session->flashdata('error'); ?>
    </div>
<?php endif; ?>
