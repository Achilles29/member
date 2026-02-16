<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pending_order_extra_model extends CI_Model
{
    public function insert_extra($pending_order_detail_id, $extra_id, $jumlah, $harga)
    {
        $jumlah = (int) $jumlah;
        if ($jumlah <= 0) $jumlah = 1;

        $data = [
            'pending_order_detail_id' => (int) $pending_order_detail_id,
            'pr_produk_extra_id' => (int) $extra_id,
            'jumlah' => $jumlah,
            'harga' => (float) $harga,
            'subtotal' => (float) $harga * $jumlah,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        return $this->db->insert('pr_pending_order_extra', $data);
    }
}

