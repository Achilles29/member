<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Stamp_model extends CI_Model
{
    protected $table = 'pos_stamp_ledger';
    protected $table_campaign = 'pos_stamp_campaign';

    public function get_active_stamp_by_customer($customer_id)
    {
        $this->db->select('
            sl.campaign_id,
            sc.campaign_name as nama_promo,
            GREATEST(SUM(sl.stamp_in - sl.stamp_out), 0) as jumlah_stamp,
            sc.redeem_required_stamp as total_stamp_target
        ');
        $this->db->from($this->table . ' sl');
        $this->db->join($this->table_campaign . ' sc', 'sl.campaign_id = sc.id', 'left');
        $this->db->where('sl.member_id', $customer_id);
        $this->db->where('sc.is_active', 1);
        $this->db->where('(sl.expired_at >= NOW() OR sl.expired_at IS NULL)');
        $this->db->group_by('sl.campaign_id');
        $this->db->having('jumlah_stamp >', 0);
        $this->db->order_by('sl.created_at', 'DESC');

        return $this->db->get()->result_array();
    }

    public function count_active_by_customer($customer_id)
    {
        $result = $this->db
            ->select('balance_after')
            ->where('member_id', $customer_id)
            ->where('(expired_at >= NOW() OR expired_at IS NULL)')
            ->order_by('created_at', 'DESC')
            ->limit(1)
            ->get($this->table)
            ->row();

        return (int) round((float) ($result->balance_after ?? 0));
    }
}
