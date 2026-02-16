<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pending_order_model extends CI_Model {
    public function create_order(
        $customer_id,
        $nomor_meja = null,
        $catatan = null,
        $total_penjualan = 0,
        $payment_method = 'KASIR',
        $payment_status = 'UNPAID',
        $payment_provider = null,
        $payment_ref = null,
        $payment_paid_at = null
    ) {
        $data = [
            'customer_id' => $customer_id,
            'nomor_meja' => $nomor_meja,
            'catatan' => $catatan,
            'total_penjualan' => $total_penjualan,
            'status' => 'MENUNGGU',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Kolom pembayaran sifatnya opsional (biar deploy aman walau DB belum di-alter).
        if ($this->db->field_exists('payment_method', 'pr_pending_order')) {
            $data['payment_method'] = $payment_method ?: 'KASIR';
        }
        if ($this->db->field_exists('payment_status', 'pr_pending_order')) {
            $data['payment_status'] = $payment_status ?: 'UNPAID';
        }
        if ($this->db->field_exists('payment_provider', 'pr_pending_order')) {
            $data['payment_provider'] = $payment_provider;
        }
        if ($this->db->field_exists('payment_ref', 'pr_pending_order')) {
            $data['payment_ref'] = $payment_ref;
        }
        if ($this->db->field_exists('payment_paid_at', 'pr_pending_order')) {
            $data['payment_paid_at'] = $payment_paid_at;
        }

        $this->db->insert('pr_pending_order', $data);
        return $this->db->insert_id();
    }

    public function mark_paid($pending_id, $provider = 'DUMMY', $ref = null)
    {
        $pending_id = (int) $pending_id;
        if ($pending_id <= 0) return false;

        $data = [
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->db->field_exists('payment_status', 'pr_pending_order')) {
            $data['payment_status'] = 'PAID';
        }
        if ($this->db->field_exists('payment_provider', 'pr_pending_order')) {
            $data['payment_provider'] = $provider;
        }
        if ($this->db->field_exists('payment_ref', 'pr_pending_order')) {
            $data['payment_ref'] = $ref;
        }
        if ($this->db->field_exists('payment_paid_at', 'pr_pending_order')) {
            $data['payment_paid_at'] = date('Y-m-d H:i:s');
        }

        return $this->db->where('id', $pending_id)->update('pr_pending_order', $data);
    }
}
