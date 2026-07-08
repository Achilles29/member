<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Kategori Model
 */
class Kategori_model extends CI_Model
{
    protected $table = 'mst_product_category';

    private function base_query()
    {
        $hasSelfOrderFlag = $this->db->field_exists('show_in_self_order', 'mst_product');
        $hasShowMember = $this->db->field_exists('show_member', 'mst_product');
        $visibilityParts = [];
        if ($hasShowMember) {
            $visibilityParts[] = 'p.show_member = 1';
        }
        if ($hasSelfOrderFlag) {
            $visibilityParts[] = 'p.show_in_self_order = 1';
        } else {
            $visibilityParts[] = 'p.show_pos = 1';
        }
        $productVisibilityExpr = implode(' AND ', $visibilityParts);

        $this->db->select('c.id, c.name as nama_kategori, c.sort_order');
        $this->db->from($this->table . ' c');
        $this->db->where('c.is_active', 1);
        $this->db->where('EXISTS (
            SELECT 1
            FROM mst_product p
            WHERE p.product_category_id = c.id
                AND p.is_active = 1
                AND ' . $productVisibilityExpr . '
        )', null, false);
    }

    public function get_all_active()
    {
        return $this->get_all();
    }

    public function get_all()
    {
        $this->base_query();
        $this->db->order_by('c.sort_order', 'ASC');
        $this->db->order_by('c.name', 'ASC');

        return $this->db->get()->result();
    }

    public function get_by_id($id)
    {
        $this->base_query();
        $this->db->where('c.id', (int) $id);

        return $this->db->get()->row();
    }
}
