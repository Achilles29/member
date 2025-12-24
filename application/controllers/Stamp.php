<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Stamp extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Stamp_model', 'Member_model']);
        $this->load->helper('url');
    }

    public function index()
    {
        if (!$this->session->userdata('member_id')) {
            redirect('login');
        }

        $member_id = $this->session->userdata('member_id');
        $data['member'] = $this->Member_model->get_member_by_id($member_id);
        $data['stamp_list'] = $this->Stamp_model->get_active_stamp_by_customer($member_id);
        $data['title'] = "Stamp Saya";

        $this->load->view('templates/header', $data);
        $this->load->view('member/stamp', $data);
        $this->load->view('templates/footer', $data);


    }



}
