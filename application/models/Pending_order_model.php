<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pending_order_model extends CI_Model {
    private $table_order = 'pos_order';
    private $table_payment = 'pos_payment';
    private $table_payment_line = 'pos_payment_line';
    private $table_payment_method = 'pos_payment_method';

    private function now()
    {
        return date('Y-m-d H:i:s');
    }

    private function generate_order_no()
    {
        return 'MSO-' . date('YmdHis') . '-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 4));
    }

    private function generate_payment_no()
    {
        return 'MSP-' . date('YmdHis') . '-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 4));
    }

    private function resolve_default_outlet_id()
    {
        $outlet_id = (int) $this->db
            ->select('id')
            ->from('pos_outlet')
            ->where('is_active', 1)
            ->order_by('id', 'ASC')
            ->limit(1)
            ->get()
            ->row('id');

        if ($outlet_id <= 0) {
            throw new RuntimeException('Outlet POS aktif belum tersedia di db_finance.');
        }

        return $outlet_id;
    }

    private function resolve_default_cashier_employee_id()
    {
        $employee_id = (int) $this->db
            ->select('employee_id')
            ->from('auth_user')
            ->where('is_active', 1)
            ->where('employee_id IS NOT NULL', null, false)
            ->order_by('id', 'ASC')
            ->limit(1)
            ->get()
            ->row('employee_id');

        if ($employee_id <= 0) {
            throw new RuntimeException('User POS dengan employee_id aktif belum tersedia di db_finance.');
        }

        return $employee_id;
    }

    private function resolve_payment_method_id($payment_method)
    {
        $payment_method = strtoupper(trim((string) $payment_method));

        if ($payment_method === 'QRIS') {
            $method_id = (int) $this->db
                ->select('id')
                ->from($this->table_payment_method)
                ->where('is_active', 1)
                ->where('method_type', 'QRIS')
                ->order_by('sort_order', 'ASC')
                ->order_by('id', 'ASC')
                ->limit(1)
                ->get()
                ->row('id');

            if ($method_id > 0) {
                return $method_id;
            }
        }

        return (int) $this->db
            ->select('id')
            ->from($this->table_payment_method)
            ->where('is_active', 1)
            ->where('method_type', 'CASH')
            ->order_by('sort_order', 'ASC')
            ->order_by('id', 'ASC')
            ->limit(1)
            ->get()
            ->row('id');
    }

    private function map_payment_status($status)
    {
        $status = strtoupper(trim((string) $status));

        if ($status === 'PAID') {
            return 'PAID';
        }

        if (in_array($status, ['FAILED', 'EXPIRED'], true)) {
            return 'FAILED';
        }

        return 'PENDING';
    }

    private function map_order_status($status)
    {
        $status = strtoupper(trim((string) $status));
        if ($status === 'PAID') {
            return 'PAID';
        }

        return 'PENDING';
    }

    private function decode_payment_meta($notes)
    {
        $notes = trim((string) $notes);
        if ($notes === '') {
            return [];
        }

        $decoded = json_decode($notes, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function encode_payment_meta(array $fields, array $existing = [])
    {
        $meta = array_merge($existing, array_filter([
            'payment_method' => isset($fields['payment_method']) ? strtoupper(trim((string) $fields['payment_method'])) : null,
            'payment_provider' => isset($fields['payment_provider']) ? trim((string) $fields['payment_provider']) : null,
            'payment_ref' => isset($fields['payment_ref']) ? trim((string) $fields['payment_ref']) : null,
            'payment_status_label' => isset($fields['payment_status']) ? strtoupper(trim((string) $fields['payment_status'])) : null,
            'payment_qr_url' => isset($fields['payment_qr_url']) ? trim((string) $fields['payment_qr_url']) : null,
        ], static function ($value) {
            return $value !== null && $value !== '';
        }));

        $json = json_encode($meta);
        if ($json !== false && strlen($json) <= 255) {
            return $json;
        }

        unset($meta['payment_qr_url']);
        $json = json_encode($meta);
        return $json !== false ? substr($json, 0, 255) : null;
    }

    private function get_payment_row($order_id)
    {
        return $this->db
            ->from($this->table_payment)
            ->where('order_id', (int) $order_id)
            ->where('payment_type', 'FINAL')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();
    }

    private function get_payment_line_row($payment_id)
    {
        return $this->db
            ->from($this->table_payment_line)
            ->where('payment_id', (int) $payment_id)
            ->order_by('line_no', 'ASC')
            ->limit(1)
            ->get()
            ->row_array();
    }

    private function ensure_payment_record($order, array $fields)
    {
        $order_id = (int) ($order['id'] ?? 0);
        if ($order_id <= 0) {
            return false;
        }

        $payment = $this->get_payment_row($order_id);
        $meta = $this->decode_payment_meta($payment['notes'] ?? '');
        $payment_method = $fields['payment_method'] ?? ($meta['payment_method'] ?? 'KASIR');
        $payment_status_label = $fields['payment_status'] ?? ($meta['payment_status_label'] ?? 'PENDING');
        $payment_status = $this->map_payment_status($payment_status_label);
        $paid_at = $fields['payment_paid_at'] ?? ($payment['paid_at'] ?? null);
        $notes = $this->encode_payment_meta($fields, $meta);
        $payment_method_id = $this->resolve_payment_method_id($payment_method);

        $payment_payload = [
            'cashier_employee_id' => (int) ($order['cashier_employee_id'] ?? 0),
            'member_id' => (int) ($order['member_id'] ?? 0) ?: null,
            'payment_type' => 'FINAL',
            'payment_status' => $payment_status,
            'paid_at' => $paid_at,
            'gross_amount' => (float) ($order['grand_total'] ?? 0),
            'net_amount' => (float) ($order['grand_total'] ?? 0),
            'change_amount' => 0,
            'notes' => $notes,
        ];

        if ($payment) {
            $this->db->where('id', (int) $payment['id'])->update($this->table_payment, $payment_payload);
            $payment_id = (int) $payment['id'];
        } else {
            $payment_payload['payment_no'] = $this->generate_payment_no();
            $payment_payload['order_id'] = $order_id;
            $this->db->insert($this->table_payment, $payment_payload);
            $payment_id = (int) $this->db->insert_id();
        }

        if ($payment_id <= 0 || $payment_method_id <= 0) {
            return false;
        }

        $payment_line = $this->get_payment_line_row($payment_id);
        $line_payload = [
            'payment_method_id' => $payment_method_id,
            'amount' => (float) ($order['grand_total'] ?? 0),
            'reference_no' => !empty($fields['payment_ref']) ? trim((string) $fields['payment_ref']) : ($payment_line['reference_no'] ?? null),
            'status' => $payment_status,
            'received_at' => $paid_at,
        ];

        if ($payment_line) {
            $this->db->where('id', (int) $payment_line['id'])->update($this->table_payment_line, $line_payload);
        } else {
            $line_payload['payment_id'] = $payment_id;
            $line_payload['line_no'] = 1;
            $this->db->insert($this->table_payment_line, $line_payload);
        }

        return true;
    }

    private function map_order_row($order, $payment, $payment_line)
    {
        if (empty($order)) {
            return null;
        }

        $meta = $this->decode_payment_meta($payment['notes'] ?? '');
        $payment_method = strtoupper((string) ($meta['payment_method'] ?? 'KASIR'));
        if ($payment_method === '') {
            $payment_method = 'KASIR';
        }

        return [
            'id' => (int) ($order['id'] ?? 0),
            'customer_id' => (int) ($order['member_id'] ?? 0),
            'member_id' => (int) ($order['member_id'] ?? 0),
            'status' => $order['status'] ?? 'PENDING',
            'catatan' => $order['notes'] ?? null,
            'total_penjualan' => (float) ($order['grand_total'] ?? 0),
            'payment_method' => $payment_method,
            'payment_status' => strtoupper((string) ($meta['payment_status_label'] ?? ($payment['payment_status'] ?? 'PENDING'))),
            'payment_provider' => $meta['payment_provider'] ?? null,
            'payment_ref' => $payment_line['reference_no'] ?? ($meta['payment_ref'] ?? null),
            'payment_paid_at' => $payment['paid_at'] ?? null,
            'payment_qr_url' => $meta['payment_qr_url'] ?? null,
            'payment_qr_string' => null,
            'order_no' => $order['order_no'] ?? null,
        ];
    }

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
        $now = $this->now();
        $grand_total = round((float) $total_penjualan, 2);
        $payment_method = strtoupper(trim((string) $payment_method));
        if (!in_array($payment_method, ['KASIR', 'QRIS'], true)) {
            $payment_method = 'KASIR';
        }

        $data = [
            'order_no' => $this->generate_order_no(),
            'order_channel' => 'SELF_ORDER',
            'order_scope' => 'REGULAR',
            'service_type' => $nomor_meja ? 'DINE_IN' : 'TAKE_AWAY',
            'outlet_id' => $this->resolve_default_outlet_id(),
            'cashier_employee_id' => $this->resolve_default_cashier_employee_id(),
            'member_id' => (int) $customer_id ?: null,
            'status' => $this->map_order_status($payment_status),
            'kitchen_status' => 'PENDING',
            'stock_commit_status' => 'PENDING',
            'ordered_at' => $now,
            'paid_at' => $payment_status === 'PAID' ? ($payment_paid_at ?: $now) : null,
            'guest_count' => 1,
            'subtotal_amount' => $grand_total,
            'discount_amount' => 0,
            'promo_amount' => 0,
            'voucher_amount' => 0,
            'point_redeem_amount' => 0,
            'compliment_amount' => 0,
            'tax_amount' => 0,
            'service_amount' => 0,
            'rounding_amount' => 0,
            'grand_total' => $grand_total,
            'paid_total' => $payment_status === 'PAID' ? $grand_total : 0,
            'change_total' => 0,
            'notes' => $catatan ? trim((string) $catatan) : null,
        ];

        $this->db->insert($this->table_order, $data);
        $order_id = (int) $this->db->insert_id();
        if ($order_id <= 0) {
            return 0;
        }

        $this->ensure_payment_record(array_merge($data, ['id' => $order_id]), [
            'payment_method' => $payment_method,
            'payment_status' => $payment_status,
            'payment_provider' => $payment_provider,
            'payment_ref' => $payment_ref,
            'payment_paid_at' => $payment_status === 'PAID' ? ($payment_paid_at ?: $now) : null,
        ]);

        return $order_id;
    }

    public function mark_paid($pending_id, $provider = 'DUMMY', $ref = null)
    {
        $pending_id = (int) $pending_id;
        if ($pending_id <= 0) return false;

        return $this->update_payment($pending_id, [
            'payment_status' => 'PAID',
            'payment_provider' => $provider,
            'payment_ref' => $ref,
            'payment_paid_at' => $this->now(),
        ]);
    }

    public function update_payment($pending_id, array $fields)
    {
        $pending_id = (int) $pending_id;
        if ($pending_id <= 0) return false;

        $order = $this->db->get_where($this->table_order, ['id' => $pending_id])->row_array();
        if (!$order) {
            return false;
        }

        $payment_status_label = strtoupper(trim((string) ($fields['payment_status'] ?? 'PENDING')));
        $paid_at = $fields['payment_paid_at'] ?? null;
        $order_payload = [
            'status' => $this->map_order_status($payment_status_label),
            'paid_at' => $payment_status_label === 'PAID' ? ($paid_at ?: $this->now()) : null,
            'paid_total' => $payment_status_label === 'PAID' ? (float) ($order['grand_total'] ?? 0) : 0,
        ];

        $this->db->where('id', $pending_id)->update($this->table_order, $order_payload);
        return $this->ensure_payment_record(array_merge($order, ['id' => $pending_id]), $fields);
    }

    public function get_for_member($pending_id, $member_id)
    {
        $order = $this->db
            ->from($this->table_order)
            ->where('id', (int) $pending_id)
            ->where('member_id', (int) $member_id)
            ->limit(1)
            ->get()
            ->row_array();

        if (!$order) {
            return null;
        }

        $payment = $this->get_payment_row((int) $order['id']);
        $payment_line = !empty($payment['id']) ? $this->get_payment_line_row((int) $payment['id']) : null;

        return $this->map_order_row($order, $payment, $payment_line);
    }

    public function get_by_payment_ref($payment_ref)
    {
        $payment_ref = trim((string) $payment_ref);
        if ($payment_ref === '') {
            return null;
        }

        $payment_line = $this->db
            ->from($this->table_payment_line)
            ->where('reference_no', $payment_ref)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();

        if (!$payment_line) {
            return null;
        }

        $payment = $this->db->get_where($this->table_payment, ['id' => (int) $payment_line['payment_id']])->row_array();
        if (!$payment) {
            return null;
        }

        $order = $this->db->get_where($this->table_order, ['id' => (int) $payment['order_id']])->row_array();
        return $this->map_order_row($order, $payment, $payment_line);
    }
}
