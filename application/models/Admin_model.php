<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Admin_model extends CI_Model
{
    private function get_default_stamp_campaign()
    {
        return $this->db
            ->where('is_active', 1)
            ->order_by('id', 'ASC')
            ->limit(1)
            ->get('pos_stamp_campaign')
            ->row_array();
    }

    private function stamp_expired_at($campaign)
    {
        if (empty($campaign['stamp_expiry_days'])) {
            return null;
        }

        return date('Y-m-d H:i:s', strtotime('+' . (int) $campaign['stamp_expiry_days'] . ' days'));
    }

    private function rebuild_stamp_balances($member_id)
    {
        $rows = $this->db
            ->select('id, stamp_in, stamp_out')
            ->where('member_id', $member_id)
            ->order_by('created_at', 'ASC')
            ->order_by('id', 'ASC')
            ->get('pos_stamp_ledger')
            ->result_array();

        $balance = 0.0;
        foreach ($rows as $row) {
            $balance += (float) ($row['stamp_in'] ?? 0) - (float) ($row['stamp_out'] ?? 0);
            $this->db->where('id', $row['id'])->update('pos_stamp_ledger', ['balance_after' => $balance]);
        }

        $this->db->where('id', $member_id)->update('crm_member', ['stamp_balance_cache' => $balance]);
    }

    private function generate_voucher_issue_no()
    {
        return 'ADM-VI-' . date('YmdHis') . '-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 6));
    }

    private function generate_voucher_code($campaign_code)
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', (string) $campaign_code), 0, 8));
        if ($prefix === '') {
            $prefix = 'VCHR';
        }

        return $prefix . '-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 6));
    }

    private function member_base_query()
    {
        $this->db->select('
            m.id,
            m.member_no,
            m.member_name AS name,
            m.mobile_phone AS phone,
            (
                SELECT COUNT(*)
                FROM pos_stamp_ledger sl
                WHERE sl.member_id = m.id
                    AND sl.ledger_type = "EARN"
                    AND sl.stamp_in > 0
            ) AS stamp_count,
            (
                SELECT GROUP_CONCAT(DISTINCT vi.voucher_code ORDER BY vi.voucher_code SEPARATOR ", ")
                FROM pos_voucher_issue vi
                WHERE vi.member_id = m.id
                    AND vi.voucher_status = "OPEN"
                    AND (vi.expired_at IS NULL OR vi.expired_at >= NOW())
            ) AS vouchers
        ', false);
        $this->db->from('crm_member m');
        $this->db->where('m.is_active', 1);
    }

    public function get_admin_by_username($username)
    {
        return $this->db
            ->select('id, username, password_hash as password, is_active')
            ->get_where('auth_user', ['username' => $username, 'is_active' => 1])
            ->row_array();
    }

    public function get_all_members()
    {
        $this->member_base_query();
        $this->db->order_by('m.member_name', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }

    public function get_member_stamp_count()
    {
        $this->member_base_query();
        $this->db->order_by('m.member_name', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }

    // Ambil member berdasarkan ID
    public function get_member_by_id($id)
    {
        $query = $this->db->get_where('crm_member', ['id' => $id]);
        $row = $query->row_array();
        if ($row) {
            $row['name'] = $row['member_name'] ?? '';
            $row['phone'] = $row['mobile_phone'] ?? '';
        }
        return $row;
    }

    // Ambil stamp berdasarkan member ID
    public function get_stamps($id)
    {
        $this->db->select('id, member_id, created_at as stamp_date');
        $this->db->from('pos_stamp_ledger');
        $this->db->where('member_id', $id);
        $this->db->where('ledger_type', 'EARN');
        $this->db->where('stamp_in >', 0);
        $this->db->order_by('created_at', 'ASC');
        $this->db->order_by('id', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }

    // Tambah stamp
    public function add_stamp($id)
    {
        $campaign = $this->get_default_stamp_campaign();
        if (!$campaign) {
            return false;
        }

        $ok = $this->db->insert('pos_stamp_ledger', [
            'member_id' => $id,
            'campaign_id' => $campaign['id'],
            'ledger_type' => 'EARN',
            'stamp_in' => 1,
            'stamp_out' => 0,
            'balance_after' => 0,
            'expired_at' => $this->stamp_expired_at($campaign),
            'notes' => 'Manual admin stamp',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($ok) {
            $this->rebuild_stamp_balances($id);
        }

        return $ok;
    }

    // Kurang stamp
    public function remove_stamp($id)
    {
        $row = $this->db
            ->select('id')
            ->from('pos_stamp_ledger')
            ->where('member_id', $id)
            ->where('ledger_type', 'EARN')
            ->where('stamp_in >', 0)
            ->order_by('created_at', 'DESC')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();

        if (!$row) {
            return false;
        }

        $ok = $this->db->delete('pos_stamp_ledger', ['id' => $row['id']]);
        if ($ok) {
            $this->rebuild_stamp_balances($id);
        }

        return $ok;
    }

    // Reset stamp
    public function reset_stamp($id)
    {
        $this->db->where('member_id', $id);
        $this->db->delete('pos_stamp_ledger');
        $this->db->where('id', $id)->update('crm_member', ['stamp_balance_cache' => 0]);
    }
    // public function search_members($query) {
    //     $this->db->select('members.id, members.name, members.phone, COUNT(stamps.id) AS stamp_count');
    //     $this->db->from('members');
    //     $this->db->join('stamps', 'stamps.member_id = members.id', 'left');
    //     $this->db->group_by('members.id');
    //     $this->db->like('members.name', $query);
    //     $this->db->or_like('members.phone', $query);
    //     $query = $this->db->get();
    //     return $query->result_array();
    // }
    public function search_members($query)
    {
        $this->member_base_query();
        $this->db->group_start();
        $this->db->like('m.member_name', $query);
        $this->db->or_like('m.mobile_phone', $query);
        $this->db->group_end();
        $this->db->order_by('m.member_name', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }


    public function add_member($name, $phone)
    {
        $data = [
            'member_no' => date('Ymd') . str_pad((string) ($this->count_all_members() + 1), 4, '0', STR_PAD_LEFT),
            'member_name' => $name,
            'mobile_phone' => $phone,
            'member_status' => 'ACTIVE',
            'member_tier' => 'Silver',
            'joined_at' => date('Y-m-d H:i:s'),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        return $this->db->insert('crm_member', $data);
    }

    public function update_member($id, $name, $phone)
    {
        $data = [
            'member_name' => $name,
            'mobile_phone' => $phone,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->where('id', $id);
        return $this->db->update('crm_member', $data);
    }

    public function delete_member($id)
    {
        $this->db->where('id', $id);
        return $this->db->update('crm_member', [
            'is_active' => 0,
            'member_status' => 'CLOSED',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function get_stamp_by_id($id)
    {
        $row = $this->db
            ->select('id, member_id, created_at as stamp_date')
            ->get_where('pos_stamp_ledger', ['id' => $id])
            ->row_array();

        return $row;
    }

    public function delete_transaction($id)
    {
        $row = $this->db->get_where('pos_stamp_ledger', ['id' => $id])->row_array();
        if (!$row) {
            return false;
        }

        $ok = $this->db->delete('pos_stamp_ledger', ['id' => $id]);
        if ($ok) {
            $this->rebuild_stamp_balances((int) $row['member_id']);
        }

        return $ok;
    }

    public function count_all_members()
    {
        return (int) $this->db->where('is_active', 1)->count_all_results('crm_member');
    }

    public function get_paginated_members($limit, $start)
    {
        $this->db->limit($limit, $start);
        $this->member_base_query();
        $this->db->order_by('m.member_name', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }

    // public function add_voucher($id, $voucher_code) {
    //     $data = ['voucher_code' => $voucher_code];
    //     $this->db->where('id', $id);
    //     return $this->db->update('members', $data);
    // }

    // public function get_voucher_by_member_id($id) {
    //     $this->db->select('voucher_code');
    //     $this->db->where('id', $id);
    //     $query = $this->db->get('members');
    //     return $query->row_array();
    // }
    public function get_vouchers_by_member($member_id)
    {
        $this->db->select('
            vi.id as member_voucher_id,
            vi.voucher_code as code,
            vc.campaign_name as description,
            CASE WHEN vc.voucher_type = "PERCENT" THEN "persentase" ELSE "nominal" END as discount_type,
            CASE WHEN vc.voucher_type = "PERCENT" THEN vi.percent_snapshot ELSE vi.amount_snapshot END as discount_value,
            vi.issued_at as start_date,
            vi.expired_at as end_date
        ', false);
        $this->db->from('pos_voucher_issue vi');
        $this->db->join('pos_voucher_campaign vc', 'vc.id = vi.campaign_id', 'left');
        $this->db->where('vi.member_id', $member_id);
        $this->db->where('vi.voucher_status', 'OPEN');
        $this->db->order_by('vi.issued_at', 'DESC');
        $query = $this->db->get();
        return $query->result_array();
    }

    public function add_member_voucher($member_id, $voucher_id)
    {
        $campaign = $this->db->get_where('pos_voucher_campaign', ['id' => $voucher_id, 'is_active' => 1])->row_array();
        if (!$campaign) {
            return false;
        }

        $expired_at = null;
        if (!empty($campaign['valid_day_count'])) {
            $expired_at = date('Y-m-d H:i:s', strtotime('+' . (int) $campaign['valid_day_count'] . ' days'));
        } elseif (!empty($campaign['end_date'])) {
            $expired_at = $campaign['end_date'] . ' 23:59:59';
        }

        return $this->db->insert('pos_voucher_issue', [
            'voucher_issue_no' => $this->generate_voucher_issue_no(),
            'campaign_id' => $voucher_id,
            'member_id' => $member_id,
            'voucher_code' => $this->generate_voucher_code($campaign['campaign_code'] ?? ''),
            'voucher_status' => 'OPEN',
            'amount_snapshot' => strtoupper((string) ($campaign['voucher_type'] ?? '')) === 'PERCENT' ? 0 : (float) ($campaign['discount_value'] ?? 0),
            'percent_snapshot' => strtoupper((string) ($campaign['voucher_type'] ?? '')) === 'PERCENT' ? (float) ($campaign['discount_value'] ?? 0) : 0,
            'issued_at' => date('Y-m-d H:i:s'),
            'expired_at' => $expired_at,
            'notes' => 'Admin assigned voucher',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function delete_member_voucher($id)
    {
        $this->db->where('id', $id);
        return $this->db->update('pos_voucher_issue', [
            'voucher_status' => 'VOID',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

}
