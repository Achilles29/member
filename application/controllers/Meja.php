<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Meja extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->database();
    }

    /**
     * Sumber secret:
     * - DB table `pr_table_qr_secret` row id=1 (pengaturan dari admin finance)
     * - ENV `TABLE_QR_SECRET` sebagai fallback legacy
     *
     * Return: ['secret' => string, 'enforce' => 0/1] atau null kalau belum ada.
     */
    private function qr_secret_info()
    {
        if ($this->db->table_exists('pr_table_qr_secret')) {
            $row = $this->db->get_where('pr_table_qr_secret', ['id' => 1])->row_array();
            $dbSecret = trim((string) ($row['secret'] ?? ''));
            if ($dbSecret !== '') {
                return [
                    'secret' => $dbSecret,
                    'enforce' => (int) ($row['enforce'] ?? 0),
                ];
            }
        }

        $secret = trim((string) getenv('TABLE_QR_SECRET'));
        if ($secret === '') {
            return null;
        }

        return [
            'secret' => $secret,
            'enforce' => 1,
        ];
    }

    private function self_order_is_enabled()
    {
        if ($this->db->table_exists('pos_self_order_setting')) {
            $row = $this->db->get_where('pos_self_order_setting', ['id' => 1])->row_array();
            if ($row) {
                return ((int) ($row['is_enabled'] ?? 1)) === 1;
            }
        }

        return true;
    }

    private function expected_sig($meja_id, $secret)
    {
        return hash_hmac('sha256', (string) $meja_id, (string) $secret);
    }

    public function index($meja_id = null, $sig = null)
    {
        if (!$this->self_order_is_enabled()) {
            show_error('Order mandiri sedang dinonaktifkan sementara.', 503);
            return;
        }

        if (!$this->db->table_exists('pr_meja')) {
            show_error('Fitur QR meja belum tersedia pada schema db_finance saat ini.', 503);
            return;
        }

        $meja_id = (int) $meja_id;
        if ($meja_id <= 0) {
            show_error('QR meja tidak valid.', 400);
            return;
        }

        $secretInfo = $this->qr_secret_info();
        if ($secretInfo !== null) {
            $expected = $this->expected_sig($meja_id, $secretInfo['secret']);
            $sig = (string) $sig;

            // Mode transisi: kalau enforce=0, QR tanpa signature masih boleh masuk.
            if ((int) $secretInfo['enforce'] === 1) {
                if ($sig === '' || !hash_equals($expected, $sig)) {
                    show_error('QR meja tidak valid (signature salah).', 403);
                    return;
                }
            } else {
                if ($sig !== '' && !hash_equals($expected, $sig)) {
                    show_error('QR meja tidak valid (signature salah).', 403);
                    return;
                }
            }
        }

        $meja = $this->db->get_where('pr_meja', ['id' => $meja_id])->row_array();
        if (!$meja) {
            show_error('Meja tidak ditemukan.', 404);
            return;
        }
        if (array_key_exists('is_active', $meja) && (int) ($meja['is_active'] ?? 0) !== 1) {
            show_error('Meja ini sedang dinonaktifkan untuk self order.', 403);
            return;
        }

        // Simpan konteks meja untuk order mandiri.
        $this->session->set_userdata('order_meja_id', $meja_id);
        $this->session->set_userdata('order_nomor_meja', $meja['nama_meja']);

        // Kalau sudah login, langsung ke flow resume (menu/review/pay).
        // Kalau belum login, masuk ke halaman start (input HP -> cek member / daftar cepat) dulu.
        if ($this->session->userdata('member_id')) {
            redirect('order/resume');
            return;
        }

        redirect('start?redirect_to=' . urlencode('order/resume'));
    }
}
