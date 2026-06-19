<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Member_model extends CI_Model
{
    protected $table = 'crm_member';
    protected $table_poin = 'pos_point_ledger';
    private $table_fields;

    private function get_table_fields()
    {
        if ($this->table_fields === null) {
            $this->table_fields = $this->db->list_fields($this->table);
        }

        return $this->table_fields;
    }

    private function has_column($column)
    {
        return in_array($column, $this->get_table_fields(), true);
    }

    private function map_member_row($row)
    {
        if (empty($row)) {
            return null;
        }

        $foto = $row['photo_path'] ?? null;

        $row['nama'] = $row['member_name'] ?? '';
        $row['kode_pelanggan'] = $row['member_no'] ?? '-';
        $row['telepon'] = $row['mobile_phone'] ?? '';
        $row['jenis_kelamin'] = $row['gender'] ?? null;
        $row['tanggal_lahir'] = $row['birth_date'] ?? null;
        $row['alamat'] = $row['address'] ?? null;
        $row['member_status'] = $row['member_status'] ?? 'ACTIVE';
        $row['status'] = !empty($row['is_active']) ? 'aktif' : 'nonaktif';
        $row['foto'] = $foto;
        $row['foto_url'] = $this->get_photo_url($foto);
        $row['initials'] = $this->get_initials($row['nama']);

        return $row;
    }

    public function get_by_phone($phone)
    {
        $row = $this->db
            ->from($this->table)
            ->where('mobile_phone', $phone)
            ->where('is_active', 1)
            ->limit(1)
            ->get()
            ->row_array();

        return $this->map_member_row($row);
    }

    public function get_by_id($id)
    {
        $row = $this->db->get_where($this->table, ['id' => $id])->row_array();

        return $this->map_member_row($row);
    }

    public function get_member_by_id($id)
    {
        return $this->get_by_id($id);
    }

    private function get_default_stamp_campaign()
    {
        return $this->db
            ->where('is_active', 1)
            ->order_by('id', 'ASC')
            ->limit(1)
            ->get('pos_stamp_campaign')
            ->row_array();
    }

    private function rebuild_stamp_balances($member_id)
    {
        $rows = $this->db
            ->select('id, stamp_in, stamp_out')
            ->where('member_id', $member_id)
            ->order_by('created_at', 'ASC')
            ->order_by('id', 'ASC')
            ->get('pos_stamp_ledger')
            ->result_array();

        $balance = 0.0;
        foreach ($rows as $row) {
            $balance += (float) ($row['stamp_in'] ?? 0) - (float) ($row['stamp_out'] ?? 0);
            $this->db->where('id', $row['id'])->update('pos_stamp_ledger', ['balance_after' => $balance]);
        }

        $this->db->where('id', $member_id)->update($this->table, ['stamp_balance_cache' => $balance]);
    }

    public function get_active_poin($id)
    {
        // Legacy balance: saldo poin sebelum migrasi (point_balance_cache di crm_member)
        $member = $this->db->select('point_balance_cache')->get_where($this->table, ['id' => $id])->row_array();
        $legacy = ($member && isset($member['point_balance_cache']))
            ? (float) $member['point_balance_cache']
            : 0;

        // New balance: running total dari pos_point_ledger (transaksi setelah migrasi)
        $result = $this->db
            ->select('balance_after')
            ->where('member_id', $id)
            ->order_by('created_at', 'DESC')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get($this->table_poin)
            ->row();
        $new = (float) ($result->balance_after ?? 0);

        return (int) round($legacy + $new);
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

    public function get_stamps_with_date($member_id)
    {
        return $this->db
            ->select('id, member_id, created_at as stamp_date')
            ->from('pos_stamp_ledger')
            ->where('member_id', $member_id)
            ->where('ledger_type', 'EARN')
            ->where('stamp_in >', 0)
            ->order_by('created_at', 'ASC')
            ->order_by('id', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_stamps($member_id)
    {
        return $this->get_stamps_with_date($member_id);
    }

    public function add_stamp($member_id)
    {
        $campaign = $this->get_default_stamp_campaign();
        if (!$campaign) {
            return false;
        }

        $expires_at = null;
        if (!empty($campaign['stamp_expiry_days'])) {
            $expires_at = date('Y-m-d H:i:s', strtotime('+' . (int) $campaign['stamp_expiry_days'] . ' days'));
        }

        $ok = $this->db->insert('pos_stamp_ledger', [
            'member_id' => $member_id,
            'campaign_id' => $campaign['id'],
            'ledger_type' => 'EARN',
            'stamp_in' => 1,
            'stamp_out' => 0,
            'balance_after' => 0,
            'expired_at' => $expires_at,
            'notes' => 'Manual admin stamp',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($ok) {
            $this->rebuild_stamp_balances($member_id);
        }

        return $ok;
    }

    public function update($id, $data)
    {
        $db_data = [];
        if (isset($data['nama'])) $db_data['member_name'] = $data['nama'];
        if (isset($data['telepon'])) $db_data['mobile_phone'] = $data['telepon'];
        if (isset($data['email'])) $db_data['email'] = $data['email'];
        if (isset($data['jenis_kelamin'])) $db_data['gender'] = $data['jenis_kelamin'] ?: null;
        if (isset($data['tanggal_lahir'])) $db_data['birth_date'] = $data['tanggal_lahir'];
        if (isset($data['alamat'])) $db_data['address'] = $data['alamat'];
        if (isset($data['status'])) $db_data['is_active'] = (strtolower((string) $data['status']) === 'aktif') ? 1 : 0;
        if (isset($data['foto']) && $this->has_column('photo_path')) $db_data['photo_path'] = $data['foto'];
        if (isset($data['password']) && $this->has_column('password_hash')) $db_data['password_hash'] = $data['password'];
        if (isset($data['deleted_at']) && $this->has_column('deleted_at')) $db_data['deleted_at'] = $data['deleted_at'];
        if (isset($data['last_login']) && $this->has_column('updated_at')) {
            $db_data['updated_at'] = $data['last_login'];
        } elseif (!empty($db_data) && $this->has_column('updated_at') && !isset($db_data['updated_at'])) {
            $db_data['updated_at'] = date('Y-m-d H:i:s');
        }

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
        $this->db->where('mobile_phone', $phone);

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
