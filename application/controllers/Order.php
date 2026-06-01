<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Order extends CI_Controller
{
    private $order_schema_ready = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->model([
            'Member_model',
            'Produk_model',
            'Pending_order_model',
            'Pending_order_detail_model',
            'Pending_order_extra_model',
        ]);
        $this->load->helper(['url', 'form']);
        $this->order_schema_ready = $this->db->table_exists('crm_member')
            && $this->db->table_exists('mst_product')
            && $this->db->table_exists('pos_order')
            && $this->db->table_exists('pos_order_line')
            && $this->db->table_exists('pos_payment')
            && $this->db->table_exists('pos_payment_line')
            && $this->db->table_exists('pos_payment_method')
            && $this->db->table_exists('pos_outlet')
            && $this->db->table_exists('auth_user');

        $public_methods = ['midtrans_callback'];
        if (!in_array($this->router->method, $public_methods, true)) {
            // Cek login member
            if (!$this->session->userdata('member_id')) {
                redirect('login?redirect_to=' . urlencode(current_url()));
                return;
            }

            if (!$this->self_order_is_enabled()) {
                if ($this->input->is_ajax_request()) {
                    $this->json_response([
                        'ok' => false,
                        'message' => 'Order mandiri sedang dinonaktifkan sementara.'
                    ], 503);
                    return;
                }
                $this->session->set_flashdata('error', 'Order mandiri sedang dinonaktifkan sementara.');
                redirect('member');
                return;
            }

            if (!$this->order_schema_ready) {
                if ($this->input->is_ajax_request()) {
                    $this->json_response([
                        'ok' => false,
                        'message' => 'Fitur order member belum siap karena tabel POS finance wajib belum lengkap di db_finance.'
                    ], 503);
                    return;
                }

                $this->session->set_flashdata('error', 'Fitur order member belum siap karena tabel POS finance wajib belum lengkap di db_finance.');
                redirect('member');
            }
        }
    }

    private function json_response($payload, $status = 200)
    {
        $this->output
            ->set_status_header((int) $status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($payload));
    }

    private function self_order_is_enabled()
    {
        if ($this->db->table_exists('pos_self_order_setting')) {
            $row = $this->db->get_where('pos_self_order_setting', ['id' => 1])->row_array();
            if ($row) {
                return ((int)($row['is_enabled'] ?? 1)) === 1;
            }
        }
        return true;
    }

    private function midtrans_config()
    {
        $cfg = [
            'server_key' => '',
            'client_key' => '',
            'is_production' => false,
            'is_enabled' => false,
        ];

        if ($this->db->table_exists('pr_qris_setting')) {
            $row = $this->db->get_where('pr_qris_setting', ['id' => 1])->row_array();
            if ($row) {
                $cfg['server_key'] = (string) ($row['midtrans_server_key'] ?? '');
                $cfg['client_key'] = (string) ($row['midtrans_client_key'] ?? '');
                $cfg['is_production'] = !empty($row['midtrans_is_production']);
                $cfg['is_enabled'] = ((int) ($row['is_enabled'] ?? 0)) === 1;
            }
        }

        if ($cfg['server_key'] === '') {
            $cfg['server_key'] = (string) getenv('MIDTRANS_SERVER_KEY');
        }
        if ($cfg['client_key'] === '') {
            $cfg['client_key'] = (string) getenv('MIDTRANS_CLIENT_KEY');
        }
        $env_prod = getenv('MIDTRANS_IS_PRODUCTION');
        if ($env_prod !== false && $env_prod !== '') {
            $cfg['is_production'] = filter_var($env_prod, FILTER_VALIDATE_BOOLEAN);
        }

        if (!$cfg['is_enabled']) {
            $cfg['is_enabled'] = $cfg['server_key'] !== '';
        }

        return $cfg;
    }

    private function midtrans_is_configured()
    {
        $cfg = $this->midtrans_config();
        return !empty($cfg['server_key']) && !empty($cfg['is_enabled']);
    }

    private function midtrans_base_url()
    {
        $cfg = $this->midtrans_config();
        return $cfg['is_production'] ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
    }

    private function midtrans_request($method, $path, $payload = null)
    {
        $cfg = $this->midtrans_config();
        $url = rtrim($this->midtrans_base_url(), '/') . '/' . ltrim($path, '/');

        $ch = curl_init($url);
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $cfg['server_key'] . ':');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $method = strtoupper((string) $method);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($payload !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            }
        }

        $body = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = null;
        if ($body !== false && $body !== '') {
            $json = json_decode($body, true);
        }

        return [
            'ok' => $err === '' && $code >= 200 && $code < 300,
            'code' => $code,
            'error' => $err ?: null,
            'body' => $body,
            'json' => $json,
        ];
    }

    private function midtrans_build_order_id($pending_id)
    {
        $pending_id = (int) $pending_id;
        return 'PO-' . $pending_id . '-' . date('YmdHis');
    }

    private function midtrans_parse_qr_actions($actions)
    {
        $qr_url = null;
        if (is_array($actions)) {
            foreach ($actions as $action) {
                if (!is_array($action)) continue;
                if (($action['name'] ?? '') === 'generate-qr-code') {
                    $qr_url = $action['url'] ?? null;
                }
            }
        }
        return $qr_url;
    }

    private function midtrans_human_error($resp, $fallback)
    {
        $fallback = (string) $fallback;
        $detail = '';

        if (is_array($resp['json'] ?? null)) {
            $json = $resp['json'];
            $status_message = trim((string) ($json['status_message'] ?? ''));
            $status_code = trim((string) ($json['status_code'] ?? ''));

            if ($status_message !== '') {
                $detail = $status_message;
                if ($status_code !== '') {
                    $detail .= ' (code: ' . $status_code . ')';
                }
            }
        }

        if ($detail === '') {
            $err = trim((string) ($resp['error'] ?? ''));
            if ($err !== '') {
                $detail = $err;
            }
        }

        if ($detail === '') {
            return $fallback;
        }

        return $fallback . ' Detail: ' . $detail;
    }

    private function midtrans_sync_status($pending_id, $order_id)
    {
        $resp = $this->midtrans_request('GET', '/v2/' . rawurlencode($order_id) . '/status');
        if (!$resp['ok']) return null;

        $data = $resp['json'];
        if (!is_array($data)) return null;

        $status = strtolower((string) ($data['transaction_status'] ?? ''));
        $paid_at = null;
        $payment_status = null;

        if (in_array($status, ['settlement', 'capture'], true)) {
            $payment_status = 'PAID';
            $paid_at = date('Y-m-d H:i:s');
        } elseif ($status === 'pending') {
            $payment_status = 'PENDING';
        } elseif (in_array($status, ['expire'], true)) {
            $payment_status = 'EXPIRED';
        } elseif (in_array($status, ['deny', 'cancel'], true)) {
            $payment_status = 'FAILED';
        }

        if ($payment_status !== null) {
            $this->Pending_order_model->update_payment($pending_id, [
                'payment_status' => $payment_status,
                'payment_provider' => 'MIDTRANS',
                'payment_ref' => $order_id,
                'payment_paid_at' => $paid_at,
            ]);
        }

        return $payment_status;
    }

    private function midtrans_item_details($pending_id)
    {
        $pending_id = (int) $pending_id;
        if ($pending_id <= 0) return [];

        $items = [];
        $details = $this->db->get_where('pos_order_line', ['order_id' => $pending_id])->result_array();
        foreach ($details as $d) {
            $produk = $this->get_product_row((int) ($d['product_id'] ?? 0));
            if (!$produk) continue;

            $qty = (int) round((float) ($d['qty'] ?? 0));
            if ($qty <= 0) continue;

            $price = (int) round((float) ($d['unit_price'] ?? $produk->harga_jual ?? 0));
            $items[] = [
                'id' => (string) ($produk->id ?? ''),
                'name' => (string) ($produk->nama_produk ?? 'Produk'),
                'price' => $price,
                'quantity' => $qty,
            ];

            $extras = $this->db->get_where('pos_order_line_extra', ['order_line_id' => (int) ($d['id'] ?? 0)])->result_array();
            foreach ($extras as $ex) {
                $extraRow = $this->get_extra_row((int) ($ex['extra_id'] ?? 0));
                if (!$extraRow) continue;
                $exQty = (int) round((float) ($ex['qty'] ?? 0));
                if ($exQty <= 0) continue;
                $exPrice = (int) round((float) ($ex['unit_price'] ?? $extraRow->harga ?? 0));
                $items[] = [
                    'id' => 'EX-' . (int) ($extraRow->id ?? 0),
                    'name' => (string) ($extraRow->nama_extra ?? 'Extra'),
                    'price' => $exPrice,
                    'quantity' => $exQty,
                ];
            }
        }

        return $items;
    }

    private function get_member_row($member_id)
    {
        $member = $this->Member_model->get_by_id((int) $member_id);
        return is_array($member) ? $member : [];
    }

    private function get_product_row($produk_id)
    {
        $produk_id = (int) $produk_id;
        if ($produk_id <= 0) {
            return null;
        }

        $this->db
            ->select('p.id, p.product_name as nama_produk, p.selling_price as harga_jual, p.photo_path as foto, c.product_division_id as pr_divisi_id', false)
            ->from('mst_product p')
            ->join('mst_product_category c', 'c.id = p.product_category_id', 'left')
            ->where('p.id', $produk_id)
            ->where('p.is_active', 1);
        if ($this->db->field_exists('show_in_self_order', 'mst_product')) {
            $this->db->where('p.show_in_self_order', 1);
        } else {
            $this->db->group_start()
                ->where('p.show_member', 1)
                ->or_where('p.show_pos', 1)
                ->group_end();
        }

        return $this->db->limit(1)->get()->row();
    }

    private function get_extra_row($extra_id)
    {
        $extra_id = (int) $extra_id;
        if ($extra_id <= 0 || !$this->db->table_exists('mst_extra')) {
            return null;
        }

        return $this->db
            ->select('id, extra_name as nama_extra, selling_price as harga, cost_amount as hpp', false)
            ->from('mst_extra')
            ->where('id', $extra_id)
            ->where('is_active', 1)
            ->limit(1)
            ->get()
            ->row();
    }

    private function get_active_extras_lookup()
    {
        if (!$this->db->table_exists('mst_extra')) {
            return [];
        }

        return $this->db
            ->select('id, extra_name as nama_extra, selling_price as harga', false)
            ->from('mst_extra')
            ->where('is_active', 1)
            ->where('show_in_self_order', 1)
            ->order_by('id', 'ASC')
            ->get()
            ->result_array();
    }

    private function normalize_cart($cart)
    {
        $cart = is_array($cart) ? $cart : [];
        $out = [];
        foreach ($cart as $produk_id => $row) {
            $produk_id = (int) $produk_id;
            if ($produk_id <= 0) continue;

            $jumlah = (int) ($row['jumlah'] ?? 0);
            if ($jumlah <= 0) continue;

            $extra_ids = $row['extra_ids'] ?? [];
            $extra_ids = is_array($extra_ids) ? $extra_ids : [];
            $extra_ids = array_values(array_filter(array_map('intval', $extra_ids)));

            $out[$produk_id] = [
                'jumlah' => $jumlah,
                'extra_ids' => $extra_ids,
            ];
        }
        return $out;
    }

    /**
     * Ambil opsi extra per-produk dengan skema group (selaras kasir/get_extra_options_produk).
     */
    private function fetch_extra_groups_for_produk($produk_id)
    {
        $produk_id = (int) $produk_id;
        if ($produk_id <= 0) {
            return ['produk_id' => 0, 'divisi_id' => 0, 'groups' => []];
        }

        if (
            !$this->db->table_exists('mst_extra_group')
            || !$this->db->table_exists('mst_extra_group_item')
            || !$this->db->table_exists('mst_product_extra_group_map')
        ) {
            return ['produk_id' => $produk_id, 'divisi_id' => 0, 'groups' => []];
        }

        $divisi_id = (int) $this->db
            ->select('k.product_division_id')
            ->from('mst_product p')
            ->join('mst_product_category k', 'k.id = p.product_category_id', 'left')
            ->where('p.id', $produk_id)
            ->get()
            ->row('product_division_id');

        $groups = $this->db
            ->select('g.id, g.group_name as nama_group, g.is_required as is_wajib, g.min_select as min_pilih, g.max_select as max_pilih, m.sort_order as urutan')
            ->from('mst_product_extra_group_map m')
            ->join('mst_extra_group g', 'g.id = m.extra_group_id', 'left')
            ->where('m.product_id', $produk_id)
            ->where('g.is_active', 1)
            ->order_by('m.sort_order', 'ASC')
            ->order_by('g.sort_order', 'ASC')
            ->get()
            ->result_array();

        if (empty($groups)) {
            return ['produk_id' => $produk_id, 'divisi_id' => $divisi_id, 'groups' => []];
        }

        $group_ids = array_map(static function ($g) {
            return (int) $g['id'];
        }, $groups);

        $this->db
            ->select('gi.extra_group_id as pr_extra_group_id, e.id, e.extra_code as sku, e.extra_name as nama_extra, e.uom_name as satuan, e.selling_price as harga, e.cost_amount as hpp, e.extra_type as tipe_extra')
            ->from('mst_extra_group_item gi')
            ->join('mst_extra e', 'e.id = gi.extra_id', 'left')
            ->where_in('gi.extra_group_id', $group_ids)
            ->where('e.is_active', 1)
            ->where('e.show_in_self_order', 1)
            ->order_by('gi.sort_order', 'ASC')
            ->order_by('e.extra_name', 'ASC');
        $items = $this->db->get()->result_array();

        $items_by_group = [];
        foreach ($items as $it) {
            $gid = (int) ($it['pr_extra_group_id'] ?? 0);
            if ($gid <= 0) continue;
            if (!isset($items_by_group[$gid])) {
                $items_by_group[$gid] = [];
            }
            $items_by_group[$gid][] = [
                'id' => (int) ($it['id'] ?? 0),
                'sku' => $it['sku'] ?? '',
                'nama_extra' => $it['nama_extra'] ?? '',
                'satuan' => $it['satuan'] ?? '',
                'harga' => (float) ($it['harga'] ?? 0),
                'hpp' => (float) ($it['hpp'] ?? 0),
                'tipe_extra' => $it['tipe_extra'] ?? 'ADD',
            ];
        }

        foreach ($groups as &$g) {
            $gid = (int) ($g['id'] ?? 0);
            $g['id'] = $gid;
            $g['is_wajib'] = (int) ($g['is_wajib'] ?? 0);
            $g['min_pilih'] = (int) ($g['min_pilih'] ?? 0);
            $g['max_pilih'] = (int) ($g['max_pilih'] ?? 1);
            if ($g['is_wajib'] === 1 && $g['min_pilih'] <= 0) {
                $g['min_pilih'] = 1;
            }
            if ($g['is_wajib'] === 0 && $g['min_pilih'] > 0) {
                $g['min_pilih'] = 0;
            }
            if ($g['max_pilih'] <= 0) {
                $g['max_pilih'] = 1;
            }
            $g['items'] = $items_by_group[$gid] ?? [];
            unset($g['urutan']);
        }
        unset($g);

        return ['produk_id' => $produk_id, 'divisi_id' => $divisi_id, 'groups' => $groups];
    }

    public function get_extra_options_produk()
    {
        $produk_id = (int) $this->input->get('produk_id');
        $this->json_response($this->fetch_extra_groups_for_produk($produk_id));
    }

    private function sanitize_extra_ids_for_produk($produk_id, $selected_extra_ids)
    {
        $produk_id = (int) $produk_id;
        $selected_extra_ids = is_array($selected_extra_ids) ? $selected_extra_ids : [];
        $selected_extra_ids = array_values(array_unique(array_filter(array_map('intval', $selected_extra_ids))));

        $opt = $this->fetch_extra_groups_for_produk($produk_id);
        $groups = (array) ($opt['groups'] ?? []);
        if (empty($groups)) {
            // Tidak ada mapping group untuk produk ini -> extra harus kosong.
            return ['ok' => true, 'extra_ids' => [], 'message' => null];
        }

        $allowed_by_group = [];
        foreach ($groups as $g) {
            $gid = (int) ($g['id'] ?? 0);
            $allowed_by_group[$gid] = [];
            foreach ((array) ($g['items'] ?? []) as $it) {
                $eid = (int) ($it['id'] ?? 0);
                if ($eid > 0) $allowed_by_group[$gid][$eid] = true;
            }
        }

        $selected_by_group = [];
        foreach ($selected_extra_ids as $eid) {
            foreach ($allowed_by_group as $gid => $allowed_map) {
                if (isset($allowed_map[$eid])) {
                    if (!isset($selected_by_group[$gid])) $selected_by_group[$gid] = [];
                    $selected_by_group[$gid][$eid] = true;
                }
            }
        }

        foreach ($groups as $g) {
            $gid = (int) ($g['id'] ?? 0);
            $nama_group = (string) ($g['nama_group'] ?? 'Group');
            $min = (int) ($g['min_pilih'] ?? 0);
            $max = (int) ($g['max_pilih'] ?? 1);
            $cnt = isset($selected_by_group[$gid]) ? count($selected_by_group[$gid]) : 0;

            if ($min > 0 && $cnt < $min) {
                return ['ok' => false, 'extra_ids' => [], 'message' => 'Pilihan extra untuk "' . $nama_group . '" minimal ' . $min . '.'];
            }
            if ($max > 0 && $cnt > $max) {
                return ['ok' => false, 'extra_ids' => [], 'message' => 'Pilihan extra untuk "' . $nama_group . '" maksimal ' . $max . '.'];
            }
        }

        // Keep hanya extra yang valid dalam mapping group produk.
        $clean = [];
        foreach ($selected_by_group as $gid => $map) {
            foreach (array_keys($map) as $eid) {
                $clean[] = (int) $eid;
            }
        }
        $clean = array_values(array_unique($clean));

        return ['ok' => true, 'extra_ids' => $clean, 'message' => null];
    }

    private function sanitize_cart_extra_rules($cart)
    {
        $cart = is_array($cart) ? $cart : [];
        $out = [];
        foreach ($cart as $produk_id => $row) {
            $produk_id = (int) $produk_id;
            $jumlah = (int) ($row['jumlah'] ?? 0);
            if ($produk_id <= 0 || $jumlah <= 0) continue;

            $san = $this->sanitize_extra_ids_for_produk($produk_id, $row['extra_ids'] ?? []);
            if (!$san['ok']) {
                return ['ok' => false, 'message' => $san['message'], 'cart' => []];
            }

            $out[$produk_id] = [
                'jumlah' => $jumlah,
                'extra_ids' => $san['extra_ids'],
            ];
        }
        return ['ok' => true, 'message' => null, 'cart' => $out];
    }

    private function compute_review_data_from_cart($cart)
    {
        $produk_list = [];
        $total = 0;

        foreach ((array) $cart as $produk_id => $row) {
            $produk_id = (int) $produk_id;
            $jumlah = (int) ($row['jumlah'] ?? 0);
            if ($produk_id <= 0 || $jumlah <= 0) continue;

            $p = $this->get_product_row($produk_id);
            if (!$p) continue;

            $harga = (float) $p->harga_jual;
            $subtotal = $harga * $jumlah;
            $total += $subtotal;

            $item = [
                'nama' => (string) $p->nama_produk,
                'jumlah' => $jumlah,
                'harga' => $harga,
                'subtotal' => $subtotal,
                'extra' => [],
            ];

            $extra_ids = $row['extra_ids'] ?? [];
            if (!empty($extra_ids)) {
                foreach ((array) $extra_ids as $ex_id) {
                    $ex = $this->get_extra_row((int) $ex_id);
                    if (!$ex) continue;
                    $item['extra'][] = [
                        'nama' => (string) $ex->nama_extra,
                        'harga' => (float) $ex->harga,
                    ];
                    $total += ((float) $ex->harga) * $jumlah;
                }
            }

            $produk_list[$produk_id] = $item;
        }

        return [$produk_list, $total];
    }

    public function resume()
    {
        // Urutan prioritas: kalau user sudah di tahap akhir, langsung arahkan.
        $step = (string) ($this->session->userdata('order_flow_step') ?? '');
        $cart_final = $this->session->userdata('order_cart');
        $cart_draft = $this->session->userdata('order_draft_cart');

        if ($step === 'pay' && is_array($cart_final) && !empty($cart_final)) {
            redirect('order/pay');
            return;
        }

        if ($step === 'pay' && is_array($cart_draft) && !empty($cart_draft)) {
            redirect('order/pay');
            return;
        }

        if ($step === 'review' && is_array($cart_draft) && !empty($cart_draft)) {
            redirect('order/review_session');
            return;
        }

        redirect('order');
    }

    public function save_cart()
    {
        // Simpan keranjang draft ke session (dipakai untuk resume setelah halaman ditutup/scan ulang).
        $raw = (string) $this->input->raw_input_stream;
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $this->json_response(['ok' => false, 'error' => 'payload_invalid'], 400);
            return;
        }

        $cart = $this->normalize_cart($payload['cart'] ?? []);
        $sanitized = $this->sanitize_cart_extra_rules($cart);
        if (!$sanitized['ok']) {
            $this->json_response(['ok' => false, 'error' => 'extra_invalid', 'message' => $sanitized['message']], 422);
            return;
        }
        $cart = $sanitized['cart'];
        $step = strtoupper(trim((string) ($payload['step'] ?? '')));
        $step = strtolower($step);
        if (!in_array($step, ['menu', 'review', 'pay'], true)) {
            $step = 'menu';
        }

        // Total dihitung server-side (anti manipulasi).
        $total = $this->compute_total_from_cart($cart);

        $this->session->set_userdata('order_draft_cart', $cart);
        $this->session->set_userdata('order_draft_total', $total);
        $this->session->set_userdata('order_flow_step', $step);

        $this->json_response(['ok' => true, 'total' => $total]);
    }

    public function clear_cart()
    {
        $this->session->unset_userdata('order_draft_cart');
        $this->session->unset_userdata('order_draft_total');
        $this->session->unset_userdata('order_cart');
        $this->session->unset_userdata('order_total');
        $this->session->unset_userdata('order_flow_step');

        redirect('order');
    }

    public function menu()
    {
        // Bypass "resume" redirect supaya tombol "Tambah menu" dari review bisa balik ke list menu.
        // Keranjang draft tetap disimpan, tapi keranjang final di-reset agar total dihitung ulang saat confirm.
        $this->session->set_userdata('order_flow_step', 'menu');
        $this->session->unset_userdata('order_cart');
        $this->session->unset_userdata('order_total');

        redirect('order');
    }

    public function index()
    {
        $customer_id = $this->session->userdata('member_id');
        $data['title'] = 'Order';
        $data['active_menu'] = 'order';
        $data['nomor_meja'] = $this->session->userdata('order_nomor_meja');
        $data['meja_id'] = (int) ($this->session->userdata('order_meja_id') ?? 0);

        // Resume logic (kalau user sudah punya keranjang / sudah sampai pay).
        $step = (string) ($this->session->userdata('order_flow_step') ?? '');
        $cart_final = $this->session->userdata('order_cart');
        $cart_draft = $this->session->userdata('order_draft_cart');
        if ($step === 'pay' && is_array($cart_final) && !empty($cart_final)) {
            redirect('order/pay');
            return;
        }
        if ($step === 'pay' && is_array($cart_draft) && !empty($cart_draft)) {
            redirect('order/pay');
            return;
        }
        if ($step === 'review' && is_array($cart_draft) && !empty($cart_draft)) {
            redirect('order/review_session');
            return;
        }

        // Ambil semua kategori aktif dan urutkan
        $this->load->model('Kategori_model');
        $kategori = $this->Kategori_model->get_all(); // status = 1, urutan ASC
        $data['kategori'] = $kategori;

        // Ambil produk berdasarkan kategori (dikelompokkan)
        $this->load->model('Produk_model');
        $data['produk_per_kategori'] = [];
        foreach ($kategori as $kat) {
            $data['produk_per_kategori'][$kat->id] = $this->Produk_model->get_by_kategori($kat->id);
        }

        $data['extras'] = $this->get_active_extras_lookup();

        // Ambil info member
        $data['member'] = $this->get_member_row($customer_id);

        // Draft cart untuk initial state (dipakai JS).
        $data['draft_cart'] = $this->session->userdata('order_draft_cart');
        $data['flow_step'] = (string) ($this->session->userdata('order_flow_step') ?? 'menu');

        // Load view
        $this->load->view('templates/member/header', $data);
        $this->load->view('order/form', $data);
        $this->load->view('templates/member/footer');
    }

    private function build_cart_from_post($produk, $extra)
    {
        $produk = is_array($produk) ? $produk : [];
        $extra = is_array($extra) ? $extra : [];

        $cart = [];
        foreach ($produk as $produk_id => $jumlah) {
            $produk_id = (int) $produk_id;
            $jumlah = (int) $jumlah;
            if ($produk_id <= 0 || $jumlah <= 0) continue;

            $cart[$produk_id] = [
                'jumlah' => $jumlah,
                'extra_ids' => [],
            ];

            if (isset($extra[$produk_id]) && is_array($extra[$produk_id])) {
                $cart[$produk_id]['extra_ids'] = array_values(array_filter(array_map('intval', $extra[$produk_id])));
            }
        }

        return $cart;
    }

    private function compute_total_from_cart($cart)
    {
        $total = 0;
        foreach ((array) $cart as $produk_id => $row) {
            $produk = $this->get_product_row((int) $produk_id);
            if (!$produk) continue;

            $jumlah = (int) ($row['jumlah'] ?? 0);
            if ($jumlah <= 0) continue;

            $harga = (float) $produk->harga_jual;
            $total += $harga * $jumlah;

            $extra_ids = $row['extra_ids'] ?? [];
            if (!empty($extra_ids)) {
                foreach ((array) $extra_ids as $ex_id) {
                    $ex = $this->get_extra_row((int) $ex_id);
                    if (!$ex) continue;
                    $total += ((float) $ex->harga) * $jumlah;
                }
            }
        }
        return $total;
    }

    public function submit()
    {
        // Backward-compat: flow lama yang langsung POST ke submit.
        $this->confirm();
    }

    public function selesai()
    {
        $customer_id = $this->session->userdata('member_id');
        $data['title'] = 'Order Terkirim';
        $data['active_menu'] = 'order';
        $data['member'] = $this->get_member_row($customer_id);
        $data['nomor_meja'] = $this->session->userdata('order_nomor_meja');
        $data['meja_id'] = (int) ($this->session->userdata('order_meja_id') ?? 0);
        $data['pending_order'] = null;
        $data['payment_method'] = $this->session->userdata('last_pending_order_payment_method');

        $pending_id = (int) ($this->session->userdata('last_pending_order_id') ?? 0);
        if ($pending_id > 0) {
            $data['pending_order'] = $this->Pending_order_model->get_for_member($pending_id, (int) $customer_id);
        }

        $this->load->view('templates/member/header', $data);
        $this->load->view('order/selesai', $data);
        $this->load->view('templates/member/footer', $data);
    }


    public function filter_produk()
    {
        $this->load->model('Produk_model');

        $keyword = $this->input->post('keyword');
        $kategori = $this->input->post('kategori');

        $data['produk'] = $this->Produk_model->search($keyword, $kategori);
        $this->load->view('order/produk_grid', $data);
    }
    public function review()
    {
        $customer_id = $this->session->userdata('member_id');
        if (!$customer_id) redirect('login');

        $produk = $this->input->post('produk');
        $extra = $this->input->post('extra');

        $cart = $this->build_cart_from_post($produk, $extra);
        $sanitized = $this->sanitize_cart_extra_rules($cart);
        if (!$sanitized['ok']) {
            $this->session->set_flashdata('error', $sanitized['message']);
            redirect('order');
        }
        $cart = $sanitized['cart'];
        if (empty($cart)) {
            $this->session->set_flashdata('error', 'Tidak ada produk yang dipilih.');
            redirect('order');
        }

        $produk_list = [];
        $total = 0;

        foreach ($produk as $produk_id => $jumlah) {
            $row = $this->get_product_row((int) $produk_id);
            if (!$row) continue;

            $harga = $row->harga_jual;
            $subtotal = $harga * $jumlah;
            $total += $subtotal;

            $produk_list[$produk_id] = [
                'nama' => $row->nama_produk,
                'jumlah' => $jumlah,
                'harga' => $harga,
                'subtotal' => $subtotal,
                'extra' => []
            ];

            // Ambil nama extra jika ada
            if (isset($cart[$produk_id]['extra_ids']) && is_array($cart[$produk_id]['extra_ids'])) {
                foreach ($cart[$produk_id]['extra_ids'] as $ex_id) {
                    $ex = $this->get_extra_row((int) $ex_id);
                    if ($ex) {
                        $produk_list[$produk_id]['extra'][] = [
                            'nama' => $ex->nama_extra,
                            'harga' => $ex->harga
                        ];
                        $total += $ex->harga * $jumlah; // dikali jumlah produk
                    }
                }
            }
        }

        $data['produk_list'] = $produk_list;
        $data['total'] = $total;
        $data['title'] = "Review Order";
        $data['nomor_meja'] = $this->session->userdata('order_nomor_meja');

        // Simpan ke session biar pay/confirm tidak tergantung hidden input.
        $this->session->set_userdata('order_draft_cart', $cart);
        $this->session->set_userdata('order_draft_total', $total);
        $this->session->set_userdata('order_cart', $cart);
        $this->session->set_userdata('order_total', $total);
        $this->session->set_userdata('order_flow_step', 'review');

        $data['active_menu'] = 'order';
        $this->load->view('templates/member/header', $data);
        $this->load->view('order/review', $data);
        $this->load->view('templates/member/footer');
    }

    public function review_session()
    {
        $customer_id = $this->session->userdata('member_id');
        if (!$customer_id) redirect('login');

        $cart = $this->session->userdata('order_draft_cart');
        if (empty($cart) || !is_array($cart)) {
            $this->session->set_flashdata('error', 'Keranjang kosong. Pilih menu dulu ya.');
            redirect('order');
        }

        $cart = $this->normalize_cart($cart);
        $sanitized = $this->sanitize_cart_extra_rules($cart);
        if (!$sanitized['ok']) {
            $this->session->set_flashdata('error', $sanitized['message']);
            redirect('order');
        }
        $cart = $sanitized['cart'];
        [$produk_list, $total] = $this->compute_review_data_from_cart($cart);
        if (empty($produk_list)) {
            $this->session->set_flashdata('error', 'Keranjang kosong. Pilih menu dulu ya.');
            redirect('order');
        }

        $this->session->set_userdata('order_cart', $cart);
        $this->session->set_userdata('order_total', $total);
        $this->session->set_userdata('order_flow_step', 'review');

        $data = [
            'title' => 'Review Order',
            'active_menu' => 'order',
            'nomor_meja' => $this->session->userdata('order_nomor_meja'),
            'produk_list' => $produk_list,
            'total' => $total,
            'member' => $this->get_member_row($customer_id),
        ];

        $this->load->view('templates/member/header', $data);
        $this->load->view('order/review', $data);
        $this->load->view('templates/member/footer', $data);
    }

    public function pay()
    {
        $customer_id = $this->session->userdata('member_id');
        if (!$customer_id) redirect('login');

        $cart = $this->session->userdata('order_cart');
        if (empty($cart) || !is_array($cart)) {
            // Fallback: kalau keranjang final belum kebentuk, ambil dari draft.
            $draft = $this->session->userdata('order_draft_cart');
            $draft = $this->normalize_cart($draft);
            if (!empty($draft)) {
                $cart = $draft;
                $this->session->set_userdata('order_cart', $cart);
                $this->session->set_userdata('order_total', $this->compute_total_from_cart($cart));
            } else {
                $this->session->set_flashdata('error', 'Keranjang kosong. Pilih menu dulu ya.');
                redirect('order');
            }
        }

        // Mark step buat resume (scan ulang langsung balik ke halaman pay).
        $this->session->set_userdata('order_flow_step', 'pay');

        $data = [
            'title' => 'Pembayaran',
            'active_menu' => 'order',
            'total' => (float) $this->session->userdata('order_total'),
            'nomor_meja' => $this->session->userdata('order_nomor_meja'),
            'payment_method' => 'KASIR',
            'qris_enabled' => $this->midtrans_is_configured(),
        ];

        $this->load->view('templates/member/header', $data);
        $this->load->view('order/pay', $data);
        $this->load->view('templates/member/footer', $data);
    }

    public function confirm()
    {
        $customer_id = (int) $this->session->userdata('member_id');
        if (!$customer_id) redirect('login');

        $cart = $this->session->userdata('order_cart');
        if (empty($cart) || !is_array($cart)) {
            $produk = $this->input->post('produk');
            $extra = $this->input->post('extra');
            $cart = $this->build_cart_from_post($produk, $extra);
        }

        if (empty($cart)) {
            $this->session->set_flashdata('error', 'Keranjang kosong. Pilih menu dulu ya.');
            redirect('order');
        }

        $sanitized = $this->sanitize_cart_extra_rules($cart);
        if (!$sanitized['ok']) {
            $this->session->set_flashdata('error', $sanitized['message']);
            redirect('order');
        }
        $cart = $sanitized['cart'];

        $nomor_meja = $this->session->userdata('order_nomor_meja');
        $catatan = $this->input->post('catatan', true);
        $payment_method = strtoupper(trim((string) $this->input->post('payment_method', true)));
        if (!in_array($payment_method, ['KASIR', 'QRIS'], true)) {
            $payment_method = 'KASIR';
        }
        if ($payment_method === 'QRIS' && !$this->midtrans_is_configured()) {
            $payment_method = 'KASIR';
            $this->session->set_flashdata('error', 'QRIS sedang nonaktif. Silakan bayar di kasir.');
        }
        $payment_status = ($payment_method === 'QRIS') ? 'PENDING' : 'UNPAID';
        $payment_provider = ($payment_method === 'QRIS') ? 'MIDTRANS' : null;

        // Hitung ulang dari DB (anti manipulasi).
        $total = $this->compute_total_from_cart($cart);

        $this->db->trans_begin();
        try {
            $order_id = $this->Pending_order_model->create_order(
                $customer_id,
                $nomor_meja,
                $catatan,
                $total,
                $payment_method,
                $payment_status,
                $payment_provider
            );

            foreach ($cart as $produk_id => $row) {
                $jumlah = (int) ($row['jumlah'] ?? 0);
                if ($jumlah <= 0) continue;

                $detail_id = $this->Pending_order_detail_model->insert_detail($order_id, (int) $produk_id, $jumlah);

                $extra_ids = $row['extra_ids'] ?? [];
                if (!empty($extra_ids) && $detail_id > 0) {
                    foreach ((array) $extra_ids as $ex_id) {
                        $ex = $this->get_extra_row((int) $ex_id);
                        if (!$ex) continue;
                        $this->Pending_order_extra_model->insert_extra($detail_id, (int) $ex_id, $jumlah, (float) $ex->harga);
                    }
                }
            }

            if ($this->db->trans_status() === false) {
                throw new Exception('DB transaction failed');
            }
            $this->db->trans_commit();

            // Simpan info order terakhir untuk halaman selesai/qris.
            $this->session->set_userdata('last_pending_order_id', (int) $order_id);
            $this->session->set_userdata('last_pending_order_payment_method', $payment_method);

            $this->session->unset_userdata('order_cart');
            $this->session->unset_userdata('order_total');
            $this->session->unset_userdata('order_draft_cart');
            $this->session->unset_userdata('order_draft_total');
            $this->session->unset_userdata('order_flow_step');

            if ($payment_method === 'QRIS') {
                redirect('order/qris/' . (int) $order_id);
            }
            redirect('order/selesai');
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            log_message('error', '[MEMBER][ORDER] confirm gagal: ' . $e->getMessage());
            $this->session->set_flashdata('error', 'Gagal mengirim pesanan. Coba lagi ya.');
            redirect('order');
        }
    }

    public function qris($pending_id = null)
    {
        $customer_id = (int) $this->session->userdata('member_id');
        if (!$customer_id) redirect('login');

        $pending_id = (int) $pending_id;
        if ($pending_id <= 0) {
            show_error('Order tidak valid.', 400);
            return;
        }

        if (!$this->midtrans_is_configured()) {
            $this->session->set_flashdata('error', 'QRIS belum dikonfigurasi. Hubungi kasir ya.');
            redirect('order/pay');
            return;
        }

        $order = $this->Pending_order_model->get_for_member($pending_id, $customer_id);

        if (!$order) {
            show_error('Order tidak ditemukan.', 404);
            return;
        }

        $payment_status = strtoupper((string) ($order['payment_status'] ?? ''));
        if ($payment_status === 'PAID') {
            $this->session->set_userdata('last_pending_order_id', (int) $pending_id);
            $this->session->set_userdata('last_pending_order_payment_method', 'QRIS');
            redirect('order/selesai');
            return;
        }

        $this->session->set_userdata('last_pending_order_id', (int) $pending_id);
        $this->session->set_userdata('last_pending_order_payment_method', 'QRIS');

        $payment_ref = (string) ($order['payment_ref'] ?? '');
        $session_key = 'qris_payload_' . (int) $pending_id;
        $qris_payload = $this->session->userdata($session_key);
        if (empty($qris_payload)) {
            $db_qr_url = (string) ($order['payment_qr_url'] ?? '');
            $db_qr_string = (string) ($order['payment_qr_string'] ?? '');
            if ($db_qr_url !== '' || $db_qr_string !== '') {
                $qris_payload = [
                    'order_id' => $payment_ref,
                    'qr_url' => $db_qr_url ?: null,
                    'qr_string' => $db_qr_string ?: null,
                ];
            }
        }

        $qris_error = null;

        if (empty($payment_ref)) {
            $midtrans_order_id = $this->midtrans_build_order_id($pending_id);

            $gross_amount = (int) round((float) ($order['total_penjualan'] ?? 0));
            if ($gross_amount <= 0) {
                show_error('Total order tidak valid.', 400);
                return;
            }

            $item_details = $this->midtrans_item_details($pending_id);

            $payload = [
                'payment_type' => 'qris',
                'transaction_details' => [
                    'order_id' => $midtrans_order_id,
                    'gross_amount' => $gross_amount,
                ],
            ];
            if (!empty($item_details)) {
                $payload['item_details'] = $item_details;
            }

            $resp = $this->midtrans_request('POST', '/v2/charge', $payload);
            if ($resp['ok'] && is_array($resp['json'])) {
                $json = $resp['json'];
                $qr_url = $this->midtrans_parse_qr_actions($json['actions'] ?? []);
                $qr_string = $json['qr_string'] ?? null;
                $has_qr = !empty($qr_url) || !empty($qr_string);

                $qris_payload = [
                    'order_id' => $midtrans_order_id,
                    'transaction_id' => $json['transaction_id'] ?? null,
                    'qr_string' => $qr_string,
                    'qr_url' => $qr_url,
                    'transaction_status' => $json['transaction_status'] ?? null,
                ];

                if ($has_qr) {
                    $this->session->set_userdata($session_key, $qris_payload);
                    $this->Pending_order_model->update_payment($pending_id, [
                        'payment_method' => 'QRIS',
                        'payment_status' => 'PENDING',
                        'payment_provider' => 'MIDTRANS',
                        'payment_ref' => $midtrans_order_id,
                        'payment_qr_url' => $qris_payload['qr_url'] ?? null,
                        'payment_qr_string' => $qris_payload['qr_string'] ?? null,
                    ]);
                } else {
                    $qris_error = $this->midtrans_human_error(
                        $resp,
                        'QRIS belum tersedia dari Midtrans. Silakan buat QR baru.'
                    );
                    log_message('error', '[MEMBER][ORDER] midtrans charge tanpa QR: ' . ($resp['body'] ?: 'no-body'));
                }
            } else {
                $qris_error = $this->midtrans_human_error(
                    $resp,
                    'Gagal membuat QRIS. Coba ulang beberapa saat lagi.'
                );
                log_message('error', '[MEMBER][ORDER] midtrans charge gagal: ' . ($resp['body'] ?: $resp['error']));
            }
        } elseif (empty($qris_payload)) {
            $qris_error = 'QRIS sudah dibuat, tapi QR tidak tersedia. Silakan buat QR baru.';
        }

        $data = [
            'title' => 'QRIS',
            'order' => $order,
            'nomor_meja' => $this->session->userdata('order_nomor_meja'),
            'active_menu' => 'order',
            'qris' => $qris_payload,
            'payment_status' => $payment_status ?: 'PENDING',
            'qris_error' => $qris_error,
        ];

        $this->load->view('templates/member/header', $data);
        $this->load->view('order/qris', $data);
        $this->load->view('templates/member/footer', $data);
    }

    public function qris_status($pending_id = null)
    {
        $customer_id = (int) $this->session->userdata('member_id');
        if (!$customer_id) {
            $this->json_response(['ok' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $pending_id = (int) $pending_id;
        if ($pending_id <= 0) {
            $this->json_response(['ok' => false, 'message' => 'Order tidak valid.'], 400);
            return;
        }

        $order = $this->Pending_order_model->get_for_member($pending_id, $customer_id);

        if (!$order) {
            $this->json_response(['ok' => false, 'message' => 'Order tidak ditemukan.'], 404);
            return;
        }

        $status = strtoupper((string) ($order['payment_status'] ?? 'PENDING'));
        $order_ref = (string) ($order['payment_ref'] ?? '');

        if (!empty($order_ref) && $status === 'PENDING') {
            $synced = $this->midtrans_sync_status($pending_id, $order_ref);
            if (!empty($synced)) {
                $status = (string) $synced;
            }
        }

        $this->json_response([
            'ok' => true,
            'status' => $status,
        ]);
    }

    public function qris_regenerate($pending_id = null)
    {
        $customer_id = (int) $this->session->userdata('member_id');
        if (!$customer_id) redirect('login');

        $pending_id = (int) $pending_id;
        if ($pending_id <= 0) {
            show_error('Order tidak valid.', 400);
            return;
        }

        $order = $this->Pending_order_model->get_for_member($pending_id, $customer_id);

        if (!$order) {
            show_error('Order tidak ditemukan.', 404);
            return;
        }

        $this->Pending_order_model->update_payment($pending_id, [
            'payment_status' => 'PENDING',
            'payment_provider' => 'MIDTRANS',
            'payment_ref' => null,
            'payment_paid_at' => null,
            'payment_qr_url' => null,
            'payment_qr_string' => null,
        ]);

        $session_key = 'qris_payload_' . (int) $pending_id;
        $this->session->unset_userdata($session_key);

        redirect('order/qris/' . (int) $pending_id);
    }

    public function qris_simulate_paid($pending_id = null)
    {
        $customer_id = (int) $this->session->userdata('member_id');
        if (!$customer_id) redirect('login');

        $pending_id = (int) $pending_id;
        if ($pending_id <= 0) {
            show_error('Order tidak valid.', 400);
            return;
        }

        $order = $this->Pending_order_model->get_for_member($pending_id, $customer_id);

        if (!$order) {
            show_error('Order tidak ditemukan.', 404);
            return;
        }

        // Update jadi PAID (dummy).
        $ref = 'DUMMY-' . date('YmdHis') . '-' . $pending_id;
        $this->Pending_order_model->mark_paid($pending_id, 'DUMMY', $ref);

        $this->session->set_userdata('last_pending_order_id', (int) $pending_id);
        $this->session->set_userdata('last_pending_order_payment_method', 'QRIS');

        redirect('order/selesai');
    }

    public function midtrans_callback()
    {
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);

        if (!is_array($payload)) {
            $this->json_response(['ok' => false, 'message' => 'Invalid payload'], 400);
            return;
        }

        $order_id = (string) ($payload['order_id'] ?? '');
        $status_code = (string) ($payload['status_code'] ?? '');
        $gross_amount = (string) ($payload['gross_amount'] ?? '');
        $signature_key = (string) ($payload['signature_key'] ?? '');

        $cfg = $this->midtrans_config();
        $expected = hash('sha512', $order_id . $status_code . $gross_amount . ($cfg['server_key'] ?? ''));
        if ($expected !== $signature_key) {
            $this->json_response(['ok' => false, 'message' => 'Signature mismatch'], 403);
            return;
        }

        $pending_id = 0;
        if (preg_match('/^PO-(\d+)-/i', $order_id, $m)) {
            $pending_id = (int) $m[1];
        }

        if ($pending_id <= 0) {
            $row = $this->Pending_order_model->get_by_payment_ref($order_id);
            $pending_id = (int) ($row['id'] ?? 0);
        }

        if ($pending_id <= 0) {
            $this->json_response(['ok' => false, 'message' => 'Order not found'], 404);
            return;
        }

        $transaction_status = strtolower((string) ($payload['transaction_status'] ?? ''));
        $payment_status = null;
        $paid_at = null;

        if (in_array($transaction_status, ['settlement', 'capture'], true)) {
            $payment_status = 'PAID';
            $paid_at = date('Y-m-d H:i:s');
        } elseif ($transaction_status === 'pending') {
            $payment_status = 'PENDING';
        } elseif ($transaction_status === 'expire') {
            $payment_status = 'EXPIRED';
        } elseif (in_array($transaction_status, ['deny', 'cancel'], true)) {
            $payment_status = 'FAILED';
        }

        if ($payment_status !== null) {
            $this->Pending_order_model->update_payment($pending_id, [
                'payment_status' => $payment_status,
                'payment_provider' => 'MIDTRANS',
                'payment_ref' => $order_id,
                'payment_paid_at' => $paid_at,
            ]);
        }

        $this->json_response(['ok' => true]);
    }
}
