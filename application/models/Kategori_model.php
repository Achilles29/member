<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Kategori Model
 */
class Kategori_model extends CI_Model
{
    protected $table = 'mst_product_category';

    private function base_query($visibility_context = 'self_order')
    {
        $hasSelfOrderFlag = $this->db->field_exists('show_in_self_order', 'mst_product');
        $hasOnlineFoodFlag = $this->db->field_exists('show_online_food', 'mst_product');
        $hasShowMember = $this->db->field_exists('show_member', 'mst_product');
        $visibilityParts = [];
        if ($hasShowMember) {
            $visibilityParts[] = 'p.show_member = 1';
        }
        if ($visibility_context === 'online_food' && $hasOnlineFoodFlag) {
            $visibilityParts[] = 'p.show_online_food = 1';
        } elseif ($visibility_context === 'online_food') {
            $visibilityParts[] = 'p.show_pos = 1';
        } elseif ($hasSelfOrderFlag) {
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

    public function get_all_active($visibility_context = 'self_order')
    {
        return $this->get_all($visibility_context);
    }

    public function get_all($visibility_context = 'self_order')
    {
        $this->base_query($visibility_context);
        $this->db->order_by('c.sort_order', 'ASC');
        $this->db->order_by('c.name', 'ASC');

        return $this->db->get()->result();
    }

    public function get_by_id($id, $visibility_context = 'self_order')
    {
        $this->base_query($visibility_context);
        $this->db->where('c.id', (int) $id);

        return $this->db->get()->row();
    }
}
