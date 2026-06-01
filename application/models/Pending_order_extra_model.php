<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pending_order_extra_model extends CI_Model
{
    public function insert_extra($pending_order_detail_id, $extra_id, $jumlah, $harga)
    {
        $jumlah = (int) $jumlah;
        if ($jumlah <= 0) $jumlah = 1;

        $order_line = $this->db->get_where('pos_order_line', ['id' => (int) $pending_order_detail_id])->row();
        if (!$order_line) {
            return false;
        }

        $extra = $this->db->get_where('mst_extra', ['id' => (int) $extra_id])->row();
        $line_no = (int) $this->db
            ->select('COALESCE(MAX(line_no), 0) AS max_line_no', false)
            ->from('pos_order_line_extra')
            ->where('order_line_id', (int) $pending_order_detail_id)
            ->get()
            ->row('max_line_no') + 1;

        $data = [
            'order_id' => (int) $order_line->order_id,
            'order_line_id' => (int) $pending_order_detail_id,
            'line_no' => $line_no,
            'extra_id' => (int) $extra_id,
            'qty' => $jumlah,
            'unit_price' => (float) $harga,
            'net_amount' => (float) $harga * $jumlah,
            'cost_amount_snapshot' => (float) ($extra->cost_amount ?? 0),
            'notes' => null,
        ];

        return $this->db->insert('pos_order_line_extra', $data);
    }
}

