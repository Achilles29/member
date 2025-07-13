<?php if ($produk): ?>
    <?php foreach ($produk as $p): ?>
        <div class="col-6 col-sm-6 col-md-6 mb-3 px-2">
            <div class="card h-100 shadow-sm product-card"
                data-id="<?= $p->id ?>"
                data-nama="<?= $p->nama_produk ?>"
                data-harga="<?= $p->harga_jual ?>"
                data-foto="<?= $p->foto ?>">
                <?php if ($p->foto): ?>
                    <img src="https://dashboard.namuacoffee.com/uploads/produk/<?= $p->foto ?>" class="card-img-top" style="height:120px; object-fit:cover;">
                <?php endif ?>
                <div class="card-body text-center py-2 px-1">
                    <h6 class="card-title mb-1" style="font-size:14px; font-weight:600"><?= strtoupper($p->nama_produk) ?></h6>
                    <p class="text-danger font-weight-bold mb-2" style="font-size:13px">Rp <?= number_format($p->harga_jual, 0, ',', '.') ?></p>
                </div>
            </div>
        </div>
    <?php endforeach ?>
<?php else: ?>
    <div class="col-12 text-center text-muted">Tidak ada produk ditemukan.</div>
<?php endif ?>