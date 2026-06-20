<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Redeem extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Redeem_model');
        $this->load->model('Member_model');
        $this->load->helper(['url', 'form']);
    }

    private function require_login(): int
    {
        $id = (int) $this->session->userdata('member_id');
        if (!$id) redirect('login');
        return $id;
    }

    public function index()
    {
        $member_id = $this->require_login();

        $poin        = $this->Redeem_model->get_poin_aktif($member_id);
        $stamp_total = $this->Redeem_model->get_stamp_aktif($member_id);

        $data['member']       = $this->Member_model->get_member_by_id($member_id);
        $data['poin']         = $poin;
        $data['stamp_total']  = $stamp_total;
        $data['redeem_poin']  = $this->Redeem_model->get_rules_by_type('poin');
        $data['redeem_stamp'] = $this->Redeem_model->get_rules_by_type('stamp');
        $data['title']        = 'Redeem Reward';
        $data['active_menu']  = 'redeem';

        $this->load->view('templates/member/header', $data);
        $this->load->view('member/redeem', $data);
        $this->load->view('templates/member/footer', $data);
    }

    public function process(int $rule_id = 0)
    {
        $member_id = $this->require_login();
        $rule_id   = (int) $rule_id;

        if ($rule_id <= 0) {
            $this->session->set_flashdata('error', 'Reward tidak valid.');
            redirect('redeem');
        }

        $result = $this->Redeem_model->process_rule_redeem($member_id, $rule_id);

        if (!$result['ok']) {
            $this->session->set_flashdata('error', $result['message'] ?? 'Redeem gagal.');
            redirect('redeem');
        }

        $this->session->set_flashdata('success',
            $result['message'] ?? ('Redeem berhasil! Voucher kamu: ' . $result['voucher_code'])
        );
        redirect('redeem');
    }
}
