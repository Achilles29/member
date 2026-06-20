<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Poin extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url', 'text']);
        $this->load->model(['Member_model', 'Poin_model']);
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
        redirect('loyalitas?tab=poin');
        exit;

        $member_id = $this->session->userdata('member_id');

        // FILTER
        $start    = $this->input->get('start') ?? date('Y-m-01');
        $end_raw  = $this->input->get('end') ?? date('Y-m-d');
        $end      = $end_raw . ' 23:59:59';

        $limit  = $this->input->get('limit') ?? 10;
        $page   = max(1, (int) $this->input->get('page'));
        $offset = ($limit !== 'semua') ? ($page - 1) * $limit : 0;

        // data poin aktif untuk level
        $poin_aktif = $this->Member_model->get_active_poin($member_id);

        $total_rows = $this->Poin_model->get_pagination_count($member_id, $start, $end);

        $data = [
            'title'       => 'Poin Saya',
            'active_menu' => 'poin',

            'member'      => $this->Member_model->get_member_by_id($member_id),
            'level'       => $this->Member_model->get_level($poin_aktif),

            // summary: aktif, digunakan, kedaluwarsa, akan_kedaluwarsa
            'poin'        => $this->Poin_model->get_summary($member_id),

            // list
            'riwayat'     => $this->Poin_model->get_riwayat($member_id, $start, $end, $limit, $offset),

            // filter state
            'start'       => $start,
            'end'         => $end_raw,
            'limit'       => $limit,
            'page'        => $page,

            // pagination
            'total_rows'  => $total_rows,
            'total_pages' => ($limit !== 'semua') ? ceil($total_rows / $limit) : 1,
        ];

        $this->load->view('templates/member/header', $data);
        $this->load->view('member/poin', $data);
        $this->load->view('templates/member/footer', $data);
    }
}
