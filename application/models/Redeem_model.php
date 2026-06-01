<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Redeem Model
 * 
 * Model untuk redeem poin/stamp
 * Di database core, tidak ada pr_redeem_setting
 * Menggunakan pos_voucher_campaign sebagai alternatif
 */
class Redeem_model extends CI_Model
{
    protected $table_voucher_campaign = 'pos_voucher_campaign';
    protected $table_voucher_issue = 'pos_voucher_issue';
    protected $table_point_ledger = 'pos_point_ledger';
    protected $table_stamp_ledger = 'pos_stamp_ledger';
    protected $table_member = 'crm_member';

    private function map_campaign($row)
    {
        if (empty($row)) {
            return null;
        }

        $is_product = strtoupper((string) ($row['voucher_type'] ?? '')) === 'FREE_PRODUCT';
        $is_percent = strtoupper((string) ($row['voucher_type'] ?? '')) === 'PERCENT';
        $is_stamp = (float) ($row['stamp_cost'] ?? 0) > 0;

        return [
            'id' => (int) $row['id'],
            'campaign_id' => (int) $row['id'],
            'campaign_code' => $row['campaign_code'] ?? null,
            'nama_redeem' => $row['campaign_name'] ?? 'Voucher',
            'jenis' => $is_stamp ? 'stamp' : 'poin',
            'jumlah_dibutuhkan' => (int) round($is_stamp ? ($row['stamp_cost'] ?? 0) : ($row['point_cost'] ?? 0)),
            'jenis_voucher' => $is_product ? 'produk' : 'diskon',
            'produk_id' => $row['free_product_id'] ?? null,
            'produk_nama' => $row['produk_nama'] ?? null,
            'tipe_diskon' => $is_percent ? 'persentase' : 'nominal',
            'nilai_voucher' => (float) ($row['discount_value'] ?? 0),
            'max_diskon' => (float) ($row['max_discount_amount'] ?? 0),
            'valid_day_count' => (int) ($row['valid_day_count'] ?? 0),
            'end_date' => $row['end_date'] ?? null,
        ];
    }

    private function generate_voucher_issue_no()
    {
        return 'VI-' . date('YmdHis') . '-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 6));
    }

    private function generate_voucher_code($campaign_code)
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', (string) $campaign_code), 0, 8));
        if ($prefix === '') {
            $prefix = 'VCHR';
        }

