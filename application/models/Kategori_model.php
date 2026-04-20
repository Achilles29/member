<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Kategori Model
 * 
 * Table pr_kategori tidak ada di database core
 * Menggunakan prd_product_category sebagai alternatif
 */
class Kategori_model extends CI_Model
{
    protected $table = 'prd_product_category';

    public function get_all_active()
    {
        return [];
    }

    public function get_all()
    {
        // Return empty array karena table structure berbeda
        return [];
        
        /* Alternative jika ingin pakai prd_product_category:
        return $this->db->order_by('category_name', 'ASC')
                        ->get($this->table)->result_array();
        */
    }

    public function get_by_id($id)
    {
        return null;
    }
}
