<?php
$ci = get_instance();
$has_self_order_context = (int) ($ci->session->userdata('order_meja_id') ?? 0) > 0
    || trim((string) ($ci->session->userdata('order_nomor_meja') ?? '')) !== '';
$order_nav_path = $has_self_order_context ? 'order' : 'online-order';
$order_nav_label = $has_self_order_context ? 'Order' : 'Online';
?>
<div class="bottom-nav">
    <a href="<?= site_url('member') ?>"><i class="fas fa-home"></i><span>Home</span></a>
    <a href="<?= site_url($order_nav_path) ?>"><i class="fas fa-star"></i><span><?= $order_nav_label ?></span></a>
    <a href="<?= site_url('poin') ?>"><i class="fas fa-star"></i><span>Poin</span></a>
    <a href="<?= site_url('stamp') ?>"><i class="fas fa-stamp"></i><span>Stamp</span></a>
    <a href="<?= site_url('voucher') ?>"><i class="fas fa-ticket-alt"></i><span>Voucher</span></a>
    <a href="<?= site_url('redeem') ?>"><i class="fas fa-gift"></i><span>Redeem</span></a>
    <a href="<?= site_url('profile') ?>"><i class="fas fa-user"></i><span>Akun</span></a>
</div>


</div>
</body>
</html>