        return $prefix . '-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 6));
    }

    private function get_point_balance($member_id)
    {
        $row = $this->db
            ->select('balance_after')
            ->where('member_id', $member_id)
            ->order_by('created_at', 'DESC')
            ->limit(1)
            ->get($this->table_point_ledger)
            ->row();

        if ($row) {
            return (float) $row->balance_after;
        }

        $member = $this->db->select('point_balance_cache')->get_where($this->table_member, ['id' => $member_id])->row();
        return (float) ($member->point_balance_cache ?? 0);
    }

    private function get_stamp_balance($member_id)
    {
        $row = $this->db
            ->select('balance_after')
            ->where('member_id', $member_id)
            ->order_by('created_at', 'DESC')
            ->limit(1)
            ->get($this->table_stamp_ledger)
            ->row();

        if ($row) {
            return (float) $row->balance_after;
        }

        $member = $this->db->select('stamp_balance_cache')->get_where($this->table_member, ['id' => $member_id])->row();
        return (float) ($member->stamp_balance_cache ?? 0);
    }

    private function sync_member_cache($member_id, $column, $balance)
    {
        $this->db->where('id', $member_id)->update($this->table_member, [$column => $balance]);
    }

    public function get_all_active_by_type($jenis)
    {
        $today = date('Y-m-d');

        $this->db->select('vc.*, mp.product_name as produk_nama');
        $this->db->from($this->table_voucher_campaign . ' vc');
        $this->db->join('mst_product mp', 'mp.id = vc.free_product_id', 'left');
        $this->db->where('vc.is_active', 1);
        $this->db->group_start();
        $this->db->where('vc.start_date IS NULL', null, false);
        $this->db->or_where('vc.start_date <=', $today);
        $this->db->group_end();
        $this->db->group_start();
        $this->db->where('vc.end_date IS NULL', null, false);
        $this->db->or_where('vc.end_date >=', $today);
        $this->db->group_end();

        if ($jenis === 'stamp') {
            $this->db->where('vc.stamp_cost >', 0);
        } else {
            $this->db->where('vc.point_cost >', 0);
        }

        $this->db->order_by('vc.campaign_name', 'ASC');

        return array_values(array_filter(array_map([$this, 'map_campaign'], $this->db->get()->result_array())));
    }

    public function get_active_redeem($jenis)
    {
        return $this->get_all_active_by_type($jenis);
    }

    public function get_redeem_by_id($id)
    {
        $row = $this->db
            ->select('vc.*, mp.product_name as produk_nama')
            ->from($this->table_voucher_campaign . ' vc')
            ->join('mst_product mp', 'mp.id = vc.free_product_id', 'left')
            ->where('vc.id', $id)
            ->where('vc.is_active', 1)
            ->get()
            ->row_array();

        return $this->map_campaign($row);
    }

    public function get_active_stamp($customer_id)
    {
        $this->db->select('sl.campaign_id, sc.campaign_name as nama_promo, GREATEST(SUM(sl.stamp_in - sl.stamp_out), 0) as jumlah_stamp, sc.redeem_required_stamp as total_stamp_target, sc.is_active as aktif', false);
        $this->db->from('pos_stamp_ledger sl');
        $this->db->join('pos_stamp_campaign sc', 'sl.campaign_id = sc.id');
        $this->db->where('sl.member_id', $customer_id);
        $this->db->where('sc.is_active', 1);
        $this->db->where('(sl.expired_at >= NOW() OR sl.expired_at IS NULL)');
        $this->db->group_by('sl.campaign_id');
        $this->db->having('jumlah_stamp >', 0);
        $this->db->order_by('sl.created_at', 'desc');
        return $this->db->get()->result_array();
    }

    public function get_active_redeem_by_type($customer_id, $jenis)
    {
        return $this->get_all_active_by_type($jenis);
    }

    public function process_redeem($customer_id, $redeem)
    {
        if (empty($redeem)) {
            return false;
        }

        $this->db->trans_begin();

        $redeem_row = is_array($redeem) ? $redeem : $this->get_redeem_by_id((int) $redeem);
        if (empty($redeem_row)) {
            $this->db->trans_rollback();
            return false;
        }

        $ok = $this->potong_saldo($customer_id, $redeem_row);
        if (!$ok) {
            $this->db->trans_rollback();
            return false;
        }

        $voucher_id = $this->simpan_voucher($customer_id, $redeem_row);
        if (!$voucher_id) {
            $this->db->trans_rollback();
            return false;
        }

        $this->log_redeem($customer_id, $redeem_row, $voucher_id);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }

        $this->db->trans_commit();
        return true;
    }

    public function get_poin_aktif($customer_id)
    {
        return (int) round($this->get_point_balance($customer_id));
    }

    public function get_setting($id)
    {
        return $this->get_redeem_by_id($id);
    }

    public function potong_saldo($member_id, $redeem)
    {
        if (($redeem['jenis'] ?? 'poin') === 'stamp') {
            $need = (float) ($redeem['jumlah_dibutuhkan'] ?? 0);
            $balance = $this->get_stamp_balance($member_id);
            if ($balance < $need || $need <= 0) {
                return false;
            }

            $new_balance = $balance - $need;
            $ok = $this->db->insert($this->table_stamp_ledger, [
                'member_id' => $member_id,
                'campaign_id' => $redeem['campaign_id'] ?? null,
                'ledger_type' => 'REDEEM',
                'stamp_in' => 0,
                'stamp_out' => $need,
                'balance_after' => $new_balance,
                'notes' => 'Redeem voucher: ' . ($redeem['nama_redeem'] ?? 'Voucher'),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if ($ok) {
                $this->sync_member_cache($member_id, 'stamp_balance_cache', $new_balance);
            }

            return $ok;
        }

        $need = (float) ($redeem['jumlah_dibutuhkan'] ?? 0);
        $balance = $this->get_point_balance($member_id);
        if ($balance < $need || $need <= 0) {
            return false;
        }

        $new_balance = $balance - $need;
        $ok = $this->db->insert($this->table_point_ledger, [
            'member_id' => $member_id,
            'ledger_type' => 'REDEEM',
            'points_in' => 0,
            'points_out' => $need,
            'balance_after' => $new_balance,
            'notes' => 'Redeem voucher: ' . ($redeem['nama_redeem'] ?? 'Voucher'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($ok) {
            $this->sync_member_cache($member_id, 'point_balance_cache', $new_balance);
        }

        return $ok;
    }

    public function simpan_voucher($member_id, $redeem)
    {
        $expired_at = null;
        if (!empty($redeem['valid_day_count'])) {
            $expired_at = date('Y-m-d H:i:s', strtotime('+' . (int) $redeem['valid_day_count'] . ' days'));
        } elseif (!empty($redeem['end_date'])) {
            $expired_at = $redeem['end_date'] . ' 23:59:59';
        }

        $campaign = $this->db->get_where($this->table_voucher_campaign, ['id' => $redeem['campaign_id']])->row_array();
        if (!$campaign) {
            return false;
        }

        $data = [
            'voucher_issue_no' => $this->generate_voucher_issue_no(),
            'campaign_id' => $redeem['campaign_id'],
            'member_id' => $member_id,
            'voucher_code' => $this->generate_voucher_code($campaign['campaign_code'] ?? ''),
            'voucher_status' => 'OPEN',
            'amount_snapshot' => strtoupper((string) ($campaign['voucher_type'] ?? '')) === 'PERCENT' ? 0 : (float) ($campaign['discount_value'] ?? 0),
            'percent_snapshot' => strtoupper((string) ($campaign['voucher_type'] ?? '')) === 'PERCENT' ? (float) ($campaign['discount_value'] ?? 0) : 0,
            'issued_at' => date('Y-m-d H:i:s'),
            'expired_at' => $expired_at,
            'notes' => 'Redeem via member app',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $ok = $this->db->insert($this->table_voucher_issue, $data);
        if (!$ok) {
            return false;
        }

        return (int) $this->db->insert_id();
    }

    public function log_redeem($member_id, $redeem, $voucher_id)
    {
        return true;
    }
}
