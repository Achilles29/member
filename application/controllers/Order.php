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

        // Cek login member
        if (!$this->session->userdata('member_id')) {
            redirect('login?redirect_to=' . urlencode(current_url()));
        }
    }

    private function json_response($payload, $status = 200)
    {
        $this->output
            ->set_status_header((int) $status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($payload));
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
        $payment_status = ($payment_method === 'QRIS') ? 'PENDING' : 'UNPAID';

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
                $payment_status
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

        $order = $this->db->get_where('pr_pending_order', [
            'id' => $pending_id,
            'customer_id' => $customer_id,
        ])->row_array();

        if (!$order) {
            show_error('Order tidak ditemukan.', 404);
            return;
        }

        $data = [
            'title' => 'QRIS (Dummy)',
            'order' => $order,
            'nomor_meja' => $this->session->userdata('order_nomor_meja'),
            'active_menu' => 'order',
        ];

        $this->load->view('templates/member/header', $data);
        $this->load->view('order/qris_dummy', $data);
        $this->load->view('templates/member/footer', $data);
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
}
