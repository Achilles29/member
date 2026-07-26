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

    private function generate_account_mutation_no($dateTime = null)
    {
        $dateTime = $dateTime ? strtotime((string) $dateTime) : time();
        if ($dateTime === false) {
            $dateTime = time();
        }

        do {
            $no = 'MUT-' . date('YmdHis', $dateTime) . '-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 4));
        } while ($this->db->where('mutation_no', $no)->count_all_results('fin_account_mutation_log') > 0);

        return $no;
    }

    private function nullable_text($value)
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        return $text === '' ? null : $text;
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

    private function find_self_order_table_context($table_id = 0, $table_no = null)
    {
        $table_id = (int) $table_id;
        $table_no = trim((string) $table_no);
        if (!$this->db->table_exists('pos_self_order_table')) {
            return [];
        }

        $db = $this->db->from('pos_self_order_table');
        if ($table_id > 0) {
            $db->where('id', $table_id);
        } elseif ($table_no !== '') {
            $db->where('nama_meja', $table_no);
        } else {
            return [];
        }

        return $db->order_by('id', 'DESC')->limit(1)->get()->row_array() ?: [];
    }

    private function find_latest_open_cashier_session($preferred_outlet_id = 0, $preferred_terminal_id = 0)
    {
        if (!$this->db->table_exists('pos_cashier_session')) {
            return [];
        }

        $preferred_outlet_id = (int) $preferred_outlet_id;
        $preferred_terminal_id = (int) $preferred_terminal_id;
        $attempts = [];
        if ($preferred_terminal_id > 0) {
            $attempts[] = ['outlet_id' => $preferred_outlet_id, 'terminal_id' => $preferred_terminal_id];
            $attempts[] = ['outlet_id' => 0, 'terminal_id' => $preferred_terminal_id];
        }
        if ($preferred_outlet_id > 0) {
            $attempts[] = ['outlet_id' => $preferred_outlet_id, 'terminal_id' => 0];
        }
        $attempts[] = ['outlet_id' => 0, 'terminal_id' => 0];

        foreach ($attempts as $attempt) {
            $db = $this->db
                ->select('s.id, s.outlet_id, s.terminal_id, s.shift_id, s.employee_id, s.session_status, s.login_at, s.last_ping_at')
                ->from('pos_cashier_session s')
                ->where('s.session_status', 'OPEN');
            if ((int) $attempt['terminal_id'] > 0) {
                $db->where('s.terminal_id', (int) $attempt['terminal_id']);
            }
            if ((int) $attempt['outlet_id'] > 0) {
                $db->where('s.outlet_id', (int) $attempt['outlet_id']);
            }

            $row = $db
                ->order_by('COALESCE(s.last_ping_at, s.login_at, s.created_at)', 'DESC', false)
                ->order_by('s.id', 'DESC')
                ->limit(1)
                ->get()
                ->row_array();

            if (!empty($row)) {
                return $row;
            }
        }

        return [];
    }

    private function resolve_self_order_context($table_id = 0, $table_no = null)
    {
        $table = $this->find_self_order_table_context($table_id, $table_no);
        $preferred_outlet_id = 0;
        $preferred_terminal_id = 0;

        if (!empty($table)) {
            if (array_key_exists('outlet_id', $table)) {
                $preferred_outlet_id = (int) ($table['outlet_id'] ?? 0);
            }
            if (array_key_exists('terminal_id', $table)) {
                $preferred_terminal_id = (int) ($table['terminal_id'] ?? 0);
            }
        }

        $session = $this->find_latest_open_cashier_session($preferred_outlet_id, $preferred_terminal_id);
        $outlet_id = !empty($session['outlet_id'])
            ? (int) $session['outlet_id']
            : ($preferred_outlet_id > 0 ? $preferred_outlet_id : $this->resolve_default_outlet_id());
        $cashier_employee_id = !empty($session['employee_id'])
            ? (int) $session['employee_id']
            : $this->resolve_default_cashier_employee_id();

        return [
            'outlet_id' => $outlet_id,
            'terminal_id' => !empty($session['terminal_id']) ? (int) $session['terminal_id'] : ($preferred_terminal_id > 0 ? $preferred_terminal_id : null),
            'shift_id' => !empty($session['shift_id']) ? (int) $session['shift_id'] : null,
            'cashier_session_id' => !empty($session['id']) ? (int) $session['id'] : null,
            'cashier_employee_id' => $cashier_employee_id,
        ];
    }

    public function resolve_current_self_order_context($table_id = 0, $table_no = null)
    {
        return $this->resolve_self_order_context($table_id, $table_no);
    }

    private function get_payment_method_row($payment_method_id)
    {
        $payment_method_id = (int) $payment_method_id;
        if ($payment_method_id <= 0) {
            return null;
        }

        return $this->db
            ->from($this->table_payment_method)
            ->where('id', $payment_method_id)
            ->limit(1)
            ->get()
            ->row_array();
    }

    private function resolve_self_order_qris_payment_method_id()
    {
        $qris_tables = [];
        if ($this->db->table_exists('pos_self_order_qris_setting')) {
            $qris_tables[] = 'pos_self_order_qris_setting';
        }
        if ($this->db->table_exists('pr_qris_setting')) {
            $qris_tables[] = 'pr_qris_setting';
        }

        foreach ($qris_tables as $table) {
            if (!$this->db->field_exists('payment_method_id', $table)) {
                continue;
            }

            $method_id = (int) $this->db
                ->select('payment_method_id')
                ->from($table)
                ->where('id', 1)
                ->limit(1)
                ->get()
                ->row('payment_method_id');

            if ($method_id <= 0) {
                continue;
            }

            $method = $this->get_payment_method_row($method_id);
            if ($method && (int) ($method['is_active'] ?? 0) === 1 && (int) ($method['company_account_id'] ?? 0) > 0) {
                return $method_id;
            }
        }

        return 0;
    }

    private function resolve_payment_method_id($payment_method)
    {
        $payment_method = strtoupper(trim((string) $payment_method));

        if ($payment_method === 'QRIS') {
            $configured_method_id = $this->resolve_self_order_qris_payment_method_id();
            if ($configured_method_id > 0) {
                return $configured_method_id;
            }

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

    private function is_terminal_rejected_order_status($status)
    {
        $status = strtoupper(trim((string) $status));
        return in_array($status, ['REJECTED', 'CANCELLED', 'CANCEL', 'VOID'], true);
    }

    private function extract_rejection_reason($note)
    {
        $note = trim((string) $note);
        if ($note === '') {
            return '';
        }
        $marker = 'Alasan penolakan:';
        $pos = strpos($note, $marker);
        if ($pos === false) {
            return $note;
        }
        return trim(substr($note, $pos + strlen($marker)));
    }

    private function get_rejection_reason($order_id)
    {
        $order_id = (int) $order_id;
        if ($order_id <= 0 || !$this->db->table_exists('pos_order_state_log')) {
            return '';
        }

        $note = (string) $this->db
            ->select('notes')
            ->from('pos_order_state_log')
            ->where('order_id', $order_id)
            ->where('event_code', 'SELF_ORDER_REJECT')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()
            ->row('notes');

        return $this->extract_rejection_reason($note);
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

    private function find_account_mutation_for_payment_line($payment_line_id)
    {
        $payment_line_id = (int) $payment_line_id;
        if ($payment_line_id <= 0 || !$this->db->table_exists('fin_account_mutation_log')) {
            return [];
        }

        return $this->db
            ->from('fin_account_mutation_log')
            ->where('ref_module', 'POS')
            ->where('ref_table', 'pos_payment_line')
            ->where('ref_id', $payment_line_id)
            ->where('mutation_type', 'IN')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()
            ->row_array() ?: [];
    }

    private function post_company_account_mutation(array $payload)
    {
        $accountId = (int) ($payload['account_id'] ?? 0);
        $mutationType = strtoupper(trim((string) ($payload['mutation_type'] ?? 'IN')));
        $amount = round((float) ($payload['amount'] ?? 0), 2);
        $mutationDate = (string) ($payload['mutation_date'] ?? $this->now());
        if ($accountId <= 0 || $amount <= 0) {
            return ['ok' => false, 'message' => 'Payload mutasi rekening self order tidak valid.'];
        }
        if (!in_array($mutationType, ['IN', 'OUT'], true)) {
            return ['ok' => false, 'message' => 'Jenis mutasi rekening self order tidak valid.'];
        }

        $account = $this->db->query(
            'SELECT * FROM fin_company_account WHERE id = ? LIMIT 1 FOR UPDATE',
            [$accountId]
        )->row_array();
        if (!$account) {
            return ['ok' => false, 'message' => 'Rekening perusahaan tidak ditemukan saat posting pembayaran self order.'];
        }

        $balanceBefore = round((float) ($account['current_balance'] ?? 0), 2);
        $balanceAfter = $mutationType === 'IN'
            ? round($balanceBefore + $amount, 2)
            : round($balanceBefore - $amount, 2);

        $this->db->where('id', $accountId)->update('fin_company_account', [
            'current_balance' => $balanceAfter,
        ]);
        $this->db->insert('fin_account_mutation_log', [
            'mutation_no' => $this->generate_account_mutation_no($mutationDate),
            'mutation_date' => date('Y-m-d', strtotime($mutationDate)),
            'account_id' => $accountId,
            'mutation_type' => $mutationType,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'ref_module' => (string) ($payload['ref_module'] ?? 'POS'),
            'ref_table' => (string) ($payload['ref_table'] ?? 'pos_payment_line'),
            'ref_id' => !empty($payload['ref_id']) ? (int) $payload['ref_id'] : null,
            'ref_no' => $this->nullable_text($payload['ref_no'] ?? null),
            'notes' => $this->nullable_text($payload['notes'] ?? null),
            'created_by' => !empty($payload['created_by']) ? (int) $payload['created_by'] : null,
        ]);

        if ($this->db->trans_status() === false) {
            return ['ok' => false, 'message' => 'Gagal menyimpan mutasi rekening self order.'];
        }

        return [
            'ok' => true,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
        ];
    }

    private function sync_company_account_mutation_for_payment($order, $payment, $payment_line, $payment_method_row)
    {
        if (empty($payment) || empty($payment_line)) {
            return ['ok' => false, 'message' => 'Data payment self order belum lengkap.'];
        }

        if (strtoupper((string) ($payment['payment_status'] ?? '')) !== 'PAID' || strtoupper((string) ($payment_line['status'] ?? '')) !== 'PAID') {
            return ['ok' => true, 'skipped' => 'status_not_paid'];
        }

        if (
            !$this->db->table_exists('fin_company_account')
            || !$this->db->table_exists('fin_account_mutation_log')
        ) {
            return ['ok' => false, 'message' => 'Schema rekening perusahaan belum tersedia untuk self order.'];
        }

        $paymentLineId = (int) ($payment_line['id'] ?? 0);
        if ($paymentLineId <= 0) {
            return ['ok' => false, 'message' => 'Payment line self order tidak valid.'];
        }

        $existingMutation = $this->find_account_mutation_for_payment_line($paymentLineId);
        if (!empty($existingMutation)) {
            return ['ok' => true, 'skipped' => 'already_posted'];
        }

        $companyAccountId = 0;
        if ($this->db->field_exists('company_account_id', $this->table_payment_line)) {
            $companyAccountId = (int) ($payment_line['company_account_id'] ?? 0);
        }
        if ($companyAccountId <= 0) {
            $companyAccountId = (int) ($payment_method_row['company_account_id'] ?? 0);
        }
        if ($companyAccountId <= 0) {
            return ['ok' => false, 'message' => 'Metode pembayaran self order belum terhubung ke rekening perusahaan.'];
        }

        $paidAt = $payment['paid_at'] ?? $payment_line['received_at'] ?? $this->now();
        $amount = round((float) ($payment_line['amount'] ?? $payment['net_amount'] ?? $order['grand_total'] ?? 0), 2);
        if ($amount <= 0) {
            return ['ok' => false, 'message' => 'Nominal payment self order tidak valid untuk diposting ke rekening.'];
        }

        return $this->post_company_account_mutation([
            'account_id' => $companyAccountId,
            'mutation_type' => 'IN',
            'amount' => $amount,
            'mutation_date' => $paidAt,
            'ref_module' => 'POS',
            'ref_table' => 'pos_payment_line',
            'ref_id' => $paymentLineId,
            'ref_no' => (string) ($payment['payment_no'] ?? ''),
            'notes' => 'Pembayaran self order via ' . (string) ($payment_method_row['method_name'] ?? 'metode pembayaran'),
            'created_by' => !empty($order['cashier_employee_id']) ? (int) $order['cashier_employee_id'] : null,
        ]);
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
        $payment_method_row = $this->get_payment_method_row($payment_method_id);

        $payment_payload = [
            'shift_id' => !empty($order['shift_id']) ? (int) ($order['shift_id'] ?? 0) : null,
            'cashier_session_id' => !empty($order['cashier_session_id']) ? (int) ($order['cashier_session_id'] ?? 0) : null,
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
            $payment['payment_no'] = $payment['payment_no'] ?? null;
        } else {
            $payment_payload['payment_no'] = $this->generate_payment_no();
            $payment_payload['order_id'] = $order_id;
            $this->db->insert($this->table_payment, $payment_payload);
            $payment_id = (int) $this->db->insert_id();
            $payment['payment_no'] = $payment_payload['payment_no'];
        }

        if ($payment_id <= 0 || $payment_method_id <= 0 || empty($payment_method_row)) {
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
        if ($this->db->field_exists('company_account_id', $this->table_payment_line)) {
            $company_account_id = (int) ($payment_method_row['company_account_id'] ?? 0);
            $line_payload['company_account_id'] = $company_account_id > 0 ? $company_account_id : null;
        }

        if ($payment_line) {
            $this->db->where('id', (int) $payment_line['id'])->update($this->table_payment_line, $line_payload);
            $payment_line = array_merge($payment_line, $line_payload);
        } else {
            $line_payload['payment_id'] = $payment_id;
            $line_payload['line_no'] = 1;
            $this->db->insert($this->table_payment_line, $line_payload);
            $payment_line = array_merge($line_payload, ['id' => (int) $this->db->insert_id()]);
        }

        $payment = array_merge($payment ?: [], $payment_payload, [
            'id' => $payment_id,
            'order_id' => $order_id,
            'payment_no' => $payment['payment_no'] ?? ($payment_payload['payment_no'] ?? null),
        ]);
        $payment_line = array_merge($payment_line ?: [], $line_payload, [
            'payment_id' => $payment_id,
        ]);

        $financeResult = $this->sync_company_account_mutation_for_payment($order, $payment, $payment_line, $payment_method_row);
        if (!($financeResult['ok'] ?? false)) {
            return false;
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
            'rejection_reason' => $this->get_rejection_reason((int) ($order['id'] ?? 0)),
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
        $payment_paid_at = null,
        $table_id = 0,
        $order_channel = 'SELF_ORDER',
        $service_type = null
    ) {
        $now = $this->now();
        $grand_total = round((float) $total_penjualan, 2);
        $payment_method = strtoupper(trim((string) $payment_method));
        if (!in_array($payment_method, ['KASIR', 'QRIS'], true)) {
            $payment_method = 'KASIR';
        }
        $order_channel = strtoupper(trim((string) $order_channel));
        if (!in_array($order_channel, ['CASHIER', 'SELF_ORDER', 'RESERVATION', 'DELIVERY'], true)) {
            $order_channel = 'SELF_ORDER';
        }
        $service_type = strtoupper(trim((string) $service_type));
        if (!in_array($service_type, ['DINE_IN', 'TAKE_AWAY', 'DELIVERY', 'PICKUP'], true)) {
            $service_type = $nomor_meja ? 'DINE_IN' : 'TAKE_AWAY';
        }
        $context = $this->resolve_self_order_context($table_id, $nomor_meja);

        $data = [
            'order_no' => $this->generate_order_no(),
            'order_channel' => $order_channel,
            'order_scope' => 'REGULAR',
            'service_type' => $service_type,
            'outlet_id' => (int) ($context['outlet_id'] ?? $this->resolve_default_outlet_id()),
            'terminal_id' => !empty($context['terminal_id']) ? (int) $context['terminal_id'] : null,
            'shift_id' => !empty($context['shift_id']) ? (int) $context['shift_id'] : null,
            'cashier_session_id' => !empty($context['cashier_session_id']) ? (int) $context['cashier_session_id'] : null,
            'cashier_employee_id' => (int) ($context['cashier_employee_id'] ?? $this->resolve_default_cashier_employee_id()),
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
            'table_no' => $nomor_meja ? trim((string) $nomor_meja) : null,
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

        if ($this->is_terminal_rejected_order_status($order['status'] ?? '')) {
            return true;
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
