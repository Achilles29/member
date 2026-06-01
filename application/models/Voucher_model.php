<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Voucher_model extends CI_Model
{
    protected $table = 'pos_voucher_issue';
    protected $table_campaign = 'pos_voucher_campaign';

    private function map_campaign_row($row)
    {
        if (empty($row)) {
            return null;
        }

        $is_product = strtoupper((string) ($row['voucher_type'] ?? '')) === 'FREE_PRODUCT';
        $is_percent = strtoupper((string) ($row['voucher_type'] ?? '')) === 'PERCENT';

        $row['jenis_voucher'] = $is_product ? 'produk' : 'diskon';
        $row['jenis'] = $row['jenis_voucher'];
        $row['tipe_diskon'] = $is_percent ? 'persentase' : 'nominal';
        $row['nilai_voucher'] = (float) ($row['discount_value'] ?? 0);
        $row['nilai'] = $row['nilai_voucher'];
        $row['max_diskon'] = (float) ($row['max_discount_amount'] ?? 0);
        $row['produk_id'] = $row['free_product_id'] ?? null;
        $row['nama_redeem'] = $row['campaign_name'] ?? 'Voucher';

        return $row;
    }

    private function map_issue_row($row)
    {
        if (empty($row)) {
            return null;
        }

        $campaign = $this->map_campaign_row($row);
        $campaign['kode_voucher'] = $row['voucher_code'] ?? '-';
        $campaign['code'] = $campaign['kode_voucher'];
        $campaign['description'] = $campaign['campaign_name'] ?? 'Voucher';
        $campaign['discount_type'] = ($campaign['tipe_diskon'] ?? 'nominal') === 'persentase' ? 'persentase' : 'nominal';
        $campaign['discount_value'] = ($campaign['discount_type'] === 'persentase')
            ? (float) ($row['percent_snapshot'] ?? $campaign['nilai_voucher'] ?? 0)
            : (float) ($row['amount_snapshot'] ?? $campaign['nilai_voucher'] ?? 0);
        $campaign['tanggal_mulai'] = $row['issued_at'] ?? ($row['start_date'] ?? null);
        $campaign['tanggal_berakhir'] = $row['expired_at'] ?? ($row['end_date'] ?? null);
        $campaign['start_date'] = $campaign['tanggal_mulai'];
        $campaign['end_date'] = $campaign['tanggal_berakhir'];
        $campaign['member_voucher_id'] = $row['id'] ?? null;
        $campaign['status_label'] = $row['voucher_status'] ?? 'OPEN';

        return $campaign;
    }

    public function get_by_status($customer_id, $status)
    {
        $this->db->select('vi.*, vc.*, mp.product_name as produk_nama');
        $this->db->from($this->table . ' vi');
        $this->db->join($this->table_campaign . ' vc', 'vc.id = vi.campaign_id', 'left');
        $this->db->join('mst_product mp', 'mp.id = vc.free_product_id', 'left');
        $this->db->where('vi.member_id', $customer_id);

        if ($status == 'aktif') {
            $this->db->where('vi.voucher_status', 'OPEN');
            $this->db->where('(vi.expired_at >= NOW() OR vi.expired_at IS NULL)');
        } elseif ($status == 'digunakan') {
            $this->db->where('vi.voucher_status', 'REDEEMED');
        } elseif ($status == 'kadaluarsa') {
            $this->db->group_start();
            $this->db->where('vi.voucher_status', 'EXPIRED');
            $this->db->or_where('vi.voucher_status', 'VOID');
            $this->db->or_where('vi.expired_at <', date('Y-m-d H:i:s'));
            $this->db->group_end();
        }

        $this->db->order_by('vi.expired_at', 'DESC');

        return array_map([$this, 'map_issue_row'], $this->db->get()->result_array());
    }

    public function count_by_status($customer_id, $status)
    {
        $this->db->from($this->table);
        $this->db->where('member_id', $customer_id);

        if ($status == 'aktif') {
            $this->db->where('voucher_status', 'OPEN');
            $this->db->where('(expired_at >= NOW() OR expired_at IS NULL)');
        } elseif ($status == 'digunakan') {
            $this->db->where('voucher_status', 'REDEEMED');
        } elseif ($status == 'kadaluarsa') {
            $this->db->group_start();
            $this->db->where('voucher_status', 'EXPIRED');
            $this->db->or_where('voucher_status', 'VOID');
            $this->db->or_where('expired_at <', date('Y-m-d H:i:s'));
            $this->db->group_end();
        }

        return (int) $this->db->count_all_results();
    }

    public function get_by_status_paginated($customer_id, $status, $limit, $offset)
    {
        $this->db->select('vi.*, vc.*, mp.product_name as produk_nama');
        $this->db->from($this->table . ' vi');
        $this->db->join($this->table_campaign . ' vc', 'vc.id = vi.campaign_id', 'left');
        $this->db->join('mst_product mp', 'mp.id = vc.free_product_id', 'left');
        $this->db->where('vi.member_id', $customer_id);

        if ($status == 'aktif') {
            $this->db->where('vi.voucher_status', 'OPEN');
            $this->db->where('(vi.expired_at >= NOW() OR vi.expired_at IS NULL)');
        } elseif ($status == 'digunakan') {
            $this->db->where('vi.voucher_status', 'REDEEMED');
        } elseif ($status == 'kadaluarsa') {
            $this->db->group_start();
            $this->db->where('vi.voucher_status', 'EXPIRED');
            $this->db->or_where('vi.voucher_status', 'VOID');
            $this->db->or_where('vi.expired_at <', date('Y-m-d H:i:s'));
            $this->db->group_end();
        }

        $this->db->order_by('vi.expired_at', 'DESC');
        $this->db->limit((int)$limit, (int)$offset);

        return array_map([$this, 'map_issue_row'], $this->db->get()->result_array());
    }

    public function get_summary($customer_id)
    {
        $today = date('Y-m-d H:i:s');

        $aktif = $this->db
            ->where('member_id', $customer_id)
            ->where('voucher_status', 'OPEN')
            ->where('(expired_at >= "' . $today . '" OR expired_at IS NULL)')
            ->count_all_results($this->table);

        $digunakan = $this->db
            ->where('member_id', $customer_id)
            ->where('voucher_status', 'REDEEMED')
            ->count_all_results($this->table);

        $this->db->where('member_id', $customer_id);
        $this->db->group_start();
        $this->db->where('voucher_status', 'EXPIRED');
        $this->db->or_where('voucher_status', 'VOID');
        $this->db->or_where('expired_at <', $today);
        $this->db->group_end();
        $kadaluarsa = $this->db->count_all_results($this->table);
        
        return [
            'aktif' => (int) $aktif,
            'digunakan' => (int) $digunakan,
            'kadaluarsa' => (int) $kadaluarsa,
            'total_nilai' => 0
        ];
    }

    public function get_by_id($id, $customer_id = null)
    {
        $this->db->select('vi.*, vc.*, mp.product_name as produk_nama');
        $this->db->from($this->table . ' vi');
        $this->db->join($this->table_campaign . ' vc', 'vc.id = vi.campaign_id', 'left');
        $this->db->join('mst_product mp', 'mp.id = vc.free_product_id', 'left');
        $this->db->where('vi.id', $id);

        if ($customer_id) {
            $this->db->where('vi.member_id', $customer_id);
        }

        return $this->map_issue_row($this->db->get()->row_array());
    }

    public function get_by_code($code, $customer_id = null)
    {
        $this->db->select('vi.*, vc.*, mp.product_name as produk_nama');
        $this->db->from($this->table . ' vi');
        $this->db->join($this->table_campaign . ' vc', 'vc.id = vi.campaign_id', 'left');
        $this->db->join('mst_product mp', 'mp.id = vc.free_product_id', 'left');
        $this->db->where('vi.voucher_code', $code);

        if ($customer_id) {
            $this->db->where('vi.member_id', $customer_id);
        }

        return $this->map_issue_row($this->db->get()->row_array());
    }

    public function get_vouchers_for_member($member_id)
    {
        return $this->get_by_status($member_id, 'aktif');
    }

    public function get_all_vouchers()
    {
        $rows = $this->db
            ->where('is_active', 1)
            ->order_by('campaign_name', 'ASC')
            ->get($this->table_campaign)
            ->result_array();

        $result = [];
        foreach ($rows as $row) {
            $mapped = $this->map_campaign_row($row);
            $result[] = [
                'id' => $row['id'],
                'code' => $row['campaign_code'],
                'description' => $mapped['nama_redeem'],
            ];
        }

        return $result;
    }

    public function use_voucher($id, $customer_id)
    {
        return true;
    }

    public function create($data)
    {
        return true;
    }

    public function auto_expire()
    {
        return true;
    }
}
