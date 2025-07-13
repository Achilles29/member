<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Member extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Member_model');
    }

    public function index()
    {
        $this->load->view('member/login');
    }

    public function login()
    {
        $phone = $this->input->post('phone');
        $member = $this->Member_model->get_member_by_phone($phone);

        if ($member) {
            $this->session->set_userdata('member_id', $member['id']);
            redirect('member/card');
        } else {
            $this->session->set_flashdata('error', 'Nomor HP tidak ditemukan.');
            redirect('member');
        }
    }

    public function register()
    {
        $this->load->view('member/register');
    }
    public function process_register()
    {
        $name = $this->input->post('name');
        $phone = $this->input->post('phone');

        $data = array(
            'name' => $name,
            'phone' => $phone
        );

        if ($this->Member_model->insert_member($data)) {
            $this->session->set_flashdata('success', 'Pendaftaran berhasil. Silakan login.');
            redirect('member');
        } else {
            $this->session->set_flashdata('error', 'Gagal mendaftar. Coba lagi.');
            redirect('member/register');
        }
    }

    public function card()
    {
        $member_id = $this->session->userdata('member_id');
        if (!$member_id) {
            redirect('member');
        }

        // Ambil data member
        $data['member'] = $this->Member_model->get_member_by_id($member_id);

        // Ambil stempel member
        $data['stamps'] = $this->Member_model->get_stamps_with_date($member_id);

        // Cari stempel pertama dan hitung tanggal berlaku
        if (!empty($data['stamps'])) {
            $first_stamp_date = $data['stamps'][0]['stamp_date'];
            $data['valid_until'] = date('Y-m-d', strtotime($first_stamp_date . ' +1 month'));
        } else {
            $data['valid_until'] = null; // Tidak ada stempel
        }

        // Ambil voucher yang aktif untuk member
        $this->load->model('Voucher_model');
        $data['vouchers'] = $this->Voucher_model->get_vouchers_for_member($member_id);

        // Tampilkan halaman kartu member
        $this->load->view('member/card', $data);
    }



    public function add_stamp()
    {
        $member_id = $this->session->userdata('member_id');
        if (!$member_id) {
            redirect('member');
        }

        // Ambil stempel yang sudah ada
        $stamps = $this->Member_model->get_stamps_with_date($member_id);
        $current_stamp_count = count($stamps);

        if ($current_stamp_count < 5) { // Maksimal 5 stempel
            $this->Member_model->add_stamp_transaction($member_id, $current_stamp_count + 1);
        }

        redirect('member/card');
    }
    public function get_stamps($id)
    {
        $stamps = $this->Member_model->get_stamps($id);

        foreach ($stamps as $stamp) {
            echo '<div class="stamp">';
            echo '<span class="date">' . date('d M', strtotime($stamp['stamp_date'])) . '</span>';
            echo '<span class="time">' . date('H:i', strtotime($stamp['stamp_date'])) . '</span>';
            echo '</div>';
        }

        for ($i = count($stamps) + 1; $i <= 5; $i++) {
            echo '<div class="stamp" style="background: #d3d3d3; color: #6d6d6d;">';
            echo '<span class="date">-</span>';
            echo '<span class="time">--:--</span>';
            echo '</div>';
        }
    }

}
