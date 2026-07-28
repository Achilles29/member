<?php
$saved_locations = is_array($saved_locations ?? null) ? $saved_locations : [];
$delivery_config = is_array($delivery_config ?? null) ? $delivery_config : [];
$storage_suffix = $order_storage_suffix ?? 'online';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<style>
  .nm-address-head { display:flex; align-items:center; justify-content:space-between; gap:10px; }
  .nm-address-list { display:grid; gap:10px; margin-top:12px; }
  .nm-address-card {
    border:1px solid rgba(54,42,35,.10);
    border-radius:16px;
    padding:12px;
    background:#fff;
    box-shadow:0 8px 18px rgba(0,0,0,.05);
  }
  .nm-address-top { display:flex; justify-content:space-between; gap:10px; align-items:flex-start; }
  .nm-address-title { font-weight:1000; color:#111827; font-size:14px; }
  .nm-address-sub { margin-top:4px; color:var(--muted); font-size:12px; font-weight:800; line-height:1.45; }
  .nm-address-badges { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
  .nm-address-badge { display:inline-flex; border-radius:999px; padding:4px 8px; font-size:10px; font-weight:1000; background:#f3f4f6; color:#374151; }
  .nm-address-badge.free { background:#dcfce7; color:#166534; }
  .nm-address-badge.default { background:#dbeafe; color:#1d4ed8; }
  .nm-address-actions { display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px; margin-top:10px; }
  .nm-address-empty { padding:18px 12px; text-align:center; color:var(--muted); font-size:13px; font-weight:800; }
  .nm-address-popup[hidden] { display:none; }
  .nm-address-popup { position:fixed; inset:0; z-index:100000; }
  .nm-address-popup__backdrop { position:absolute; inset:0; background:rgba(17,24,39,.45); }
  .nm-address-popup__panel {
    position:absolute;
    left:50%;
    transform:translateX(-50%);
    bottom:0;
    width:100%;
    max-width:420px;
    max-height:88vh;
    overflow:hidden;
    display:flex;
    flex-direction:column;
    background:#fff;
    border-radius:22px 22px 0 0;
    box-shadow:var(--shadow);
  }
  .nm-address-popup__head { padding:14px 14px 10px; display:flex; justify-content:space-between; align-items:center; gap:10px; border-bottom:1px solid #f3f4f6; }
  .nm-address-popup__title { font-weight:1000; font-size:15px; }
  .nm-address-popup__body { padding:14px; overflow:auto; display:grid; gap:10px; }
  .nm-address-popup__foot { padding:12px 14px 14px; border-top:1px solid #f3f4f6; display:grid; grid-template-columns:1fr 1fr; gap:8px; }
  .nm-address-field label { display:block; font-size:11px; color:var(--muted); font-weight:1000; margin-bottom:5px; }
  .nm-address-field input, .nm-address-field textarea, .nm-address-field select {
    width:100%;
    border:1px solid #e5e7eb;
    border-radius:14px;
    padding:11px 12px;
    font:inherit;
    font-size:13px;
    box-sizing:border-box;
  }
  .nm-address-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
  .nm-address-map-tools { display:grid; grid-template-columns:1fr auto; gap:8px; }
  .nm-address-map { height:260px; border-radius:16px; overflow:hidden; border:1px solid rgba(54,42,35,.12); background:#eef2ec; }
  .nm-address-search-results { border:1px solid rgba(54,42,35,.12); border-radius:14px; overflow:hidden; background:#fff; }
  .nm-address-search-result { width:100%; border:0; background:#fff; text-align:left; padding:10px 12px; border-bottom:1px solid rgba(54,42,35,.08); }
  .nm-address-search-result:last-child { border-bottom:0; }
  .nm-address-search-result strong { display:block; font-size:13px; color:#111827; }
  .nm-address-search-result span { display:block; font-size:11px; color:var(--muted); margin-top:2px; }
  .nm-address-toast {
    position:fixed;
    left:50%;
    transform:translateX(-50%);
    bottom:112px;
    z-index:100100;
    width:calc(100% - 32px);
    max-width:388px;
    border-radius:16px;
    padding:12px;
    background:#176b3a;
    color:#fff;
    font-size:13px;
    font-weight:900;
    box-shadow:var(--shadow);
  }
  .nm-address-toast.warn { background:#9a4e0f; }
  @media (max-width: 380px) {
    .nm-address-actions { grid-template-columns:1fr; }
    .nm-address-map-tools, .nm-address-grid { grid-template-columns:1fr; }
  }
</style>

<div class="page-content nm-page">
  <div class="nm-topbar nm-topbar--mini">
    <div>
      <div class="nm-name">Alamat Tersimpan</div>
      <div class="nm-level">Pakai ulang alamat untuk online order</div>
    </div>
    <a class="nm-logout" href="<?= site_url('member/logout') ?>" title="Logout">
      <i class="f7-icons">rectangle_porous_arrow_right</i>
    </a>
  </div>

  <div class="nm-card" style="margin-top:-22px;">
    <div class="nm-address-head">
      <div>
        <div class="nm-section-title">Daftar Alamat</div>
        <div class="nm-order__hint">Alamat yang disimpan saat checkout juga muncul di sini.</div>
      </div>
      <button type="button" class="nm-btn nm-btn--primary" id="nmAddressNew">Tambah</button>
    </div>

    <div class="nm-address-list" id="nmAddressList">
      <?php if (empty($saved_locations)): ?>
        <div class="nm-address-empty">Belum ada alamat tersimpan.</div>
      <?php endif; ?>
      <?php foreach ($saved_locations as $loc): ?>
        <div class="nm-address-card" data-id="<?= (int) ($loc['id'] ?? 0) ?>">
          <div class="nm-address-top">
            <div>
              <div class="nm-address-title"><?= html_escape((string) ($loc['label'] ?? 'Alamat')) ?></div>
              <div class="nm-address-sub"><?= html_escape((string) ($loc['address'] ?? '-')) ?></div>
              <?php if (!empty($loc['address_note'])): ?>
                <div class="nm-address-sub">Patokan: <?= html_escape((string) $loc['address_note']) ?></div>
              <?php endif; ?>
              <?php if (!empty($loc['recipient_name']) || !empty($loc['recipient_phone'])): ?>
                <div class="nm-address-sub">Penerima: <?= html_escape(trim((string) ($loc['recipient_name'] ?? '') . ' ' . (string) ($loc['recipient_phone'] ?? ''))) ?></div>
              <?php endif; ?>
            </div>
          </div>
          <div class="nm-address-badges">
            <?php if (!empty($loc['is_default'])): ?><span class="nm-address-badge default">Default</span><?php endif; ?>
            <?php if (!empty($loc['free_delivery_enabled'])): ?><span class="nm-address-badge free">Gratis ongkir</span><?php endif; ?>
            <span class="nm-address-badge"><?= html_escape((string) ($loc['latitude'] ?? '-')) ?>, <?= html_escape((string) ($loc['longitude'] ?? '-')) ?></span>
          </div>
          <div class="nm-address-actions">
            <button type="button" class="nm-btn nm-btn--primary nm-address-use" data-id="<?= (int) ($loc['id'] ?? 0) ?>">Pakai</button>
            <button type="button" class="nm-btn nm-btn--ghost nm-address-edit" data-id="<?= (int) ($loc['id'] ?? 0) ?>">Edit</button>
            <button type="button" class="nm-btn nm-btn--danger nm-address-delete" data-id="<?= (int) ($loc['id'] ?? 0) ?>">Hapus</button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php $this->load->view('templates/member/bottom_nav'); ?>
</div>

<div class="nm-address-popup" id="nmAddressPopup" hidden>
  <div class="nm-address-popup__backdrop" id="nmAddressBackdrop"></div>
  <div class="nm-address-popup__panel" role="dialog" aria-modal="true" aria-label="Form alamat">
    <div class="nm-address-popup__head">
      <div class="nm-address-popup__title" id="nmAddressTitle">Tambah Alamat</div>
      <button type="button" class="nm-iconbtn" id="nmAddressClose" aria-label="Tutup"><i class="f7-icons">xmark</i></button>
    </div>
    <form id="nmAddressForm">
      <div class="nm-address-popup__body">
        <input type="hidden" name="id" value="">
        <div class="nm-address-grid">
          <div class="nm-address-field">
            <label>Label</label>
            <input type="text" name="label" maxlength="80" placeholder="Rumah / Kantor" required>
          </div>
          <div class="nm-address-field">
            <label>Default</label>
            <select name="is_default">
              <option value="0">Tidak</option>
              <option value="1">Ya</option>
            </select>
          </div>
        </div>
        <div class="nm-address-grid">
          <div class="nm-address-field">
            <label>Nama penerima</label>
            <input type="text" name="recipient_name" maxlength="150">
          </div>
          <div class="nm-address-field">
            <label>Nomor HP</label>
            <input type="tel" name="recipient_phone" maxlength="32">
          </div>
        </div>
        <div class="nm-address-field">
          <label>Alamat</label>
          <textarea name="address" rows="2" maxlength="255" placeholder="Alamat lengkap atau patokan utama" required></textarea>
        </div>
        <div class="nm-address-field">
          <label>Catatan/patokan</label>
          <input type="text" name="address_note" maxlength="255" placeholder="Contoh: pagar hitam, depan minimarket">
        </div>
        <div class="nm-address-field">
          <label>Cari titik</label>
          <div class="nm-address-map-tools">
            <input type="search" id="nmAddressSearch" placeholder="Cari alamat atau gedung">
            <button type="button" class="nm-btn nm-btn--ghost" id="nmAddressFind">Cari</button>
          </div>
        </div>
        <div class="nm-address-search-results" id="nmAddressResults" hidden></div>
        <button type="button" class="nm-btn nm-btn--ghost nm-btn--block" id="nmAddressMyLocation">Lokasi saya</button>
        <div class="nm-address-map" id="nmAddressMap"></div>
        <div class="nm-address-grid">
          <div class="nm-address-field">
            <label>Latitude</label>
            <input type="number" step="0.0000001" name="latitude" required>
          </div>
          <div class="nm-address-field">
            <label>Longitude</label>
            <input type="number" step="0.0000001" name="longitude" required>
          </div>
        </div>
        <input type="hidden" name="location_accuracy" value="0">
      </div>
      <div class="nm-address-popup__foot">
        <button type="button" class="nm-btn nm-btn--ghost nm-btn--block" id="nmAddressCancel">Batal</button>
        <button type="submit" class="nm-btn nm-btn--primary nm-btn--block">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function () {
  const LOCATIONS = <?= json_encode($saved_locations, JSON_UNESCAPED_SLASHES) ?> || [];
  const DELIVERY_CONFIG = <?= json_encode($delivery_config, JSON_UNESCAPED_SLASHES) ?> || {};
  const STORAGE_SUFFIX = <?= json_encode((string) $storage_suffix) ?>;
  const LOCATION_KEY = 'nm_order_location_v1_' + STORAGE_SUFFIX;
  const SAVE_URL = <?= json_encode(base_url($order_base_path . '/address_save'), JSON_UNESCAPED_SLASHES) ?>;
  const DELETE_URL = <?= json_encode(base_url($order_base_path . '/address_delete'), JSON_UNESCAPED_SLASHES) ?>;
  const DEFAULT_URL = <?= json_encode(base_url($order_base_path . '/address_default'), JSON_UNESCAPED_SLASHES) ?>;
  const ORDER_URL = <?= json_encode(base_url($order_base_path), JSON_UNESCAPED_SLASHES) ?>;
  const popup = document.getElementById('nmAddressPopup');
  const form = document.getElementById('nmAddressForm');
  const title = document.getElementById('nmAddressTitle');
  const results = document.getElementById('nmAddressResults');
  let map = null;
  let marker = null;

  const byId = {};
  LOCATIONS.forEach(function (row) { byId[String(row.id || '')] = row; });
  const esc = function (v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); };

  function toast(message, warn) {
    const el = document.createElement('div');
    el.className = 'nm-address-toast' + (warn ? ' warn' : '');
    el.textContent = message;
    document.body.appendChild(el);
    setTimeout(function () { el.remove(); }, 2600);
  }

  function pointFromForm() {
    const lat = Number(form.elements.latitude.value || DELIVERY_CONFIG.outlet_lat || -6.2);
    const lng = Number(form.elements.longitude.value || DELIVERY_CONFIG.outlet_lng || 106.8166667);
    return [Number.isFinite(lat) ? lat : -6.2, Number.isFinite(lng) ? lng : 106.8166667];
  }

  function setPoint(lat, lng, address, accuracy) {
    lat = Number(lat); lng = Number(lng);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
    form.elements.latitude.value = lat.toFixed(7);
    form.elements.longitude.value = lng.toFixed(7);
    form.elements.location_accuracy.value = String(Number(accuracy || 0));
    if (address && !String(form.elements.address.value || '').trim()) form.elements.address.value = String(address).slice(0, 255);
    if (marker) marker.setLatLng([lat, lng]);
    if (map) map.setView([lat, lng], 16);
  }

  function ensureMap() {
    if (typeof L === 'undefined') return;
    const point = pointFromForm();
    if (!map) {
      map = L.map('nmAddressMap').setView(point, 15);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom:19, attribution:'&copy; OpenStreetMap' }).addTo(map);
      marker = L.marker(point, {draggable:true}).addTo(map).bindPopup('Titik pengantaran');
      marker.on('dragend', function () {
        const pos = marker.getLatLng();
        setPoint(pos.lat, pos.lng);
      });
      map.on('click', function (event) {
        setPoint(event.latlng.lat, event.latlng.lng);
      });
    } else {
      marker.setLatLng(point);
      map.setView(point, 15);
    }
    setTimeout(function () { map.invalidateSize(); }, 160);
  }

  function openForm(row) {
    form.reset();
    row = row || {};
    form.elements.id.value = row.id || '';
    form.elements.label.value = row.label || 'Rumah';
    form.elements.is_default.value = String(row.is_default || 0);
    form.elements.recipient_name.value = row.recipient_name || '';
    form.elements.recipient_phone.value = row.recipient_phone || '';
    form.elements.address.value = row.address || '';
    form.elements.address_note.value = row.address_note || '';
    form.elements.latitude.value = row.latitude || DELIVERY_CONFIG.outlet_lat || '';
    form.elements.longitude.value = row.longitude || DELIVERY_CONFIG.outlet_lng || '';
    form.elements.location_accuracy.value = row.location_accuracy || 0;
    title.textContent = row.id ? 'Edit Alamat' : 'Tambah Alamat';
    results.hidden = true;
    popup.hidden = false;
    document.body.classList.add('nm-no-scroll');
    setTimeout(ensureMap, 180);
  }

  function closeForm() {
    popup.hidden = true;
    document.body.classList.remove('nm-no-scroll');
  }

  function renderSearchRows(rows) {
    if (!rows.length) {
      results.hidden = true;
      return;
    }
    results.innerHTML = rows.map(function (row, idx) {
      return '<button type="button" class="nm-address-search-result" data-idx="' + idx + '"><strong>' + esc(row.title || 'Lokasi') + '</strong><span>' + esc(row.address || '') + '</span></button>';
    }).join('');
    results.hidden = false;
    results.onclick = function (event) {
      const btn = event.target.closest('.nm-address-search-result');
      if (!btn) return;
      const row = rows[Number(btn.getAttribute('data-idx') || 0)];
      if (!row) return;
      form.elements.address.value = String(row.address || '').slice(0, 255);
      setPoint(row.lat, row.lng, row.address);
      results.hidden = true;
    };
  }

  async function searchAddress() {
    const input = document.getElementById('nmAddressSearch');
    const q = String(input && input.value || '').trim();
    if (q.length < 3) {
      toast('Ketik minimal 3 karakter alamat.', true);
      return;
    }
    try {
      const url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=6&countrycodes=id&q=' + encodeURIComponent(q);
      const res = await fetch(url, {headers:{'Accept':'application/json'}});
      const json = await res.json();
      const rows = (Array.isArray(json) ? json : []).map(function (row) {
        const lat = Number(row && row.lat);
        const lng = Number(row && row.lon);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
        const address = String(row.display_name || q);
        return {lat:lat, lng:lng, title:String(row.name || address.split(',')[0] || 'Lokasi'), address:address};
      }).filter(Boolean);
      renderSearchRows(rows);
      if (!rows.length) toast('Alamat tidak ditemukan. Geser pin di map.', true);
    } catch (_) {
      toast('Pencarian alamat belum tersedia. Geser pin di map.', true);
    }
  }

  async function postForm(url, data) {
    const res = await fetch(url, {method:'POST', body:data, credentials:'same-origin'});
    const json = await res.json().catch(function () { return null; });
    if (!res.ok || !json || json.ok === false) throw new Error((json && json.message) || 'Gagal menyimpan data.');
    return json;
  }

  document.getElementById('nmAddressNew').addEventListener('click', function () { openForm(null); });
  document.getElementById('nmAddressClose').addEventListener('click', closeForm);
  document.getElementById('nmAddressCancel').addEventListener('click', closeForm);
  document.getElementById('nmAddressBackdrop').addEventListener('click', closeForm);
  document.getElementById('nmAddressFind').addEventListener('click', searchAddress);
  document.getElementById('nmAddressSearch').addEventListener('keydown', function (event) {
    if (event.key === 'Enter') {
      event.preventDefault();
      searchAddress();
    }
  });
  document.getElementById('nmAddressMyLocation').addEventListener('click', function () {
    if (!navigator.geolocation) {
      toast('Browser tidak mendukung lokasi.', true);
      return;
    }
    navigator.geolocation.getCurrentPosition(function (pos) {
      setPoint(pos.coords.latitude, pos.coords.longitude, '', pos.coords.accuracy || 0);
    }, function () {
      toast('Izinkan akses lokasi dulu.', true);
    }, {enableHighAccuracy:true, timeout:15000, maximumAge:60000});
  });
  form.elements.latitude.addEventListener('change', function () { const p = pointFromForm(); if (marker) marker.setLatLng(p); if (map) map.setView(p, 16); });
  form.elements.longitude.addEventListener('change', function () { const p = pointFromForm(); if (marker) marker.setLatLng(p); if (map) map.setView(p, 16); });

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    try {
      await postForm(SAVE_URL, new FormData(form));
      toast('Alamat berhasil disimpan.');
      setTimeout(function () { window.location.reload(); }, 450);
    } catch (error) {
      toast(error.message || 'Gagal menyimpan alamat.', true);
    }
  });

  document.getElementById('nmAddressList').addEventListener('click', async function (event) {
    const edit = event.target.closest('.nm-address-edit');
    const del = event.target.closest('.nm-address-delete');
    const use = event.target.closest('.nm-address-use');
    if (edit) {
      openForm(byId[String(edit.getAttribute('data-id') || '')]);
      return;
    }
    if (del) {
      const id = del.getAttribute('data-id') || '';
      if (!confirm('Hapus alamat ini?')) return;
      try {
        await postForm(DELETE_URL + '/' + encodeURIComponent(id), new FormData());
        toast('Alamat berhasil dihapus.');
        setTimeout(function () { window.location.reload(); }, 450);
      } catch (error) {
        toast(error.message || 'Gagal menghapus alamat.', true);
      }
      return;
    }
    if (use) {
      const row = byId[String(use.getAttribute('data-id') || '')];
      if (!row) return;
      try {
        localStorage.setItem(LOCATION_KEY, JSON.stringify({
          lat: Number(row.latitude),
          lng: Number(row.longitude),
          accuracy: Number(row.location_accuracy || 0),
          saved_location_id: Number(row.id || 0),
          label: String(row.label || ''),
          recipient_name: String(row.recipient_name || ''),
          recipient_phone: String(row.recipient_phone || ''),
          address_note: String(row.address_note || ''),
          address: String(row.address || ''),
          at: new Date().toISOString()
        }));
      } catch (_) {}
      try { await postForm(DEFAULT_URL + '/' + encodeURIComponent(String(row.id || 0)), new FormData()); } catch (_) {}
      window.location.href = ORDER_URL;
    }
  });
})();
</script>
