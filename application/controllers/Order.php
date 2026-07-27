<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Order extends CI_Controller
{
    private $order_schema_ready = false;
    private $self_order_context_cache = null;
    private $order_mode = 'self';

    public function __construct()
    {
        parent::__construct();
        $this->order_mode = strtolower((string) $this->router->class) === 'online_order' ? 'online' : 'self';
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

            if ($this->is_self_order_flow() && !$this->has_self_order_context()) {
                if ($this->input->is_ajax_request()) {
                    $this->json_response([
                        'ok' => false,
                        'message' => 'Scan QR meja dulu untuk membuka self order.'
                    ], 403);
                    return;
                }
                redirect('online-order');
                return;
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

    private function is_self_order_flow()
    {
        return $this->order_mode !== 'online';
    }

    private function is_online_order_flow()
    {
        return $this->order_mode === 'online';
    }

    private function has_self_order_context()
    {
        return (int) ($this->session->userdata('order_meja_id') ?? 0) > 0
            || trim((string) ($this->session->userdata('order_nomor_meja') ?? '')) !== '';
    }

    private function order_base_path()
    {
        return $this->is_online_order_flow() ? 'online-order' : 'order';
    }

    private function order_session_key($suffix)
    {
        $suffix = trim((string) $suffix);
        return ($this->is_online_order_flow() ? 'online_order_' : 'order_') . $suffix;
    }

    private function order_session($suffix)
    {
        return $this->session->userdata($this->order_session_key($suffix));
    }

    private function set_order_session($suffix, $value)
    {
        $this->session->set_userdata($this->order_session_key($suffix), $value);
    }

    private function unset_order_session($suffixes)
    {
        foreach ((array) $suffixes as $suffix) {
            $this->session->unset_userdata($this->order_session_key($suffix));
        }
    }

    private function redirect_order($path = '')
    {
        $path = trim((string) $path, '/');
        redirect($this->order_base_path() . ($path !== '' ? '/' . $path : ''));
    }

    private function product_visibility_context()
    {
        return $this->is_online_order_flow() ? 'online_food' : 'self_order';
    }

    private function current_order_channel()
    {
        return $this->is_online_order_flow() ? 'DELIVERY' : 'SELF_ORDER';
    }

    private function current_service_type($nomor_meja = null)
    {
        if ($this->is_online_order_flow()) {
            return 'DELIVERY';
        }

        return trim((string) $nomor_meja) !== '' ? 'DINE_IN' : 'TAKE_AWAY';
    }

    private function order_view_data(array $data = [])
    {
        $base = [
            'order_mode' => $this->order_mode,
            'is_online_order' => $this->is_online_order_flow(),
            'order_base_path' => $this->order_base_path(),
            'order_storage_suffix' => $this->is_online_order_flow() ? 'online' : 'self_' . (int) ($this->session->userdata('order_meja_id') ?? 0),
            'active_menu' => $this->is_online_order_flow() ? 'online_order' : 'order',
        ];
        if ($this->is_online_order_flow() && !array_key_exists('manual_whatsapp_url', $data)) {
            $base['manual_whatsapp_url'] = $this->online_food_whatsapp_url([]);
        }
        return array_merge($base, $data);
    }

    private function online_food_settings()
    {
        $settings = [
            'payment_default' => 'MANUAL',
            'payment_auto_enabled' => 0,
            'payment_manual_enabled' => 1,
            'manual_whatsapp_number' => '',
            'manual_whatsapp_template' => 'Halo admin, saya mau konfirmasi pesanan Online Food {order_no} dengan total Rp {total}. Mohon dibantu untuk metode pembayaran manual/COD dan estimasi pengantarannya.',
            'manual_payment_instructions' => 'Untuk pembayaran manual, hubungi admin melalui tombol WhatsApp. Setelah admin mengonfirmasi pesanan, kasir akan memproses order dan pembayaran dilakukan melalui POS.',
            'midtrans_server_key' => '',
            'midtrans_client_key' => '',
            'midtrans_is_production' => 0,
        ];

        if ($this->db->table_exists('pos_online_food_setting')) {
            $row = $this->db->get_where('pos_online_food_setting', ['id' => 1])->row_array();
            if ($row) {
                foreach ($settings as $key => $default) {
                    if (array_key_exists($key, $row)) {
                        $settings[$key] = $row[$key];
                    }
                }
            }
        }

        return $settings;
    }

    private function online_food_whatsapp_url(array $pending_order = [])
    {
        $settings = $this->online_food_settings();
        $phone = preg_replace('/\D+/', '', (string) ($settings['manual_whatsapp_number'] ?? ''));
        if ($phone === '') {
            return '';
        }
        if (strpos($phone, '0') === 0) {
            $phone = '62' . substr($phone, 1);
        }

        $order_no = (string) ($pending_order['order_no'] ?? '');
        if ($order_no === '' && !empty($pending_order['id'])) {
            $order_no = '#' . (int) $pending_order['id'];
        }
        $template = trim((string) ($settings['manual_whatsapp_template'] ?? ''));
        if ($template === '') {
            $template = 'Halo admin, saya ingin konfirmasi order online food {order_no}.';
        }
        $message = str_replace(
            ['{order_no}', '{order_id}', '{total}'],
            [$order_no, (string) ($pending_order['id'] ?? ''), number_format((float) ($pending_order['grand_total'] ?? 0), 0, ',', '.')],
            $template
        );

        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
    }

    private function online_customer_location_from_post()
    {
        $lat = trim((string) $this->input->post('customer_lat', true));
        $lng = trim((string) $this->input->post('customer_lng', true));
        $accuracy = trim((string) $this->input->post('customer_location_accuracy', true));
        if ($lat === '' || $lng === '' || !is_numeric($lat) || !is_numeric($lng)) {
            return null;
        }
        $latFloat = (float) $lat;
        $lngFloat = (float) $lng;
        if ($latFloat < -90 || $latFloat > 90 || $lngFloat < -180 || $lngFloat > 180) {
            return null;
        }
        return [
            'lat' => $latFloat,
            'lng' => $lngFloat,
            'accuracy' => is_numeric($accuracy) ? max(0, (float) $accuracy) : 0,
        ];
    }

    private function midtrans_config()
    {
        $cfg = [
            'server_key' => '',
            'client_key' => '',
            'is_production' => false,
            'is_enabled' => false,
        ];

        $qrisTable = null;
        if ($this->is_online_order_flow() && $this->db->table_exists('pos_online_food_setting')) {
            $qrisTable = 'pos_online_food_setting';
        } elseif ($this->db->table_exists('pos_self_order_qris_setting')) {
            $qrisTable = 'pos_self_order_qris_setting';
        } elseif ($this->db->table_exists('pr_qris_setting')) {
            $qrisTable = 'pr_qris_setting';
        }

        if ($qrisTable !== null) {
            $row = $this->db->get_where($qrisTable, ['id' => 1])->row_array();
            if ($row) {
                $cfg['server_key'] = (string) ($row['midtrans_server_key'] ?? '');
                $cfg['client_key'] = (string) ($row['midtrans_client_key'] ?? '');
                $cfg['is_production'] = !empty($row['midtrans_is_production']);
                $cfg['is_enabled'] = $this->is_online_order_flow()
                    ? ((int) ($row['payment_auto_enabled'] ?? 0)) === 1
                    : ((int) ($row['is_enabled'] ?? 0)) === 1;
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

    private function current_self_order_context()
    {
        if (is_array($this->self_order_context_cache)) {
            return $this->self_order_context_cache;
        }

        $table_id = (int) ($this->session->userdata('order_meja_id') ?? 0);
        $table_no = (string) ($this->session->userdata('order_nomor_meja') ?? '');
        $context = $this->Pending_order_model->resolve_current_self_order_context($table_id, $table_no);
        $this->self_order_context_cache = is_array($context) ? $context : [];

        return $this->self_order_context_cache;
    }

    private function current_self_order_outlet_id()
    {
        $context = $this->current_self_order_context();
        return (int) ($context['outlet_id'] ?? 0);
    }

    private function get_product_row($produk_id)
    {
        $produk_id = (int) $produk_id;
        if ($produk_id <= 0) {
            return null;
        }
        return $this->Produk_model->get_by_id($produk_id, $this->current_self_order_outlet_id(), $this->product_visibility_context());
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

        $this->db
            ->select('e.id, e.extra_name as nama_extra, e.selling_price as harga', false)
            ->from('mst_extra e')
            ->where('e.is_active', 1);

        if ($this->db->field_exists('show_in_self_order', 'mst_extra')) {
            $this->db->where('e.show_in_self_order', 1);
        }

        return $this->db->order_by('e.extra_name', 'ASC')->get()->result_array();
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
                'catatan' => $this->normalize_order_line_note($row['catatan'] ?? ''),
            ];
        }
        return $out;
    }

    private function normalize_order_line_note($value)
    {
        $text = trim((string) $value);
        if ($text === '') {
            return '';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, 255);
        }

        return substr($text, 0, 255);
    }

    /**
     * Ambil opsi extra per-produk — selaras dengan logika POS (Pos_model::load_product_extra_group_map).
     * Tabel: mst_product_extra_map → mst_extra_group → mst_extra_group_item → mst_extra
     */
    private function fetch_extra_groups_for_produk($produk_id)
    {
        $produk_id = (int) $produk_id;
        if ($produk_id <= 0) {
            return ['produk_id' => 0, 'groups' => []];
        }

        if (
            !$this->db->table_exists('mst_product_extra_map')
            || !$this->db->table_exists('mst_extra_group')
            || !$this->db->table_exists('mst_extra_group_item')
            || !$this->db->table_exists('mst_extra')
        ) {
            return ['produk_id' => $produk_id, 'groups' => []];
        }

        $extra_join = 'e.id = gi.extra_id AND e.is_active = 1';
        if ($this->db->field_exists('show_in_self_order', 'mst_extra')) {
            $extra_join .= ' AND e.show_in_self_order = 1';
        }

        $rows = $this->db
            ->select('
                g.id AS group_id,
                g.group_name,
                g.sort_order AS group_sort_order,
                g.is_required,
                g.min_select,
                g.max_select,
                gi.sort_order AS item_sort_order,
                e.id AS extra_id,
                e.extra_name,
                e.extra_type,
                e.selling_price,
                e.cost_amount
            ', false)
            ->from('mst_product_extra_map m')
            ->join('mst_extra_group g', 'g.id = m.extra_group_id AND g.is_active = 1', 'inner')
            ->join('mst_extra_group_item gi', 'gi.extra_group_id = g.id', 'inner')
            ->join('mst_extra e', $extra_join, 'inner')
            ->where('m.product_id', $produk_id)
            ->order_by('COALESCE(g.sort_order, 999999)', 'ASC', false)
            ->order_by('g.id', 'ASC')
            ->order_by('COALESCE(gi.sort_order, 999999)', 'ASC', false)
            ->order_by('e.id', 'ASC')
            ->get()
            ->result_array();

        $groups_map = [];
        foreach ($rows as $row) {
            $gid = (int) $row['group_id'];
            if (!isset($groups_map[$gid])) {
                $is_required = (int) ($row['is_required'] ?? 0);
                $min = (int) ($row['min_select'] ?? 0);
                $max = (int) ($row['max_select'] ?? 1);
                if ($is_required === 1 && $min <= 0) $min = 1;
                if ($is_required === 0) $min = 0;
                if ($max <= 0) $max = 1;

                $groups_map[$gid] = [
                    'id'         => $gid,
                    'nama_group' => (string) ($row['group_name'] ?? ''),
                    'group_sort_order' => (int) ($row['group_sort_order'] ?? 0),
                    'is_wajib'   => $is_required,
                    'min_pilih'  => $min,
                    'max_pilih'  => $max,
                    'items'      => [],
                ];
            }
            $groups_map[$gid]['items'][] = [
                'id'         => (int) ($row['extra_id'] ?? 0),
                'nama_extra' => (string) ($row['extra_name'] ?? ''),
                'harga'      => (float) ($row['selling_price'] ?? 0),
                'hpp'        => (float) ($row['cost_amount'] ?? 0),
                'tipe_extra' => (string) ($row['extra_type'] ?? 'ADD'),
                'item_sort_order' => (int) ($row['item_sort_order'] ?? 0),
            ];
        }
        $groups = array_values($groups_map);
        usort($groups, static function (array $a, array $b): int {
            $cmp = ((int) ($a['group_sort_order'] ?? 0)) <=> ((int) ($b['group_sort_order'] ?? 0));
            if ($cmp !== 0) {
                return $cmp;
            }
            return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
        });
        foreach ($groups as &$group) {
            $items = array_values((array) ($group['items'] ?? []));
            usort($items, static function (array $a, array $b): int {
                $cmp = ((int) ($a['item_sort_order'] ?? 0)) <=> ((int) ($b['item_sort_order'] ?? 0));
                if ($cmp !== 0) {
                    return $cmp;
                }
                return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
            });
            foreach ($items as &$item) {
                unset($item['item_sort_order']);
            }
            unset($item);
            $group['items'] = $items;
            unset($group['group_sort_order']);
        }
        unset($group);

        return ['produk_id' => $produk_id, 'groups' => $groups];
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
                'catatan' => $this->normalize_order_line_note($row['catatan'] ?? ''),
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
                'catatan' => $this->normalize_order_line_note($row['catatan'] ?? ''),
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
        $step = (string) ($this->order_session('flow_step') ?? '');
        $cart_final = $this->order_session('cart');
        $cart_draft = $this->order_session('draft_cart');

        if ($step === 'pay' && is_array($cart_final) && !empty($cart_final)) {
            $this->redirect_order('pay');
            return;
        }

        if ($step === 'pay' && is_array($cart_draft) && !empty($cart_draft)) {
            $this->redirect_order('pay');
            return;
        }

        if ($step === 'review' && is_array($cart_draft) && !empty($cart_draft)) {
            $this->redirect_order('review_session');
            return;
        }

        $this->redirect_order();
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
        $availability = $this->validate_cart_product_availability($cart);
        if (!$availability['ok']) {
            $this->json_response(['ok' => false, 'error' => 'stock_unavailable', 'message' => $availability['message']], 422);
            return;
        }
        $step = strtoupper(trim((string) ($payload['step'] ?? '')));
        $step = strtolower($step);
        if (!in_array($step, ['menu', 'review', 'pay'], true)) {
            $step = 'menu';
        }

        // Total dihitung server-side (anti manipulasi).
        $total = $this->compute_total_from_cart($cart);

        $this->set_order_session('draft_cart', $cart);
        $this->set_order_session('draft_total', $total);
        $this->set_order_session('flow_step', $step);

        $this->json_response(['ok' => true, 'total' => $total]);
    }

    public function clear_cart()
    {
        $this->unset_order_session(['draft_cart', 'draft_total', 'cart', 'total', 'flow_step']);

        $this->redirect_order();
    }

    public function menu()
    {
        // Bypass "resume" redirect supaya tombol "Tambah menu" dari review bisa balik ke list menu.
        // Keranjang draft tetap disimpan, tapi keranjang final di-reset agar total dihitung ulang saat confirm.
        $this->set_order_session('flow_step', 'menu');
        $this->unset_order_session(['cart', 'total']);

        $this->redirect_order();
    }

    public function index()
    {
        $customer_id = $this->session->userdata('member_id');
        $data = $this->order_view_data([
            'title' => $this->is_online_order_flow() ? 'Online Order' : 'Order',
            'nomor_meja' => $this->is_self_order_flow() ? $this->session->userdata('order_nomor_meja') : null,
            'meja_id' => $this->is_self_order_flow() ? (int) ($this->session->userdata('order_meja_id') ?? 0) : 0,
        ]);

        // Resume logic (kalau user sudah punya keranjang / sudah sampai pay).
        $step = (string) ($this->order_session('flow_step') ?? '');
        $cart_final = $this->order_session('cart');
        $cart_draft = $this->order_session('draft_cart');
        if ($step === 'pay' && is_array($cart_final) && !empty($cart_final)) {
            $this->redirect_order('pay');
            return;
        }
        if ($step === 'pay' && is_array($cart_draft) && !empty($cart_draft)) {
            $this->redirect_order('pay');
            return;
        }
        if ($step === 'review' && is_array($cart_draft) && !empty($cart_draft)) {
            $this->redirect_order('review_session');
            return;
        }

        // Ambil semua kategori aktif dan urutkan
        $this->load->model('Kategori_model');
        $kategori = $this->Kategori_model->get_all($this->product_visibility_context()); // status = 1, urutan ASC
        $data['kategori'] = $kategori;

        // Ambil produk berdasarkan kategori (dikelompokkan)
        $this->load->model('Produk_model');
        $data['produk_per_kategori'] = [];
        $outlet_id = $this->current_self_order_outlet_id();
        foreach ($kategori as $kat) {
            $data['produk_per_kategori'][$kat->id] = $this->Produk_model->get_by_kategori($kat->id, $outlet_id, $this->product_visibility_context());
        }

        $data['extras'] = $this->get_active_extras_lookup();

        // Ambil info member
        $data['member'] = $this->get_member_row($customer_id);

        // Draft cart untuk initial state (dipakai JS).
        $data['draft_cart'] = $this->order_session('draft_cart');
        $data['flow_step'] = (string) ($this->order_session('flow_step') ?? 'menu');

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

    private function validate_cart_product_availability($cart)
    {
        $cart = $this->normalize_cart($cart);
        if (empty($cart)) {
            return ['ok' => true];
        }

        foreach ($cart as $produk_id => $row) {
            $produk = $this->get_product_row((int) $produk_id);
            if (!$produk) {
                return [
                    'ok' => false,
                    'message' => 'Salah satu menu di keranjang sudah tidak tersedia. Silakan pilih ulang menu.'
                ];
            }

            if ((int) ($produk->is_available_for_order ?? 0) !== 1) {
                $reason = trim((string) ($produk->bottleneck_name ?? ''));
                $suffix = $reason !== '' ? ' Penyebab: ' . $reason . '.' : '';
                return [
                    'ok' => false,
                    'message' => 'Menu ' . (string) ($produk->nama_produk ?? 'ini') . ' sedang habis.' . $suffix . ' Silakan hapus dari keranjang lalu pilih menu lain.'
                ];
            }

            $requestedQty = max(0, (int) ($row['jumlah'] ?? 0));
            $availableQty = (float) ($produk->stok_tersedia ?? 0);
            if ($requestedQty > 0 && $availableQty > 0 && $requestedQty > floor($availableQty)) {
                return [
                    'ok' => false,
                    'message' => 'Stok menu ' . (string) ($produk->nama_produk ?? 'ini') . ' hanya cukup untuk ' . (int) floor($availableQty) . ' porsi. Silakan kurangi jumlahnya.'
                ];
            }
        }

        return ['ok' => true];
    }

    public function submit()
    {
        // Backward-compat: flow lama yang langsung POST ke submit.
        $this->confirm();
    }

    public function selesai()
    {
        $customer_id = $this->session->userdata('member_id');
        $data = $this->order_view_data([
            'title' => $this->is_online_order_flow() ? 'Online Order Terkirim' : 'Order Terkirim',
            'member' => $this->get_member_row($customer_id),
            'nomor_meja' => $this->is_self_order_flow() ? $this->session->userdata('order_nomor_meja') : null,
            'meja_id' => $this->is_self_order_flow() ? (int) ($this->session->userdata('order_meja_id') ?? 0) : 0,
            'pending_order' => null,
            'payment_method' => $this->order_session('last_pending_order_payment_method'),
        ]);

        $pending_id = (int) ($this->order_session('last_pending_order_id') ?? 0);
        if ($pending_id > 0) {
            $data['pending_order'] = $this->Pending_order_model->get_for_member($pending_id, (int) $customer_id);
            if ($this->is_online_order_flow() && strtoupper((string) $data['payment_method']) === 'KASIR') {
                $data['manual_whatsapp_url'] = $this->online_food_whatsapp_url((array) $data['pending_order']);
            }
        }

        $this->load->view('templates/member/header', $data);
        $this->load->view('order/selesai', $data);
        $this->load->view('templates/member/footer', $data);
    }


    public function filter_produk()
    {
        $this->load->model('Produk_model');
        $outlet_id = $this->current_self_order_outlet_id();

        $keyword = $this->input->post('keyword');
        $kategori = $this->input->post('kategori');

        $data['produk'] = $this->Produk_model->search($keyword, $kategori, $outlet_id, $this->product_visibility_context());
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
            $this->redirect_order();
        }
        $cart = $sanitized['cart'];
        $availability = $this->validate_cart_product_availability($cart);
        if (!$availability['ok']) {
            $this->session->set_flashdata('error', $availability['message']);
            $this->redirect_order();
            return;
        }
        if (empty($cart)) {
            $this->session->set_flashdata('error', 'Tidak ada produk yang dipilih.');
            $this->redirect_order();
        }

        [$produk_list, $total] = $this->compute_review_data_from_cart($cart);

        $data['produk_list'] = $produk_list;
        $data['total'] = $total;
        $data = $this->order_view_data(array_merge($data, [
            'title' => $this->is_online_order_flow() ? 'Review Online Order' : 'Review Order',
            'nomor_meja' => $this->is_self_order_flow() ? $this->session->userdata('order_nomor_meja') : null,
        ]));

        // Simpan ke session biar pay/confirm tidak tergantung hidden input.
        $this->set_order_session('draft_cart', $cart);
        $this->set_order_session('draft_total', $total);
        $this->set_order_session('cart', $cart);
        $this->set_order_session('total', $total);
        $this->set_order_session('flow_step', 'review');

        $this->load->view('templates/member/header', $data);
        $this->load->view('order/review', $data);
        $this->load->view('templates/member/footer');
    }

    public function review_session()
    {
        $customer_id = $this->session->userdata('member_id');
        if (!$customer_id) redirect('login');

        $cart = $this->order_session('draft_cart');
        if (empty($cart) || !is_array($cart)) {
            $this->session->set_flashdata('error', 'Keranjang kosong. Pilih menu dulu ya.');
            $this->redirect_order();
        }

        $cart = $this->normalize_cart($cart);
        $sanitized = $this->sanitize_cart_extra_rules($cart);
        if (!$sanitized['ok']) {
            $this->session->set_flashdata('error', $sanitized['message']);
            $this->redirect_order();
        }
        $cart = $sanitized['cart'];
        $availability = $this->validate_cart_product_availability($cart);
        if (!$availability['ok']) {
            $this->session->set_flashdata('error', $availability['message']);
            $this->redirect_order();
            return;
        }
        [$produk_list, $total] = $this->compute_review_data_from_cart($cart);
        if (empty($produk_list)) {
            $this->session->set_flashdata('error', 'Keranjang kosong. Pilih menu dulu ya.');
            $this->redirect_order();
        }

        $this->set_order_session('cart', $cart);
        $this->set_order_session('total', $total);
        $this->set_order_session('flow_step', 'review');

        $data = $this->order_view_data([
            'title' => $this->is_online_order_flow() ? 'Review Online Order' : 'Review Order',
            'nomor_meja' => $this->is_self_order_flow() ? $this->session->userdata('order_nomor_meja') : null,
            'produk_list' => $produk_list,
            'total' => $total,
            'member' => $this->get_member_row($customer_id),
        ]);

        $this->load->view('templates/member/header', $data);
        $this->load->view('order/review', $data);
        $this->load->view('templates/member/footer', $data);
    }

    public function pay()
    {
        $customer_id = $this->session->userdata('member_id');
        if (!$customer_id) redirect('login');

        $cart = $this->order_session('cart');
        if (empty($cart) || !is_array($cart)) {
            // Fallback: kalau keranjang final belum kebentuk, ambil dari draft.
            $draft = $this->order_session('draft_cart');
            $draft = $this->normalize_cart($draft);
            if (!empty($draft)) {
                $cart = $draft;
                $this->set_order_session('cart', $cart);
                $this->set_order_session('total', $this->compute_total_from_cart($cart));
            } else {
                $this->session->set_flashdata('error', 'Keranjang kosong. Pilih menu dulu ya.');
                $this->redirect_order();
            }
        }

        $availability = $this->validate_cart_product_availability($cart);
        if (!$availability['ok']) {
            $this->session->set_flashdata('error', $availability['message']);
            $this->redirect_order();
            return;
        }

        // Mark step buat resume (scan ulang langsung balik ke halaman pay).
        $this->set_order_session('flow_step', 'pay');

        $onlineSettings = $this->is_online_order_flow() ? $this->online_food_settings() : [];
        $manualPaymentEnabled = $this->is_online_order_flow()
            ? ((int) ($onlineSettings['payment_manual_enabled'] ?? 1) === 1)
            : true;
        $qrisPaymentEnabled = $this->is_online_order_flow()
            ? (((int) ($onlineSettings['payment_auto_enabled'] ?? 0) === 1) && $this->midtrans_is_configured())
            : $this->midtrans_is_configured();
        $defaultPaymentMethod = strtoupper((string) ($onlineSettings['payment_default'] ?? 'MANUAL')) === 'AUTO' && $qrisPaymentEnabled
            ? 'QRIS'
            : 'KASIR';
        if (!$manualPaymentEnabled && $qrisPaymentEnabled) {
            $defaultPaymentMethod = 'QRIS';
        }

        $data = $this->order_view_data([
            'title' => $this->is_online_order_flow() ? 'Pembayaran Online Order' : 'Pembayaran',
            'total' => (float) $this->order_session('total'),
            'nomor_meja' => $this->is_self_order_flow() ? $this->session->userdata('order_nomor_meja') : null,
            'payment_method' => $defaultPaymentMethod,
            'manual_payment_enabled' => $manualPaymentEnabled,
            'qris_enabled' => $qrisPaymentEnabled,
            'cash_payment_label' => $this->is_online_order_flow() ? 'Manual admin / konfirmasi WA' : 'Bayar di kasir',
            'payment_hint' => $this->is_online_order_flow()
                ? 'Pilih pembayaran otomatis QRIS atau manual admin. Ongkir berdasarkan jarak akan ditambahkan pada tahap berikutnya.'
                : 'Pilih metode pembayaran. Default: bayar di kasir. QRIS via Midtrans.',
            'manual_payment_instructions' => (string) ($onlineSettings['manual_payment_instructions'] ?? ''),
            'catatan_placeholder' => $this->is_online_order_flow()
                ? 'Contoh: alamat lengkap, patokan, atau instruksi pengantaran.'
                : 'Contoh: tanpa es, kurang manis, dll.',
        ]);

        $this->load->view('templates/member/header', $data);
        $this->load->view('order/pay', $data);
        $this->load->view('templates/member/footer', $data);
    }

    public function confirm()
    {
        $customer_id = (int) $this->session->userdata('member_id');
        if (!$customer_id) redirect('login');

        $cart = $this->order_session('cart');
        if (empty($cart) || !is_array($cart)) {
            $produk = $this->input->post('produk');
            $extra = $this->input->post('extra');
            $cart = $this->build_cart_from_post($produk, $extra);
        }

        if (empty($cart)) {
            $this->session->set_flashdata('error', 'Keranjang kosong. Pilih menu dulu ya.');
            $this->redirect_order();
        }

        $sanitized = $this->sanitize_cart_extra_rules($cart);
        if (!$sanitized['ok']) {
            $this->session->set_flashdata('error', $sanitized['message']);
            $this->redirect_order();
        }
        $cart = $sanitized['cart'];
        $availability = $this->validate_cart_product_availability($cart);
        if (!$availability['ok']) {
            $this->session->set_flashdata('error', $availability['message']);
            $this->redirect_order();
            return;
        }

        $nomor_meja = $this->is_self_order_flow() ? $this->session->userdata('order_nomor_meja') : null;
        $table_id = $this->is_self_order_flow() ? (int) $this->session->userdata('order_meja_id') : 0;
        $catatan = $this->input->post('catatan', true);
        if ($this->is_online_order_flow()) {
            $customerLocation = $this->online_customer_location_from_post();
            if (!$customerLocation) {
                $this->session->set_flashdata('error', 'Lokasi wajib aktif untuk online order. Izinkan lokasi lalu kirim ulang pesanan.');
                $this->redirect_order('pay');
                return;
            }
            $locationNote = 'Lokasi customer: ' . number_format((float) $customerLocation['lat'], 7, '.', '') . ',' . number_format((float) $customerLocation['lng'], 7, '.', '');
            if (!empty($customerLocation['accuracy'])) {
                $locationNote .= ' akurasi ' . number_format((float) $customerLocation['accuracy'], 0, ',', '.') . 'm';
            }
            $catatan = trim((string) $catatan . ((string) $catatan !== '' ? "\n" : '') . $locationNote);
        }
        $payment_method = strtoupper(trim((string) $this->input->post('payment_method', true)));
        if (!in_array($payment_method, ['KASIR', 'QRIS'], true)) {
            $payment_method = 'KASIR';
        }
        $onlineSettings = $this->is_online_order_flow() ? $this->online_food_settings() : [];
        if ($this->is_online_order_flow()) {
            $manualEnabled = (int) ($onlineSettings['payment_manual_enabled'] ?? 1) === 1;
            $autoEnabled = (int) ($onlineSettings['payment_auto_enabled'] ?? 0) === 1;
            if ($payment_method === 'KASIR' && !$manualEnabled && $autoEnabled) {
                $payment_method = 'QRIS';
            }
            if ($payment_method === 'QRIS' && !$autoEnabled && $manualEnabled) {
                $payment_method = 'KASIR';
            }
        }
        if ($payment_method === 'QRIS' && !$this->midtrans_is_configured()) {
            $payment_method = 'KASIR';
            $this->session->set_flashdata('error', $this->is_online_order_flow()
                ? 'QRIS sedang nonaktif. Silakan pilih bayar saat diterima.'
                : 'QRIS sedang nonaktif. Silakan bayar di kasir.'
            );
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
                $payment_provider,
                null,
                null,
                $table_id,
                $this->current_order_channel(),
                $this->current_service_type($nomor_meja)
            );

            foreach ($cart as $produk_id => $row) {
                $jumlah = (int) ($row['jumlah'] ?? 0);
                if ($jumlah <= 0) continue;
                $produk_row = $this->get_product_row((int) $produk_id);
                if (!$produk_row) continue;

                $detail_id = $this->Pending_order_detail_model->insert_detail(
                    $order_id,
                    (int) $produk_id,
                    $jumlah,
                    $this->normalize_order_line_note($row['catatan'] ?? ''),
                    (float) ($produk_row->harga_jual ?? 0)
                );

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
            $this->set_order_session('last_pending_order_id', (int) $order_id);
            $this->set_order_session('last_pending_order_payment_method', $payment_method);

            $this->unset_order_session(['cart', 'total', 'draft_cart', 'draft_total', 'flow_step']);

            if ($payment_method === 'QRIS') {
                $this->redirect_order('qris/' . (int) $order_id);
            }
            $this->redirect_order('selesai');
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            log_message('error', '[MEMBER][ORDER] confirm gagal: ' . $e->getMessage());
            $this->session->set_flashdata('error', 'Gagal mengirim pesanan. Coba lagi ya.');
            $this->redirect_order();
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

        $order = $this->Pending_order_model->get_for_member($pending_id, $customer_id);

        if (!$order) {
            show_error('Order tidak ditemukan.', 404);
            return;
        }

        $order_status = strtoupper((string) ($order['status'] ?? 'PENDING'));
        $is_rejected_order = in_array($order_status, ['REJECTED', 'CANCELLED', 'CANCEL', 'VOID'], true);
        if (!$is_rejected_order && !$this->midtrans_is_configured()) {
            $this->session->set_flashdata('error', 'QRIS belum dikonfigurasi. Hubungi kasir ya.');
            $this->redirect_order('pay');
            return;
        }
        $payment_status = strtoupper((string) ($order['payment_status'] ?? ''));
        if ($is_rejected_order) {
            $payment_status = 'REJECTED';
        }
        if (!$is_rejected_order && $payment_status === 'PAID') {
            $this->set_order_session('last_pending_order_id', (int) $pending_id);
            $this->set_order_session('last_pending_order_payment_method', 'QRIS');
            $this->redirect_order('selesai');
            return;
        }

        $this->set_order_session('last_pending_order_id', (int) $pending_id);
        $this->set_order_session('last_pending_order_payment_method', 'QRIS');

        $payment_ref = (string) ($order['payment_ref'] ?? '');
        $session_key = $this->order_session_key('qris_payload_' . (int) $pending_id);
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

        if ($is_rejected_order) {
            $qris_payload = [];
            $qris_error = 'Pesanan ini sudah ditolak kasir.'
                . (!empty($order['rejection_reason']) ? ' Alasan: ' . (string) $order['rejection_reason'] : '');
        }

        $data = $this->order_view_data([
            'title' => 'QRIS',
            'order' => $order,
            'nomor_meja' => $this->is_self_order_flow() ? $this->session->userdata('order_nomor_meja') : null,
            'qris' => $qris_payload,
            'payment_status' => $payment_status ?: 'PENDING',
            'qris_error' => $qris_error,
            'is_rejected_order' => $is_rejected_order,
        ]);

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

        $order_status = strtoupper((string) ($order['status'] ?? 'PENDING'));
        if (in_array($order_status, ['REJECTED', 'CANCELLED', 'CANCEL', 'VOID'], true)) {
            $this->json_response([
                'ok' => true,
                'status' => 'REJECTED',
                'order_status' => $order_status,
                'rejected' => true,
            ]);
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

        if (in_array(strtoupper((string) ($order['status'] ?? 'PENDING')), ['REJECTED', 'CANCELLED', 'CANCEL', 'VOID'], true)) {
            $this->session->set_flashdata('error', 'Pesanan ini sudah ditolak kasir dan QR tidak bisa dibuat ulang.');
            $this->redirect_order();
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

        $session_key = $this->order_session_key('qris_payload_' . (int) $pending_id);
        $this->session->unset_userdata($session_key);

        $this->redirect_order('qris/' . (int) $pending_id);
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

        $this->set_order_session('last_pending_order_id', (int) $pending_id);
        $this->set_order_session('last_pending_order_payment_method', 'QRIS');

        $this->redirect_order('selesai');
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
