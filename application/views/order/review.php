<?php
$order_base_path = $order_base_path ?? 'order';
$order_storage_suffix = $order_storage_suffix ?? ('self_' . (int) ($this->session->userdata('order_meja_id') ?? 0));
$is_online_order = !empty($is_online_order);
$delivery_quote = is_array($delivery_quote ?? null) ? $delivery_quote : [];
$saved_locations = is_array($saved_locations ?? null) ? $saved_locations : [];
$delivery_config = is_array($delivery_config ?? null) ? $delivery_config : [];
$delivery_fee = (float) ($delivery_quote['delivery_fee'] ?? 0);
$grand_total = (float) ($delivery_quote['grand_total'] ?? ((float) ($total ?? 0) + $delivery_fee));
?>
<?php if ($is_online_order): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<style>
  .nm-delivery-map { height: 290px; border-radius: 14px; overflow: hidden; border: 1px solid rgba(54, 42, 35, .14); background: #eef2ec; margin-top: 10px; }
  .nm-delivery-tools { display: grid; grid-template-columns: 1fr auto; gap: 8px; margin-top: 10px; }
  .nm-delivery-tools input { width: 100%; min-height: 44px; border: 1px solid rgba(54,42,35,.16); border-radius: 12px; padding: 0 12px; font-size: 14px; }
  .nm-delivery-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px; }
  .nm-delivery-quote { display: grid; gap: 8px; margin-top: 10px; }
  .nm-delivery-quote__row { display: flex; justify-content: space-between; gap: 12px; font-size: 14px; color: #51453f; }
  .nm-delivery-quote__row strong { color: #2f2925; }
  .nm-delivery-alert { color: #a64022; font-size: 13px; line-height: 1.45; margin-top: 8px; }
  .nm-location-list { display: flex; gap: 8px; overflow-x: auto; padding: 8px 0 2px; }
  .nm-location-chip { border: 1px solid rgba(54,42,35,.16); background: #fff; border-radius: 12px; padding: 8px 10px; min-width: 150px; text-align: left; color: #3c332e; }
  .nm-location-chip.is-active { border-color: #1f6f58; box-shadow: 0 0 0 2px rgba(31,111,88,.12); }
  .nm-location-chip strong { display: block; font-size: 13px; }
  .nm-location-chip span { display: block; font-size: 11px; color: #766b64; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .nm-search-results { border: 1px solid rgba(54,42,35,.14); border-radius: 12px; overflow: hidden; margin-top: 8px; background: #fff; }
  .nm-search-result { width: 100%; border: 0; background: #fff; text-align: left; padding: 10px 12px; border-bottom: 1px solid rgba(54,42,35,.08); }
  .nm-search-result:last-child { border-bottom: 0; }
  .nm-search-result strong { display: block; font-size: 13px; color: #302823; }
  .nm-search-result span { display: block; font-size: 11px; color: #7a6d66; margin-top: 2px; }
  .nm-delivery-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 10px; }
  .nm-delivery-fields input { width: 100%; min-height: 42px; border: 1px solid rgba(54,42,35,.16); border-radius: 12px; padding: 0 12px; font-size: 13px; }
  .nm-delivery-save { display: flex; align-items: center; gap: 8px; margin-top: 10px; font-size: 13px; color: #51453f; }
  @media (max-width: 420px) {
    .nm-delivery-tools { grid-template-columns: 1fr; }
    .nm-delivery-actions { grid-template-columns: 1fr; }
    .nm-delivery-fields { grid-template-columns: 1fr; }
  }
</style>
<?php endif; ?>
<div class="page-content nm-page nm-order">
  <div class="nm-topbar nm-topbar--mini">
    <div>
      <div class="nm-name"><?= html_escape($title ?? 'Review Order') ?></div>
      <div class="nm-level">
        <?php if (!empty($nomor_meja)): ?>
          Meja <?= html_escape($nomor_meja) ?>
        <?php endif; ?>
      </div>
    </div>
    <a class="nm-logout" href="<?= site_url('member/logout') ?>" title="Logout">
      <i class="f7-icons">rectangle_porous_arrow_right</i>
    </a>
  </div>

  <div class="nm-card" style="margin-top:-22px;">
    <div class="nm-order__reviewList">
      <?php foreach (($produk_list ?? []) as $item): ?>
        <div class="nm-reviewitem">
          <div class="nm-reviewitem__left">
            <div class="nm-reviewitem__name"><?= html_escape((string) ($item['nama'] ?? '')) ?></div>
            <div class="nm-reviewitem__sub">
              <span><?= (int) ($item['jumlah'] ?? 0) ?>x</span>
              <?php if (!empty($item['extra'])): ?>
                <span class="nm-reviewitem__dot">•</span>
                <span class="nm-reviewitem__extras">+ <?= html_escape(implode(', ', array_column($item['extra'], 'nama'))) ?></span>
              <?php endif; ?>
            </div>
            <?php if (!empty($item['catatan'])): ?>
              <div class="nm-reviewitem__sub" style="margin-top:4px;">
                <span>Catatan: <?= html_escape((string) $item['catatan']) ?></span>
              </div>
            <?php endif; ?>
          </div>
          <div class="nm-reviewitem__right">
            <div class="nm-reviewitem__price">Rp <?= number_format((float) ($item['subtotal'] ?? 0), 0, ',', '.') ?></div>
            <?php if (!empty($item['extra'])): ?>
              <div class="nm-reviewitem__extraPrice">
                +Rp <?= number_format((float) (array_sum(array_column($item['extra'], 'harga')) * (int) ($item['jumlah'] ?? 0)), 0, ',', '.') ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="nm-card">
    <?php if ($is_online_order): ?>
      <div class="nm-order__hint" id="nmReviewLocationStatus">Tentukan lokasi pengantaran untuk menghitung rute dan ongkir.</div>
      <?php if (!empty($saved_locations)): ?>
        <div class="nm-location-list" id="nmSavedLocations">
          <?php foreach ($saved_locations as $loc): ?>
            <button
              type="button"
              class="nm-location-chip"
              data-location-id="<?= (int) ($loc['id'] ?? 0) ?>"
              data-lat="<?= html_escape((string) ($loc['latitude'] ?? '')) ?>"
              data-lng="<?= html_escape((string) ($loc['longitude'] ?? '')) ?>"
              data-accuracy="<?= html_escape((string) ($loc['location_accuracy'] ?? 0)) ?>"
              data-address="<?= html_escape((string) ($loc['address'] ?? '')) ?>"
              data-note="<?= html_escape((string) ($loc['address_note'] ?? '')) ?>"
              data-recipient-name="<?= html_escape((string) ($loc['recipient_name'] ?? '')) ?>"
              data-recipient-phone="<?= html_escape((string) ($loc['recipient_phone'] ?? '')) ?>"
            >
              <strong><?= html_escape((string) ($loc['label'] ?? 'Alamat')) ?></strong>
              <span><?= html_escape((string) ($loc['address'] ?? '')) ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <div class="nm-delivery-tools">
        <input type="search" id="nmDeliveryAddress" placeholder="Cari alamat atau tulis patokan..." autocomplete="off">
        <button type="button" class="nm-btn nm-btn--ghost" id="nmFindAddress">Cari</button>
      </div>
      <div class="nm-search-results" id="nmSearchResults" hidden></div>
      <div class="nm-delivery-actions">
        <button type="button" class="nm-btn nm-btn--ghost nm-btn--block" id="nmReviewEnableLocation">Lokasi saya</button>
        <button type="button" class="nm-btn nm-btn--ghost nm-btn--block" id="nmUseTypedAddress">Pakai alamat ini</button>
      </div>
      <div class="nm-delivery-fields">
        <input type="text" id="nmLocationLabel" placeholder="Label: Rumah / Kantor">
        <input type="text" id="nmRecipientName" placeholder="Nama penerima">
        <input type="tel" id="nmRecipientPhone" placeholder="Nomor HP penerima">
        <input type="text" id="nmAddressNote" placeholder="Catatan/patokan">
      </div>
      <label class="nm-delivery-save">
        <input type="checkbox" id="nmSaveLocation" value="1">
        <span>Simpan lokasi ini</span>
      </label>
      <div class="nm-delivery-map" id="nmDeliveryMap"></div>
      <div class="nm-delivery-alert" id="nmDeliveryAlert" hidden></div>
      <div class="nm-delivery-quote" id="nmDeliveryQuote" hidden>
        <div class="nm-delivery-quote__row">
          <span>Jarak rute</span>
          <strong id="nmDeliveryDistance">-</strong>
        </div>
        <div class="nm-delivery-quote__row">
          <span>Estimasi waktu</span>
          <strong id="nmDeliveryDuration">-</strong>
        </div>
        <div class="nm-delivery-quote__row">
          <span>Ongkir</span>
          <strong id="nmDeliveryFee">Rp 0</strong>
        </div>
      </div>
      <hr style="border:0;border-top:1px solid rgba(54,42,35,.12);margin:14px 0;">
      <div class="nm-order__totalRow">
        <span>Subtotal menu</span>
        <strong>Rp <?= number_format((float) ($total ?? 0), 0, ',', '.') ?></strong>
      </div>
      <div class="nm-order__totalRow">
        <span>Ongkir</span>
        <strong id="nmDeliveryFeeSummary">Rp <?= number_format($delivery_fee, 0, ',', '.') ?></strong>
      </div>
      <div class="nm-order__hint" id="nmDeliveryPaymentHint" style="margin-top:6px;">Ongkir dicatat terpisah dari sales POS.</div>
    <?php else: ?>
      <?php $grand_total = (float) ($total ?? 0); ?>
    <?php endif; ?>
    <div class="nm-order__totalRow">
      <span>Total</span>
      <strong id="nmGrandTotal">Rp <?= number_format($grand_total, 0, ',', '.') ?></strong>
    </div>
    <div class="nm-order__reviewActions">
      <a class="nm-btn nm-btn--ghost" id="nmAddMenu" href="<?= base_url($order_base_path . '/menu') ?>">Tambah menu</a>
      <a class="nm-btn nm-btn--primary" id="nmReviewPay" href="<?= base_url($order_base_path . '/pay') ?>">Bayar</a>
    </div>
  </div>

  <?php $this->load->view('templates/member/bottom_nav'); ?>
  <?php if ($is_online_order) $this->load->view('order/_online_whatsapp_float'); ?>
</div>

<?php if ($is_online_order): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<?php endif; ?>
<script>
  (function () {
    // Mark step di localStorage supaya scan ulang bisa langsung balik ke tahap ini/pay.
    const STORAGE_SUFFIX = <?= json_encode((string) $order_storage_suffix) ?>;
    const IS_ONLINE_ORDER = <?= $is_online_order ? 'true' : 'false' ?>;
    const STEP_KEY = 'nm_order_step_v1_' + STORAGE_SUFFIX;
    const LOCATION_KEY = 'nm_order_location_v1_' + STORAGE_SUFFIX;
    const QUOTE_KEY = 'nm_order_delivery_quote_v1_' + STORAGE_SUFFIX;
    const DELIVERY_QUOTE_URL = <?= json_encode(base_url($order_base_path . '/delivery_quote'), JSON_UNESCAPED_SLASHES) ?>;
    const SUBTOTAL = <?= json_encode((float) ($total ?? 0)) ?>;
    const SERVER_QUOTE = <?= json_encode($delivery_quote, JSON_UNESCAPED_SLASHES) ?>;
    const SAVED_LOCATIONS = <?= json_encode($saved_locations, JSON_UNESCAPED_SLASHES) ?>;
    const DELIVERY_CONFIG = <?= json_encode($delivery_config, JSON_UNESCAPED_SLASHES) ?>;
    let deliveryQuoteOk = !IS_ONLINE_ORDER;
    let selectedSavedLocationId = Number(SERVER_QUOTE && SERVER_QUOTE.saved_location_id || 0);
    let deliveryMap = null;
    let deliveryMarker = null;
    let outletMarker = null;
    let routeLayer = null;

    try { localStorage.setItem(STEP_KEY, 'review'); } catch (_) {}

    // Tombol "Tambah menu": override step jadi "menu" supaya halaman order tidak auto-resume balik ke review.
    const btn = document.getElementById('nmAddMenu');
    if (btn) {
      btn.addEventListener('click', function () {
        try { localStorage.setItem(STEP_KEY, 'menu'); } catch (_) {}
      });
    }

    function hasLocation() {
      if (!IS_ONLINE_ORDER) return true;
      try {
        const loc = JSON.parse(localStorage.getItem(LOCATION_KEY) || 'null');
        return Number.isFinite(Number(loc && loc.lat)) && Number.isFinite(Number(loc && loc.lng));
      } catch (_) {
        return false;
      }
    }
    function getLocation() {
      try {
        const loc = JSON.parse(localStorage.getItem(LOCATION_KEY) || 'null');
        const lat = Number(loc && loc.lat);
        const lng = Number(loc && loc.lng);
        if (Number.isFinite(lat) && Number.isFinite(lng)) {
          return {
            lat: lat,
            lng: lng,
            accuracy: Number(loc.accuracy || 0),
            address: String(loc.address || ''),
            at: loc.at || ''
          };
        }
      } catch (_) {}
      return null;
    }
    function saveLocation(loc) {
      try { localStorage.setItem(LOCATION_KEY, JSON.stringify(loc)); } catch (_) {}
    }
    function distanceKm(lat1, lng1, lat2, lng2) {
      const toRad = (v) => Number(v) * Math.PI / 180;
      const dLat = toRad(lat2 - lat1);
      const dLng = toRad(lng2 - lng1);
      const a = Math.pow(Math.sin(dLat / 2), 2) + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.pow(Math.sin(dLng / 2), 2);
      return 6371 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }
    function setDeliveryFields(loc) {
      if (!loc) return;
      const label = document.getElementById('nmLocationLabel');
      const recipientName = document.getElementById('nmRecipientName');
      const recipientPhone = document.getElementById('nmRecipientPhone');
      const note = document.getElementById('nmAddressNote');
      if (label && loc.label) label.value = String(loc.label || '');
      if (recipientName && loc.recipient_name) recipientName.value = String(loc.recipient_name || '');
      if (recipientPhone && loc.recipient_phone) recipientPhone.value = String(loc.recipient_phone || '');
      if (note && loc.address_note) note.value = String(loc.address_note || '');
    }
    function fieldValue(id) {
      const el = document.getElementById(id);
      return el ? String(el.value || '') : '';
    }
    function fieldChecked(id) {
      const el = document.getElementById(id);
      return !!(el && el.checked);
    }
    function currency(n) {
      return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
    }
    function setAlert(message) {
      const alert = document.getElementById('nmDeliveryAlert');
      if (!alert) return;
      alert.hidden = !message;
      alert.textContent = message || '';
    }
    function updateLocationUi() {
      const status = document.getElementById('nmReviewLocationStatus');
      const button = document.getElementById('nmReviewEnableLocation');
      if (!IS_ONLINE_ORDER || !status || !button) return;
      const ok = hasLocation();
      status.textContent = ok ? 'Lokasi pengantaran sudah dipilih. Geser pin untuk mengubah titik.' : 'Tentukan lokasi pengantaran untuk menghitung rute dan ongkir.';
    }
    function applyQuote(quote) {
      if (!IS_ONLINE_ORDER || !quote || quote.ok === false) return;
      deliveryQuoteOk = true;
      selectedSavedLocationId = Number(quote.saved_location_id || selectedSavedLocationId || 0);
      try { localStorage.setItem(QUOTE_KEY, JSON.stringify(quote)); } catch (_) {}
      if (Number.isFinite(Number(quote.customer_lat)) && Number.isFinite(Number(quote.customer_lng))) {
        saveLocation({
          lat: Number(quote.customer_lat),
          lng: Number(quote.customer_lng),
          accuracy: Number(quote.customer_location_accuracy || 0),
          saved_location_id: Number(quote.saved_location_id || selectedSavedLocationId || 0),
          recipient_name: String(quote.recipient_name || ''),
          recipient_phone: String(quote.recipient_phone || ''),
          address_note: String(quote.address_note || ''),
          address: String(quote.address || ''),
          at: new Date().toISOString()
        });
      }
      const box = document.getElementById('nmDeliveryQuote');
      const distance = document.getElementById('nmDeliveryDistance');
      const duration = document.getElementById('nmDeliveryDuration');
      const fee = document.getElementById('nmDeliveryFee');
      const feeSummary = document.getElementById('nmDeliveryFeeSummary');
      const grand = document.getElementById('nmGrandTotal');
      const hint = document.getElementById('nmDeliveryPaymentHint');
      if (box) box.hidden = false;
      if (distance) distance.textContent = Number(quote.distance_km || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' km';
      if (duration) duration.textContent = quote.duration_min ? (Number(quote.duration_min).toLocaleString('id-ID', { maximumFractionDigits: 0 }) + ' menit') : '-';
      if (fee) fee.textContent = currency(quote.delivery_fee || 0);
      if (feeSummary) feeSummary.textContent = currency(quote.delivery_fee || 0);
      if (grand) grand.textContent = currency(quote.grand_total || (SUBTOTAL + Number(quote.delivery_fee || 0)));
      if (hint) {
        hint.textContent = quote.fee_paid_by === 'FREE'
          ? ('Ongkir gratis' + (quote.free_reason ? ': ' + quote.free_reason : '') + '. Tetap tercatat terpisah dari sales POS.')
          : 'Ongkir dicatat terpisah dari sales POS dan diselesaikan sesuai pengiriman.';
      }
      drawRoute(quote);
      setAlert('');
    }
    function drawRoute(quote) {
      if (!deliveryMap || !quote) return;
      const outlet = [Number(quote.outlet_lat), Number(quote.outlet_lng)];
      const customer = [Number(quote.customer_lat), Number(quote.customer_lng)];
      if (!Number.isFinite(outlet[0]) || !Number.isFinite(outlet[1]) || !Number.isFinite(customer[0]) || !Number.isFinite(customer[1])) return;
      if (!outletMarker) outletMarker = L.marker(outlet).addTo(deliveryMap).bindPopup('Outlet');
      outletMarker.setLatLng(outlet);
      if (deliveryMarker) deliveryMarker.setLatLng(customer);
      if (routeLayer) routeLayer.remove();
      if (quote.route_geojson && quote.route_geojson.coordinates) {
        routeLayer = L.geoJSON(quote.route_geojson, { style: { color: '#1f6f58', weight: 5, opacity: .88 } }).addTo(deliveryMap);
        deliveryMap.fitBounds(routeLayer.getBounds(), { padding: [24, 24] });
      } else {
        routeLayer = L.polyline([outlet, customer], { color: '#1f6f58', weight: 4, dashArray: '8,8' }).addTo(deliveryMap);
        deliveryMap.fitBounds(routeLayer.getBounds(), { padding: [24, 24] });
      }
    }
    function requestQuote(loc) {
      if (!IS_ONLINE_ORDER || !loc) return Promise.resolve(false);
      const status = document.getElementById('nmReviewLocationStatus');
      if (status) status.textContent = 'Menghitung rute dan ongkir...';
      deliveryQuoteOk = false;
      const body = new URLSearchParams();
      body.set('customer_lat', String(loc.lat));
      body.set('customer_lng', String(loc.lng));
      body.set('customer_location_accuracy', String(loc.accuracy || 0));
      body.set('customer_address', String(loc.address || ''));
      body.set('saved_location_id', String(selectedSavedLocationId || loc.saved_location_id || 0));
      body.set('recipient_name', String(fieldValue('nmRecipientName') || loc.recipient_name || ''));
      body.set('recipient_phone', String(fieldValue('nmRecipientPhone') || loc.recipient_phone || ''));
      body.set('address_note', String(fieldValue('nmAddressNote') || loc.address_note || ''));
      body.set('location_label', String(fieldValue('nmLocationLabel') || loc.label || ''));
      body.set('save_location', fieldChecked('nmSaveLocation') ? '1' : '0');
      return fetch(DELIVERY_QUOTE_URL, {
        method: 'POST',
        body: body,
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' }
      }).then(function (res) {
        return res.json().catch(function () { return null; }).then(function (json) {
          if (!res.ok || !json || json.ok === false) {
            setAlert((json && json.message) ? json.message : 'Ongkir belum bisa dihitung. Pilih lokasi ulang.');
            updateLocationUi();
            return false;
          }
          applyQuote(json);
          updateLocationUi();
          return true;
        });
      }).catch(function () {
        setAlert('Koneksi hitung ongkir belum stabil. Coba lagi sebentar.');
        updateLocationUi();
        return false;
      });
    }
    function ensureMap() {
      if (!IS_ONLINE_ORDER || deliveryMap || typeof L === 'undefined') return;
      const el = document.getElementById('nmDeliveryMap');
      if (!el) return;
      const loc = getLocation() || { lat: -6.2, lng: 106.8166667, accuracy: 0, address: '' };
      deliveryMap = L.map(el).setView([loc.lat, loc.lng], 15);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
      }).addTo(deliveryMap);
      deliveryMarker = L.marker([loc.lat, loc.lng], { draggable: true }).addTo(deliveryMap).bindPopup('Lokasi pengantaran');
      const setPoint = function (lat, lng, address) {
        const current = getLocation() || {};
        const next = {
          lat: Number(lat),
          lng: Number(lng),
          accuracy: Number(current.accuracy || 0),
          saved_location_id: selectedSavedLocationId,
          recipient_name: String(fieldValue('nmRecipientName') || current.recipient_name || ''),
          recipient_phone: String(fieldValue('nmRecipientPhone') || current.recipient_phone || ''),
          address_note: String(fieldValue('nmAddressNote') || current.address_note || ''),
          address: String(address !== undefined ? address : (current.address || '')),
          at: new Date().toISOString()
        };
        saveLocation(next);
        deliveryMarker.setLatLng([next.lat, next.lng]);
        const addressInput = document.getElementById('nmDeliveryAddress');
        if (addressInput && next.address) addressInput.value = next.address;
        requestQuote(next);
      };
      deliveryMarker.on('dragend', function () {
        const point = deliveryMarker.getLatLng();
        setPoint(point.lat, point.lng);
      });
      deliveryMap.on('click', function (event) {
        setPoint(event.latlng.lat, event.latlng.lng);
      });
      setTimeout(function () { deliveryMap.invalidateSize(); }, 120);
      if (SERVER_QUOTE && SERVER_QUOTE.ok) {
        applyQuote(SERVER_QUOTE);
      } else if (hasLocation()) {
        requestQuote(loc);
      }
    }
    function requestLocation(done) {
      const status = document.getElementById('nmReviewLocationStatus');
      if (!navigator.geolocation) {
        if (status) status.textContent = 'Browser tidak mendukung lokasi. Gunakan browser lain untuk online order.';
        if (done) done(false);
        return;
      }
      if (status) status.textContent = 'Mengambil lokasi kamu...';
      navigator.geolocation.getCurrentPosition(function (pos) {
        const current = getLocation() || {};
        const next = {
          lat: pos.coords.latitude,
          lng: pos.coords.longitude,
          accuracy: pos.coords.accuracy || 0,
          address: String(current.address || ''),
          at: new Date().toISOString()
        };
        saveLocation(next);
        if (deliveryMarker) deliveryMarker.setLatLng([next.lat, next.lng]);
        if (deliveryMap) deliveryMap.setView([next.lat, next.lng], 16);
        updateLocationUi();
        requestQuote(next).then(function (ok) {
          if (done) done(ok);
        });
      }, function () {
        if (status) status.textContent = 'Lokasi wajib aktif. Izinkan akses lokasi lalu coba lagi.';
        if (done) done(false);
      }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 });
    }
    updateLocationUi();
    ensureMap();
    const locBtn = document.getElementById('nmReviewEnableLocation');
    if (locBtn) locBtn.addEventListener('click', function () { requestLocation(); });
    const addressInput = document.getElementById('nmDeliveryAddress');
    const currentLoc = getLocation();
    if (addressInput && currentLoc && currentLoc.address) addressInput.value = currentLoc.address;
    setDeliveryFields(currentLoc);
    const savedWrap = document.getElementById('nmSavedLocations');
    if (savedWrap) {
      savedWrap.addEventListener('click', function (event) {
        const btn = event.target.closest('.nm-location-chip');
        if (!btn) return;
        selectedSavedLocationId = Number(btn.getAttribute('data-location-id') || 0);
        savedWrap.querySelectorAll('.nm-location-chip').forEach(function (item) {
          item.classList.toggle('is-active', item === btn);
        });
        const loc = {
          lat: Number(btn.getAttribute('data-lat')),
          lng: Number(btn.getAttribute('data-lng')),
          accuracy: Number(btn.getAttribute('data-accuracy') || 0),
          saved_location_id: selectedSavedLocationId,
          label: btn.querySelector('strong') ? btn.querySelector('strong').textContent : '',
          address: String(btn.getAttribute('data-address') || ''),
          address_note: String(btn.getAttribute('data-note') || ''),
          recipient_name: String(btn.getAttribute('data-recipient-name') || ''),
          recipient_phone: String(btn.getAttribute('data-recipient-phone') || ''),
          at: new Date().toISOString()
        };
        saveLocation(loc);
        setDeliveryFields(loc);
        if (addressInput) addressInput.value = loc.address;
        if (deliveryMarker) deliveryMarker.setLatLng([loc.lat, loc.lng]);
        if (deliveryMap) deliveryMap.setView([loc.lat, loc.lng], 16);
        requestQuote(loc);
      });
    }
    const useTypedAddress = document.getElementById('nmUseTypedAddress');
    if (useTypedAddress) {
      useTypedAddress.addEventListener('click', function () {
        const loc = getLocation();
        if (!loc) {
          setAlert('Pilih titik lokasi dulu, lalu pakai alamat/patokan ini.');
          return;
        }
        loc.address = String(addressInput && addressInput.value || '').trim();
        loc.at = new Date().toISOString();
        saveLocation(loc);
        requestQuote(loc);
      });
    }
    const findAddress = document.getElementById('nmFindAddress');
    const resultBox = document.getElementById('nmSearchResults');
    let searchTimer = null;
    function chooseSearchResult(loc) {
      selectedSavedLocationId = 0;
      saveLocation(loc);
      setDeliveryFields(loc);
      if (deliveryMarker) deliveryMarker.setLatLng([loc.lat, loc.lng]);
      if (deliveryMap) deliveryMap.setView([loc.lat, loc.lng], 16);
      if (addressInput) addressInput.value = loc.address;
      if (resultBox) resultBox.hidden = true;
      requestQuote(loc);
    }
    function renderSearchResults(rows) {
      if (!resultBox) return;
      if (!rows.length) {
        resultBox.hidden = true;
        return;
      }
      resultBox.innerHTML = rows.map(function (loc, idx) {
        return '<button type="button" class="nm-search-result" data-idx="' + String(idx) + '">' +
          '<strong>' + String(loc.title || 'Lokasi').replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }) + '</strong>' +
          '<span>' + String(loc.address || '').replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }) + (loc.near_km ? ' - ' + loc.near_km.toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' km dari outlet' : '') + '</span>' +
          '</button>';
      }).join('');
      resultBox.hidden = false;
      resultBox.onclick = function (event) {
        const btn = event.target.closest('.nm-search-result');
        if (!btn) return;
        const row = rows[Number(btn.getAttribute('data-idx') || 0)];
        if (row) chooseSearchResult(row);
      };
    }
    function normalizePhotonRows(json, q) {
      return (json && Array.isArray(json.features) ? json.features : []).map(function (row) {
        const coords = row && row.geometry && Array.isArray(row.geometry.coordinates) ? row.geometry.coordinates : null;
        if (!coords || coords.length < 2) return null;
        const props = row.properties || {};
        const label = [props.name, props.street, props.city, props.county, props.state, props.country]
          .filter(function (part, idx, arr) { return part && arr.indexOf(part) === idx; })
          .join(', ');
        const lat = Number(coords[1]);
        const lng = Number(coords[0]);
        const near = Number.isFinite(Number(DELIVERY_CONFIG.outlet_lat)) && Number.isFinite(Number(DELIVERY_CONFIG.outlet_lng))
          ? distanceKm(Number(DELIVERY_CONFIG.outlet_lat), Number(DELIVERY_CONFIG.outlet_lng), lat, lng)
          : 0;
        return {
          lat: lat,
          lng: lng,
          accuracy: 0,
          saved_location_id: 0,
          title: String(props.name || props.street || 'Lokasi'),
          address: String(label || q),
          near_km: near,
          at: new Date().toISOString()
        };
      }).filter(Boolean);
    }
    function normalizeNominatimRows(json, q) {
      return (Array.isArray(json) ? json : []).map(function (row) {
        const lat = Number(row && row.lat);
        const lng = Number(row && row.lon);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
        const address = String(row.display_name || q);
        const near = Number.isFinite(Number(DELIVERY_CONFIG.outlet_lat)) && Number.isFinite(Number(DELIVERY_CONFIG.outlet_lng))
          ? distanceKm(Number(DELIVERY_CONFIG.outlet_lat), Number(DELIVERY_CONFIG.outlet_lng), lat, lng)
          : 0;
        return {
          lat: lat,
          lng: lng,
          accuracy: 0,
          saved_location_id: 0,
          title: String(row.name || address.split(',')[0] || 'Lokasi'),
          address: address,
          near_km: near,
          at: new Date().toISOString()
        };
      }).filter(Boolean);
    }
    function uniqueAndSortLocations(rows) {
      const seen = {};
      return rows.filter(function (row) {
        const key = Number(row.lat).toFixed(5) + ',' + Number(row.lng).toFixed(5);
        if (seen[key]) return false;
        seen[key] = true;
        return true;
      }).sort(function (a, b) {
        return Number(a.near_km || 0) - Number(b.near_km || 0);
      }).slice(0, 8);
    }
    function manualLocationRow(q) {
      const center = deliveryMap ? deliveryMap.getCenter() : null;
      const loc = getLocation() || {};
      const lat = Number(loc.lat || (center && center.lat) || DELIVERY_CONFIG.outlet_lat || -6.2);
      const lng = Number(loc.lng || (center && center.lng) || DELIVERY_CONFIG.outlet_lng || 106.8166667);
      const near = Number.isFinite(Number(DELIVERY_CONFIG.outlet_lat)) && Number.isFinite(Number(DELIVERY_CONFIG.outlet_lng))
        ? distanceKm(Number(DELIVERY_CONFIG.outlet_lat), Number(DELIVERY_CONFIG.outlet_lng), lat, lng)
        : 0;
      return {
        lat: lat,
        lng: lng,
        accuracy: Number(loc.accuracy || 0),
        saved_location_id: 0,
        title: 'Pakai teks ini',
        address: q + ' - geser pin jika titik belum tepat',
        near_km: near,
        at: new Date().toISOString()
      };
    }
    function searchLocations(q, quiet) {
      q = String(q || '').trim();
      if (q.length < 3) {
        if (resultBox) resultBox.hidden = true;
        return;
      }
      if (!quiet) setAlert('Mencari alamat...');
      const nominatimUrl = 'https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=8&countrycodes=id&q=' + encodeURIComponent(q);
      fetch(nominatimUrl, { headers: { 'Accept': 'application/json' } })
        .then(function (res) { return res.json(); })
        .then(function (nominatimJson) {
          const nominatimRows = normalizeNominatimRows(nominatimJson, q);
          let photonUrl = 'https://photon.komoot.io/api/?limit=6&q=' + encodeURIComponent(q);
          if (Number.isFinite(Number(DELIVERY_CONFIG.outlet_lat)) && Number.isFinite(Number(DELIVERY_CONFIG.outlet_lng))) {
            photonUrl += '&lat=' + encodeURIComponent(String(DELIVERY_CONFIG.outlet_lat)) + '&lon=' + encodeURIComponent(String(DELIVERY_CONFIG.outlet_lng));
          }
          return fetch(photonUrl, { headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.json(); })
            .catch(function () { return null; })
            .then(function (photonJson) {
              const rows = uniqueAndSortLocations(nominatimRows.concat(normalizePhotonRows(photonJson, q)));
              rows.push(manualLocationRow(q));
              setAlert('');
              renderSearchResults(rows);
            });
        })
        .catch(function () {
          setAlert('');
          renderSearchResults([manualLocationRow(q)]);
        });
    }
    if (addressInput) {
      addressInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
          searchLocations(addressInput.value, true);
        }, 650);
      });
    }
    if (findAddress) {
      findAddress.addEventListener('click', function () {
        const q = String(addressInput && addressInput.value || '').trim();
        if (!q) {
          setAlert('Tulis alamat atau patokan dulu.');
          return;
        }
        searchLocations(q, false);
      });
    }
    const payBtn = document.getElementById('nmReviewPay');
    if (payBtn) {
      payBtn.addEventListener('click', function (event) {
        if (!IS_ONLINE_ORDER) return;
        event.preventDefault();
        const loc = getLocation();
        if (!loc) {
          requestLocation(function (ok) {
            if (ok && deliveryQuoteOk) window.location.href = payBtn.href;
          });
          return;
        }
        requestQuote(loc).then(function (ok) {
          if (ok) window.location.href = payBtn.href;
        });
      });
    }
  })();
</script>
