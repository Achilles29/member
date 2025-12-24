<h1>Tambah Member</h1>

<?php if ($this->session->flashdata('error')): ?>
    <div style="color: red; margin-bottom: 20px;">
        <?php echo $this->session->flashdata('error'); ?>
    </div>
<?php endif; ?>

<form action="<?php echo site_url('admin/save_member'); ?>" method="post" style="width: 400px; margin: 0 auto;">
    <div style="margin-bottom: 15px;">
        <label for="name" style="display: block; margin-bottom: 5px;">Nama</label>
        <input type="text" id="name" name="name" 
               style="width: 100%; padding: 10px; font-size: 14px; border: 1px solid #ddd; border-radius: 5px;">
    </div>
    <div style="margin-bottom: 15px;">
        <label for="phone" style="display: block; margin-bottom: 5px;">Nomor HP</label>
        <input type="text" id="phone" name="phone" 
               style="width: 100%; padding: 10px; font-size: 14px; border: 1px solid #ddd; border-radius: 5px;">
    </div>
    <div style="text-align: center;">
        <button type="submit" style="padding: 10px 15px; background: #4a90e2; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Simpan
        </button>
    </div>
</form>
