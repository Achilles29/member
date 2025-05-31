<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Register extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('Member_model');
    }

    public function index() {
        $this->load->view('auth/register');
    }

    public function process()
    {
        $this->load->model('Customer_model');
    
        $nama          = $this->input->post('nama');
        $telepon       = $this->input->post('telepon');
        $alamat        = $this->input->post('alamat');
        $jenis_kelamin = $this->input->post('jenis_kelamin');
        $tanggal_lahir = $this->input->post('tanggal_lahir');
    
        // Cek nomor HP sudah terdaftar
        $existing = $this->Customer_model->get_customer_by_telepon($telepon);
        if ($existing) {
            $this->session->set_flashdata('error', 'Nomor HP sudah terdaftar sebagai member.');
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
    
        $this->Customer_model->insert_customer($data);
    
        $this->session->set_flashdata('success', 'Pendaftaran berhasil. Silakan login.');
        redirect('login');
    }
    }
