<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Card Model
 * 
 * Model untuk member card
 * 
 * @package    Member Application
 * @category   Models
 */
class Card_model extends CI_Model
{
    protected $table = 'crm_customer';
    protected $table_member = 'crm_member_account';

    /**
     * Get member card data
     */
    public function get_member_by_id($id)
    {
        $this->db->select('c.*, m.member_no, m.tier_code, m.status as member_status');
        $this->db->from($this->table . ' c');
        $this->db->join($this->table_member . ' m', 'm.customer_id = c.id', 'left');
        $this->db->where('c.id', $id);
        
        $result = $this->db->get()->row_array();
        
        if ($result) {
            // Map to compatible format
            $result['nama'] = $result['customer_name'];
            $result['telepon'] = $result['phone'];
            $result['kode_pelanggan'] = $result['customer_code'];
        }
        
        return $result;
    }


}
