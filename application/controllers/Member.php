<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Member extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url', 'text']);
        $this->load->model([
            'Member_model',
            'Poin_model',
            'Voucher_model'
        ]);
    }

    private function check_login()
    {
        if (!$this->session->userdata('member_id')) {
            redirect('login');
            exit;
        }
    }

    public function index()
    {
        $this->check_login();

        $this->load->model('Member_content_model');
        $this->load->model('Stamp_model');

        $member_id = $this->session->userdata('member_id');

        $data = [
            'title'         => 'Dashboard Member',
            'member'        => $this->Member_model->get_member_by_id($member_id),
            'poin'          => $this->Member_model->get_active_poin($member_id),
            'voucher_aktif' => $this->Voucher_model->get_by_status($member_id, 'aktif'),
            'stamp_list'    => $this->Stamp_model->get_active_stamp_by_customer($member_id),
            'promos'        => $this->Member_content_model->get_active_promo(),
            'news'          => $this->Member_content_model->get_active_news(),
        ];

        $data['level'] = $this->Member_model->get_level($data['poin']);
        $data['active_menu'] = 'home';

        // === TEMPLATING VIA CONTROLLER (CI3 BENAR) ===
        $this->load->view('templates/member/header', $data);
        $this->load->view('member/index', $data);
        $this->load->view('templates/member/footer', $data);
    }

    public function logout()
    {
        $this->session->unset_userdata('member_id');
        redirect('login');
    }
}
