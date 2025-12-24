<!-- Swiper & CSS -->
<link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>

<style>
  .product-card img {
    height: 120px;
    object-fit: cover;
  }

  @media (max-width: 576px) {
    .produk-container .col-6 {
      flex: 0 0 50%;
      max-width: 50%;
    }
  }

  .swiper-hint {
    text-align: center;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    color: #888;
    animation: fadeHint 3s infinite;
  }

  @keyframes fadeHint {

    0%,
    100% {
      opacity: 0.3;
    }

    50% {
      opacity: 1;
    }
  }

  .swiper-hint i {
    margin: 0 4px;
  }
</style>

<div class="container mt-4">
  <h4><?= $title ?></h4>

  <!-- Filter kategori & pencarian -->
  <div class="row mb-3">
    <div class="col-6">
      <select id="kategoriSelector" class="form-control">
        <?php foreach ($kategori as $index => $kat): ?>
          <option value="<?= $index ?>"><?= $kat->nama_kategori ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <div class="col-6">
      <input type="text" id="search_produk" class="form-control" placeholder="Cari produk...">
    </div>
  </div>
  <div class="swiper-hint">
    <i class="fas fa-arrow-left"></i> Geser untuk melihat kategori lain <i class="fas fa-arrow-right"></i>
  </div>

  <!-- Swiper Slide Produk per Kategori -->
  <div class="swiper mySwiper">
    <div class="swiper-wrapper">
      <?php foreach ($kategori as $index => $kat): ?>
        <div class="swiper-slide">
          <h5 class="text-center mb-3"><?= $kat->nama_kategori ?></h5>
          <div class="row produk-container">
            <?php foreach ($produk_per_kategori[$kat->id] as $p): ?>
              <div class="col-6 col-md-4 col-lg-3 mb-3 produk-item" data-nama="<?= strtolower($p->nama_produk) ?>">
                <div class="card product-card" data-id="<?= $p->id ?>" data-nama="<?= $p->nama_produk ?>" data-harga="<?= $p->harga_jual ?>" data-foto="<?= $p->foto ?>">
                  <img src="https://dashboard.namuacoffee.com/uploads/produk/<?= $p->foto ?>" class="card-img-top">
                  <div class="card-body text-center p-2">
                    <small class="fw-bold"><?= $p->nama_produk ?></small><br>
                    <small class="text-danger">Rp <?= number_format($p->harga_jual, 0, ',', '.') ?></small>
                  </div>
                </div>
              </div>
            <?php endforeach ?>
          </div>
        </div>
      <?php endforeach ?>
    </div>
  </div>

  <!-- Keranjang (Floating Button + Hidden Form) -->
  <form method="post" action="<?= base_url('order/review') ?>">
    <div id="order_list"></div>
    <a href="#" id="cartToggle" class="btn btn-danger rounded-circle shadow"
      style="position:fixed; bottom:80px; right:20px; width:60px; height:60px; z-index:999;">
      <i class="fas fa-shopping-cart fa-lg d-flex justify-content-center align-items-center h-100"></i>
    </a>
  </form>
</div>

