<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Voucher_model extends CI_Model
{

    public function get_by_status($customer_id, $status)
    {
        $this->db->from('pr_voucher');
        $this->db->where('customer_id', $customer_id);

        if ($status == 'aktif') {
            $this->db->where('sisa_voucher >', 0);
            $this->db->where('status', 'aktif');
            $this->db->where('tanggal_berakhir >=', date('Y-m-d'));
        } elseif ($status == 'digunakan') {
            $this->db->where('sisa_voucher', 0);
        } elseif ($status == 'kadaluarsa') {
            $this->db->group_start();
            $this->db->where('status', 'noaktif');
            $this->db->or_where('tanggal_berakhir <', date('Y-m-d'));
            $this->db->group_end();
        }

        $this->db->order_by('tanggal_berakhir', 'DESC');
        return $this->db->get()->result_array();
    }

public function count_by_status($customer_id, $status)
{
    $this->db->from('pr_voucher');
    $this->db->where('customer_id', $customer_id);

    if ($status == 'aktif') {
        $this->db->where('sisa_voucher >', 0);
        $this->db->where('status', 'aktif');
        $this->db->where('tanggal_berakhir >=', date('Y-m-d'));
    } elseif ($status == 'digunakan') {
        $this->db->where('sisa_voucher', 0);
    } elseif ($status == 'kadaluarsa') {
        $this->db->group_start();
        $this->db->where('status', 'noaktif');
        $this->db->or_where('tanggal_berakhir <', date('Y-m-d'));
        $this->db->group_end();
    }

    return (int) $this->db->count_all_results();
}

public function get_by_status_paginated($customer_id, $status, $limit, $offset)
{
    $this->db->from('pr_voucher');
    $this->db->where('customer_id', $customer_id);

    if ($status == 'aktif') {
        $this->db->where('sisa_voucher >', 0);
        $this->db->where('status', 'aktif');
        $this->db->where('tanggal_berakhir >=', date('Y-m-d'));
    } elseif ($status == 'digunakan') {
        $this->db->where('sisa_voucher', 0);
    } elseif ($status == 'kadaluarsa') {
        $this->db->group_start();
        $this->db->where('status', 'noaktif');
        $this->db->or_where('tanggal_berakhir <', date('Y-m-d'));
        $this->db->group_end();
    }

    $this->db->order_by('tanggal_berakhir', 'DESC');
    $this->db->limit((int)$limit, (int)$offset);

    return $this->db->get()->result_array();
}

}
