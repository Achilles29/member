<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/Member_Controller.php';

class Loyalitas extends Member_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Poin_model', 'Stamp_model', 'Voucher_model']);
    }

    public function index()
    {
        $id = $this->member_id;

        // ─── POIN (with date filter) ────────────────────────────────────────
        $start   = $this->input->get('start') ?: date('Y-m-01');
        $end_raw = $this->input->get('end')   ?: date('Y-m-d');
        $end_ts  = $end_raw . ' 23:59:59';
        $limit   = $this->input->get('limit') ?: 10;
        $page    = max(1, (int)($this->input->get('page') ?: 1));
        $offset  = ($limit !== 'semua') ? ($page - 1) * $limit : 0;

        $poin_aktif = $this->Member_model->get_active_poin($id);
        $total_rows = $this->Poin_model->get_pagination_count($id, $start, $end_ts);

        // ─── STAMP ──────────────────────────────────────────────────────────
        $stamp_list    = $this->Stamp_model->get_active_stamp_by_customer($id) ?: [];
        $stamp_total_col = (int) array_sum(array_column($stamp_list, 'jumlah_stamp'));
        $stamp_total_tar = (int) array_sum(array_column($stamp_list, 'total_stamp_target'));

        // ─── VOUCHER ────────────────────────────────────────────────────────
        $v_aktif      = $this->Voucher_model->get_by_status($id, 'aktif')      ?: [];
        $v_digunakan  = $this->Voucher_model->get_by_status($id, 'digunakan')  ?: [];
        $v_kadaluarsa = $this->Voucher_model->get_by_status($id, 'kadaluarsa') ?: [];

        $this->render('member/loyalitas', [
            'title'       => 'Loyalty Saya',
            'active_menu' => 'reward',

            // poin
            'poin_aktif'  => $poin_aktif,
            'poin'        => $this->Poin_model->get_summary($id),
            'level'       => $this->Member_model->get_level($poin_aktif),
            'riwayat'     => $this->Poin_model->get_riwayat($id, $start, $end_ts, $limit, $offset),
            'start'       => $start,
            'end'         => $end_raw,
            'limit'       => $limit,
            'page'        => $page,
            'total_rows'  => $total_rows,
            'total_pages' => ($limit !== 'semua') ? ceil($total_rows / $limit) : 1,

            // stamp
            'stamp_list'      => $stamp_list,
            'stamp_total_col' => $stamp_total_col,
            'stamp_total_tar' => $stamp_total_tar,

            // voucher
            'voucher_aktif'      => $v_aktif,
            'voucher_digunakan'  => $v_digunakan,
            'voucher_kadaluarsa' => $v_kadaluarsa,
        ]);
    }
}
