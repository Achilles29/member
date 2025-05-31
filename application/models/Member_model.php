<?php
class Member_model extends CI_Model {
    public function get_by_phone($phone) {
        return $this->db->get_where('pr_customer', ['telepon' => $phone])->row_array();
    }

    public function get_by_id($id) {
        return $this->db->get_where('pr_customer', ['id' => $id])->row_array();
    }

    public function get_poin_by_customer($customer_id) {
        $this->db->select_sum('jumlah_poin');
        $this->db->where('customer_id', $customer_id);
        $this->db->where('status', 'aktif');
        $result = $this->db->get('pr_customer_poin')->row();
        return $result->jumlah_poin ?? 0;
    }

public function get_member_by_id($id) {
    return $this->db->get_where('pr_customer', ['id' => $id])->row_array();
}

public function get_active_poin($id) {
    $this->db->select_sum('jumlah_poin');
    $this->db->from('pr_customer_poin');
    $this->db->where('customer_id', $id);
    $this->db->where('status', 'AKTIF');
    $this->db->where('tanggal_kedaluwarsa >=', date('Y-m-d'));
    $query = $this->db->get();
    return (int) $query->row()->jumlah_poin;
}

public function get_level($poin) {
    if ($poin >= 300) return 'Platinum';
    if ($poin >= 150) return 'Gold';
    return 'Silver';
}
}
