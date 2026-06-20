<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Redeem_model extends CI_Model
{
    private function get_point_balance(int $member_id): float
    {
        $row = $this->db->query(
            'SELECT balance_after FROM pos_point_ledger WHERE member_id = ? ORDER BY id DESC LIMIT 1',
            [$member_id]
        )->row_array();
        if ($row) return (float) $row['balance_after'];

        $m = $this->db->select('point_balance_cache')->get_where('crm_member', ['id' => $member_id])->row_array();
        return (float) ($m['point_balance_cache'] ?? 0);
    }

    private function get_stamp_balance(int $member_id): float
    {
        $row = $this->db->query(
            'SELECT balance_after FROM pos_stamp_ledger WHERE member_id = ? ORDER BY id DESC LIMIT 1',
            [$member_id]
        )->row_array();
        if ($row) return (float) $row['balance_after'];

        $m = $this->db->select('stamp_balance_cache')->get_where('crm_member', ['id' => $member_id])->row_array();
        return (float) ($m['stamp_balance_cache'] ?? 0);
    }

    public function get_poin_aktif(int $member_id): int
    {
        return (int) round($this->get_point_balance($member_id));
    }

    public function get_stamp_aktif(int $member_id): int
    {
        return (int) round($this->get_stamp_balance($member_id));
    }

    private function map_rule(array $row, string $jenis): array
    {
        $reward_type   = strtoupper($row['reward_type'] ?? '');
        $jenis_voucher = 'lainnya';
        $tipe_diskon   = 'nominal';
        $nilai_voucher = 0.0;
        $max_diskon    = 0.0;
        $produk_id     = null;
        $produk_nama   = null;

        if (in_array($reward_type, ['PRODUCT', 'FREE_PRODUCT'], true)) {
            $jenis_voucher = 'produk';
            $produk_id     = $row['product_id'] ?? null;
            $produk_nama   = $row['produk_nama'] ?? null;
        } elseif ($reward_type === 'DISCOUNT_AMOUNT') {
            $jenis_voucher = 'diskon';
            $tipe_diskon   = 'nominal';
            $nilai_voucher = (float) ($row['discount_amount'] ?? 0);
        } elseif ($reward_type === 'DISCOUNT_PERCENT') {
            $jenis_voucher = 'diskon';
            $tipe_diskon   = 'persentase';
            $nilai_voucher = (float) ($row['discount_percent'] ?? 0);
        } elseif ($reward_type === 'VOUCHER') {
            $jenis_voucher = 'diskon';
            $vc_type       = strtoupper($row['vc_voucher_type'] ?? '');
            if ($vc_type === 'PERCENT') {
                $tipe_diskon   = 'persentase';
                $nilai_voucher = (float) ($row['vc_discount_value'] ?? 0);
                $max_diskon    = (float) ($row['vc_max_discount'] ?? 0);
            } else {
                $tipe_diskon   = 'nominal';
                $nilai_voucher = (float) ($row['vc_discount_value'] ?? 0);
            }
        }

        $cost = $jenis === 'stamp'
            ? (float) ($row['stamp_cost'] ?? 0)
            : (float) ($row['point_cost'] ?? 0);

        return [
            'id'                => (int) $row['id'],
            'nama_redeem'       => $row['rule_name'] ?? 'Reward',
            'deskripsi'         => $row['description'] ?? '',
            'jenis'             => $jenis,
            'cost_type'         => strtoupper($row['cost_type'] ?? 'POINT'),
            'jumlah_dibutuhkan' => (int) round($cost),
            'jenis_voucher'     => $jenis_voucher,
            'tipe_diskon'       => $tipe_diskon,
            'nilai_voucher'     => $nilai_voucher,
            'max_diskon'        => $max_diskon,
            'produk_id'         => $produk_id,
            'produk_nama'       => $produk_nama,
            'reward_notes'      => $row['reward_notes'] ?? '',
            'reward_type'       => $reward_type,
            'min_spend_amount'  => (float) ($row['min_spend_amount'] ?? 0),
        ];
    }

    public function get_rules_by_type(string $jenis): array
    {
        if (!$this->db->table_exists('pos_redeem_rule')) {
            return [];
        }

        $this->db->select('rr.*, mp.product_name AS produk_nama,
            vc.discount_value AS vc_discount_value, vc.voucher_type AS vc_voucher_type,
            vc.max_discount_amount AS vc_max_discount');
        $this->db->from('pos_redeem_rule rr');
        $this->db->join('mst_product mp', 'mp.id = rr.product_id', 'left');
        $this->db->join('pos_voucher_campaign vc', 'vc.id = rr.voucher_campaign_id', 'left');
        $this->db->where('rr.is_active', 1);

        // Exclude rules where stock is exhausted
        $this->db->group_start();
        $this->db->where('rr.stock_qty IS NULL', null, false);
        $this->db->or_where('rr.redeemed_count < rr.stock_qty', null, false);
        $this->db->group_end();

        if ($jenis === 'stamp') {
            $this->db->group_start();
            $this->db->where('rr.cost_type', 'STAMP');
            $this->db->or_where('rr.cost_type', 'BOTH');
            $this->db->group_end();
        } else {
            $this->db->group_start();
            $this->db->where('rr.cost_type', 'POINT');
            $this->db->or_where('rr.cost_type', 'BOTH');
            $this->db->group_end();
        }

        $this->db->order_by('rr.rule_name', 'ASC');
        $rows = $this->db->get()->result_array();

        return array_map(function ($row) use ($jenis) {
            return $this->map_rule($row, $jenis);
        }, $rows);
    }

    public function process_rule_redeem(int $member_id, int $rule_id): array
    {
        if (!$this->db->table_exists('pos_redeem_rule')) {
            return ['ok' => false, 'message' => 'Fitur redeem rule belum tersedia.'];
        }

        $prev_debug = $this->db->db_debug;
        $this->db->db_debug = false;
        $this->db->trans_begin();

        try {
            // 1. Load & validate rule
            $rule = $this->db->from('pos_redeem_rule')
                ->where('id', $rule_id)->where('is_active', 1)->limit(1)
                ->get()->row_array();
            if (!$rule) {
                throw new RuntimeException('Reward tidak ditemukan atau sudah tidak aktif.');
            }

            // 2. Stock check
            if ($rule['stock_qty'] !== null && (int) $rule['stock_qty'] <= (int) ($rule['redeemed_count'] ?? 0)) {
                throw new RuntimeException('Stok reward sudah habis.');
            }

            // 3. Validity check (valid_days dihitung dari created_at)
            if (!empty($rule['valid_days'])) {
                $expiry = strtotime((string) $rule['created_at']) + ((int) $rule['valid_days'] * 86400);
                if (time() > $expiry) {
                    throw new RuntimeException('Reward ini sudah kadaluarsa.');
                }
            }

            // 4. Lock member row & baca saldo terkini dari ledger
            $member = $this->db->query(
                'SELECT * FROM crm_member WHERE id = ? FOR UPDATE',
                [$member_id]
            )->row_array();
            if (!$member) {
                throw new RuntimeException('Member tidak ditemukan.');
            }

            $point_bal = $this->get_point_balance($member_id);
            $stamp_bal = $this->get_stamp_balance($member_id);
            $cost_type = strtoupper($rule['cost_type'] ?? 'POINT');
            $now       = date('Y-m-d H:i:s');
            $ledger_note = $rule['rule_name'];

            $point_ledger_id = null;
            $stamp_ledger_id = null;

            // 5. Potong poin
            if ($cost_type === 'POINT' || $cost_type === 'BOTH') {
                $cost = (float) ($rule['point_cost'] ?? 0);
                if ($cost <= 0) {
                    throw new RuntimeException('Biaya poin tidak valid.');
                }
                if ($point_bal < $cost - 0.0001) {
                    throw new RuntimeException(sprintf(
                        'Poin tidak cukup. Butuh %s poin, saldo kamu %s poin.',
                        number_format($cost, 0, ',', '.'),
                        number_format($point_bal, 0, ',', '.')
                    ));
                }
                $point_after = round($point_bal - $cost, 4);
                $this->db->insert('pos_point_ledger', [
                    'member_id'     => $member_id,
                    'order_id'      => null,
                    'payment_id'    => null,
                    'rule_id'       => null,
                    'ledger_type'   => 'REDEEM',
                    'points_in'     => 0,
                    'points_out'    => $cost,
                    'balance_after' => $point_after,
                    'notes'         => $ledger_note,
                    'created_at'    => $now,
                ]);
                $point_ledger_id = (int) $this->db->insert_id();
                $this->db->where('id', $member_id)->update('crm_member', ['point_balance_cache' => $point_after]);
                $point_bal = $point_after;
            }

            // 6. Potong stamp
            if ($cost_type === 'STAMP' || $cost_type === 'BOTH') {
                $cost    = (float) ($rule['stamp_cost'] ?? 0);
                $camp_id = !empty($rule['stamp_campaign_id']) ? (int) $rule['stamp_campaign_id'] : null;
                if ($cost <= 0) {
                    throw new RuntimeException('Biaya stamp tidak valid.');
                }
                if ($stamp_bal < $cost - 0.0001) {
                    throw new RuntimeException(sprintf(
                        'Stamp tidak cukup. Butuh %s stamp, saldo kamu %s stamp.',
                        number_format($cost, 0, ',', '.'),
                        number_format($stamp_bal, 0, ',', '.')
                    ));
                }
                $stamp_after = round($stamp_bal - $cost, 4);
                $this->db->insert('pos_stamp_ledger', [
                    'member_id'     => $member_id,
                    'order_id'      => null,
                    'payment_id'    => null,
                    'campaign_id'   => $camp_id,
                    'ledger_type'   => 'REDEEM',
                    'stamp_in'      => 0,
                    'stamp_out'     => $cost,
                    'balance_after' => $stamp_after,
                    'notes'         => $ledger_note,
                    'created_at'    => $now,
                ]);
                $stamp_ledger_id = (int) $this->db->insert_id();
                $this->db->where('id', $member_id)->update('crm_member', ['stamp_balance_cache' => $stamp_after]);
                $stamp_bal = $stamp_after;
            }

            // 7. Buat pos_voucher_issue
            $voucher_code = 'VR-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
            $reward_type  = strtoupper($rule['reward_type'] ?? '');
            $expired_at   = !empty($rule['valid_days'])
                ? date('Y-m-d H:i:s', strtotime('+' . (int) $rule['valid_days'] . ' days'))
                : null;

            $voucher_row = [
                'campaign_id'      => null,
                'redeem_rule_id'   => $rule_id,
                'member_id'        => $member_id,
                'voucher_issue_no' => $voucher_code,
                'voucher_code'     => $voucher_code,
                'voucher_status'   => 'OPEN',
                'amount_snapshot'  => 0,
                'percent_snapshot' => 0,
                'min_spend_amount' => $rule['min_spend_amount'] ?? null,
                'issued_at'        => $now,
                'expired_at'       => $expired_at,
                'notes'            => $rule['rule_name'],
                'created_at'       => $now,
            ];

            if ($reward_type === 'VOUCHER' && !empty($rule['voucher_campaign_id'])) {
                $vc = $this->db->query(
                    'SELECT * FROM pos_voucher_campaign WHERE id = ? AND is_active = 1 LIMIT 1',
                    [$rule['voucher_campaign_id']]
                )->row_array();
                if ($vc) {
                    $voucher_row['campaign_id']     = (int) $vc['id'];
                    $voucher_row['amount_snapshot'] = (float) ($vc['discount_value'] ?? 0);
                }
            } elseif ($reward_type === 'DISCOUNT_AMOUNT') {
                $voucher_row['amount_snapshot'] = (float) ($rule['discount_amount'] ?? 0);
            } elseif ($reward_type === 'DISCOUNT_PERCENT') {
                $voucher_row['percent_snapshot'] = (float) ($rule['discount_percent'] ?? 0);
            } elseif (in_array($reward_type, ['PRODUCT', 'FREE_PRODUCT'], true)) {
                $prod_name = '';
                if (!empty($rule['product_id'])) {
                    $prod = $this->db->query(
                        'SELECT product_name FROM mst_product WHERE id = ? LIMIT 1',
                        [$rule['product_id']]
                    )->row_array();
                    $prod_name = $prod['product_name'] ?? '';
                }
                $qty = !empty($rule['product_qty']) ? (float) $rule['product_qty'] : 1;
                $voucher_row['notes'] = 'Gratis: ' . ($prod_name ?: $rule['rule_name'])
                    . ($qty != 1 ? ' x' . rtrim(rtrim(number_format($qty, 2), '0'), '.') : '');
            } elseif ($reward_type === 'MERCHANDISE') {
                $voucher_row['notes'] = 'Merchandise: ' . ($rule['reward_notes'] ?: $rule['rule_name']);
            } else {
                $voucher_row['notes'] = $rule['reward_notes'] ?: $rule['rule_name'];
            }

            // Hanya insert kolom redeem_rule_id jika kolom sudah ada
            if (!$this->db->field_exists('redeem_rule_id', 'pos_voucher_issue')) {
                unset($voucher_row['redeem_rule_id']);
            }
            if (!$this->db->field_exists('min_spend_amount', 'pos_voucher_issue')) {
                unset($voucher_row['min_spend_amount']);
            }

            $this->db->insert('pos_voucher_issue', $voucher_row);
            $voucher_issue_id = (int) $this->db->insert_id();

            // 8. Log ke pos_redeem_transaction
            if ($this->db->table_exists('pos_redeem_transaction')) {
                $redeem_no   = 'RDM-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 6));
                $redeem_type = ($cost_type === 'BOTH' || $cost_type === 'POINT') ? 'POINT' : 'STAMP';

                $tx_row = [
                    'redeem_no'        => $redeem_no,
                    'member_id'        => $member_id,
                    'redeem_type'      => $redeem_type,
                    'point_ledger_id'  => $point_ledger_id,
                    'points_used'      => $point_ledger_id ? (float) ($rule['point_cost'] ?? 0) : null,
                    'stamp_ledger_id'  => $stamp_ledger_id,
                    'stamps_used'      => $stamp_ledger_id ? (float) ($rule['stamp_cost'] ?? 0) : null,
                    'voucher_issue_id' => $voucher_issue_id,
                    'voucher_code'     => $voucher_code,
                    'reward_type'      => 'CUSTOM',
                    'reward_desc'      => $rule['rule_name'],
                    'reward_amount'    => $rule['discount_amount'] ?? $rule['discount_percent'] ?? null,
                    'notes'            => null,
                    'redeemed_by'      => null,
                    'created_at'       => $now,
                ];
                if ($this->db->field_exists('rule_id', 'pos_redeem_transaction')) {
                    $tx_row['rule_id'] = $rule_id;
                }
                $this->db->insert('pos_redeem_transaction', $tx_row);
            }

            // 9. Naikkan redeemed_count
            $this->db->query(
                'UPDATE pos_redeem_rule SET redeemed_count = redeemed_count + 1 WHERE id = ?',
                [$rule_id]
            );

            if ($this->db->trans_status() === false) {
                $err = $this->db->error();
                throw new RuntimeException($err['message'] ?: 'Gagal memproses redeem.');
            }

            $this->db->trans_commit();
            $this->db->db_debug = $prev_debug;

            // Bangun deskripsi voucher
            $desc = $voucher_row['notes'];
            if ($reward_type === 'DISCOUNT_AMOUNT' && ($voucher_row['amount_snapshot'] ?? 0) > 0) {
                $desc = 'Diskon Rp ' . number_format($voucher_row['amount_snapshot'], 0, ',', '.');
                if (!empty($rule['min_spend_amount'])) {
                    $desc .= ' (min. belanja Rp ' . number_format((float) $rule['min_spend_amount'], 0, ',', '.') . ')';
                }
            } elseif ($reward_type === 'DISCOUNT_PERCENT' && ($voucher_row['percent_snapshot'] ?? 0) > 0) {
                $desc = 'Diskon ' . rtrim(rtrim(number_format($voucher_row['percent_snapshot'], 2), '0'), '.') . '%';
                if (!empty($rule['min_spend_amount'])) {
                    $desc .= ' (min. belanja Rp ' . number_format((float) $rule['min_spend_amount'], 0, ',', '.') . ')';
                }
            }

            return [
                'ok'           => true,
                'voucher_code' => $voucher_code,
                'voucher_desc' => $desc,
                'message'      => 'Redeem berhasil! Kode voucher: ' . $voucher_code,
            ];

        } catch (Throwable $e) {
            $this->db->trans_rollback();
            $this->db->db_debug = $prev_debug;
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}
