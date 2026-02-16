<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Start extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Member_model', 'Customer_model']);
        $this->load->helper('url');
    }

    private function safe_redirect_to($redirect_to)
    {
        $redirect_to = trim((string) $redirect_to);
        if ($redirect_to === '') return null;

        // Hanya izinkan redirect internal (relative path atau absolute yang masih satu base_url).
        if (strpos($redirect_to, 'http://') === 0 || strpos($redirect_to, 'https://') === 0) {
            $base = rtrim(base_url(), '/');
            if (strpos($redirect_to, $base) !== 0) {
                return null;
            }
            $redirect_to = substr($redirect_to, strlen($base));
            if ($redirect_to === '') $redirect_to = '/';
        }

        if ($redirect_to[0] !== '/') {
            $redirect_to = '/' . $redirect_to;
        }

        // Hindari loop ke logout atau url aneh.
        if (strpos($redirect_to, '/logout') !== false) {
            return null;
        }

        return ltrim($redirect_to, '/');
    }

    private function require_meja_context()
    {
        $meja_id = (int) ($this->session->userdata('order_meja_id') ?? 0);
        if ($meja_id <= 0) {
            show_error('Belum ada meja. Scan QR meja dulu ya.', 400);
            return false;
        }
        return true;
    }

    private function phone_candidates($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') return [];

        $digits = preg_replace('/[^\d]/', '', $raw);
        if ($digits === '') return [];

        $cands = [];

        // apa adanya (digits only)
        $cands[] = $digits;

        // format umum Indonesia: 62xxxxxxxxx -> 0xxxxxxxxx
        if (strpos($digits, '62') === 0) {
            $cands[] = '0' . substr($digits, 2);
        }

        // kalau user ngetik 8xxxx, coba jadikan 08xxxx
        if (strpos($digits, '8') === 0) {
            $cands[] = '0' . $digits;
        }

        // Unique + filter panjang yang terlalu pendek.
        $out = [];
        foreach ($cands as $v) {
            $v = trim($v);
            if ($v === '') continue;
            if (strlen($v) < 8) continue;
            $out[$v] = true;
        }
        return array_keys($out);
    }

    public function index()
    {
        if (!$this->require_meja_context()) return;

        // Sudah login: jangan ganggu, langsung resume.
        if ($this->session->userdata('member_id')) {
            redirect('order/resume');
            return;
        }

        $data = [
            'redirect_to' => $this->input->get('redirect_to', true),
            'nomor_meja' => $this->session->userdata('order_nomor_meja'),
        ];
        $this->load->view('auth/start_phone', $data);
    }

    public function check_phone()
    {
        if (!$this->require_meja_context()) return;

        $redirect_to = $this->safe_redirect_to($this->input->post('redirect_to'));
        $telepon_raw = (string) $this->input->post('telepon');
        $cands = $this->phone_candidates($telepon_raw);

        if (empty($cands)) {
            $this->session->set_flashdata('error', 'Nomor HP tidak valid.');
            if ($redirect_to) {
                redirect('start?redirect_to=' . urlencode($redirect_to));
                return;
            }
            redirect('start');
            return;
        }

        $member = null;
        $telepon_norm = $cands[0];
        foreach ($cands as $tel) {
            $row = $this->Member_model->get_by_phone($tel);
            if ($row) {
                $member = $row;
                $telepon_norm = $tel;
                break;
            }
        }

        if ($member) {
            $this->session->set_userdata('member_id', (int) $member['id']);
            redirect($redirect_to ? $redirect_to : 'order/resume');
            return;
        }

        // Bukan member: lanjut ke daftar cepat (nama + no HP).
        $data = [
            'redirect_to' => $redirect_to,
            'telepon' => $telepon_norm,
            'nomor_meja' => $this->session->userdata('order_nomor_meja'),
        ];
        $this->load->view('auth/start_register', $data);
    }

    public function register()
    {
        if (!$this->require_meja_context()) return;

        $redirect_to = $this->safe_redirect_to($this->input->post('redirect_to'));
        $telepon_raw = (string) $this->input->post('telepon');
        $nama = trim((string) $this->input->post('nama'));

        $cands = $this->phone_candidates($telepon_raw);
        $telepon = $cands[0] ?? '';

        if ($telepon === '' || $nama === '') {
            $this->session->set_flashdata('error', 'Nama dan nomor HP wajib diisi.');
            if ($redirect_to) {
                redirect('start?redirect_to=' . urlencode($redirect_to));
                return;
            }
            redirect('start');
            return;
        }

        // Kalau ternyata sudah terdaftar, langsung login.
        $existing = $this->Customer_model->get_customer_by_telepon($telepon);
        if ($existing) {
            $this->session->set_userdata('member_id', (int) $existing['id']);
            redirect($redirect_to ? $redirect_to : 'order/resume');
            return;
        }

        // Generate kode pelanggan otomatis (YYYYMMDDxxxx) seperti flow register existing.
        $tanggal = date('Ymd');
        $last = (int) $this->Customer_model->get_last_by_date($tanggal);
        $urutan = str_pad(($last + 1), 4, '0', STR_PAD_LEFT);
        $kode_pelanggan = $tanggal . $urutan;

        $data = [
            'kode_pelanggan' => $kode_pelanggan,
            'nama' => $nama,
            'telepon' => $telepon,
            // Field tambahan: kalau kolomnya ada dan NOT NULL, minimal kebagian value aman.
            'alamat' => '',
            'jenis_kelamin' => '',
            'tanggal_lahir' => null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $new_id = $this->Customer_model->insert_customer($data);
        if (!$new_id) {
            $this->session->set_flashdata('error', 'Pendaftaran gagal. Coba lagi ya.');
            if ($redirect_to) {
                redirect('start?redirect_to=' . urlencode($redirect_to));
                return;
            }
            redirect('start');
            return;
        }

        $this->session->set_userdata('member_id', (int) $new_id);
        redirect($redirect_to ? $redirect_to : 'order/resume');
    }
}

