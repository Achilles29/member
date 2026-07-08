<?php if ($produk): ?>
    <?php foreach ($produk as $p): ?>
        <?php
            $stok_tersedia = (float) ($p->stok_tersedia ?? 0);
            $stock_mode = strtoupper(trim((string) ($p->stock_mode ?? 'AUTO')));
            $is_auto_stock = ($stock_mode === 'AUTO');
            $has_photo = !empty($p->foto);
            $is_habis = ($stok_tersedia <= 0) || (int) ($p->is_available_for_order ?? 0) !== 1;
            $show_sold_out_badge = $is_auto_stock && $is_habis;
            $show_limited_badge = $is_auto_stock && !$is_habis && $stok_tersedia > 0 && $stok_tersedia < 5;
        ?>
        <div class="col-6 col-sm-6 col-md-6 mb-3 px-2">
            <div class="card h-100 shadow-sm product-card <?= $is_habis ? 'is-disabled' : '' ?>"
                data-id="<?= $p->id ?>"
                data-nama="<?= $p->nama_produk ?>"
                data-harga="<?= $p->harga_jual ?>"
                data-deskripsi="<?= html_escape(trim((string) ($p->deskripsi ?? ''))) ?>"
                data-foto="<?= $p->foto ?>">
                <?php if ($has_photo): ?>
                    <img src="https://core.namuacoffee.com/uploads/produk/<?= $p->foto ?>" class="card-img-top" style="height:120px; object-fit:cover;">
                <?php endif ?>
                <div class="card-body <?= $has_photo ? 'text-center py-2 px-1' : 'py-3 px-3 text-left' ?>">
                    <?php if ($show_sold_out_badge): ?>
                        <div class="mb-1 text-danger" style="font-size:11px; font-weight:700;">Sold Out</div>
                    <?php elseif ($show_limited_badge): ?>
                        <div class="mb-1 text-warning" style="font-size:11px; font-weight:700;">Limited</div>
                    <?php elseif ($is_habis): ?>
                        <div class="mb-1 text-danger" style="font-size:11px; font-weight:700;">Habis</div>
                    <?php endif; ?>
                    <h6 class="card-title mb-1" style="font-size:14px; font-weight:600"><?= strtoupper($p->nama_produk) ?></h6>
                    <?php if (!$has_photo && trim((string) ($p->deskripsi ?? '')) !== ''): ?>
                        <div class="text-muted mb-2" style="font-size:12px; line-height:1.5;"><?= html_escape($p->deskripsi) ?></div>
                    <?php endif; ?>
                    <p class="text-danger font-weight-bold mb-2" style="font-size:13px">Rp <?= number_format($p->harga_jual, 0, ',', '.') ?></p>
                </div>
            </div>
        </div>
    <?php endforeach ?>
<?php else: ?>
    <div class="col-12 text-center text-muted">Tidak ada produk ditemukan.</div>
<?php endif ?>
