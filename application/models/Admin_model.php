<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_model extends CI_Model {

    public function get_admin_by_username($username) {
        return $this->db->get_where('admins', array('username' => $username))->row_array();
    }

    public function get_all_members() {
        $query = $this->db->get('members');
        return $query->result_array();
    }
    public function get_member_stamp_count() {
        $this->db->select('members.id, members.name, members.phone, COUNT(stamps.id) AS stamp_count');
        $this->db->from('members');
        $this->db->join('stamps', 'stamps.member_id = members.id', 'left');
        $this->db->group_by('members.id');
        $query = $this->db->get();
        return $query->result_array();
    }

    // Ambil member berdasarkan ID
    public function get_member_by_id($id) {
        $query = $this->db->get_where('members', ['id' => $id]);
        return $query->row_array();
    }

    // Ambil stamp berdasarkan member ID
    public function get_stamps($id) {
        $this->db->where('member_id', $id);
        $this->db->order_by('stamp_date', 'ASC');
        $query = $this->db->get('stamps');
        return $query->result_array();
    }

    // Tambah stamp
    public function add_stamp($id) {
        $data = [
            'member_id' => $id,
            'stamp_date' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('stamps', $data);
    }

    // Kurang stamp
    public function remove_stamp($id) {
        $this->db->where('member_id', $id);
        $this->db->order_by('stamp_date', 'DESC');
        $this->db->limit(1);
        $this->db->delete('stamps');
    }

    // Reset stamp
    public function reset_stamp($id) {
        $this->db->where('member_id', $id);
        $this->db->delete('stamps');
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
    public function search_members($query) {
        $this->db->select('
            members.id, 
            members.name, 
            members.phone, 
            COUNT(stamps.id) AS stamp_count, 
            GROUP_CONCAT(DISTINCT vouchers.code SEPARATOR ", ") AS vouchers
        ');
        $this->db->from('members');
        $this->db->join('stamps', 'stamps.member_id = members.id', 'left');
        $this->db->join('member_vouchers', 'member_vouchers.member_id = members.id', 'left');
        $this->db->join('vouchers', 'vouchers.id = member_vouchers.voucher_id', 'left');
        $this->db->group_by('members.id');
        $this->db->like('members.name', $query);
        $this->db->or_like('members.phone', $query);
        $query = $this->db->get();
        return $query->result_array();
    }


    public function add_member($name, $phone) {
        $data = [
            'name' => $name,
            'phone' => $phone,
        ];
        $this->db->insert('members', $data);
    }

    public function update_member($id, $name, $phone) {
        $data = [
            'name' => $name,
            'phone' => $phone,
        ];
        $this->db->where('id', $id);
        $this->db->update('members', $data);
    }

public function delete_member($id) {
    // Hapus data terkait di tabel stamps
    $this->db->where('member_id', $id);
    $this->db->delete('stamps');

    // Hapus data member di tabel members
    $this->db->where('id', $id);
    return $this->db->delete('members');
}

public function get_stamp_by_id($id) {
    return $this->db->get_where('stamps', ['id' => $id])->row_array();
}

public function delete_transaction($id) {
    $this->db->where('id', $id);
    return $this->db->delete('stamps');
}

public function count_all_members() {
    return $this->db->count_all('members');
}

public function get_paginated_members($limit, $start) {
    $this->db->limit($limit, $start);
    $this->db->select('members.id, members.name, members.phone, COUNT(stamps.id) as stamp_count');
    $this->db->from('members');
    $this->db->join('stamps', 'stamps.member_id = members.id', 'left');
    $this->db->group_by('members.id');
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
public function get_vouchers_by_member($member_id) {
    $this->db->select('member_vouchers.id as member_voucher_id, vouchers.*');
    $this->db->from('member_vouchers');
    $this->db->join('vouchers', 'vouchers.id = member_vouchers.voucher_id');
    $this->db->where('member_vouchers.member_id', $member_id);
    $query = $this->db->get();
    return $query->result_array();
}

public function add_member_voucher($member_id, $voucher_id) {
    $data = [
        'member_id' => $member_id,
        'voucher_id' => $voucher_id
    ];
    $this->db->insert('member_vouchers', $data);
}

public function delete_member_voucher($id) {
    $this->db->where('id', $id);
    $this->db->delete('member_vouchers');
}

}
