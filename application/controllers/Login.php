<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Login Controller
 * 
 * Controller untuk autentikasi member
 * 
 * @package    Member Application
 * @category   Controllers
 */
class Login extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        $this->load->model('Member_model');
        $this->load->helper(['url', 'security']);
        $this->load->library(['session', 'form_validation']);
    }

    /**
     * Show login page
     */
    public function index()
    {
        // Redirect if already logged in
        if ($this->session->userdata('member_id')) {
            redirect('member');
        }

        $data = [
            'title' => 'Login Member',
            'redirect_to' => $this->input->get('redirect_to', true),
        ];
        
        $this->load->view('auth/login', $data);
    }

    /**
     * Process login
     */
    public function do_login()
    {
        // Validation
        $this->form_validation->set_rules('telepon', 'Nomor Telepon', 'required|trim|numeric');

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', 'Nomor telepon harus diisi dengan benar.');
            redirect('login');
            return;
        }

        $telepon = $this->input->post('telepon', true);
        $member = $this->Member_model->get_by_phone($telepon);
        $redirect_to = $this->safe_redirect_to($this->input->post('redirect_to'));

        if ($member && ($member['is_active'] == 1 && $member['member_status'] === 'ACTIVE')) {
            // Set session
            $this->session->set_userdata([
                'member_id' => $member['id'],
                'member_name' => $member['nama'],
                'member_phone' => $member['telepon'],
                'login_time' => time()
            ]);

            // Update last login
            $this->Member_model->update($member['id'], [
                'last_login' => date('Y-m-d H:i:s')
            ]);

            // Log login activity
            $this->log_activity($member['id'], 'login', 'Member login successful');

            // Set success message
            $this->session->set_flashdata('success', 'Selamat datang, ' . $member['nama'] . '!');

            // Redirect
            if ($redirect_to) {
                redirect($redirect_to);
            }
            redirect('member');
            
        } elseif ($member && ($member['is_active'] == 0 || $member['member_status'] !== 'ACTIVE')) {
            $this->session->set_flashdata('error', 'Akun Anda tidak aktif. Hubungi customer service.');
            redirect('login');
            
        } else {
            // Failed login attempt
            $this->session->set_flashdata('error', 'Nomor telepon tidak terdaftar. Silakan daftar terlebih dahulu.');
            
            if ($redirect_to) {
                redirect('login?redirect_to=' . urlencode($redirect_to));
            }
            redirect('login');
        }
    }

    /**
     * Logout
     */
    public function logout()
    {
        $member_id = $this->session->userdata('member_id');
        
        if ($member_id) {
            $this->log_activity($member_id, 'logout', 'Member logout');
        }

        // Destroy session
        $this->session->unset_userdata([
            'member_id',
            'member_name',
            'member_phone',
            'login_time'
        ]);

        $this->session->set_flashdata('success', 'Anda telah berhasil logout.');
        redirect('login');
    }

    /**
     * Safe redirect URL validation
     */
    private function safe_redirect_to($redirect_to)
    {
        $redirect_to = trim((string)$redirect_to);
        
        if ($redirect_to === '') {
            return null;
        }

        // Only allow internal redirects
        if (strpos($redirect_to, 'http://') === 0 || strpos($redirect_to, 'https://') === 0) {
            $base = rtrim(base_url(), '/');
            
            if (strpos($redirect_to, $base) !== 0) {
                return null;
            }
            
            $redirect_to = substr($redirect_to, strlen($base));
            
            if ($redirect_to === '') {
                $redirect_to = '/';
            }
        }

        if ($redirect_to[0] !== '/') {
            $redirect_to = '/' . $redirect_to;
        }

        // Prevent redirect to logout or login
        $blocked_paths = ['/logout', '/login', '/register'];
        foreach ($blocked_paths as $path) {
            if (strpos($redirect_to, $path) !== false) {
                return null;
            }
        }

        return ltrim($redirect_to, '/');
    }

    /**
     * Log member activity
     */
    private function log_activity($member_id, $action, $description)
    {
        $data = [
            'member_id' => $member_id,
            'action' => $action,
            'description' => $description,
            'ip_address' => $this->input->ip_address(),
            'user_agent' => $this->input->user_agent(),
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Check if table exists before logging
        if ($this->db->table_exists('pr_member_activity_log')) {
            $this->db->insert('pr_member_activity_log', $data);
        }
    }

    /**
     * Send OTP (for future enhancement)
     */
    public function send_otp()
    {
        $this->form_validation->set_rules('telepon', 'Nomor Telepon', 'required|trim|numeric');

        if ($this->form_validation->run() == FALSE) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'error',
                    'message' => 'Nomor telepon tidak valid'
                ]));
            return;
        }

        $telepon = $this->input->post('telepon', true);
        $member = $this->Member_model->get_by_phone($telepon);

        if (!$member) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'error',
                    'message' => 'Nomor telepon tidak terdaftar'
                ]));
            return;
        }

        // Generate OTP
        $otp = rand(100000, 999999);
        
        // Store OTP in session (temporary)
        $this->session->set_userdata([
            'otp' => $otp,
            'otp_phone' => $telepon,
            'otp_time' => time()
        ]);

        // TODO: Send OTP via SMS/WhatsApp
        // For now, just return the OTP (development only)
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'success',
                'message' => 'OTP berhasil dikirim',
                'otp' => ENVIRONMENT === 'development' ? $otp : null
            ]));
    }

    /**
     * Verify OTP
     */
    public function verify_otp()
    {
        $this->form_validation->set_rules('otp', 'Kode OTP', 'required|exact_length[6]|numeric');

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', 'Kode OTP harus 6 digit angka.');
            redirect('login');
            return;
        }

        $otp_input = $this->input->post('otp', true);
        $otp_stored = $this->session->userdata('otp');
        $otp_phone = $this->session->userdata('otp_phone');
        $otp_time = $this->session->userdata('otp_time');

        // Check OTP expiration (5 minutes)
        if (time() - $otp_time > 300) {
            $this->session->set_flashdata('error', 'Kode OTP telah kadaluarsa.');
            redirect('login');
            return;
        }

        if ($otp_input == $otp_stored) {
            $member = $this->Member_model->get_by_phone($otp_phone);
            
            if ($member) {
                $this->session->set_userdata([
                    'member_id' => $member['id'],
                    'member_name' => $member['nama'],
                    'member_phone' => $member['telepon'],
                    'login_time' => time()
                ]);

                // Clear OTP data
                $this->session->unset_userdata(['otp', 'otp_phone', 'otp_time']);

                redirect('member');
            }
        } else {
            $this->session->set_flashdata('error', 'Kode OTP tidak valid.');
            redirect('login');
        }
    }
}
