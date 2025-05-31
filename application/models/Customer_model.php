<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Customer_model extends CI_Model {

    public function insert_customer($data)
    {
        return $this->db->insert('pr_customer', $data);
    }

    public function get_customer_by_telepon($telepon)
    {
        return $this->db->get_where('pr_customer', ['telepon' => $telepon])->row_array();
    }

    public function get_customer_by_id($id)
    {
        return $this->db->get_where('pr_customer', ['id' => $id])->row_array();
    }

// Hitung jumlah register hari ini (untuk urutan kode pelanggan)
public function get_last_by_date($tanggal) {
    $prefix = $tanggal; // Format: YYYYMMDD
    $this->db->like('kode_pelanggan', $prefix, 'after');
    $this->db->from('pr_customer');
    return $this->db->count_all_results();
}


}
