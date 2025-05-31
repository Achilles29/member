<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Admin_model');
        $this->load->model('Member_model');
        $this->load->model('Voucher_model');
    }

    public function login() {
        $this->load->view('admin/login');
    }

    public function process_login() {
        $username = $this->input->post('username');
        $password = $this->input->post('password');

        $admin = $this->Admin_model->get_admin_by_username($username);

        if ($admin && password_verify($password, $admin['password'])) {
            $this->session->set_userdata('admin_id', $admin['id']);
            redirect('admin/dashboard');
        } else {
            $this->session->set_flashdata('error', 'Username atau password salah.');
            redirect('admin/login');
        }
    }

    public function dashboard() {
//        $data['page'] = 'admin/dashboard';
//        $this->load->view('admin/template', $data);
        $this->load->view('templates/header');
        $this->load->view('admin/dashboard');
        $this->load->view('templates/footer');

    }

    public function verify_transaction($member_id) {
        $this->check_login();
        $result = $this->Member_model->add_stamp($member_id);

        if ($result) {
            $this->session->set_flashdata('success', 'Stamp berhasil ditambahkan.');
        } else {
            $this->session->set_flashdata('error', 'Member sudah memiliki 5 stamp.');
        }
        redirect('admin/dashboard');
    }

    private function check_login() {
        if (!$this->session->userdata('admin_id')) {
            redirect('admin/login');
        }
    }

    // Halaman daftar member
 
public function members() {
    $this->load->library('pagination');

    // Ambil nilai halaman dari URL atau set default ke 0 (halaman pertama)
    $page = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;

    // Konfigurasi pagination
    $config['base_url'] = site_url('admin/members');
    $config['total_rows'] = $this->Admin_model->count_all_members(); // Total data members
    $config['per_page'] = 10; // Jumlah data per halaman
    $config['uri_segment'] = 0;
    $config['full_tag_open'] = '<nav><ul class="pagination">';
    $config['full_tag_close'] = '</ul></nav>';
    $config['first_tag_open'] = '<li class="page-item">';
    $config['first_tag_close'] = '</li>';
    $config['last_tag_open'] = '<li class="page-item">';
    $config['last_tag_close'] = '</li>';
    $config['next_tag_open'] = '<li class="page-item">';
    $config['next_tag_close'] = '</li>';
    $config['prev_tag_open'] = '<li class="page-item">';
    $config['prev_tag_close'] = '</li>';
    $config['num_tag_open'] = '<li class="page-item">';
    $config['num_tag_close'] = '</li>';
    $config['cur_tag_open'] = '<li class="page-item active"><span class="page-link">';
    $config['cur_tag_close'] = '</span></li>';
    $config['attributes'] = array('class' => 'page-link');

    $this->pagination->initialize($config);

    // Ambil data members dengan pagination
    $data['members'] = $this->Admin_model->get_paginated_members($config['per_page'], $page);
    $data['pagination'] = $this->pagination->create_links();

    $this->load->view('templates/header');
    $this->load->view('admin/members', $data);
    $this->load->view('templates/footer');
}


    // Halaman stamp per member
    public function stamps($id) {
        $data['member'] = $this->Admin_model->get_member_by_id($id);
        $data['stamps'] = $this->Admin_model->get_stamps($id);
        // $data['page'] = 'admin/stamps';
        // $this->load->view('admin/template', $data);
        $this->load->view('templates/header');
        $this->load->view('admin/stamps', $data);
        $this->load->view('templates/footer');
    }

    // Tambah stamp
    public function add_stamp($id) {
        $current_stamps = count($this->Admin_model->get_stamps($id));
        if ($current_stamps < 5) { // Maksimal 5 stamp
            $this->Admin_model->add_stamp($id);
        } else {
            $this->session->set_flashdata('error', 'Maksimal 5 stempel.');
        }
        redirect('admin/members');
    }
    public function remove_stamp($id) {
        $current_stamps = count($this->Admin_model->get_stamps($id));
        if ($current_stamps > 0) {
            $this->Admin_model->remove_stamp($id);
        }
        redirect('admin/members');
    }

    public function reset_stamp($id) {
        $this->Admin_model->reset_stamp($id);
        redirect('admin/members');
    }

    public function add_stamp_detail($id) {
        $current_stamps = count($this->Admin_model->get_stamps($id));
        if ($current_stamps < 5) { // Maksimal 5 stamp
            $this->Admin_model->add_stamp($id);
        } else {
            $this->session->set_flashdata('error', 'Maksimal 5 stempel.');
        }
        redirect('admin/stamps/' . $id); // Kembali ke halaman stamps
    }

    public function remove_stamp_detail($id) {
        $current_stamps = count($this->Admin_model->get_stamps($id));
        if ($current_stamps > 0) {
            $this->Admin_model->remove_stamp($id);
        }
        redirect('admin/stamps/' . $id); // Kembali ke halaman stamps
    }

    public function reset_stamp_detail($id) {
        $this->Admin_model->reset_stamp($id);
        redirect('admin/stamps/' . $id); // Kembali ke halaman stamps
    }



    // public function search_members() {
    //     $query = $this->input->post('query');
    //     $members = $this->Admin_model->search_members($query);

    //     echo '<table style="width: 100%; border-collapse: collapse; font-size: 14px;">
    //             <thead>
    //                 <tr style="background: #4a90e2; color: white;">
    //                     <th style="padding: 10px;">ID</th>
    //                     <th style="padding: 10px;">Nama</th>
    //                     <th style="padding: 10px;">Nomor HP</th>
    //                     <th style="padding: 10px;">Jumlah Stamp</th>
    //                     <th style="padding: 10px;">Aksi</th>
    //                 </tr>
    //             </thead>
    //             <tbody>';

    //     foreach ($members as $member) {
    //         echo '<tr style="text-align: center;">
    //                 <td style="padding: 10px; border-bottom: 1px solid #ddd;">' . $member['id'] . '</td>
    //                 <td style="padding: 10px; border-bottom: 1px solid #ddd;">' . $member['name'] . '</td>
    //                 <td style="padding: 10px; border-bottom: 1px solid #ddd;">' . $member['phone'] . '</td>
    //                 <td style="padding: 10px; border-bottom: 1px solid #ddd;">' . $member['stamp_count'] . '/5</td>
    //                 <td style="padding: 10px; border-bottom: 1px solid #ddd;">
    //                     <a href="' . site_url('admin/add_stamp/' . $member['id']) . '" style="color: #4a90e2; text-decoration: none;">Tambah</a> |
    //                     <a href="' . site_url('admin/remove_stamp/' . $member['id']) . '" style="color: #4a90e2; text-decoration: none;">Kurang</a> |
    //                     <a href="' . site_url('admin/reset_stamp/' . $member['id']) . '" style="color: #4a90e2; text-decoration: none;">Reset</a> |
    //                     <a href="' . site_url('admin/stamps/' . $member['id']) . '" style="color: #4a90e2; text-decoration: none;">Lihat</a>
    //                 </td>
    //             </tr>';
    //     }

    //     echo '</tbody></table>';
    // }

