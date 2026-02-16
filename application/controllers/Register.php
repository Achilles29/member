<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Register extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Member_model');
        $this->load->helper('url');
    }

    public function index()
    {
        $data = [
            'redirect_to' => $this->input->get('redirect_to', true),
        ];
        $this->load->view('auth/register', $data);
    }

    private function safe_redirect_to($redirect_to)
    {
        $redirect_to = trim((string)$redirect_to);
        if ($redirect_to === '') return null;

        if (strpos($redirect_to, 'http://') === 0 || strpos($redirect_to, 'https://') === 0) {
            $base = rtrim(base_url(), '/');
            if (strpos($redirect_to, $base) !== 0) {
                return null;
            }
            $redirect_to = substr($redirect_to, strlen($base));
            if ($redirect_to === '') $redirect_to = '/';
        }

        if ($redirect_to[0] !== '/') {
            $redirect_to = '/' . $redirect_to;
        }

        if (strpos($redirect_to, '/logout') !== false) {
            return null;
        }
        return ltrim($redirect_to, '/');
    }

    public function process()
    {
        $this->load->model('Customer_model');

        $nama          = $this->input->post('nama');
        $telepon       = $this->input->post('telepon');
        $alamat        = $this->input->post('alamat');
        $jenis_kelamin = $this->input->post('jenis_kelamin');
        $tanggal_lahir = $this->input->post('tanggal_lahir');
        $redirect_to   = $this->safe_redirect_to($this->input->post('redirect_to'));

        // Cek nomor HP sudah terdaftar
        $existing = $this->Customer_model->get_customer_by_telepon($telepon);
        if ($existing) {
            $this->session->set_flashdata('error', 'Nomor HP sudah terdaftar sebagai member.');
            if ($redirect_to) {
                redirect('register?redirect_to=' . urlencode($redirect_to));
            }
            redirect('register');
        }

        // 🧠 Generate kode pelanggan otomatis (YYYYMMDDxxxx)
        $tanggal = date('Ymd');
        $last = $this->Customer_model->get_last_by_date($tanggal);
        $urutan = str_pad(($last + 1), 4, '0', STR_PAD_LEFT);
        $kode_pelanggan = $tanggal . $urutan;

        $data = [
            'kode_pelanggan' => $kode_pelanggan,
            'nama'           => $nama,
            'telepon'        => $telepon,
            'alamat'         => $alamat,
            'jenis_kelamin'  => $jenis_kelamin,
            'tanggal_lahir'  => $tanggal_lahir,
            'created_at'     => date('Y-m-d H:i:s')
        ];

        $new_id = $this->Customer_model->insert_customer($data);
        if (!$new_id) {
            $this->session->set_flashdata('error', 'Pendaftaran gagal. Coba lagi ya.');
            if ($redirect_to) {
                redirect('register?redirect_to=' . urlencode($redirect_to));
            }
            redirect('register');
        }

        // Auto-login supaya alurnya cepat (sesuai order mandiri).
        $this->session->set_userdata('member_id', (int)$new_id);

        if ($redirect_to) {
            redirect($redirect_to);
        }
        redirect('order');
    }
}
