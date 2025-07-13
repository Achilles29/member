<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pending_order_model extends CI_Model {
    public function create_order($customer_id, $nomor_meja = null) {
        $data = [
            'customer_id' => $customer_id,
            'nomor_meja' => $nomor_meja,
            'status' => 'MENUNGGU',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('pr_pending_order', $data);
        return $this->db->insert_id();
    }
}
