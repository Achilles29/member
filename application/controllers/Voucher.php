<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Voucher extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Voucher_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $member_id = $this->session->userdata('member_id');

        $data['title'] = 'Voucher Saya';
        $data['member'] = $this->db->get_where('pr_customer', ['id' => $member_id])->row_array();

        $data['voucher_aktif'] = $this->Voucher_model->get_by_status($member_id, 'aktif');
        $data['voucher_digunakan'] = $this->Voucher_model->get_by_status($member_id, 'digunakan');
        $data['voucher_kadaluarsa'] = $this->Voucher_model->get_by_status($member_id, 'kadaluarsa');

        $data['title'] = "Voucher Saya";
        $this->load->view('templates/header', $data);
        $this->load->view('member/voucher', $data);
        $this->load->view('templates/footer', $data);


    }

    public function digunakan()
    {
        $member_id = $this->session->userdata('member_id');

        $data['title'] = 'Voucher Terpakai';
        $data['member'] = $this->db->get_where('pr_customer', ['id' => $member_id])->row_array();

        $data['voucher_aktif'] = $this->Voucher_model->get_by_status($member_id, 'aktif');
        $data['voucher_digunakan'] = $this->Voucher_model->get_by_status($member_id, 'digunakan');
        $data['voucher_kadaluarsa'] = $this->Voucher_model->get_by_status($member_id, 'kadaluarsa');

        $this->load->view('templates/header', $data);
        $this->load->view('member/voucher_digunakan', $data);
        $this->load->view('templates/footer', $data);

    }

    public function kadaluarsa()
    {
        $member_id = $this->session->userdata('member_id');

        $data['title'] = 'Voucher kadaluarsa';
        $data['member'] = $this->db->get_where('pr_customer', ['id' => $member_id])->row_array();

        $data['voucher_aktif'] = $this->Voucher_model->get_by_status($member_id, 'aktif');
        $data['voucher_digunakan'] = $this->Voucher_model->get_by_status($member_id, 'digunakan');
        $data['voucher_kadaluarsa'] = $this->Voucher_model->get_by_status($member_id, 'kadaluarsa');
        $this->load->view('templates/header', $data);
        $this->load->view('member/voucher_kadaluarsa', $data);
        $this->load->view('templates/footer', $data);

    }

}
