<div class="container mt-4">
  <h4><?= $title ?></h4>

  <!-- Filter Kategori + Pencarian -->
  <div class="row mb-3">
    <div class="col-md-6">
      <select id="filter_kategori" class="form-control">
        <option value="">Semua Kategori</option>
        <?php foreach ($kategori as $k): ?>
          <option value="<?= $k->id ?>"><?= $k->nama_kategori ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <div class="col-md-6">
      <input type="text" id="search_produk" class="form-control" placeholder="Cari produk...">
    </div>
  </div>

  <!-- FORM utama kirim ke /order/review -->
  <form method="post" action="<?= base_url('order/review') ?>">
    <div class="row" id="produk_list">
      <?php $this->load->view('order/produk_grid', ['produk' => $produk]); ?>
    </div>

    <!-- Tempat input tersembunyi -->
    <div id="order_list"></div>

    <!-- Floating Cart Button -->
    <a href="#" id="cartToggle" class="btn btn-danger rounded-circle shadow"
      style="position:fixed; bottom:80px; right:20px; width:60px; height:60px; z-index:999;">
      <i class="fas fa-shopping-cart fa-lg d-flex justify-content-center align-items-center h-100"></i>
    </a>

    <!-- Modal Keranjang -->
    <div class="modal fade" id="cartModal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h6 class="modal-title">Keranjang Anda</h6>
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
          </div>
          <div class="modal-body" id="cartContent">
            <p class="text-muted text-center">Belum ada item</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-success btn-block w-100" id="lanjutkanOrder">Lanjutkan Order</button>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<!-- Modal Produk -->
<div class="modal fade" id="modalOrder" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title" id="modalOrderLabel">Tambah ke Order</h6>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="modal_produk_id">
        <div class="text-center mb-2">
          <img id="modal_foto" src="" class="img-fluid rounded" style="max-height:120px;">
        </div>
        <h6 id="modal_nama" class="text-center mb-2"></h6>
        <p class="text-center text-danger font-weight-bold mb-3" id="modal_harga"></p>
        <div class="form-group">
          <label>Jumlah</label>
          <input type="number" min="1" id="modal_jumlah" class="form-control" value="1">
        </div>
        <div class="form-group">
          <label>Extra:</label>
          <div id="extra_selected" class="text-muted">Tidak ada</div>
          <button type="button" class="btn btn-sm btn-outline-secondary mt-2" data-toggle="modal" data-target="#modalExtra">
            Pilih Extra
          </button>
        </div>
        <button type="button" class="btn btn-primary btn-block" id="btnTambahOrder">Tambah ke Order</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Extra -->
<div class="modal fade" id="modalExtra" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable modal-sm">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title">Pilih Extra</h6>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body p-3">
        <?php foreach ($this->db->get_where('pr_produk_extra', ['status' => 'aktif'])->result() as $ex): ?>
          <div class="form-check">
            <input class="form-check-input extra-option" type="checkbox"
              value="<?= $ex->id ?>" data-nama="<?= $ex->nama_extra ?>" data-harga="<?= $ex->harga ?>">
            <label class="form-check-label">
              <?= $ex->nama_extra ?> <small class="text-muted">(+Rp <?= number_format($ex->harga, 0, ',', '.') ?>)</small>
            </label>
          </div>
        <?php endforeach ?>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-sm btn-primary" id="btnSetExtra" data-dismiss="modal">Simpan</button>
      </div>
    </div>
  </div>
</div>

<!-- Script -->
<script>
  const BASE_URL = "<?= base_url() ?>";
  const cartData = {};
  let selectedExtras = [];

  $(document).ready(function() {
    // AJAX filter produk
    function loadProduk() {
      let keyword = $('#search_produk').val();
      let kategori = $('#filter_kategori').val();
      $.post(BASE_URL + 'order/filter_produk', {
        keyword,
        kategori
      }, function(res) {
        $('#produk_list').html(res);
      });
    }
    $('#search_produk').on('keyup', loadProduk);
    $('#filter_kategori').on('change', loadProduk);

    // klik produk (delegated)
    $(document).on('click', '.product-card', function() {
      const id = $(this).data('id');
      const nama = $(this).data('nama');
      const harga = $(this).data('harga');
      const foto = $(this).data('foto');
      const hargaFormatted = parseInt(harga).toLocaleString('id-ID');

      $('#modal_produk_id').val(id);
      $('#modal_nama').text(nama);
      // $('#modal_harga').text('Rp ' + harga.toLocaleString());
      $('#modal_harga').text('Rp ' + hargaFormatted);

      $('#modal_foto').attr('src', 'https://dashboard.namuacoffee.com/uploads/produk/' + foto);
      $('#modal_jumlah').val(1);
      $('.extra-option').prop('checked', false);
      $('#extra_selected').text('Tidak ada');
      selectedExtras = [];
      $('#modalOrder').modal('show');
    });

    $('#btnSetExtra').on('click', function() {
      selectedExtras = [];
      let labelList = [];
      $('.extra-option:checked').each(function() {
        selectedExtras.push($(this).val());
        labelList.push($(this).data('nama'));
      });
      $('#extra_selected').text(labelList.length > 0 ? labelList.join(', ') : 'Tidak ada');
    });

    $('#btnTambahOrder').on('click', function() {
      const id = $('#modal_produk_id').val();
      const nama = $('#modal_nama').text();
      const harga = parseInt($('#modal_harga').text().replace(/[^\d]/g, ''));
      const jumlah = parseInt($('#modal_jumlah').val()) || 0;

      const extra_ids = [];
      const extra_nama = [];
      $('.extra-option:checked').each(function() {
        extra_ids.push($(this).val());
        extra_nama.push($(this).data('nama'));
      });

      // simpan ke JS cart
      cartData[id] = {
        nama,
        harga,
        jumlah,
        extras: extra_nama
      };

      // hapus input lama
      $(`input[name="produk[${id}]"]`).remove();
      $(`input[name="extra[${id}][]"]`).remove();

      // tambah input hidden baru
      $('#order_list').append(`<input type="hidden" name="produk[${id}]" value="${jumlah}">`);
      extra_ids.forEach(extra_id => {
        $('#order_list').append(`<input type="hidden" name="extra[${id}][]" value="${extra_id}">`);
      });

      $('#modalOrder').modal('hide');
    });

    $('#cartToggle').on('click', function(e) {
      e.preventDefault();
      updateCartDisplay();
      $('#cartModal').modal('show');
    });

    $('#lanjutkanOrder').on('click', function() {
      $('form').submit();
    });

    function updateCartDisplay() {
      let html = '';
      let total = 0;
      if (Object.keys(cartData).length === 0) {
        $('#cartContent').html('<p class="text-muted text-center">Belum ada item</p>');
        return;
      }

      html += '<ul class="list-group">';
      for (let id in cartData) {
        const item = cartData[id];
        const extras = item.extras.length ? `<br><small>+ ${item.extras.join(', ')}</small>` : '';
        const subtotal = item.harga * item.jumlah;
        total += subtotal;
        html += `
          <li class="list-group-item d-flex justify-content-between">
            <div><strong>${item.nama}</strong>${extras}</div>
            <div>Rp ${subtotal.toLocaleString()}</div>
          </li>
        `;
      }
      html += `</ul><hr><p class="text-end fw-bold">Total: Rp ${total.toLocaleString()}</p>`;
      $('#cartContent').html(html);
    }
  });
</script>