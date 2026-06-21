<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Produk_model extends CI_Model {
    private function base_query()
    {
        $hasSelfOrderFlag = $this->db->field_exists('show_in_self_order', 'mst_product');
        $availabilitySub = '(
            SELECT
                pac.product_id,
                MAX(CASE WHEN pac.availability_status IN ("AVAILABLE", "LIMITED") THEN pac.estimated_available_qty ELSE 0 END) AS stok_tersedia
            FROM pos_product_availability_cache pac
            GROUP BY pac.product_id
        ) stock';

        $this->db->select('
            p.id,
            p.product_name AS nama_produk,
            p.selling_price AS harga_jual,
            p.photo_path AS foto,
            p.product_category_id AS kategori_id,
            c.name AS nama_kategori,
            c.product_division_id AS pr_divisi_id,
            COALESCE(stock.stok_tersedia, 0) AS stok_tersedia
        ', false);
        $this->db->from('mst_product p');
        $this->db->join('mst_product_category c', 'c.id = p.product_category_id', 'left');
        $this->db->join($availabilitySub, 'stock.product_id = p.id', 'left', false);
        $this->db->where('p.is_active', 1);
        if ($hasSelfOrderFlag) {
            $this->db->where('p.show_in_self_order', 1);
        } else {
            $this->db->where('p.show_member', 1);
        }
    }

    public function get_all() {
        $this->base_query();
        $this->db->order_by('c.sort_order', 'ASC');
        $this->db->order_by('p.product_name', 'ASC');

        return $this->db->get()->result();
    }

    public function get_by_kategori($kategori_id = null) {
        $this->base_query();
        if ($kategori_id) {
            $this->db->where('p.product_category_id', $kategori_id);
        }
        $this->db->order_by('p.product_name', 'ASC');

        return $this->db->get()->result();
    }
    
	public function search($keyword = null, $kategori_id = null) {
	    $this->base_query();
	    if ($kategori_id) {
	        $this->db->where('p.product_category_id', $kategori_id);
	    }
	    if ($keyword) {
	        $this->db->like('p.product_name', $keyword);
	    }
	    $this->db->order_by('p.product_name', 'ASC');

	    return $this->db->get()->result();
	}

}