public function search_members() {
    $query = $this->input->post('query');
    $members = $this->Admin_model->search_members($query);

    $output = '<table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <thead>
                    <tr style="background: #4a90e2; color: white;">
                        <th style="padding: 10px;">ID</th>
                        <th style="padding: 10px;">Nama</th>
                        <th style="padding: 10px;">Nomor HP</th>
                        <th style="padding: 10px;">Jumlah Stamp</th>
                        <th style="padding: 10px;">Voucher</th>
                        <th style="padding: 10px;">Aksi</th>
                        <th style="padding: 10px;">Edit / Hapus</th>
                        <th style="padding: 10px;">Card</th>
                    </tr>
                </thead>
                <tbody>';

    if (!empty($members)) {
        foreach ($members as $member) {
            $output .= '<tr style="text-align: center;">
                            <td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($member['id']) . '</td>
                            <td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($member['name']) . '</td>
                            <td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($member['phone']) . '</td>
                            <td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($member['stamp_count']) . '/5</td>
                            <td style="padding: 10px; border-bottom: 1px solid #ddd;">
                                <a href="' . site_url('admin/member_vouchers/' . $member['id']) . '" style="color: #4a90e2; text-decoration: none;">
                                    Lihat Voucher
                                </a>
                            </td>
                            <td style="padding: 10px; border-bottom: 1px solid #ddd;">
                                <a href="' . site_url('admin/add_stamp/' . $member['id']) . '" style="color: #4a90e2; text-decoration: none;">Tambah</a> |
                                <a href="' . site_url('admin/remove_stamp/' . $member['id']) . '" style="color: #4a90e2; text-decoration: none;">Kurang</a> |
                                <a href="' . site_url('admin/reset_stamp/' . $member['id']) . '" style="color: #4a90e2; text-decoration: none;">Reset</a> |
                                <a href="' . site_url('admin/stamps/' . $member['id']) . '" style="color: #4a90e2; text-decoration: none;">Lihat</a>
                            </td>
                            <td style="padding: 10px; border-bottom: 1px solid #ddd;">
                                <a href="' . site_url('admin/edit_member/' . $member['id']) . '" style="color: #4a90e2; text-decoration: none;">Edit</a> |
                                <a href="' . site_url('admin/delete_member/' . $member['id']) . '" 
                                   onclick="return confirm(\'Anda yakin ingin menghapus member ini?\');" 
                                   style="color: red; text-decoration: none;">Hapus</a>
                            </td>
                            <td style="padding: 10px; border-bottom: 1px solid #ddd;">
                                <a href="' . site_url('admin/member_card/' . $member['id']) . '" style="color: #4a90e2; text-decoration: none;">Lihat Card</a>
                            </td>
                        </tr>';
        }
    } else {
        $output .= '<tr>
                        <td colspan="8" style="padding: 10px; text-align: center; border-bottom: 1px solid #ddd;">Tidak ada data ditemukan</td>
                    </tr>';
    }

    $output .= '</tbody></table>';
    echo $output;
}

    public function add_member() {
        // $data['page'] = 'admin/add_member';
        // $this->load->view('admin/template', $data);
        $this->load->view('templates/header');
        $this->load->view('admin/add_member');
        $this->load->view('templates/footer');

    }

    public function save_member() {
        $name = $this->input->post('name');
        $phone = $this->input->post('phone');

        // Validasi input
        if (empty($name) || empty($phone)) {
            $this->session->set_flashdata('error', 'Nama dan Nomor HP wajib diisi.');
            redirect('admin/add_member');
        } else {
            $this->Admin_model->add_member($name, $phone);
            $this->session->set_flashdata('success', 'Member berhasil ditambahkan.');
            redirect('admin/members');
        }
    }
   public function edit_member($id) {
        $data['member'] = $this->Admin_model->get_member_by_id($id);
        // $data['page'] = 'admin/edit_member';
        // $this->load->view('admin/template', $data);
        $this->load->view('templates/header');
        $this->load->view('admin/edit_member', $data);
        $this->load->view('templates/footer');

    }

    public function update_member($id) {
        $name = $this->input->post('name');
        $phone = $this->input->post('phone');

        if (empty($name) || empty($phone)) {
            $this->session->set_flashdata('error', 'Nama dan Nomor HP wajib diisi.');
            redirect('admin/edit_member/' . $id);
        } else {
            $this->Admin_model->update_member($id, $name, $phone);
            $this->session->set_flashdata('success', 'Member berhasil diperbarui.');
            redirect('admin/members');
        }
    }

    public function delete_member($id) {
        if ($this->Admin_model->delete_member($id)) {
            $this->session->set_flashdata('success', 'Member berhasil dihapus.');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus member.');
        }
        redirect('admin/members');
    }
    
    public function delete_transaction($id) {
        $stamp = $this->Admin_model->get_stamp_by_id($id);
        if ($stamp) {
            $this->Admin_model->delete_transaction($id);
            $this->session->set_flashdata('success', 'Transaksi berhasil dihapus.');
        } else {
            $this->session->set_flashdata('error', 'Transaksi tidak ditemukan.');
        }
        redirect('admin/stamps/' . $stamp['member_id']);
    }


    public function logout() {
        $this->session->unset_userdata('admin_id');
        redirect('admin/login');
    }

