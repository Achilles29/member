<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Transaksi_model extends CI_Model
{
    private function apply_list_filter($customer_id, $month, $search)
    {
        $month = preg_match('/^\d{4}-\d{2}$/', (string)$month) ? $month : date('Y-m');
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));

        $this->db->from('pos_order o');
        $this->db->where('o.member_id', $customer_id);
        $this->db->where('DATE(o.ordered_at) >=', $start);
        $this->db->where('DATE(o.ordered_at) <=', $end);

        if ($search !== '') {
            $this->db->like('o.order_no', $search);
        }
    }

    public function get_count_by_customer($customer_id, $month, $search = '')
    {
        $this->apply_list_filter($customer_id, $month, trim((string)$search));
        return $this->db->count_all_results();
    }

    public function get_list_by_customer($customer_id, $month, $search = '', $limit = 10, $offset = 0)
    {
        $this->db->select('
            o.id,
            o.order_no AS no_transaksi,
            DATE(o.ordered_at) AS tanggal,
            o.ordered_at AS waktu_order,
            o.paid_at AS waktu_bayar,
            o.grand_total AS total_penjualan,
            COALESCE(o.paid_total, o.grand_total) AS total_pembayaran,
            o.status AS status_pembayaran
        ');
        $this->apply_list_filter($customer_id, $month, trim((string)$search));
        $this->db->order_by('o.ordered_at', 'DESC');
        $this->db->order_by('o.id', 'DESC');

        if ($limit !== 'semua') {
            $this->db->limit((int)$limit, (int)$offset);
        }

        return $this->db->get()->result_array();
    }

    public function get_by_id_customer($id, $customer_id)
    {
        return $this->db
            ->select("
                o.id,
                o.order_no AS no_transaksi,
                DATE(o.ordered_at) AS tanggal,
                o.ordered_at AS waktu_order,
                o.paid_at AS waktu_bayar,
                o.grand_total AS total_penjualan,
                COALESCE(o.paid_total, o.grand_total) AS total_pembayaran,
                o.status AS status_pembayaran,
                o.notes,
                (
                    SELECT GROUP_CONCAT(DISTINCT pm.method_name ORDER BY pm.method_name SEPARATOR ', ')
                    FROM pos_payment p
                    LEFT JOIN pos_payment_line pl ON pl.payment_id = p.id
                    LEFT JOIN pos_payment_method pm ON pm.id = pl.payment_method_id
                    WHERE p.order_id = o.id
                        AND p.payment_status <> 'VOID'
                ) AS metode_pembayaran
            ", false)
            ->from('pos_order o')
            ->where('o.id', (int)$id)
            ->where('o.member_id', (int)$customer_id)
            ->get()
            ->row_array();
    }

    public function get_detail_items($transaksi_id)
    {
        $items = $this->db
            ->select('
                l.id,
                l.qty AS jumlah,
                l.unit_price AS harga,
                l.line_status AS status,
                l.notes AS catatan,
                p.product_name AS nama_produk
            ')
            ->from('pos_order_line l')
            ->join('mst_product p', 'p.id = l.product_id', 'left')
            ->where('l.order_id', (int)$transaksi_id)
            ->where('l.line_type', 'PRODUCT')
            ->where('l.line_status <>', 'VOID')
            ->order_by('l.line_no', 'ASC')
            ->order_by('l.id', 'ASC')
            ->get()
            ->result_array();

        if (empty($items)) {
            return [];
        }

        $detail_ids = array_map(static function ($row) {
            return (int)$row['id'];
        }, $items);

        $extra_rows = $this->db
            ->select('
                e.order_line_id AS detail_transaksi_id,
                e.qty AS jumlah,
                e.unit_price AS harga,
                mx.extra_name AS nama_extra
            ')
            ->from('pos_order_line_extra e')
            ->join('mst_extra mx', 'mx.id = e.extra_id', 'left')
            ->where_in('e.order_line_id', $detail_ids)
            ->order_by('e.line_no', 'ASC')
            ->order_by('e.id', 'ASC')
            ->get()
            ->result_array();

        $extra_map = [];
        foreach ($extra_rows as $ex) {
            $did = (int)$ex['detail_transaksi_id'];
            if (!isset($extra_map[$did])) {
                $extra_map[$did] = [];
            }
            $extra_map[$did][] = [
                'nama_extra' => $ex['nama_extra'] ?? 'Extra',
                'jumlah' => (int)$ex['jumlah'],
                'harga' => (int)$ex['harga'],
                'subtotal' => (int)$ex['jumlah'] * (int)$ex['harga'],
            ];
        }

        foreach ($items as &$item) {
            $did = (int)$item['id'];
            $item['extras'] = $extra_map[$did] ?? [];
        }
        unset($item);

        return $items;
    }

    public function get_outlet_struk()
    {
        return $this->db
            ->select('outlet_name AS nama_outlet, address AS alamat, phone AS no_telepon, notes AS custom_footer', false)
            ->order_by('id', 'ASC')
            ->get('pos_outlet')
            ->row_array();
    }
}
