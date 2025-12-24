<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Order extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Produk_model', 'Pending_order_model', 'Pending_order_detail_model']);
        $this->load->helper(['url', 'form']);

        // Cek login member
        if (!$this->session->userdata('member_id')) {
            redirect('login?redirect_to=' . urlencode(current_url()));
        }
    }

    public function index()
    {
        $customer_id = $this->session->userdata('member_id');
        $data['title'] = 'Order Mandiri';

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

        // Ambil info member
        $data['member'] = $this->db->get_where('pr_customer', ['id' => $customer_id])->row_array();

        // Load view
        $this->load->view('templates/header', $data);
        $this->load->view('order/form', $data);
        $this->load->view('templates/footer');
    }


    public function submit()
    {
        $customer_id = $this->session->userdata('member_id');
        $produk = $this->input->post('produk'); // format: [id_produk => jumlah]

        if (!$produk || empty($produk)) {
            $this->session->set_flashdata('error', 'Tidak ada produk yang dipilih.');
            redirect('order');
        }

        $order_id = $this->Pending_order_model->create_order($customer_id, null); // tanpa nomor meja

        foreach ($produk as $id_produk => $jumlah) {
            if ($jumlah > 0) {
                $this->Pending_order_detail_model->insert_detail($order_id, $id_produk, $jumlah);
            }
        }

        redirect('order/selesai');
    }

    public function selesai()
    {
        $customer_id = $this->session->userdata('member_id');
        $data['title'] = 'Order Terkirim';
        $data['member'] = $this->db->get_where('pr_customer', ['id' => $customer_id])->row_array();

        $this->load->view('templates/header', $data);
        $this->load->view('order/selesai', $data);
        $this->load->view('templates/footer');
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

        if (!$produk || empty($produk)) {
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

        $this->load->view('templates/header', $data);
        $this->load->view('order/review', $data);
        $this->load->view('templates/footer');
    }
}
