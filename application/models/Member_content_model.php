<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Member Content Model
 * 
 * Model untuk konten member (promo & news)
 * Sementara return empty karena table tidak ada di database core
 */
class Member_content_model extends CI_Model
{
    /**
     * Get active promo
     * Returns empty array karena table member_promo tidak ada di core
     */
    public function get_active_promo()
    {
        // Table member_promo tidak ada di database core
        // Return empty array untuk mencegah error
        return [];
        
        /* Original code yang membutuhkan table member_promo:
        return $this->db->where('is_active', 1)
                        ->order_by('urutan', 'ASC')
                        ->get('member_promo')->result_array();
        */
    }

    /**
     * Get active news
     * Returns empty array karena table member_news tidak ada di core
     */
    public function get_active_news()
    {
        // Table member_news tidak ada di database core
        // Return empty array untuk mencegah error
        return [];
        
        /* Original code yang membutuhkan table member_news:
        return $this->db->where('is_active', 1)
                        ->order_by('urutan', 'ASC')
                        ->get('member_news')->result_array();
        */
    }
}
