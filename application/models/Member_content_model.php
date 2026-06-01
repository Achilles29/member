<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Member Content Model
 */
class Member_content_model extends CI_Model
{
    public function get_active_promo()
    {
        if (!$this->db->table_exists('member_promo')) {
            return [];
        }

        return $this->db->where('is_active', 1)
            ->order_by('urutan', 'ASC')
            ->get('member_promo')
            ->result_array();
    }

    public function get_active_news()
    {
        if (!$this->db->table_exists('member_news')) {
            return [];
        }

        return $this->db->where('is_active', 1)
            ->order_by('urutan', 'ASC')
            ->get('member_news')
            ->result_array();
    }
}
