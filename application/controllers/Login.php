<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Login extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Member_model');
        $this->load->helper('url');
    }

    public function index()
    {
        $data = [
            'redirect_to' => $this->input->get('redirect_to', true),
        ];
        $this->load->view('auth/login', $data);
    }

    private function safe_redirect_to($redirect_to)
    {
        $redirect_to = trim((string)$redirect_to);
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

    public function do_login()
    {
        $telepon = $this->input->post('telepon');
        $member = $this->Member_model->get_by_phone($telepon);
        $redirect_to = $this->safe_redirect_to($this->input->post('redirect_to'));

        if ($member) {
            $this->session->set_userdata('member_id', $member['id']);
            if ($redirect_to) {
                redirect($redirect_to);
            }
            redirect('member');
        } else {
            $this->session->set_flashdata('error', 'Nomor HP tidak ditemukan.');
            if ($redirect_to) {
                redirect('login?redirect_to=' . urlencode($redirect_to));
            }
            redirect('login');
        }
    }

    public function logout()
    {
        $this->session->unset_userdata('member_id');
        redirect('login');
    }
}
