<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Pending_order_detail_model extends CI_Model {
    public function insert_detail($order_id, $produk_id, $jumlah) {
        $produk = $this->db->get_where('pr_produk', ['id' => $produk_id])->row();

        $data = [
            'pending_order_id' => $order_id,
            'pr_produk_id' => $produk_id,
            'jumlah' => $jumlah,
            'harga' => $produk->harga_jual,
            'subtotal' => $jumlah * $produk->harga_jual,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('pr_pending_order_detail', $data);
    }
}
