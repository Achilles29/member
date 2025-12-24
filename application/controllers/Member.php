<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Member extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url', 'text']);
        $this->load->model(['Member_model', 'Poin_model']);
        $this->load->model('Voucher_model');
    }

    private function check_login()
    {
        if (!$this->session->userdata('member_id')) {
            header("Location: " . base_url('login'));
            exit;
        }
    }

    public function index()
    {
        $this->check_login();
        $data['title'] = "Dashboard Member";

        $this->load->model('Member_content_model');
        $this->load->model('Stamp_model');
        $member_id = $this->session->userdata('member_id');
        $data['voucher_aktif'] = $this->Voucher_model->get_by_status($member_id, 'aktif');

        $data['stamp_list'] = $this->Stamp_model->get_active_stamp_by_customer($member_id);

        $data['promos'] = $this->Member_content_model->get_active_promo();
        $data['news']   = $this->Member_content_model->get_active_news();
        $data['member'] = $this->Member_model->get_member_by_id($member_id);
        $data['poin']   = $this->Member_model->get_active_poin($member_id);
        $data['level']  = $this->Member_model->get_level($data['poin']);

        $this->load->view('templates/header', $data);
        $this->load->view('member/index', $data);
        $this->load->view('templates/footer', $data);
    }

    public function logout()
    {
        $this->session->unset_userdata('member_id');
        header("Location: " . base_url('login'));
        exit;
    }
}
