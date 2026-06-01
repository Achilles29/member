<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Poin_model extends CI_Model
{
    protected $table = 'pos_point_ledger';
    protected $table_order = 'pos_order';

    public function get_active_poin($member_id)
    {
        $result = $this->db
            ->select('balance_after')
            ->where('member_id', $member_id)
            ->order_by('created_at', 'DESC')
            ->limit(1)
            ->get($this->table)
            ->row();

        return (int) round((float) ($result->balance_after ?? 0));
    }

    public function get_riwayat_poin($member_id)
    {
        $this->db->select('
            pl.*,
            o.order_no as no_transaksi,
            o.grand_total as total_harga,
            o.status as status_transaksi
        ');
        $this->db->from($this->table . ' pl');
        $this->db->join($this->table_order . ' o', 'o.id = pl.order_id', 'left');
        $this->db->where('pl.member_id', $member_id);
        $this->db->order_by('pl.created_at', 'DESC');

        return $this->db->get()->result_array();
    }

    public function get_total_poin($member_id)
    {
        return $this->get_active_poin($member_id);
    }

    public function get_kedaluwarsa_segera($member_id, $days = 7)
    {
        $now = date('Y-m-d H:i:s');
        $soon = date('Y-m-d H:i:s', strtotime("+{$days} days"));

        $this->db->where('member_id', $member_id);
        $this->db->where('ledger_type', 'EARN');
        $this->db->where('expired_at >=', $now);
        $this->db->where('expired_at <=', $soon);
        $this->db->order_by('expired_at', 'ASC');

        return $this->db->get($this->table)->result_array();
    }

    public function get_summary($member_id)
    {
        $today = date('Y-m-d H:i:s');
        $next_month = date('Y-m-d H:i:s', strtotime('+30 days'));

        $aktif = $this->get_active_poin($member_id);

        $digunakan = $this->db->select_sum('points_out')
            ->where('member_id', $member_id)
            ->where('ledger_type', 'REDEEM')
            ->get($this->table)->row()->points_out ?? 0;

        $kedaluwarsa = $this->db->select_sum('points_out')
            ->where('member_id', $member_id)
            ->where('ledger_type', 'EXPIRE')
            ->get($this->table)->row()->points_out ?? 0;

        $akan = $this->db->select_sum('points_in')
            ->where('member_id', $member_id)
            ->where('ledger_type', 'EARN')
            ->where('expired_at >=', $today)
            ->where('expired_at <=', $next_month)
            ->get($this->table)->row()->points_in ?? 0;

        $total_earned = $this->db->select_sum('points_in')
            ->where('member_id', $member_id)
            ->where('ledger_type', 'EARN')
            ->get($this->table)->row()->points_in ?? 0;

        return [
            'aktif' => (int) abs($aktif),
            'digunakan' => (int) abs($digunakan),
            'kedaluwarsa' => (int) abs($kedaluwarsa),
            'akan_kedaluwarsa' => (int) abs($akan),
            'total_earned' => (int) abs($total_earned)
        ];
    }

    public function get_riwayat($customer_id, $start_date, $end_date, $limit = null, $offset = 0)
    {
        $this->db->select('
            pl.*,
            o.order_no as no_transaksi,
            o.grand_total as total_harga,
            o.status as status_transaksi,
            CASE
                WHEN pl.ledger_type = "REDEEM" THEN pl.points_out
                WHEN pl.ledger_type = "EXPIRE" THEN pl.points_out
                ELSE pl.points_in
            END AS jumlah_poin,
            CASE
                WHEN pl.ledger_type = "REDEEM" THEN "digunakan"
                WHEN pl.ledger_type = "EXPIRE" THEN "kedaluwarsa"
                ELSE "aktif"
            END AS status
        ', false);
        $this->db->from($this->table . ' pl');
        $this->db->join($this->table_order . ' o', 'o.id = pl.order_id', 'left');
        $this->db->where('pl.member_id', $customer_id);
        $this->db->where('pl.created_at >=', $start_date);
        $this->db->where('pl.created_at <=', $end_date);
        $this->db->order_by('pl.created_at', 'DESC');

        if ($limit !== null && $limit !== 'semua') {
            $this->db->limit($limit, $offset);
        }

        return $this->db->get()->result_array();
    }

    public function get_pagination_count($customer_id, $start_date, $end_date)
    {
        $this->db->from($this->table);
        $this->db->where('member_id', $customer_id);
        $this->db->where('created_at >=', $start_date);
        $this->db->where('created_at <=', $end_date);

        return $this->db->count_all_results();
    }

    public function add($data)
    {
        return true;
    }

    public function update_status($id, $status)
    {
        return true;
    }

    public function auto_expire()
    {
        return true;
    }
}
