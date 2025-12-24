<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Login extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Member_model');
        $this->load->helper('url'); // pastikan ada, tapi kita tetap pakai header()
    }

    public function index()
    {
        $this->load->view('auth/login');
    }

    public function do_login()
    {
        $telepon = $this->input->post('telepon');
        $member = $this->Member_model->get_by_phone($telepon);

        if ($member) {
            $this->session->set_userdata('member_id', $member['id']);
            header("Location: " . base_url('member'));
            exit;
        } else {
            $this->session->set_flashdata('error', 'Nomor HP tidak ditemukan.');
            header("Location: " . base_url('login'));
            exit;
        }
    }

    public function logout()
    {
        $this->session->unset_userdata('member_id');
        header("Location: " . base_url('login'));
        exit;
    }
}
