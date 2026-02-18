<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Order extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model([
            'Produk_model',
            'Pending_order_model',
            'Pending_order_detail_model',
            'Pending_order_extra_model',
        ]);
        $this->load->helper(['url', 'form']);

        $public_methods = ['midtrans_callback'];
        if (!in_array($this->router->method, $public_methods, true)) {
            // Cek login member
            if (!$this->session->userdata('member_id')) {
                redirect('login?redirect_to=' . urlencode(current_url()));
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
        $details = $this->db->get_where('pr_pending_order_detail', ['pending_order_id' => $pending_id])->result_array();
        foreach ($details as $d) {
            $produk = $this->db->get_where('pr_produk', ['id' => (int) ($d['produk_id'] ?? 0)])->row();
            if (!$produk) continue;

            $qty = (int) ($d['jumlah'] ?? 0);
            if ($qty <= 0) continue;

            $price = (int) round((float) ($produk->harga_jual ?? 0));
            $items[] = [
                'id' => (string) ($produk->id ?? ''),
                'name' => (string) ($produk->nama_produk ?? 'Produk'),
                'price' => $price,
                'quantity' => $qty,
            ];

            $extras = $this->db->get_where('pr_pending_order_extra', ['pending_order_detail_id' => (int) ($d['id'] ?? 0)])->result_array();
            foreach ($extras as $ex) {
                $extraRow = $this->db->get_where('pr_produk_extra', ['id' => (int) ($ex['extra_id'] ?? 0)])->row();
                if (!$extraRow) continue;
                $exQty = (int) ($ex['jumlah'] ?? 0);
                if ($exQty <= 0) continue;
                $exPrice = (int) round((float) ($ex['harga'] ?? 0));
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

    private function compute_review_data_from_cart($cart)
    {
        $produk_list = [];
        $total = 0;

        foreach ((array) $cart as $produk_id => $row) {
            $produk_id = (int) $produk_id;
            $jumlah = (int) ($row['jumlah'] ?? 0);
            if ($produk_id <= 0 || $jumlah <= 0) continue;

            $p = $this->db->get_where('pr_produk', ['id' => $produk_id])->row();
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
                    $ex = $this->db->get_where('pr_produk_extra', ['id' => (int) $ex_id])->row();
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

        // Extras aktif (buat popup pilihan extra).
        $data['extras'] = $this->db->get_where('pr_produk_extra', ['status' => 'aktif'])->result_array();

        // Ambil info member
        $data['member'] = $this->db->get_where('pr_customer', ['id' => $customer_id])->row_array();

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
            $produk = $this->db->get_where('pr_produk', ['id' => (int) $produk_id])->row();
            if (!$produk) continue;

            $jumlah = (int) ($row['jumlah'] ?? 0);
            if ($jumlah <= 0) continue;

            $harga = (float) $produk->harga_jual;
            $total += $harga * $jumlah;

            $extra_ids = $row['extra_ids'] ?? [];
            if (!empty($extra_ids)) {
                foreach ((array) $extra_ids as $ex_id) {
                    $ex = $this->db->get_where('pr_produk_extra', ['id' => (int) $ex_id])->row();
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
        $data['member'] = $this->db->get_where('pr_customer', ['id' => $customer_id])->row_array();
        $data['nomor_meja'] = $this->session->userdata('order_nomor_meja');
        $data['meja_id'] = (int) ($this->session->userdata('order_meja_id') ?? 0);
        $data['pending_order'] = null;
        $data['payment_method'] = $this->session->userdata('last_pending_order_payment_method');

        $pending_id = (int) ($this->session->userdata('last_pending_order_id') ?? 0);
        if ($pending_id > 0) {
            $data['pending_order'] = $this->db->get_where('pr_pending_order', [
                'id' => $pending_id,
                'customer_id' => (int) $customer_id,
            ])->row_array();
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
        if (empty($cart)) {
            $this->session->set_flashdata('error', 'Tidak ada produk yang dipilih.');
            redirect('order');
        }

        $produk_list = [];
        $total = 0;

        foreach ($produk as $produk_id => $jumlah) {
            $row = $this->db->get_where('pr_produk', ['id' => $produk_id])->row();
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
            if (isset($extra[$produk_id])) {
                foreach ($extra[$produk_id] as $ex_id) {
                    $ex = $this->db->get_where('pr_produk_extra', ['id' => $ex_id])->row();
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
            'member' => $this->db->get_where('pr_customer', ['id' => $customer_id])->row_array(),
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
                        $ex = $this->db->get_where('pr_produk_extra', ['id' => (int) $ex_id])->row();
                        if (!$ex) continue;
                        $this->Pending_order_extra_model->insert_extra($detail_id, (int) $ex_id, 1, (float) $ex->harga);
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

        $order = $this->db->get_where('pr_pending_order', [
            'id' => $pending_id,
            'customer_id' => $customer_id,
        ])->row_array();

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

        $payment_ref = (string) ($order['payment_ref'] ?? '');
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
                    $qris_error = 'QRIS belum tersedia dari Midtrans. Silakan buat QR baru.';
                    log_message('error', '[MEMBER][ORDER] midtrans charge tanpa QR: ' . ($resp['body'] ?: 'no-body'));
                }
            } else {
                $qris_error = 'Gagal membuat QRIS. Coba ulang beberapa saat lagi.';
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

        $order = $this->db->get_where('pr_pending_order', [
            'id' => $pending_id,
            'customer_id' => $customer_id,
        ])->row_array();

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

        $order = $this->db->get_where('pr_pending_order', [
            'id' => $pending_id,
            'customer_id' => $customer_id,
        ])->row_array();

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

        $order = $this->db->get_where('pr_pending_order', [
            'id' => $pending_id,
            'customer_id' => $customer_id,
        ])->row_array();

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
            $row = $this->db->get_where('pr_pending_order', [
                'payment_ref' => $order_id,
            ])->row_array();
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
