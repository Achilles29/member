<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Pending_order_detail_model extends CI_Model {
    private function map_availability_mode($stock_mode)
    {
        $stock_mode = strtoupper(trim((string) $stock_mode));
        if ($stock_mode === 'MANUAL_AVAILABLE') {
            return 'FORCE_AVAILABLE';
        }
        if ($stock_mode === 'MANUAL_OUT') {
            return 'FORCE_OUT';
        }

        return 'AUTO';
    }

    public function insert_detail($order_id, $produk_id, $jumlah) {
        $produk = $this->db->get_where('mst_product', ['id' => (int) $produk_id])->row();
        if (!$produk) {
            return 0;
        }

        $order = $this->db->get_where('pos_order', ['id' => (int) $order_id])->row();
        if (!$order) {
            return 0;
        }

        $jumlah = (int) $jumlah;
        if ($jumlah <= 0) {
            return 0;
        }

        $harga = (float) ($produk->selling_price ?? 0);
        $hpp_snapshot = $produk->hpp_live_cache !== null
            ? (float) $produk->hpp_live_cache
            : (float) ($produk->hpp_standard ?? 0);
        $line_no = (int) $this->db
            ->select('COALESCE(MAX(line_no), 0) AS max_line_no', false)
            ->from('pos_order_line')
            ->where('order_id', (int) $order_id)
            ->get()
            ->row('max_line_no') + 1;

        $data = [
            'order_id' => (int) $order_id,
            'line_no' => $line_no,
            'product_id' => (int) $produk_id,
            'line_type' => 'PRODUCT',
            'product_division_id_snapshot' => !empty($produk->product_division_id) ? (int) $produk->product_division_id : null,
            'operational_division_id' => !empty($produk->default_operational_division_id) ? (int) $produk->default_operational_division_id : null,
            'uom_id' => !empty($produk->uom_id) ? (int) $produk->uom_id : null,
            'qty' => $jumlah,
            'unit_price' => $harga,
            'discount_amount' => 0,
            'net_amount' => $jumlah * $harga,
            'hpp_standard_snapshot' => (float) ($produk->hpp_standard ?? 0),
            'hpp_live_snapshot' => $hpp_snapshot,
            'cogs_amount' => $jumlah * $hpp_snapshot,
            'availability_mode_snapshot' => $this->map_availability_mode($produk->stock_mode ?? 'AUTO'),
            'line_status' => 'OPEN',
            'process_status' => 'NOT_PROCESSED',
            'notes' => null,
        ];

        $this->db->insert('pos_order_line', $data);
        return (int) $this->db->insert_id();
    }
}
