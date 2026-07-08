<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Produk_model extends CI_Model {
    private function apply_outlet_availability_join($outlet_id = 0)
    {
        $outlet_id = (int) $outlet_id;
        if ($this->db->table_exists('pos_product_availability_cache') && $outlet_id > 0) {
            $this->db->join(
                'pos_product_availability_cache pac',
                'pac.product_id = p.id AND pac.outlet_id = ' . $this->db->escape($outlet_id),
                'left',
                false
            );
            return;
        }

        $availabilitySub = '(
            SELECT
                NULL AS product_id,
                0 AS estimated_available_qty,
                "OUT" AS availability_status,
                "" AS bottleneck_name_snapshot
            WHERE 1 = 0
        ) pac';
        $this->db->join($availabilitySub, 'pac.product_id = p.id', 'left', false);
    }

    private function base_query($outlet_id = 0)
    {
        $hasSelfOrderFlag = $this->db->field_exists('show_in_self_order', 'mst_product');
        $hasShowMember = $this->db->field_exists('show_member', 'mst_product');
        $hasProductCode = $this->db->field_exists('product_code', 'mst_product');

        $this->db->select('
            p.id,
            p.product_name AS nama_produk,
            p.selling_price AS harga_jual,
            p.photo_path AS foto,
            p.description AS deskripsi,
            ' . ($hasProductCode ? 'p.product_code,' : 'NULL AS product_code,') . '
            p.product_category_id AS kategori_id,
            c.name AS nama_kategori,
            c.product_division_id AS pr_divisi_id,
            p.stock_mode,
            COALESCE(pac.availability_status, "OUT") AS availability_status,
            COALESCE(pac.bottleneck_name_snapshot, "") AS bottleneck_name,
            CASE
                WHEN UPPER(COALESCE(p.stock_mode, "AUTO")) = "MANUAL_AVAILABLE" THEN 999999
                WHEN UPPER(COALESCE(p.stock_mode, "AUTO")) = "MANUAL_OUT" THEN 0
                WHEN COALESCE(pac.availability_status, "OUT") IN ("AVAILABLE", "LIMITED") THEN COALESCE(pac.estimated_available_qty, 0)
                ELSE 0
            END AS stok_tersedia,
            CASE
                WHEN UPPER(COALESCE(p.stock_mode, "AUTO")) = "MANUAL_AVAILABLE" THEN 1
                WHEN UPPER(COALESCE(p.stock_mode, "AUTO")) = "MANUAL_OUT" THEN 0
                WHEN COALESCE(pac.availability_status, "OUT") IN ("AVAILABLE", "LIMITED")
                     AND COALESCE(pac.estimated_available_qty, 0) > 0 THEN 1
                ELSE 0
            END AS is_available_for_order
        ', false);
        $this->db->from('mst_product p');
        $this->db->join('mst_product_category c', 'c.id = p.product_category_id', 'left');
        $this->apply_outlet_availability_join($outlet_id);
        $this->db->where('p.is_active', 1);
        if ($hasShowMember) {
            $this->db->where('p.show_member', 1);
        }
        if ($hasSelfOrderFlag) {
            $this->db->where('p.show_in_self_order', 1);
        } else {
            $this->db->where('p.show_pos', 1);
        }
    }

    private function apply_product_order()
    {
        if ($this->db->field_exists('product_code', 'mst_product')) {
            $this->db->order_by('CASE WHEN p.product_code IS NULL OR p.product_code = "" THEN 1 ELSE 0 END', 'ASC', false);
            $this->db->order_by('p.product_code', 'ASC');
        }

        $this->db->order_by('p.product_name', 'ASC');
        $this->db->order_by('p.id', 'ASC');
    }

    public function get_all($outlet_id = 0) {
        $this->base_query($outlet_id);
        $this->db->order_by('c.sort_order', 'ASC');
        $this->apply_product_order();

        return $this->db->get()->result();
    }

    public function get_by_kategori($kategori_id = null, $outlet_id = 0) {
        $this->base_query($outlet_id);
        if ($kategori_id) {
            $this->db->where('p.product_category_id', $kategori_id);
        }
        $this->apply_product_order();

        return $this->db->get()->result();
    }
    
	public function search($keyword = null, $kategori_id = null, $outlet_id = 0) {
	    $this->base_query($outlet_id);
	    if ($kategori_id) {
	        $this->db->where('p.product_category_id', $kategori_id);
	    }
	    if ($keyword) {
	        $this->db->like('p.product_name', $keyword);
	    }
	    $this->apply_product_order();

	    return $this->db->get()->result();
	}

    public function get_by_id($product_id, $outlet_id = 0)
    {
        $this->base_query($outlet_id);
        $this->db->where('p.id', (int) $product_id);
        return $this->db->limit(1)->get()->row();
    }

}
