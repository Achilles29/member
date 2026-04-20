<?php
// Framework7-based order UI (senada dengan halaman member lain).
// Cart disimpan ke session (AJAX: order/save_cart) + localStorage (anti hilang kalau tab ditutup).
?>

<div class="page-content nm-page nm-order">
  <div class="nm-topbar nm-topbar--mini">
    <div>
      <div class="nm-name">Order</div>
      <div class="nm-level">
        <?php if (!empty($nomor_meja)): ?>
          Meja <?= html_escape($nomor_meja) ?>
        <?php else: ?>
          Scan QR meja dulu ya
        <?php endif; ?>
      </div>
    </div>
    <a class="nm-logout" href="<?= site_url('member/logout') ?>" title="Logout">
      <i class="f7-icons">rectangle_porous_arrow_right</i>
    </a>
  </div>

  <?php if (!empty($this->session->flashdata('error'))): ?>
    <div class="nm-card" style="margin-top:-22px;">
      <div class="nm-alert nm-alert--danger">
        <?= html_escape((string) $this->session->flashdata('error')) ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="nm-card nm-order__search" style="margin-top:-22px;">
    <div class="nm-order__searchRow">
      <i class="f7-icons" aria-hidden="true">search</i>
      <input id="nmSearch" type="search" placeholder="Cari menu..." autocomplete="off">
    </div>
  </div>

  <div class="nm-card nm-order__category" style="margin-top:10px;">
    <button type="button" class="nm-catbtn" id="nmOpenCategories" aria-label="Pilih kategori">
      <div>
        <div class="nm-catbtn__label">Kategori</div>
        <div class="nm-catbtn__value" id="nmActiveCategory">Semua</div>
      </div>
      <i class="f7-icons" aria-hidden="true">chevron_down</i>
    </button>
  </div>

  <div id="nmProduk">
    <?php foreach (($kategori ?? []) as $idx => $kat): ?>
      <div class="nm-order__section" data-kat-id="<?= (int) $kat->id ?>">
        <div class="nm-section-head">
          <div>
            <div class="nm-section-title"><?= html_escape($kat->nama_kategori) ?></div>
            <div class="nm-section-sub">Tap menu untuk tambah ke keranjang</div>
          </div>
        </div>

        <div class="nm-order__grid">
          <?php foreach (($produk_per_kategori[$kat->id] ?? []) as $p): ?>
            <?php
              // Samakan dengan dashboard/beranda:
              // - stok <= 0 => HABIS
              // - stok <= 10 => KRITIS
              $stok_tersedia = (float) ($p->stok_tersedia ?? 0);
              $is_habis = ($stok_tersedia <= 0);
              $is_kritis = (!$is_habis && $stok_tersedia <= 10);
            ?>
            <button
              type="button"
              class="nm-order__item <?= $is_habis ? 'is-disabled' : '' ?>"
              data-produk-id="<?= (int) $p->id ?>"
              data-produk-nama="<?= html_escape($p->nama_produk) ?>"
              data-produk-harga="<?= (float) $p->harga_jual ?>"
              data-produk-foto="<?= html_escape((string) ($p->foto ?? '')) ?>"
              <?= $is_habis ? 'disabled aria-disabled="true"' : '' ?>
            >
              <div class="nm-order__imgWrap">
                <img
                  loading="lazy"
                  src="https://dashboard.namuacoffee.com/uploads/produk/<?= html_escape((string) ($p->foto ?? '')) ?>"
                  alt="<?= html_escape($p->nama_produk) ?>"
                >
                <?php if ($is_habis): ?>
                  <div class="nm-order__badge nm-order__badge--habis">HABIS</div>
                <?php elseif ($is_kritis): ?>
                  <div class="nm-order__badge nm-order__badge--limited">KRITIS</div>
                <?php endif; ?>
              </div>
              <div class="nm-order__meta">
                <div class="nm-order__name"><?= html_escape($p->nama_produk) ?></div>
                <div class="nm-order__price">Rp <?= number_format((float) $p->harga_jual, 0, ',', '.') ?></div>
                <?php if ($is_habis): ?>
                  <div class="nm-order__stockinfo nm-order__stockinfo--habis">HABIS</div>
                <?php elseif ($is_kritis): ?>
                  <div class="nm-order__stockinfo nm-order__stockinfo--limited">KRITIS</div>
                <?php endif; ?>
              </div>
              <div class="nm-order__add">
                <?php if ($is_habis): ?>
                  <span>Tidak tersedia</span>
                  <i class="f7-icons" aria-hidden="true">lock</i>
                <?php else: ?>
                  <span>Tambah</span>
                  <i class="f7-icons" aria-hidden="true">plus</i>
                <?php endif; ?>
              </div>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Bottom cart bar (di atas tabbar) -->
  <div class="nm-cartbar" id="nmCartBar" hidden>
    <button type="button" class="nm-cartbar__btn" id="nmOpenCart">
      <div class="nm-cartbar__left">
        <div class="nm-cartbar__badge" id="nmCartCount">0</div>
        <div>
          <div class="nm-cartbar__title">Keranjang</div>
          <div class="nm-cartbar__sub" id="nmCartSub">0 item</div>
        </div>
      </div>
      <div class="nm-cartbar__right">
        <div class="nm-cartbar__total" id="nmCartTotal">Rp 0</div>
        <i class="f7-icons" aria-hidden="true">chevron_up</i>
      </div>
    </button>
  </div>

  <!-- Cart sheet -->
  <div class="nm-sheet" id="nmCartSheet" hidden>
    <div class="nm-sheet__backdrop" id="nmSheetBackdrop"></div>
    <div class="nm-sheet__panel" role="dialog" aria-modal="true" aria-label="Keranjang">
      <div class="nm-sheet__handle"></div>
      <div class="nm-sheet__head">
        <div class="nm-sheet__title">Keranjang</div>
        <button type="button" class="nm-iconbtn" id="nmCloseCart" aria-label="Tutup">
          <i class="f7-icons">xmark</i>
        </button>
      </div>

      <div class="nm-sheet__content">
        <div id="nmCartList"></div>
        <div class="nm-sheet__summary">
          <div class="nm-sheet__sumRow">
            <span>Total</span>
            <strong id="nmSheetTotal">Rp 0</strong>
          </div>
          <div class="nm-sheet__actions">
            <button type="button" class="nm-btn nm-btn--ghost" id="nmClearCart">Kosongkan</button>
            <button type="button" class="nm-btn nm-btn--primary" id="nmToReview">Lanjut</button>
          </div>
          <div class="nm-sheet__hint">Kalau kamu tutup halaman lalu scan lagi, keranjang tetap ada.</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Add item popup -->
  <div class="nm-popup" id="nmPopup" hidden>
    <div class="nm-popup__backdrop" id="nmPopupBackdrop"></div>
    <div class="nm-popup__panel" role="dialog" aria-modal="true" aria-label="Tambah menu">
      <div class="nm-popup__head">
        <div>
          <div class="nm-popup__title" id="nmPopName">Menu</div>
          <div class="nm-popup__price" id="nmPopPrice">Rp 0</div>
        </div>
        <button type="button" class="nm-iconbtn" id="nmPopClose" aria-label="Tutup">
          <i class="f7-icons">xmark</i>
        </button>
      </div>

      <div class="nm-popup__body">
        <div class="nm-popup__row">
          <span>Jumlah</span>
          <div class="nm-stepper">
            <button type="button" class="nm-stepper__btn" id="nmQtyMinus" aria-label="Kurangi">-</button>
            <input id="nmQty" type="number" min="1" value="1">
            <button type="button" class="nm-stepper__btn" id="nmQtyPlus" aria-label="Tambah">+</button>
          </div>
        </div>

        <div class="nm-popup__extrasToggle">
          <button type="button" class="nm-btn nm-btn--ghost nm-btn--block" id="nmToggleExtras" hidden>
            <i class="f7-icons" aria-hidden="true">plus_circle</i>
            <span id="nmToggleExtrasText">Tambah Extra</span>
          </button>
        </div>

        <div class="nm-popup__extras" id="nmExtrasWrap" hidden>
          <div class="nm-popup__extrasHead">Extra</div>
          <div class="nm-popup__extrasList" id="nmExtrasList"></div>
        </div>
      </div>

      <div class="nm-popup__foot">
        <button type="button" class="nm-btn nm-btn--primary nm-btn--block" id="nmAddToCart">Tambah ke keranjang</button>
      </div>
    </div>
  </div>

  <!-- Category sheet -->
  <div class="nm-sheet nm-sheet--cat" id="nmCatSheet" hidden>
    <div class="nm-sheet__backdrop" id="nmCatBackdrop"></div>
    <div class="nm-sheet__panel" id="nmCatPanel" role="dialog" aria-modal="true" aria-label="Kategori">
      <div class="nm-sheet__handle"></div>
      <div class="nm-sheet__head">
        <div class="nm-sheet__title">Kategori</div>
        <button type="button" class="nm-iconbtn" id="nmCloseCat" aria-label="Tutup">
          <i class="f7-icons">xmark</i>
        </button>
      </div>
      <div class="nm-sheet__content">
        <div class="nm-catlist" id="nmCatList">
          <?php foreach (($kategori ?? []) as $kat): ?>
            <button
              type="button"
              class="nm-catitem"
              data-kat-id="<?= (int) $kat->id ?>"
              data-kat-nama="<?= html_escape($kat->nama_kategori) ?>"
            >
              <?= html_escape($kat->nama_kategori) ?>
              <i class="f7-icons" aria-hidden="true">chevron_right</i>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <?php $this->load->view('templates/member/bottom_nav'); ?>
