<div class="container mt-4">
    <h4><?= $title ?></h4>
    <ul class="list-group mb-3">
        <?php foreach ($produk_list as $item): ?>
            <li class="list-group-item">
                <div class="d-flex justify-content-between">
                    <div>
                        <strong><?= $item['nama'] ?></strong> x<?= $item['jumlah'] ?>
                        <?php if ($item['extra']): ?>
                            <br><small class="text-muted">+ <?= implode(', ', array_column($item['extra'], 'nama')) ?></small>
                        <?php endif; ?>
                    </div>
                    <div>
                        Rp <?= number_format($item['subtotal'], 0, ',', '.') ?>
                        <?php if ($item['extra']): ?>
                            <br><small>+ Rp <?= number_format(array_sum(array_column($item['extra'], 'harga')) * $item['jumlah'], 0, ',', '.') ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </li>
        <?php endforeach ?>
    </ul>

    <h5 class="text-end">Total: Rp <?= number_format($total, 0, ',', '.') ?></h5>

    <!-- Tombol Lanjut ke Bayar -->
    <form action="<?= base_url('order/pay') ?>" method="post">
        <input type="hidden" name="total" value="<?= $total ?>">
        <button type="submit" class="btn btn-success btn-block mt-3">Bayar Sekarang</button>
    </form>
</div>