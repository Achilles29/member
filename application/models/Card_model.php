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
    protected $table = 'crm_member';

    /**
     * Get member card data
     */
    public function get_member_by_id($id)
    {
        $result = $this->db->get_where($this->table, ['id' => $id])->row_array();

        if ($result) {
            $result['nama'] = $result['member_name'] ?? '';
            $result['telepon'] = $result['mobile_phone'] ?? '';
            $result['kode_pelanggan'] = $result['member_no'] ?? '';
            $result['name'] = $result['nama'];
            $result['phone'] = $result['telepon'];
        }

        return $result;
    }


}