<div id="notifToast" class="toast position-fixed bg-success text-white" style="top: 20px; right: 20px; z-index: 2000;" role="alert" aria-live="assertive" aria-atomic="true" data-delay="2000">
  <div class="toast-body">
    Produk ditambahkan ke keranjang.
  </div>
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
<!-- Modal Keranjang -->
<div class="modal fade" id="cartModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title">Keranjang Saya</h6>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body" id="cartContent">
        <!-- Konten keranjang akan di-render di sini -->
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-sm btn-primary" id="lanjutkanOrder">Lanjutkan</button>
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
    // Swiper init
    const swiper = new Swiper('.mySwiper', {
      slidesPerView: 1,
      spaceBetween: 20,
      autoHeight: true,
      watchSlidesProgress: true
    });

    // Saat dropdown berubah → ganti slide
    $('#kategoriSelector').on('change', function() {
      swiper.slideTo(parseInt($(this).val()));
    });

    // Saat slide di-swipe → update dropdown
    swiper.on('slideChange', function() {
      $('#kategoriSelector').val(swiper.activeIndex);
    });
    $('#kategoriSelector').on('change', function() {
      swiper.slideTo(parseInt($(this).val()));
    });

    // Pencarian produk
    $('#search_produk').on('keyup', function() {
      const keyword = $(this).val().toLowerCase();

      $('.swiper-slide').each(function() {
        let matchInSlide = false;
        $(this).find('.produk-item').each(function() {
          const nama = $(this).data('nama');
          const match = nama.includes(keyword);
          $(this).toggle(match);
          if (match) matchInSlide = true;
        });

        // Tampilkan/hidden slide hanya jika ada match
        if (keyword.length > 0) {
          $(this).toggle(matchInSlide);
        } else {
          $(this).show(); // reset jika input kosong
        }
      });
    });


    // Klik produk → buka modal
    $(document).on('click', '.product-card', function() {
      const id = $(this).data('id');
      const nama = $(this).data('nama');
      const harga = $(this).data('harga');
      const foto = $(this).data('foto');

      $('#modal_produk_id').val(id);
      $('#modal_nama').text(nama);
      $('#modal_harga').text('Rp ' + parseInt(harga).toLocaleString('id-ID'));
      $('#modal_foto').attr('src', 'https://dashboard.namuacoffee.com/uploads/produk/' + foto);
      $('#modal_jumlah').val(1);
      $('.extra-option').prop('checked', false);
      $('#extra_selected').text('Tidak ada');
      selectedExtras = [];
      $('#modalOrder').modal('show');
    });

    // Set extra
    $('#btnSetExtra').on('click', function() {
      selectedExtras = [];
      let labelList = [];
      $('.extra-option:checked').each(function() {
        selectedExtras.push($(this).val());
        labelList.push($(this).data('nama'));
      });
      $('#extra_selected').text(labelList.length > 0 ? labelList.join(', ') : 'Tidak ada');
    });

    // Tambah ke cart
    $('#btnTambahOrder').on('click', function() {
      const id = $('#modal_produk_id').val();
      const nama = $('#modal_nama').text();
      const harga = parseInt($('#modal_harga').text().replace(/[^\d]/g, ''));
      const jumlah = parseInt($('#modal_jumlah').val()) || 0;

      const extra_ids = [],
        extra_nama = [];
      $('.extra-option:checked').each(function() {
        extra_ids.push($(this).val());
        extra_nama.push($(this).data('nama'));
      });

      cartData[id] = {
        nama,
        harga,
        jumlah,
        extras: extra_nama
      };

      $(`input[name="produk[${id}]"]`).remove();
      $(`input[name="extra[${id}][]"]`).remove();

      $('#order_list').append(`<input type="hidden" name="produk[${id}]" value="${jumlah}">`);
      extra_ids.forEach(extra_id => {
        $('#order_list').append(`<input type="hidden" name="extra[${id}][]" value="${extra_id}">`);
      });

      $('#modalOrder').modal('hide');

      $('#notifToast').toast({
        delay: 2000
      });
      $('#notifToast').toast('show');

    });

    // Toggle keranjang
    $('#cartToggle').on('click', function(e) {
      e.preventDefault();
      updateCartDisplay();
      $('#cartModal').modal('show');
    });

    $('#lanjutkanOrder').on('click', function() {
      $('form').submit();
    });

    // Update tampilan cart
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
        html += `<li class="list-group-item d-flex justify-content-between">
        <div><strong>${item.nama}</strong>${extras}</div>
        <div>Rp ${subtotal.toLocaleString()}</div>
      </li>`;
      }
      html += `</ul><hr><p class="text-end fw-bold">Total: Rp ${total.toLocaleString()}</p>`;
      $('#cartContent').html(html);
    }
  });
</script>