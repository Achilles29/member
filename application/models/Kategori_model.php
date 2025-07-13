<?php
defined('BASEPATH') OR exit('No direct script access allowed');


class Kategori_model extends CI_Model {
    public function get_all() {
        return $this->db
            ->where('status', 1)
            ->order_by('urutan', 'ASC')
            ->get('pr_kategori')
            ->result();
    }
}
