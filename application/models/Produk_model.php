<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Produk_model extends CI_Model {
    public function get_all() {
        return $this->db
            ->where('tampil', 1)
            ->order_by('nama_produk', 'ASC')
            ->get('pr_produk')
            ->result();
    }
    public function get_by_kategori($kategori_id = null) {
        $this->db->where('tampil', 1);
        if ($kategori_id) {
            $this->db->where('kategori_id', $kategori_id);
        }
        return $this->db->order_by('nama_produk', 'ASC')->get('pr_produk')->result();
    }
    
public function search($keyword = null, $kategori_id = null) {
    $this->db->where('tampil', 1);
    if ($kategori_id) {
        $this->db->where('kategori_id', $kategori_id);
    }
    if ($keyword) {
        $this->db->like('nama_produk', $keyword);
    }
    return $this->db->order_by('nama_produk', 'ASC')->get('pr_produk')->result();
}

}
