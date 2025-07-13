<?php

class Member_content_model extends CI_Model
{
    public function get_active_promo()
    {
        return $this->db->where('is_active', 1)
                        ->order_by('urutan', 'ASC')
                        ->get('member_promo')->result_array();
    }

    public function get_active_news()
    {
        return $this->db->where('is_active', 1)
                        ->order_by('urutan', 'ASC')
                        ->get('member_news')->result_array();
    }
}
