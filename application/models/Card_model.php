<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Card_model extends CI_Model
{
    public function get_member_by_id($id)
    {
        return $this->db->get_where('pr_customer', ['id' => $id])->row_array();
    }


}
