<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Redeem Model
 * 
 * Model untuk redeem poin/stamp
 * Di database core, tidak ada pr_redeem_setting
 * Menggunakan pos_voucher_campaign sebagai alternatif
 */
class Redeem_model extends CI_Model
{
    protected $table_voucher_campaign = 'pos_voucher_campaign';

    public function get_all_active_by_type($jenis)
    {
        // Table pr_redeem_setting tidak ada di database core
        // Return empty untuk mencegah error
        return [];
    }

    public function get_active_redeem($jenis)
    {
        // Return empty array
        return [];
    }

    public function get_redeem_by_id($id)
    {
        // Return null
        return null;
    }

    public function get_active_stamp($customer_id)
    {
        // Get member account ID first
        $member_account = $this->db->select('id')
            ->where('customer_id', $customer_id)
            ->get('crm_member_account')
            ->row();
        
        if (!$member_account) {
            return [];
        }
        
        $this->db->select('sl.campaign_id, sc.campaign_name as nama_promo, SUM(sl.stamp_amount) as stamp_amount, sc.stamp_target as total_stamp_target, sc.is_active as aktif');
        $this->db->from('pos_stamp_ledger sl');
        $this->db->join('pos_stamp_campaign sc', 'sl.campaign_id = sc.id');
        $this->db->where('sl.member_account_id', $member_account->id);
        $this->db->where('sl.ledger_type', 'EARN');
        $this->db->where('sc.is_active', 1);
        $this->db->where('(sl.expires_at >= NOW() OR sl.expires_at IS NULL)');
        $this->db->group_by('sl.campaign_id');
        $this->db->order_by('sl.created_at', 'desc');
        return $this->db->get()->result_array();
    }

    public function get_active_redeem_by_type($customer_id, $jenis)
    {
        // Return empty untuk mencegah error
        return [];
    }

    public function process_redeem($customer_id, $redeem)
    {
        // Not implemented in core database structure
        return false;
    }

    public function get_poin_aktif($customer_id)
    {
        // Get from point ledger
        $member_account = $this->db->select('id')
            ->where('customer_id', $customer_id)
            ->get('crm_member_account')
            ->row();
        
        if (!$member_account) {
            return 0;
        }

        $this->db->select('balance_after');
        $this->db->from('pos_point_ledger');
        $this->db->where('member_account_id', $member_account->id);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit(1);
        $result = $this->db->get()->row();

        return (int) ($result->balance_after ?? 0);
    }
}
