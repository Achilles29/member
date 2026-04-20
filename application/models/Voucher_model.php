<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Voucher_model extends CI_Model
{
    protected $table = 'pos_voucher_wallet';
    protected $table_member = 'crm_member_account';
    protected $table_campaign = 'pos_voucher_campaign';

    private function get_member_account_id($customer_id)
    {
        $member = $this->db->select('id')
            ->where('customer_id', $customer_id)
            ->get($this->table_member)
            ->row();
        
        return $member->id ?? null;
    }

    public function get_by_status($customer_id, $status)
    {
        $member_account_id = $this->get_member_account_id($customer_id);
        
        if (!$member_account_id) {
            return [];
        }

        $this->db->select('vw.*, vc.campaign_name, vc.voucher_type, vc.voucher_value');
        $this->db->from($this->table . ' vw');
        $this->db->join($this->table_campaign . ' vc', 'vc.id = vw.campaign_id', 'left');
        $this->db->where('vw.member_account_id', $member_account_id);

        if ($status == 'aktif') {
            $this->db->where('vw.voucher_status', 'AVAILABLE');
            $this->db->where('(vw.expired_at >= NOW() OR vw.expired_at IS NULL)');
        } elseif ($status == 'digunakan') {
            $this->db->where('vw.voucher_status', 'REDEEMED');
        } elseif ($status == 'kadaluarsa') {
            $this->db->group_start();
            $this->db->where('vw.voucher_status', 'EXPIRED');
            $this->db->or_where('vw.voucher_status', 'VOID');
            $this->db->or_where('vw.expired_at <', date('Y-m-d H:i:s'));
            $this->db->group_end();
        }

        $this->db->order_by('vw.expired_at', 'DESC');
        
        return $this->db->get()->result_array();
    }

    public function count_by_status($customer_id, $status)
    {
        $member_account_id = $this->get_member_account_id($customer_id);
        
        if (!$member_account_id) {
            return 0;
        }

        $this->db->from($this->table);
        $this->db->where('member_account_id', $member_account_id);

        if ($status == 'aktif') {
            $this->db->where('voucher_status', 'AVAILABLE');
            $this->db->where('(expired_at >= NOW() OR expired_at IS NULL)');
        } elseif ($status == 'digunakan') {
            $this->db->where('voucher_status', 'REDEEMED');
        } elseif ($status == 'kadaluarsa') {
            $this->db->group_start();
            $this->db->where('voucher_status', 'EXPIRED');
            $this->db->or_where('voucher_status', 'VOID');
            $this->db->or_where('expired_at <', date('Y-m-d H:i:s'));
            $this->db->group_end();
        }

        return (int) $this->db->count_all_results();
    }

    public function get_by_status_paginated($customer_id, $status, $limit, $offset)
    {
        $member_account_id = $this->get_member_account_id($customer_id);
        
        if (!$member_account_id) {
            return [];
        }

        $this->db->select('vw.*, vc.campaign_name, vc.voucher_type, vc.voucher_value');
        $this->db->from($this->table . ' vw');
        $this->db->join($this->table_campaign . ' vc', 'vc.id = vw.campaign_id', 'left');
        $this->db->where('vw.member_account_id', $member_account_id);

        if ($status == 'aktif') {
            $this->db->where('vw.voucher_status', 'AVAILABLE');
            $this->db->where('(vw.expired_at >= NOW() OR vw.expired_at IS NULL)');
        } elseif ($status == 'digunakan') {
            $this->db->where('vw.voucher_status', 'REDEEMED');
        } elseif ($status == 'kadaluarsa') {
            $this->db->group_start();
            $this->db->where('vw.voucher_status', 'EXPIRED');
            $this->db->or_where('vw.voucher_status', 'VOID');
            $this->db->or_where('vw.expired_at <', date('Y-m-d H:i:s'));
            $this->db->group_end();
        }

        $this->db->order_by('vw.expired_at', 'DESC');
        $this->db->limit((int)$limit, (int)$offset);

        return $this->db->get()->result_array();
    }

    public function get_summary($customer_id)
    {
        $member_account_id = $this->get_member_account_id($customer_id);
        
        if (!$member_account_id) {
            return [
                'aktif' => 0,
                'digunakan' => 0,
                'kadaluarsa' => 0,
                'total_nilai' => 0
            ];
        }

        $today = date('Y-m-d H:i:s');
        
        $aktif = $this->db
            ->where('member_account_id', $member_account_id)
            ->where('voucher_status', 'AVAILABLE')
            ->where('(expired_at >= "' . $today . '" OR expired_at IS NULL)')
            ->count_all_results($this->table);
        
        $digunakan = $this->db
            ->where('member_account_id', $member_account_id)
            ->where('voucher_status', 'REDEEMED')
            ->count_all_results($this->table);
        
        $this->db->where('member_account_id', $member_account_id);
        $this->db->group_start();
        $this->db->where('voucher_status', 'EXPIRED');
        $this->db->or_where('voucher_status', 'VOID');
        $this->db->or_where('expired_at <', $today);
        $this->db->group_end();
        $kadaluarsa = $this->db->count_all_results($this->table);
        
        return [
            'aktif' => (int) $aktif,
            'digunakan' => (int) $digunakan,
            'kadaluarsa' => (int) $kadaluarsa,
            'total_nilai' => 0
        ];
    }

    public function get_by_id($id, $customer_id = null)
    {
        $this->db->where('id', $id);
        
        if ($customer_id) {
            $member_account_id = $this->get_member_account_id($customer_id);
            if ($member_account_id) {
                $this->db->where('member_account_id', $member_account_id);
            }
        }
        
        return $this->db->get($this->table)->row_array();
    }

    public function get_by_code($code, $customer_id = null)
    {
        $this->db->where('voucher_code', $code);
        
        if ($customer_id) {
            $member_account_id = $this->get_member_account_id($customer_id);
            if ($member_account_id) {
                $this->db->where('member_account_id', $member_account_id);
            }
        }
        
        return $this->db->get($this->table)->row_array();
    }

    public function use_voucher($id, $customer_id)
    {
        return true;
    }

    public function create($data)
    {
        return true;
    }

    public function auto_expire()
    {
        return true;
    }
}
