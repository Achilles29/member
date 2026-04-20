<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Transaksi_model extends CI_Model
{
    private function apply_list_filter($customer_id, $month, $search)
    {
        $month = preg_match('/^\d{4}-\d{2}$/', (string)$month) ? $month : date('Y-m');
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));

        $this->db->from('pr_transaksi t');
        $this->db->where('t.customer_id', $customer_id);
        $this->db->where('t.tanggal >=', $start);
        $this->db->where('t.tanggal <=', $end);

        if ($search !== '') {
            $this->db->like('t.no_transaksi', $search);
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
            t.id,
            t.no_transaksi,
            t.tanggal,
            t.waktu_order,
            t.waktu_bayar,
            t.total_penjualan,
            t.total_pembayaran,
            t.status_pembayaran
        ');
        $this->apply_list_filter($customer_id, $month, trim((string)$search));
        $this->db->order_by('t.tanggal', 'DESC');
        $this->db->order_by('t.id', 'DESC');

        if ($limit !== 'semua') {
            $this->db->limit((int)$limit, (int)$offset);
        }

        return $this->db->get()->result_array();
    }

    public function get_by_id_customer($id, $customer_id)
    {
        return $this->db
            ->select("
                t.*,
                (
                    SELECT GROUP_CONCAT(DISTINCT mp.metode_pembayaran ORDER BY mp.metode_pembayaran SEPARATOR ', ')
                    FROM pr_pembayaran pb
                    LEFT JOIN pr_metode_pembayaran mp ON mp.id = pb.metode_id
                    WHERE pb.transaksi_id = t.id
                ) AS metode_pembayaran
            ", false)
            ->from('pr_transaksi t')
            ->where('t.id', (int)$id)
            ->where('t.customer_id', (int)$customer_id)
            ->get()
            ->row_array();
    }

    public function get_detail_items($transaksi_id)
    {
        $items = $this->db
            ->select('
                d.id,
                d.jumlah,
                d.harga,
                d.status,
                d.catatan,
                p.nama_produk
            ')
            ->from('pr_detail_transaksi d')
            ->join('pr_produk p', 'p.id = d.pr_produk_id', 'left')
            ->where('d.pr_transaksi_id', (int)$transaksi_id)
            ->group_start()
                ->where('d.status', 'BERHASIL')
                ->or_where('d.status IS NULL', null, false)
            ->group_end()
            ->order_by('d.id', 'ASC')
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
                e.detail_transaksi_id,
                e.jumlah,
                e.harga,
                pe.nama_extra
            ')
            ->from('pr_detail_extra e')
            ->join('pr_produk_extra pe', 'pe.id = e.pr_produk_extra_id', 'left')
            ->where_in('e.detail_transaksi_id', $detail_ids)
            ->group_start()
                ->where('e.status', 'BERHASIL')
                ->or_where('e.status IS NULL', null, false)
            ->group_end()
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
        return $this->db->order_by('id', 'DESC')->get('pr_struk')->row_array();
    }
}
