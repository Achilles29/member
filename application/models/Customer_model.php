<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Customer Model
 * 
 * Model untuk mengelola customer/member registration
 * 
 * @package    Member Application
 * @category   Models
 */
class Customer_model extends CI_Model
{
    protected $table = 'crm_customer';
    protected $table_member = 'crm_member_account';

    /**
     * Insert new customer
     */
    public function insert_customer($data)
    {
        // Map to new structure
        $customer_data = [
            'customer_code' => $data['kode_pelanggan'] ?? $this->generate_customer_code(),
            'customer_name' => $data['nama'] ?? '',
            'phone' => $data['telepon'] ?? '',
            'email' => $data['email'] ?? null,
            'birth_date' => $data['tanggal_lahir'] ?? null,
            'gender' => $this->map_gender($data['jenis_kelamin'] ?? null),
            'address' => $data['alamat'] ?? null,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $ok = $this->db->insert($this->table, $customer_data);
        if (!$ok) return false;
        
        $customer_id = (int)$this->db->insert_id();
        
        // Create member account
        $member_data = [
            'member_no' => $customer_data['customer_code'],
            'customer_id' => $customer_id,
            'tier_code' => 'SILVER',
            'status' => 'ACTIVE',
            'joined_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert($this->table_member, $member_data);
        
        return $customer_id;
    }

    /**
     * Get customer by phone
     */
    public function get_customer_by_telepon($telepon)
    {
        $this->db->select('c.*, m.member_no, m.tier_code, m.status as member_status');
        $this->db->from($this->table . ' c');
        $this->db->join($this->table_member . ' m', 'm.customer_id = c.id', 'left');
        $this->db->where('c.phone', $telepon);
        
        $result = $this->db->get()->row_array();
        
        if ($result) {
            // Map back to old field names for compatibility
            $result['nama'] = $result['customer_name'];
            $result['telepon'] = $result['phone'];
            $result['jenis_kelamin'] = $this->reverse_map_gender($result['gender'] ?? null);
            $result['tanggal_lahir'] = $result['birth_date'];
            $result['alamat'] = $result['address'];
        }
        
        return $result;
    }

    /**
     * Get customer by ID
     */
    public function get_customer_by_id($id)
    {
        $this->db->select('c.*, m.member_no, m.tier_code, m.status as member_status');
        $this->db->from($this->table . ' c');
        $this->db->join($this->table_member . ' m', 'm.customer_id = c.id', 'left');
        $this->db->where('c.id', $id);
        
        $result = $this->db->get()->row_array();
        
        if ($result) {
            // Map back to old field names
            $result['nama'] = $result['customer_name'];
            $result['telepon'] = $result['phone'];
            $result['jenis_kelamin'] = $this->reverse_map_gender($result['gender'] ?? null);
            $result['tanggal_lahir'] = $result['birth_date'];
            $result['alamat'] = $result['address'];
            $result['kode_pelanggan'] = $result['customer_code'];
        }
        
        return $result;
    }

    /**
     * Get last customer by date
     */
    public function get_last_by_date($tanggal)
    {
        $prefix = $tanggal;
        $this->db->like('customer_code', $prefix, 'after');
        $this->db->from($this->table);
        
        return $this->db->count_all_results();
    }

    /**
     * Generate customer code
     */
    private function generate_customer_code()
    {
        $date = date('Ymd');
        $count = $this->get_last_by_date($date) + 1;
        
        return $date . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Map gender to enum
     */
    private function map_gender($jenis_kelamin)
    {
        if ($jenis_kelamin === 'L') return 'MALE';
        if ($jenis_kelamin === 'P') return 'FEMALE';
        return 'OTHER';
    }

    /**
     * Reverse map gender
     */
    private function reverse_map_gender($gender)
    {
        if ($gender === 'MALE') return 'L';
        if ($gender === 'FEMALE') return 'P';
        return null;
    }
}
