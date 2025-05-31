<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Profile extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->helper(['url', 'form']);
        $this->load->model('Member_model');
        $this->load->library('upload');
    }

    private function check_login() {
        if (!$this->session->userdata('member_id')) {
            redirect('login');
        }
    }

    public function index() {
        $this->check_login();
        $member_id = $this->session->userdata('member_id');
        $data['member'] = $this->Member_model->get_member_by_id($member_id);
        $this->load->view('templates/header', $data);
        $this->load->view('member/profile', $data);
        $this->load->view('templates/footer', $data);


    }
    public function update() {
        $this->check_login();
        $this->load->helper('url');
    
        $member_id = $this->session->userdata('member_id');
    
        $data = [
            'nama' => $this->input->post('nama'),
            'jenis_kelamin' => $this->input->post('jenis_kelamin'),
            'tanggal_lahir' => $this->input->post('tanggal_lahir'),
            'alamat' => $this->input->post('alamat'),
            'telepon' => $this->input->post('telepon'),
            'email' => $this->input->post('email'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
    
        // ==== Upload foto jika ada ====
        if (!empty($_FILES['foto']['name']) && is_uploaded_file($_FILES['foto']['tmp_name'])) {
            $upload_dir = FCPATH . 'uploads/foto_pelanggan/';
    
            // Cek folder, buat jika belum ada
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
    
            $config['upload_path']   = $upload_dir;
            $config['allowed_types'] = 'jpg|jpeg|png';
            $config['max_size']      = 2048; // 2MB
            $config['file_ext_tolower'] = true;
            $config['file_name']     = 'member_' . $member_id . '_' . time();
    
            $this->upload->initialize($config);
    
            if ($this->upload->do_upload('foto')) {
                $upload_data = $this->upload->data();
                $data['foto'] = $upload_data['file_name']; // Simpan nama file ke DB
            } else {
                log_message('error', '❌ Upload foto gagal: ' . $this->upload->display_errors());
            }
        }
    
        // ==== Update DB ====
        $this->db->where('id', $member_id)->update('pr_customer', $data);
    
        redirect('profile');
    }
    
}
