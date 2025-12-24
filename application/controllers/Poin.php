<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Poin extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url', 'text']);
        $this->load->model('Member_model');
        $this->load->model('Poin_model');
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
        $data['title'] = "Poin Saya";

        $this->load->model('Poin_model');
        $this->load->model('Member_model');
        $member_id = $this->session->userdata('member_id');



        // Ambil filter dari query
        $start = $this->input->get('start') ?? date('Y-m-01');
        // $end = $this->input->get('end') ?? date('Y-m-d');
        $end_raw = $this->input->get('end') ?? date('Y-m-d');  // tetap untuk tampilan input date
        $end = $end_raw . ' 23:59:59';  // digunakan untuk filter created_at

        $limit = $this->input->get('limit') ?? 10;
        $page = max(1, (int) $this->input->get('page'));
        $offset = ($limit !== 'semua') ? ($page - 1) * $limit : 0;


        $total_rows = $this->Poin_model->get_pagination_count($member_id, $start, $end);
        $data['total_rows'] = $total_rows;
        $data['page'] = $page;
        $data['total_pages'] = ($limit !== 'semua') ? ceil($total_rows / $limit) : 1;

        $data['riwayat'] = $this->Poin_model->get_pagination($member_id, $start, $end, $limit, $offset);


        $data['member'] = $this->Member_model->get_member_by_id($member_id);
        $data['poin'] = $this->Poin_model->get_summary($member_id);
        $data['riwayat'] = $this->Poin_model->get_riwayat($member_id, $start, $end, $limit, $offset);
        $data['pagination'] = $this->Poin_model->get_pagination($member_id, $start, $end, $limit, $page);

        $data['start'] = $start;
        // $data['end'] = $end;
        $data['end'] = $end_raw;

        $data['limit'] = $limit;
        $this->load->view('templates/header', $data);
        $this->load->view('member/poin', $data);
        $this->load->view('templates/footer', $data);

    }

    // private function check_login() {
    //     if (!$this->session->userdata('member_id')) {
    //         redirect('login');
    //     }
    // }

    // public function index() {
    //     $this->check_login();
    //     $member_id = $this->session->userdata('member_id');
    //     $data['poin_list'] = $this->Poin_model->get_riwayat_poin($member_id);
    //     $data['total'] = $this->Poin_model->get_total_poin($member_id);
    //     $data['kedaluwarsa_segera'] = $this->Poin_model->get_kedaluwarsa_segera($member_id);
    //     $this->load->view('member/poin', $data);
    // }
}
