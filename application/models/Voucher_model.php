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

        $campaign_id  = !empty($row['campaign_id']) ? (int) $row['campaign_id'] : null;
        $amount_snap  = (float) ($row['amount_snapshot'] ?? 0);
        $percent_snap = (float) ($row['percent_snapshot'] ?? 0);
        $notes        = (string) ($row['notes'] ?? '');

        if ($campaign_id) {
            // Campaign-based voucher
            $c = $this->map_campaign_row($row);
            $dtype  = $c['tipe_diskon'] === 'persentase' ? 'persentase' : 'nominal';
            // Prioritas: snapshot dari pos_voucher_issue, fallback ke nilai campaign
            $dvalue = $dtype === 'persentase'
                ? ($percent_snap > 0 ? $percent_snap : $c['nilai_voucher'])
                : ($amount_snap  > 0 ? $amount_snap  : $c['nilai_voucher']);

            $desc = $c['jenis_voucher'] === 'produk'
                ? ('Gratis: ' . html_escape($row['produk_nama'] ?? 'Produk'))
                : ($dtype === 'persentase'
                    ? 'Diskon ' . rtrim(rtrim(number_format($dvalue, 2), '0'), '.') . '%'
                      . ($c['max_diskon'] > 0 ? ' (max Rp ' . number_format((int) $c['max_diskon'], 0, ',', '.') . ')' : '')
                    : 'Diskon Rp ' . number_format((int) $dvalue, 0, ',', '.'));

            return array_merge($c, [
                'kode_voucher'      => $row['voucher_code'] ?? '-',
                'code'              => $row['voucher_code'] ?? '-',
                'description'       => $desc,
                'discount_type'     => $dtype,
                'discount_value'    => $dvalue,
                'nilai_voucher'     => $dvalue,
                'nilai'             => $dvalue,
                'tanggal_mulai'     => $row['issued_at']  ?? $row['start_date'] ?? null,
                'tanggal_berakhir'  => $row['expired_at'] ?? $row['end_date']   ?? null,
                'start_date'        => $row['issued_at']  ?? $row['start_date'] ?? null,
                'end_date'          => $row['expired_at'] ?? $row['end_date']   ?? null,
                'member_voucher_id' => $row['id'] ?? null,
                'status_label'      => $row['voucher_status'] ?? 'OPEN',
            ]);
        }

        // Rule-based voucher (campaign_id = NULL) — derive dari snapshots + notes
        $jenis_voucher = 'voucher';
        $tipe_diskon   = 'nominal';
        $nilai         = 0.0;
        $description   = $notes ?: 'Voucher Reward';

        if ($percent_snap > 0) {
            $jenis_voucher = 'diskon';
            $tipe_diskon   = 'persentase';
            $nilai         = $percent_snap;
            $description   = 'Diskon ' . rtrim(rtrim(number_format($percent_snap, 2), '0'), '.') . '%';
        } elseif ($amount_snap > 0) {
            $jenis_voucher = 'diskon';
            $tipe_diskon   = 'nominal';
            $nilai         = $amount_snap;
            $description   = 'Diskon Rp ' . number_format((int) $amount_snap, 0, ',', '.');
        } elseif (strpos($notes, 'Gratis:') === 0) {
            $jenis_voucher = 'produk';
            $description   = $notes;
        } elseif (strpos($notes, 'Merchandise:') === 0) {
            $jenis_voucher = 'voucher';
            $description   = $notes;
        }

        return [
            'id'                => $row['id'] ?? null,
            'member_voucher_id' => $row['id'] ?? null,
            'campaign_id'       => null,
            'kode_voucher'      => $row['voucher_code'] ?? '-',
            'code'              => $row['voucher_code'] ?? '-',
            'nama_redeem'       => $notes ?: 'Reward',
            'campaign_name'     => $notes ?: 'Reward',
            'description'       => $description,
            'jenis_voucher'     => $jenis_voucher,
            'jenis'             => $jenis_voucher,
            'tipe_diskon'       => $tipe_diskon,
            'discount_type'     => $tipe_diskon,
            'nilai_voucher'     => $nilai,
            'nilai'             => $nilai,
            'discount_value'    => $nilai,
            'max_diskon'        => 0,
            'tanggal_mulai'     => $row['issued_at']  ?? null,
            'tanggal_berakhir'  => $row['expired_at'] ?? null,
            'start_date'        => $row['issued_at']  ?? null,
            'end_date'          => $row['expired_at'] ?? null,
            'status_label'      => $row['voucher_status'] ?? 'OPEN',
            'voucher_status'    => $row['voucher_status'] ?? 'OPEN',
            'produk_nama'       => null,
            'produk_id'         => null,
            'notes'             => $notes,
            'voucher_type'      => null,
        ];
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
