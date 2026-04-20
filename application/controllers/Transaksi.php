<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Transaksi extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url', 'text']);
        $this->load->model(['Member_model', 'Transaksi_model']);
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
        $member_id = (int)$this->session->userdata('member_id');

        $month = $this->input->get('month', true);
        if (!preg_match('/^\d{4}-\d{2}$/', (string)$month)) {
            $month = date('Y-m');
        }

        $search = trim((string)$this->input->get('search', true));
        $limit = $this->input->get('limit', true) ?: '10';
        $allowed_limit = ['10', '20', '50', 'semua'];
        if (!in_array((string)$limit, $allowed_limit, true)) {
            $limit = '10';
        }

        $page = max(1, (int)$this->input->get('page'));
        $offset = ($limit === 'semua') ? 0 : ($page - 1) * (int)$limit;

        $total_rows = $this->Transaksi_model->get_count_by_customer($member_id, $month, $search);
        $list = $this->Transaksi_model->get_list_by_customer($member_id, $month, $search, $limit, $offset);
        $total_pages = ($limit === 'semua') ? 1 : (int)ceil($total_rows / (int)$limit);

        $data = [
            'title' => 'Riwayat Transaksi',
            'active_menu' => 'transaksi',
            'member' => $this->Member_model->get_member_by_id($member_id),
            'month' => $month,
            'search' => $search,
            'limit' => $limit,
            'page' => $page,
            'total_rows' => $total_rows,
            'total_pages' => $total_pages,
            'transaksi' => $list,
        ];

        $this->load->view('templates/member/header', $data);
        $this->load->view('member/transaksi', $data);
        $this->load->view('templates/member/footer', $data);
    }

    public function detail($id = null)
    {
        $this->check_login();
        $member_id = (int)$this->session->userdata('member_id');
        $id = (int)$id;

        $trx = $this->Transaksi_model->get_by_id_customer($id, $member_id);
        if (!$trx) {
            show_404();
            return;
        }

        $data = [
            'title' => 'Detail Transaksi',
            'active_menu' => 'transaksi',
            'member' => $this->Member_model->get_member_by_id($member_id),
            'transaksi' => $trx,
            'items' => $this->Transaksi_model->get_detail_items($id),
        ];

        $this->load->view('templates/member/header', $data);
        $this->load->view('member/transaksi_detail', $data);
        $this->load->view('templates/member/footer', $data);
    }

    public function struk($id = null)
    {
        $this->check_login();
        $member_id = (int)$this->session->userdata('member_id');
        $id = (int)$id;

        $trx = $this->Transaksi_model->get_by_id_customer($id, $member_id);
        if (!$trx) {
            show_404();
            return;
        }

        $data = [
            'title' => 'Struk Transaksi',
            'active_menu' => 'transaksi',
            'member' => $this->Member_model->get_member_by_id($member_id),
            'transaksi' => $trx,
            'items' => $this->Transaksi_model->get_detail_items($id),
            'outlet' => $this->Transaksi_model->get_outlet_struk(),
        ];

        $this->load->view('templates/member/header', $data);
        $this->load->view('member/transaksi_struk', $data);
        $this->load->view('templates/member/footer', $data);
    }
}

