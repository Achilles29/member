<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Customer extends CI_Controller
{
    public function show($id)
    {
        $this->load->model('Customer_model');

        // Ambil data pelanggan berdasarkan ID
        $data['customer'] = $this->Customer_model->get_customer($id);

        // Ambil data stempel pelanggan
        $data['stamps'] = $this->Customer_model->get_stamps($id);

        // Muat halaman view pelanggan
        if ($data['customer']) {
            $this->load->view('customer_page', $data);
        } else {
            show_404(); // Jika pelanggan tidak ditemukan
        }
    }
}
