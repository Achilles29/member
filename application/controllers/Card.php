<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Card extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('Member_model');
        $this->load->model('Poin_model');
    }

    public function index() {
        $member_id = $this->session->userdata('member_id');
        if (!$member_id) redirect('member');

        $data['member'] = $this->Member_model->get_member_by_id($member_id);
        $data['poin'] = $this->Member_model->get_active_poin($member_id);
        $data['level'] = $this->Member_model->get_level($data['poin']);

        $this->load->view('member/card', $data);
    }
    public function login() {
        $this->load->view('member/login');
    }

    public function do_login() {
        $phone = $this->input->post('phone');
        $member = $this->Member_model->get_by_phone($phone);

        if ($member) {
            $this->session->set_userdata('member_id', $member['id']);
            redirect('card');
        } else {
            $this->session->set_flashdata('error', 'Nomor HP tidak ditemukan.');
            redirect('card/login');
        }
    }

    public function logout() {
        $this->session->unset_userdata('member_id');
        redirect('card/login');
    }
}

