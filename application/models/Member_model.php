<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Member_model extends CI_Model
{
    protected $table = 'crm_customer';
    protected $table_member = 'crm_member_account';
    protected $table_poin = 'pos_point_ledger';

    public function get_by_phone($phone)
    {
        $this->db->select('c.*, m.member_no, m.tier_code, m.status as member_status');
        $this->db->from($this->table . ' c');
        $this->db->join($this->table_member . ' m', 'm.customer_id = c.id', 'left');
        $this->db->where('c.phone', $phone);
        $this->db->where('c.is_active', 1);
        
        return $this->db->get()->row_array();
    }

    public function get_by_id($id)
    {
        $this->db->select('
            c.id, 
            c.customer_name as nama, 
            c.customer_code as kode_pelanggan,
            c.phone as telepon, 
            c.email, 
            c.gender as jenis_kelamin, 
            c.birth_date as tanggal_lahir, 
            c.address as alamat, 
            c.is_active as status, 
            c.created_at, 
            c.updated_at,
            m.member_no, 
            m.tier_code, 
            m.status as member_status
        ');
        $this->db->from($this->table . ' c');
        $this->db->join($this->table_member . ' m', 'm.customer_id = c.id', 'left');
        $this->db->where('c.id', $id);
        $member = $this->db->get()->row_array();
        
        if ($member) {
            $member['foto'] = null;
            $member['foto_url'] = base_url('assets/img/default-avatar.png');
            $member['initials'] = $this->get_initials($member['nama'] ?? '');
            // Pastikan kode_pelanggan ada, prioritas member_no
            if (!isset($member['kode_pelanggan']) || empty($member['kode_pelanggan'])) {
                $member['kode_pelanggan'] = $member['member_no'] ?? '-';
            }
        }
        
        return $member;
    }

    public function get_member_by_id($id)
    {
        return $this->get_by_id($id);
    }

    public function get_active_poin($id)
    {
        $member_account = $this->db->select('id')->where('customer_id', $id)->get($this->table_member)->row();
        
        if (!$member_account) return 0;
        
        $this->db->select('balance_after');
        $this->db->from($this->table_poin);
        $this->db->where('member_account_id', $member_account->id);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit(1);
        $result = $this->db->get()->row();
        
        return (int) ($result->balance_after ?? 0);
    }

    public function get_level($poin)
    {
        if ($poin >= 1000) return 'Diamond';
        if ($poin >= 500) return 'Platinum';
        if ($poin >= 200) return 'Gold';
        return 'Silver';
    }

    public function get_level_color($level)
    {
        $colors = [
            'Diamond' => '#00C9FF',
            'Platinum' => '#E5E4E2',
            'Gold' => '#FFD700',
            'Silver' => '#C0C0C0'
        ];
        
        return $colors[$level] ?? '#C0C0C0';
    }

    public function get_statistics($member_id)
    {
        // Return simple statistics without errors
        return [
            'total_transaksi' => 0,
            'total_belanja' => 0,
            'poin_aktif' => $this->get_active_poin($member_id),
            'voucher_aktif' => 0,
            'stamp_aktif' => 0
        ];
    }

    public function update($id, $data)
    {
        $db_data = [];
        if (isset($data['nama'])) $db_data['customer_name'] = $data['nama'];
        if (isset($data['telepon'])) $db_data['phone'] = $data['telepon'];
        if (isset($data['email'])) $db_data['email'] = $data['email'];
        if (isset($data['jenis_kelamin'])) {
            $db_data['gender'] = ($data['jenis_kelamin'] === 'L') ? 'MALE' : (($data['jenis_kelamin'] === 'P') ? 'FEMALE' : 'OTHER');
        }
        if (isset($data['tanggal_lahir'])) $db_data['birth_date'] = $data['tanggal_lahir'];
        if (isset($data['alamat'])) $db_data['address'] = $data['alamat'];
        if (isset($data['status'])) $db_data['is_active'] = ($data['status'] === 'aktif') ? 1 : 0;
        if (isset($data['last_login'])) $db_data['updated_at'] = $data['last_login'];
        
        if (empty($db_data)) return false;
        
        $this->db->where('id', $id);
        return $this->db->update($this->table, $db_data);
    }

    private function get_photo_url($foto)
    {
        if ($foto && file_exists(FCPATH . 'uploads/foto_pelanggan/' . $foto)) {
            return base_url('uploads/foto_pelanggan/' . $foto);
        }
        
        return base_url('assets/img/default-avatar.png');
    }

    private function get_initials($nama)
    {
        if (!$nama) return 'NA';
        
        $words = explode(' ', $nama);
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        
        return strtoupper(substr($nama, 0, 2));
    }

    public function phone_exists($phone, $exclude_id = null)
    {
        $this->db->where('phone', $phone);
        
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        
        return $this->db->count_all_results($this->table) > 0;
    }

    public function email_exists($email, $exclude_id = null)
    {
        if (!$email) return false;
        
        $this->db->where('email', $email);
        
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        
        return $this->db->count_all_results($this->table) > 0;
    }
}