public function member_card($member_id) {
    if (!$member_id) {
        $this->session->set_flashdata('error', 'Member tidak ditemukan.');
        redirect('admin/members');
    }

    // Ambil data member
    $data['member'] = $this->Member_model->get_member_by_id($member_id);

    // Ambil stempel member
    $data['stamps'] = $this->Member_model->get_stamps_with_date($member_id);

    // Hitung validitas kartu berdasarkan stempel pertama
    if (!empty($data['stamps'])) {
        $first_stamp_date = $data['stamps'][0]['stamp_date'];
        $data['valid_until'] = date('Y-m-d', strtotime($first_stamp_date . ' +1 month'));
    } else {
        $data['valid_until'] = null; // Tidak ada stempel
    }

    // Ambil voucher yang berlaku untuk member
    $this->load->model('Voucher_model');
    $data['vouchers'] = $this->Voucher_model->get_vouchers_for_member($member_id);

    // Load tampilan kartu member
    $this->load->view('admin/member_card', $data);
}


// public function add_voucher($id) {
//     $voucher_code = $this->input->post('voucher_code');

//     // Validasi input
//     if (empty($voucher_code)) {
//         $this->session->set_flashdata('error', 'Kode voucher tidak boleh kosong.');
//         redirect('admin/member_card/' . $id);
//     }

//     // Simpan kode voucher
//     if ($this->Admin_model->add_voucher($id, $voucher_code)) {
//         $this->session->set_flashdata('success', 'Kode voucher berhasil ditambahkan.');
//     } else {
//         $this->session->set_flashdata('error', 'Gagal menambahkan kode voucher.');
//     }

//     redirect('admin/member_card/' . $id);
// }

// public function get_voucher($id) {
//     $voucher = $this->Admin_model->get_voucher_by_member_id($id);
//     echo json_encode($voucher);
// }
public function member_vouchers($member_id) {
    $this->load->model('Admin_model');
    $data['member'] = $this->Admin_model->get_member_by_id($member_id);
    $data['vouchers'] = $this->Admin_model->get_vouchers_by_member($member_id);
    $data['all_vouchers'] = $this->Voucher_model->get_all_vouchers(); // Ambil semua voucher dari database
    $this->load->view('templates/header');
    $this->load->view('admin/member_vouchers', $data);
    $this->load->view('templates/footer');
}

public function add_member_voucher() {
    $member_id = $this->input->post('member_id');
    $voucher_id = $this->input->post('voucher_id');
    $this->Admin_model->add_member_voucher($member_id, $voucher_id);
    redirect('admin/member_vouchers/' . $member_id);
}

public function delete_member_voucher($id, $member_id) {
    $this->Admin_model->delete_member_voucher($id);
    redirect('admin/member_vouchers/' . $member_id);
}

}
