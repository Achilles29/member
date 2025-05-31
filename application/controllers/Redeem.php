<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Redeem extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('Redeem_model');
        $this->load->model('Member_model');
        $this->load->helper(['url', 'form', 'text']);
    }

    public function index() {
        $member_id = $this->session->userdata('member_id');
        if (!$member_id) {
            redirect('login');
        }

        

        $data['member'] = $this->Member_model->get_member_by_id($member_id);
        $data['poin'] = $this->Member_model->get_active_poin($member_id);
        $data['stamp'] = $this->Redeem_model->get_active_stamp($member_id);
        $data['redeem_poin'] = $this->Redeem_model->get_all_active_by_type('poin');
        $data['redeem_stamp'] = $this->Redeem_model->get_all_active_by_type('stamp');
                
        $data['title'] = "Redeem";

        $this->load->view('templates/header', $data);
        $this->load->view('member/redeem', $data);
        $this->load->view('templates/footer', $data);

    }


    private function generate_kode_voucher($nama_redeem)
{
    $prefix = strtoupper(preg_replace('/[^A-Z0-9]/', '', substr($nama_redeem, 0, 6)));
    $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
    return $prefix . '-' . $random;
}


public function process($id)
{
    $member_id = $this->session->userdata('member_id');
    $redeem = $this->Redeem_model->get_setting($id);

    if (!$redeem) {
        $this->session->set_flashdata('error', 'Redeem tidak ditemukan.');
        redirect('redeem');
    }

    // Proses potong poin/stamp
    $isValid = $this->Redeem_model->potong_saldo($member_id, $redeem);
    if (!$isValid) {
        $this->session->set_flashdata('error', ucfirst($redeem['jenis']) . ' tidak mencukupi.');
        redirect('redeem');
    }

    // Simpan voucher
    $voucher_id = $this->Redeem_model->simpan_voucher($member_id, $redeem);

    // Simpan log redeem
    $this->Redeem_model->log_redeem($member_id, $redeem, $voucher_id);

    $this->session->set_flashdata('success', 'Redeem berhasil! Voucher telah dibuat.');
    redirect('redeem');
}

    
}
