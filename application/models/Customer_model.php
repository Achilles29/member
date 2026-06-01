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
    protected $table = 'crm_member';

    private function map_row($row)
    {
        if (empty($row)) {
            return null;
        }

        $row['nama'] = $row['member_name'] ?? '';
        $row['telepon'] = $row['mobile_phone'] ?? '';
        $row['jenis_kelamin'] = $row['gender'] ?? null;
        $row['tanggal_lahir'] = $row['birth_date'] ?? null;
        $row['alamat'] = $row['address'] ?? null;
        $row['kode_pelanggan'] = $row['member_no'] ?? null;
        $row['member_status'] = $row['member_status'] ?? 'ACTIVE';
        $row['name'] = $row['nama'];
        $row['phone'] = $row['telepon'];

        return $row;
    }

    /**
     * Insert new customer
     */
    public function insert_customer($data)
    {
        $customer_data = [
            'member_no' => $data['kode_pelanggan'] ?? $this->generate_customer_code(),
            'member_name' => $data['nama'] ?? '',
            'mobile_phone' => $data['telepon'] ?? '',
            'email' => $data['email'] ?? null,
            'birth_date' => $data['tanggal_lahir'] ?? null,
            'gender' => $this->map_gender($data['jenis_kelamin'] ?? null),
            'address' => $data['alamat'] ?? null,
            'member_tier' => 'Silver',
            'member_status' => 'ACTIVE',
            'is_active' => 1,
            'joined_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        $ok = $this->db->insert($this->table, $customer_data);
        if (!$ok) return false;

        return (int)$this->db->insert_id();
    }

    /**
     * Get customer by phone
     */
    public function get_customer_by_telepon($telepon)
    {
        $result = $this->db->get_where($this->table, ['mobile_phone' => $telepon])->row_array();

        return $this->map_row($result);
    }

    /**
     * Get customer by ID
     */
    public function get_customer_by_id($id)
    {
        $result = $this->db->get_where($this->table, ['id' => $id])->row_array();

        return $this->map_row($result);
    }

    /**
     * Get last customer by date
     */
    public function get_last_by_date($tanggal)
    {
        $prefix = $tanggal;
        $this->db->like('member_no', $prefix, 'after');
        $this->db->from($this->table);

        return $this->db->count_all_results();
    }

    public function get_customer($id)
    {
        return $this->get_customer_by_id($id);
    }

    public function get_stamps($id)
    {
        return $this->db
            ->select('id, created_at as stamp_date')
            ->from('pos_stamp_ledger')
            ->where('member_id', $id)
            ->where('ledger_type', 'EARN')
            ->where('stamp_in >', 0)
            ->order_by('created_at', 'ASC')
            ->get()
            ->result_array();
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
        if ($jenis_kelamin === 'L') return 'L';
        if ($jenis_kelamin === 'P') return 'P';
        return null;
    }

    /**
     * Reverse map gender
     */
    private function reverse_map_gender($gender)
    {
        if ($gender === 'L') return 'L';
        if ($gender === 'P') return 'P';
        return null;
    }
}