</div>

<script>
  (function () {
    const BASE_URL = <?= json_encode(base_url(), JSON_UNESCAPED_SLASHES) ?>;
    const MEJA_ID = <?= (int) ($meja_id ?? 0) ?>;
    const SERVER_DRAFT = <?= json_encode(is_array($draft_cart ?? null) ? $draft_cart : [], JSON_UNESCAPED_SLASHES) ?>;
    const SERVER_STEP = <?= json_encode((string) ($flow_step ?? 'menu')) ?>;
    const EXTRA_LOOKUP = <?= json_encode(array_values(array_map(static function ($ex) {
      return [
        'id' => (int) ($ex['id'] ?? 0),
        'nama_extra' => (string) ($ex['nama_extra'] ?? ''),
        'harga' => (float) ($ex['harga'] ?? 0),
      ];
    }, (array)($extras ?? []))), JSON_UNESCAPED_SLASHES) ?>;

    const CART_KEY = 'nm_order_cart_v1_' + String(MEJA_ID || 0);
    const STEP_KEY = 'nm_order_step_v1_' + String(MEJA_ID || 0);

    const elSearch = document.getElementById('nmSearch');
    const elProduk = document.getElementById('nmProduk');

    const elCartBar = document.getElementById('nmCartBar');
    const elCartCount = document.getElementById('nmCartCount');
    const elCartSub = document.getElementById('nmCartSub');
    const elCartTotal = document.getElementById('nmCartTotal');

    const elSheet = document.getElementById('nmCartSheet');
    const elSheetBackdrop = document.getElementById('nmSheetBackdrop');
    const elCartList = document.getElementById('nmCartList');
    const elSheetTotal = document.getElementById('nmSheetTotal');

    const elPopup = document.getElementById('nmPopup');
    const elPopupBackdrop = document.getElementById('nmPopupBackdrop');
    const elPopName = document.getElementById('nmPopName');
    const elPopPrice = document.getElementById('nmPopPrice');
    const elQty = document.getElementById('nmQty');
    const elExtrasWrap = document.getElementById('nmExtrasWrap');
    const elToggleExtras = document.getElementById('nmToggleExtras');
    const elToggleExtrasText = document.getElementById('nmToggleExtrasText');
    const elExtrasList = document.getElementById('nmExtrasList');

    const elActiveCategory = document.getElementById('nmActiveCategory');
    const elCatSheet = document.getElementById('nmCatSheet');
    const elCatBackdrop = document.getElementById('nmCatBackdrop');
    const elCatPanel = document.getElementById('nmCatPanel');
    const elCatList = document.getElementById('nmCatList');

    const currency = (n) => 'Rp ' + (Number(n || 0)).toLocaleString('id-ID');
    const extraLookupMap = {};
    (EXTRA_LOOKUP || []).forEach((ex) => {
      const id = Number(ex && ex.id || 0);
      if (id > 0) {
        extraLookupMap[id] = {
          nama: String(ex.nama_extra || ''),
          harga: Number(ex.harga || 0),
        };
      }
    });

    const getProdukNode = (produkId) => document.querySelector('[data-produk-id="' + String(produkId) + '"]');
    const getProdukMeta = (produkId) => {
      const btn = getProdukNode(produkId);
      if (!btn) return null;
      const harga = Number(btn.getAttribute('data-produk-harga') || 0);
      return {
        id: Number(btn.getAttribute('data-produk-id')),
        nama: btn.getAttribute('data-produk-nama') || '',
        harga: harga,
      };
    };

    const normalizeCart = (cart) => {
      const out = {};
      if (!cart || typeof cart !== 'object') return out;
      for (const [k, row] of Object.entries(cart)) {
        const id = Number(k);
        const jumlah = Number(row && row.jumlah || 0);
        if (!Number.isFinite(id) || id <= 0) continue;
        if (!Number.isFinite(jumlah) || jumlah <= 0) continue;
        const extra_ids = Array.isArray(row.extra_ids) ? row.extra_ids.map(Number).filter((x) => Number.isFinite(x) && x > 0) : [];
        out[String(id)] = { jumlah: Math.floor(jumlah), extra_ids: extra_ids };
      }
      return out;
    };

    const cartCount = (cart) => Object.values(cart).reduce((acc, it) => acc + Number(it.jumlah || 0), 0);

    // Cart internal: 1 baris per produk (sengaja, supaya konsisten dengan struktur cart server).
    // Bentuk: { [produk_id]: { produk_id, jumlah, extra_ids } }
    let cart = {};
    let step = 'menu';

    const loadLocal = () => {
      try {
        const raw = localStorage.getItem(CART_KEY);
        const st = localStorage.getItem(STEP_KEY);
        const parsed = raw ? JSON.parse(raw) : null;
        return { cart: parsed, step: st || null };
      } catch (_) {
        return { cart: null, step: null };
      }
    };

    const saveLocal = () => {
      try {
        localStorage.setItem(CART_KEY, JSON.stringify(cart));
        localStorage.setItem(STEP_KEY, step);
      } catch (_) {}
    };

    const mergeServerDraft = (serverDraft) => {
      // serverDraft dari CI: { produk_id: {jumlah, extra_ids} }
      const d = normalizeCart(serverDraft);
      const merged = {};
      for (const [produkId, row] of Object.entries(d)) {
        merged[String(produkId)] = { produk_id: Number(produkId), jumlah: row.jumlah, extra_ids: row.extra_ids };
      }
      return merged;
    };

    const computeTotals = () => {
      let total = 0;
      for (const row of Object.values(cart)) {
        const meta = getProdukMeta(row.produk_id);
        if (!meta) continue;
        total += meta.harga * Number(row.jumlah || 0);
        // extras price dari lookup map (disinkronkan server).
        const extraIds = Array.isArray(row.extra_ids) ? row.extra_ids : [];
        for (const exId of extraIds) {
          const exMeta = extraLookupMap[Number(exId)] || null;
          const exHarga = exMeta ? Number(exMeta.harga || 0) : 0;
          total += exHarga * Number(row.jumlah || 0);
        }
      }
      return total;
    };

    const toServerCart = () => {
      const out = {};
      for (const [pid, row] of Object.entries(cart)) {
        out[String(pid)] = { jumlah: Number(row.jumlah || 0), extra_ids: Array.isArray(row.extra_ids) ? row.extra_ids : [] };
      }
      return out;
    };

    const saveServer = async (forceStep) => {
      const payload = { cart: toServerCart(), step: forceStep || step };
      try {
        await fetch(BASE_URL + 'order/save_cart', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
          credentials: 'same-origin',
        });
      } catch (_) {}
    };

    const renderCartBar = () => {
      const cnt = cartCount(cart);
      const total = computeTotals();
      if (cnt <= 0) {
        elCartBar.hidden = true;
        return;
      }
      elCartBar.hidden = false;
      elCartCount.textContent = String(cnt);
      elCartSub.textContent = String(cnt) + ' item';
      elCartTotal.textContent = currency(total);
      elSheetTotal.textContent = currency(total);
    };

	    const renderCartSheet = () => {
	      if (Object.keys(cart).length === 0) {
	        elCartList.innerHTML = '<div class="nm-empty">Keranjang masih kosong.</div>';
	        renderCartBar();
	        return;
	      }

      const rows = Object.entries(cart).map(([k, row]) => {
        const meta = getProdukMeta(row.produk_id);
        if (!meta) return '';
        const extras = (row.extra_ids || []).map((exId) => {
          const exMeta = extraLookupMap[Number(exId)] || null;
          const nm = exMeta ? (exMeta.nama || '') : '';
          return nm ? nm : ('Extra #' + String(exId));
        });
        const extraLabel = extras.length ? ('<div class="nm-cartitem__extras">+' + extras.map((x) => escapeHtml(x)).join(', ') + '</div>') : '';
	        return (
	          '<div class="nm-cartitem">' +
	            '<div class="nm-cartitem__main">' +
	              '<div class="nm-cartitem__name">' + escapeHtml(meta.nama) + '</div>' +
	              extraLabel +
	              '<div class="nm-cartitem__price">' + currency(meta.harga) + '</div>' +
	            '</div>' +
	            '<div class="nm-cartitem__ctrl">' +
	              '<div class="nm-cartstep" aria-label="Ubah jumlah">' +
	                '<button type="button" class="nm-mini" data-act="minus" data-key="' + escapeAttr(k) + '" aria-label="Kurangi">-</button>' +
	                '<div class="nm-cartitem__qty" aria-label="Jumlah">' + String(row.jumlah) + '</div>' +
	                '<button type="button" class="nm-mini" data-act="plus" data-key="' + escapeAttr(k) + '" aria-label="Tambah">+</button>' +
	              '</div>' +
	              '<button type="button" class="nm-mini nm-mini--danger nm-cartitem__del" data-act="del" data-key="' + escapeAttr(k) + '">Hapus</button>' +
	            '</div>' +
	          '</div>'
	        );
	      }).join('');

      elCartList.innerHTML = rows;
      renderCartBar();
    };

    const openSheet = () => {
      // Track last state as "cart" (local only) so scan ulang bisa balik buka keranjang.
      step = 'cart';
      saveLocal();
      elSheet.hidden = false;
      document.body.classList.add('nm-no-scroll');
      renderCartSheet();
    };
    const closeSheet = () => {
      elSheet.hidden = true;
      document.body.classList.remove('nm-no-scroll');
      // Kalau user menutup sheet secara normal, anggap balik ke "menu".
      step = 'menu';
      saveLocal();
    };

    const openPopup = (opts) => {
      const options = opts && typeof opts === 'object' ? opts : {};
      const hasExtras = Boolean(options.hasExtras);
      const showExtras = Boolean(options.showExtras);
      elPopup.hidden = false;
      document.body.classList.add('nm-no-scroll');
      if (elToggleExtras) {
        elToggleExtras.hidden = !hasExtras;
      }
      if (elExtrasWrap) {
        elExtrasWrap.hidden = !showExtras;
      }
      if (elToggleExtrasText) {
        elToggleExtrasText.textContent = (hasExtras && showExtras) ? 'Sembunyikan Extra' : 'Tambah Extra';
      }
    };
    const closePopup = () => {
      elPopup.hidden = true;
      document.body.classList.remove('nm-no-scroll');
    };

    const escapeHtml = (s) => String(s || '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const escapeAttr = (s) => escapeHtml(s).replace(/"/g, '&quot;');

    // Init cart: merge server + local (local wins).
    const local = loadLocal();
    const fromServer = mergeServerDraft(SERVER_DRAFT);
    cart = Object.assign({}, fromServer, (local.cart && typeof local.cart === 'object' ? local.cart : {}));
    step = (local.step || SERVER_STEP || 'menu');
    saveLocal();

    // Auto-resume client-side kalau session hilang tapi localStorage masih ada step.
    // Supaya "scan lagi" langsung lanjut tanpa kehilangan keranjang.
    const autoResume = async () => {
      const hasItems = Object.keys(cart).length > 0;
      if (!hasItems) return;

      if (step === 'pay') {
        await saveServer('pay');
        window.location.href = BASE_URL + 'order/pay';
        return;
      }
      if (step === 'review') {
        await saveServer('review');
        window.location.href = BASE_URL + 'order/review_session';
        return;
      }
    };

    // Render initial
    renderCartBar();

    // Kategori: semua produk tetap tampil (dropdown hanya untuk lompat/scroll).
    (function initCategoryScrollAndDropdown() {
      const sections = Array.from(document.querySelectorAll('.nm-order__section'));
      if (!sections.length) return;

      const getKatName = (katId) => {
        if (!katId) return '';
        const btn = document.querySelector('.nm-catitem[data-kat-id="' + String(katId) + '"]');
        // Jangan pakai textContent karena icon Framework7 ikut kebaca ("chevron_right").
        return btn ? String(btn.getAttribute('data-kat-nama') || '').trim() : '';
      };

      const setActiveCategory = (katId) => {
        const nm = getKatName(katId);
        if (elActiveCategory) elActiveCategory.textContent = nm || 'Semua';
      };

      // Scroll container (Framework7 pakai .page-content sebagai scroller).
      const scrollContainer = (elProduk && elProduk.closest('.page-content')) || document.querySelector('.page-content') || document.scrollingElement || document.documentElement;

      // Aktifkan chip berdasarkan posisi scroll.
      if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries) => {
          // Cari section yang paling "visible" di area atas.
          const visible = entries
            .filter((e) => e.isIntersecting)
            .sort((a, b) => (b.intersectionRatio || 0) - (a.intersectionRatio || 0))[0];
          if (visible && visible.target) {
            setActiveCategory(visible.target.getAttribute('data-kat-id'));
          }
        }, { root: scrollContainer === document.documentElement ? null : scrollContainer, threshold: [0.15, 0.25, 0.35], rootMargin: '-120px 0px -60% 0px' });

        sections.forEach((sec) => io.observe(sec));
      }

      const openCatSheet = () => {
        if (!elCatSheet) return;
        elCatSheet.hidden = false;
        document.body.classList.add('nm-no-scroll');
      };
      const closeCatSheet = () => {
        if (!elCatSheet) return;
        elCatSheet.hidden = true;
        document.body.classList.remove('nm-no-scroll');
      };

      // Dropdown kategori (sheet)
      const elOpenCategories = document.getElementById('nmOpenCategories');
      if (elOpenCategories) elOpenCategories.addEventListener('click', openCatSheet);
      if (document.getElementById('nmCloseCat')) document.getElementById('nmCloseCat').addEventListener('click', closeCatSheet);
      if (elCatBackdrop) elCatBackdrop.addEventListener('click', closeCatSheet);

      // Klik item kategori = scroll ke section
      const scrollToSection = (sec) => {
        if (!sec) return;
        const doScroll = () => {
          if (scrollContainer && typeof scrollContainer.scrollTo === 'function') {
            const containerTop = scrollContainer.getBoundingClientRect ? scrollContainer.getBoundingClientRect().top : 0;
            const secTop = sec.getBoundingClientRect ? sec.getBoundingClientRect().top : 0;
            const currentTop = scrollContainer.scrollTop || 0;
            const targetTop = currentTop + (secTop - containerTop) - 10;
            scrollContainer.scrollTo({ top: targetTop, behavior: 'smooth' });
          } else {
            sec.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        };

        // iOS kadang perlu delay setelah sheet ditutup.
        requestAnimationFrame(() => {
          doScroll();
          setTimeout(doScroll, 50);
        });
      };

      if (elCatList) elCatList.addEventListener('click', (e) => {
        const btn = e.target.closest('.nm-catitem');
        if (!btn) return;
        const katId = btn.getAttribute('data-kat-id');
        setActiveCategory(katId);
        const sec = document.querySelector('.nm-order__section[data-kat-id="' + String(katId) + '"]');
        closeCatSheet();
        setTimeout(() => scrollToSection(sec), 50);
      });

      // Swipe down to close (biar sheet bisa "ditutup digeser")
      if (elCatPanel) {
        let startY = 0;
        let moved = 0;
        elCatPanel.addEventListener('touchstart', (e) => {
          const t = e.touches && e.touches[0];
          startY = t ? t.clientY : 0;
          moved = 0;
        }, { passive: true });
        elCatPanel.addEventListener('touchmove', (e) => {
          const t = e.touches && e.touches[0];
          const y = t ? t.clientY : 0;
          moved = Math.max(0, y - startY);
        }, { passive: true });
        elCatPanel.addEventListener('touchend', () => {
          if (moved > 80) closeCatSheet();
          startY = 0;
          moved = 0;
        });
      }

      // Init label awal
      const first = sections[0] ? sections[0].getAttribute('data-kat-id') : null;
      if (first) setActiveCategory(first);
    })();

    // Save server on load (best-effort)
    saveServer(step);
    autoResume();

    // Kalau terakhir user ada di keranjang, buka sheet otomatis.
    if (step === 'cart' && Object.keys(cart).length > 0) {
      openSheet();
    }

    // Search filter (menyaring item, bukan kategori)
    elSearch.addEventListener('input', () => {
      const q = String(elSearch.value || '').trim().toLowerCase();
      document.querySelectorAll('.nm-order__item').forEach((it) => {
        const nm = (it.getAttribute('data-produk-nama') || '').toLowerCase();
        it.style.display = (!q || nm.includes(q)) ? '' : 'none';
      });

      // Saat search, sembunyikan judul kategori biar tidak penuh layar.
      document.querySelectorAll('.nm-section-head').forEach((h) => {
        h.style.display = q ? 'none' : '';
      });

      // Sembunyikan section yang tidak punya item terlihat.
      document.querySelectorAll('.nm-order__section').forEach((sec) => {
        const anyVisible = Array.from(sec.querySelectorAll('.nm-order__item')).some((it) => it.style.display !== 'none');
        sec.style.display = anyVisible ? '' : 'none';
      });
    });

    const fetchExtraGroups = async (produkId) => {
      try {
        const res = await fetch(
          BASE_URL + 'order/get_extra_options_produk?produk_id=' + encodeURIComponent(String(produkId)),
          { credentials: 'same-origin' }
        );
        if (!res.ok) return [];
        const json = await res.json();
        return Array.isArray(json && json.groups) ? json.groups : [];
      } catch (_) {
        return [];
      }
    };

    const getSelectedExtraIdsFromPopup = () =>
      Array.from(document.querySelectorAll('.nm-extra-option:checked'))
        .map((x) => Number(x.value))
        .filter((x) => x > 0);

    const validateExtraGroups = (groups) => {
      for (const g of (groups || [])) {
        const gid = Number(g && g.id || 0);
        const min = Number(g && g.min_pilih || 0);
        const max = Math.max(1, Number(g && g.max_pilih || 1));
        const name = String(g && g.nama_group || 'Group Extra');
        const cnt = document.querySelectorAll('.nm-extra-option[data-group-id="' + String(gid) + '"]:checked').length;
        if (min > 0 && cnt < min) {
          return { ok: false, message: 'Pilihan extra untuk "' + name + '" minimal ' + String(min) + '.' };
        }
        if (cnt > max) {
          return { ok: false, message: 'Pilihan extra untuk "' + name + '" maksimal ' + String(max) + '.' };
        }
      }
      return { ok: true, message: '' };
    };

    const renderExtraGroups = (groups, preselectedIds) => {
      if (!elExtrasList) return;
      const selected = new Set((preselectedIds || []).map((x) => Number(x)));
      let html = '';
      (groups || []).forEach((g) => {
        const gid = Number(g && g.id || 0);
        const min = Number(g && g.min_pilih || 0);
        const max = Math.max(1, Number(g && g.max_pilih || 1));
        const nama = escapeHtml(String(g && g.nama_group || 'Extra'));
        const items = Array.isArray(g && g.items) ? g.items : [];
        html += `<div style="margin-bottom:12px;">
          <div style="font-weight:600; margin-bottom:4px;">${nama}</div>
          <div style="font-size:12px; color:#666; margin-bottom:6px;">Min ${min} / Max ${max}</div>`;
        items.forEach((it) => {
          const eid = Number(it && it.id || 0);
          const nm = escapeHtml(String(it && it.nama_extra || 'Extra'));
          const harga = Number(it && it.harga || 0);
          extraLookupMap[eid] = { nama: String(it && it.nama_extra || ''), harga: harga };
          html += `<label class="nm-check">
            <input class="nm-extra-option" type="checkbox" value="${eid}" data-group-id="${gid}" data-max="${max}" ${selected.has(eid) ? 'checked' : ''}>
            <span>${nm}<small>+${currency(harga)}</small></span>
          </label>`;
        });
        html += `</div>`;
      });
      elExtrasList.innerHTML = html || '<div class="nm-empty">Produk ini tidak punya extra.</div>';

      document.querySelectorAll('.nm-extra-option').forEach((el) => {
        el.addEventListener('change', (ev) => {
          const gid = ev.target.getAttribute('data-group-id');
          const max = Math.max(1, Number(ev.target.getAttribute('data-max') || 1));
          const checked = Array.from(document.querySelectorAll('.nm-extra-option[data-group-id="' + String(gid) + '"]:checked'));
          if (checked.length > max) {
            ev.target.checked = false;
            alert('Maksimal pilihan extra untuk group ini ' + String(max) + '.');
          }
        });
      });
    };

    // Popup add product
    let currentProduk = null;
    let currentExtraGroups = [];
    elProduk.addEventListener('click', async (e) => {
      const btn = e.target.closest('.nm-order__item');
      if (!btn) return;
      if (btn.disabled || btn.getAttribute('aria-disabled') === 'true') return;
      const pid = Number(btn.getAttribute('data-produk-id') || 0);
      const nm = btn.getAttribute('data-produk-nama') || '';
      const harga = Number(btn.getAttribute('data-produk-harga') || 0);

      currentProduk = { id: pid, nama: nm, harga: harga };
      elPopName.textContent = nm;
      elPopPrice.textContent = currency(harga);
      elQty.value = '1';

      const row = cart[String(pid)] || null;
      const preselected = Array.isArray(row && row.extra_ids) ? row.extra_ids : [];
      currentExtraGroups = await fetchExtraGroups(pid);
      renderExtraGroups(currentExtraGroups, preselected);
      const hasExtras = Boolean(currentExtraGroups && currentExtraGroups.length > 0);
      openPopup({
        hasExtras: hasExtras,
        showExtras: hasExtras
      });
    });

    // Toggle extras supaya modal tidak kepanjangan
    if (elToggleExtras && elExtrasWrap) {
      elToggleExtras.addEventListener('click', () => {
        const willShow = Boolean(elExtrasWrap.hidden);
        elExtrasWrap.hidden = !willShow;
        if (elToggleExtrasText) elToggleExtrasText.textContent = willShow ? 'Sembunyikan Extra' : 'Tambah Extra';
        if (willShow) {
          setTimeout(() => {
            // Scroll di dalam modal body (lebih stabil daripada scrollIntoView global).
            const body = elExtrasWrap.closest('.nm-popup__body');
            if (body) {
              body.scrollTo({ top: Math.max(0, elExtrasWrap.offsetTop - 8), behavior: 'smooth' });
            } else {
              elExtrasWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
          }, 50);
        }
      });
    }

    document.getElementById('nmPopClose').addEventListener('click', closePopup);
    elPopupBackdrop.addEventListener('click', closePopup);
    document.getElementById('nmQtyMinus').addEventListener('click', () => {
      const v = Math.max(1, Number(elQty.value || 1) - 1);
      elQty.value = String(v);
    });
    document.getElementById('nmQtyPlus').addEventListener('click', () => {
      const v = Math.max(1, Number(elQty.value || 1) + 1);
      elQty.value = String(v);
    });

	    document.getElementById('nmAddToCart').addEventListener('click', async () => {
	      if (!currentProduk || !currentProduk.id) return;
	      const jumlah = Math.max(1, Math.floor(Number(elQty.value || 1)));
        const check = validateExtraGroups(currentExtraGroups);
        if (!check.ok) {
          alert(check.message || 'Pilihan extra belum sesuai aturan.');
          return;
        }
	      const extraIds = getSelectedExtraIdsFromPopup();
	      cart[String(currentProduk.id)] = { produk_id: currentProduk.id, jumlah: jumlah, extra_ids: extraIds };
	      step = 'menu';
	      saveLocal();
	      renderCartBar();
	      await saveServer('menu');
	      closePopup();
	      // Jangan langsung buka keranjang setelah tambah item:
	      // biar user tetap di daftar menu dan bisa pilih item lain.
	    });

    // Sheet open/close
    document.getElementById('nmOpenCart').addEventListener('click', openSheet);
    document.getElementById('nmCloseCart').addEventListener('click', closeSheet);
    elSheetBackdrop.addEventListener('click', closeSheet);

    // Sheet actions
    elCartList.addEventListener('click', async (e) => {
      const btn = e.target.closest('button[data-act]');
      if (!btn) return;
      const act = btn.getAttribute('data-act');
      const k = btn.getAttribute('data-key');
      if (!k || !cart[k]) return;

      if (act === 'plus') cart[k].jumlah += 1;
      if (act === 'minus') cart[k].jumlah = Math.max(1, cart[k].jumlah - 1);
      if (act === 'del') delete cart[k];

      step = 'menu';
      saveLocal();
      renderCartSheet();
      await saveServer('menu');
    });

    document.getElementById('nmClearCart').addEventListener('click', async () => {
      cart = {};
      step = 'menu';
      saveLocal();
      renderCartSheet();
      await saveServer('menu');
      closeSheet();
    });

    document.getElementById('nmToReview').addEventListener('click', async () => {
      if (Object.keys(cart).length === 0) return;
      step = 'review';
      saveLocal();
      await saveServer('review');
      window.location.href = BASE_URL + 'order/review_session';
    });
  })();
</script>
