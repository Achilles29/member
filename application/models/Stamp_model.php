<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Stamp_model extends CI_Model
{
    protected $table = 'pos_stamp_ledger';
    protected $table_member = 'crm_member_account';
    protected $table_campaign = 'pos_stamp_campaign';

    private function get_member_account_id($customer_id)
    {
        $member = $this->db->select('id')
            ->where('customer_id', $customer_id)
            ->get($this->table_member)
            ->row();
        
        return $member->id ?? null;
    }

    public function get_active_stamp_by_customer($customer_id)
    {
        $member_account_id = $this->get_member_account_id($customer_id);
        
        if (!$member_account_id) {
            return [];
        }

        $this->db->select('
            sl.campaign_id,
            sc.campaign_name as nama_promo,
            SUM(sl.stamp_amount) as jumlah_stamp,
            sc.stamp_target as total_stamp_target
        ');
        $this->db->from($this->table . ' sl');
        $this->db->join($this->table_campaign . ' sc', 'sl.campaign_id = sc.id', 'left');
        $this->db->where('sl.member_account_id', $member_account_id);
        $this->db->where('sl.ledger_type', 'EARN');
        $this->db->where('sc.is_active', 1);
        $this->db->where('(sl.expires_at >= NOW() OR sl.expires_at IS NULL)');
        $this->db->group_by('sl.campaign_id');
        $this->db->order_by('sl.created_at', 'DESC');
        
        return $this->db->get()->result_array();
    }

    public function count_active_by_customer($customer_id)
    {
        $member_account_id = $this->get_member_account_id($customer_id);
        
        if (!$member_account_id) {
            return 0;
        }

        $this->db->select_sum('stamp_amount');
        $this->db->where('member_account_id', $member_account_id);
        $this->db->where('ledger_type', 'EARN');
        $this->db->where('(expires_at >= NOW() OR expires_at IS NULL)');
        $result = $this->db->get($this->table)->row();
        
        return (int) ($result->stamp_amount ?? 0);
    }
}
