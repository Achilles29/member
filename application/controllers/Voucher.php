<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Voucher extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Voucher_model', 'Member_model']);
        $this->load->library('form_validation');
        $this->load->helper('url');
    }

    private function require_login()
    {
        if (!$this->session->userdata('member_id')) {
            redirect('login');
        }
    }

    private function base_data()
    {
        $member_id = $this->session->userdata('member_id');
        return [
            'member_id' => $member_id,
            'member' => $this->Member_model->get_member_by_id($member_id),
            'active_menu' => 'voucher',
        ];
    }

    public function index()
    {
        $this->require_login();
        redirect('loyalitas?tab=voucher');
        exit;

        $d = $this->base_data();

        $data['title'] = 'Voucher Saya';
        $data['member'] = $d['member'];
        $data['active_menu'] = $d['active_menu'];

        $data['voucher_aktif'] = $this->Voucher_model->get_by_status($d['member_id'], 'aktif');
        $data['voucher_digunakan'] = $this->Voucher_model->get_by_status($d['member_id'], 'digunakan');
        $data['voucher_kadaluarsa'] = $this->Voucher_model->get_by_status($d['member_id'], 'kadaluarsa');

        $this->load->view('templates/member/header', $data);
        $this->load->view('member/voucher', $data);
        $this->load->view('templates/member/footer', $data);
    }

    public function digunakan()
    {
        $this->require_login();
        $d = $this->base_data();

        $data['title'] = 'Voucher Digunakan';
        $data['member'] = $d['member'];
        $data['active_menu'] = $d['active_menu'];

        $data['voucher_digunakan'] = $this->Voucher_model->get_by_status($d['member_id'], 'digunakan');

        $this->load->view('templates/member/header', $data);
        $this->load->view('member/voucher_digunakan', $data);
        $this->load->view('templates/member/footer', $data);
    }

public function kadaluarsa()
{
    $this->require_login();
    $d = $this->base_data();

    $this->load->library('pagination');

    $per_page = 10;

    // Ambil raw dulu (bisa NULL)
    $page_raw = $this->input->get('page', true);

    // Kalau NULL / kosong / bukan angka => paksa jadi "1" (STRING)
    if ($page_raw === null || $page_raw === '' || !ctype_digit((string)$page_raw)) {
        $_GET['page'] = '1';
        $page = 1;
    } else {
        $page = (int) $page_raw;
        if ($page < 1) $page = 1;
    }

    $total  = $this->Voucher_model->count_by_status($d['member_id'], 'kadaluarsa');
    $offset = ($page - 1) * $per_page;

    $data['title'] = 'Voucher Kadaluarsa';
    $data['member'] = $d['member'];
    $data['active_menu'] = $d['active_menu'];

    $data['voucher_kadaluarsa'] = $this->Voucher_model->get_by_status_paginated(
        $d['member_id'],
        'kadaluarsa',
        $per_page,
        $offset
    );

    $config['base_url'] = site_url('voucher/kadaluarsa');
    $config['total_rows'] = $total;
    $config['per_page'] = $per_page;

    $config['page_query_string'] = TRUE;
    $config['query_string_segment'] = 'page';
    $config['use_page_numbers'] = TRUE;
    $config['reuse_query_string'] = TRUE;

    // ini bikin link halaman pertama juga punya ?page=1 (biar konsisten)
    $config['first_url'] = site_url('voucher/kadaluarsa?page=1');

    $config['num_links'] = 2;

    $config['full_tag_open'] = '<div class="nm-pagination-f7">';
    $config['full_tag_close'] = '</div>';

    $config['first_link'] = '«';
    $config['last_link']  = '»';
    $config['next_link']  = '›';
    $config['prev_link']  = '‹';

    $config['cur_tag_open'] = '<a class="active" href="javascript:void(0)">';
    $config['cur_tag_close']= '</a>';

    $this->pagination->initialize($config);

    $data['pagination_links'] = ($total > $per_page) ? $this->pagination->create_links() : '';

    $this->load->view('templates/member/header', $data);
    $this->load->view('member/voucher_kadaluarsa', $data);
    $this->load->view('templates/member/footer', $data);
}

}
