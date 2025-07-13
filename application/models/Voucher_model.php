<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Voucher_model extends CI_Model
{
    //     public function get_all_vouchers() {
    //         return $this->db->get('pr_voucher')->result_array();
    //     }

    //     public function get_voucher_by_id($id) {
    //         $this->db->where('id', $id);
    //         $query = $this->db->get('pr_voucher');
    //         return $query->row_array();
    //     }

    //     public function add_voucher($data) {
    //         return $this->db->insert('pr_voucher', $data);
    //     }

    //     public function update_voucher($id, $data) {
    //         $this->db->where('id', $id);
    //         return $this->db->update('pr_voucher', $data);
    //     }

    //     public function delete_voucher($id) {
    //         $this->db->where('id', $id);
    //         return $this->db->delete('pr_voucher');
    //     }

    // // public function get_vouchers_for_member($member_id) {
    // //     $this->db->select('vouchers.code, vouchers.description, vouchers.discount_type, vouchers.discount_value, vouchers.end_date');
    // //     $this->db->from('member_vouchers');
    // //     $this->db->join('vouchers', 'member_vouchers.voucher_id = vouchers.id');
    // //     $this->db->where('member_vouchers.member_id', $member_id);
    // //     $this->db->where('vouchers.start_date <=', date('Y-m-d'));
    // //     $this->db->where('vouchers.end_date >=', date('Y-m-d'));
    // //     return $this->db->get()->result_array();
    // // }
    // public function get_vouchers_for_member($member_id) {
    //     $this->db->select('vouchers.code, vouchers.description, vouchers.discount_type, vouchers.discount_value, vouchers.start_date, vouchers.end_date');
    //     $this->db->from('member_vouchers');
    //     $this->db->join('vouchers', 'member_vouchers.voucher_id = vouchers.id');
    //     $this->db->where('member_vouchers.member_id', $member_id);
    //     $this->db->where('vouchers.end_date >=', date('Y-m-d')); // Filter hanya voucher yang belum berakhir
    //     $this->db->order_by('vouchers.end_date', 'ASC'); // Urutkan berdasarkan tanggal berakhir
    //     return $this->db->get()->result_array();
    // }
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


}
