<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Stamp_model extends CI_Model
{
    public function get_active_stamp_by_customer($customer_id)
    {
        $this->db->select('pcs.promo_stamp_id, ps.nama_promo, SUM(pcs.jumlah_stamp) as jumlah_stamp, ps.total_stamp_target');
        $this->db->from('pr_customer_stamp pcs');
        $this->db->join('pr_promo_stamp ps', 'pcs.promo_stamp_id = ps.id');
        $this->db->where('pcs.customer_id', $customer_id);
        $this->db->where('pcs.status', 'aktif');
        $this->db->where('ps.aktif', 1); // ⬅️ tambahkan baris ini
        $this->db->group_by('pcs.promo_stamp_id');
        $this->db->order_by('pcs.updated_at', 'desc');
        return $this->db->get()->result_array();
    }

}
