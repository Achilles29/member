<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Member_Controller.php';

/**
 * Profile Controller
 * 
 * Controller untuk mengelola profil member
 * 
 * @package    Member Application
 * @category   Controllers
 */
class Profile extends Member_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        $this->load->library('upload');
        $this->load->helper('form');
    }

    /**
     * Show profile page
     */
    public function index()
    {
        $data = [
            'title'       => 'Profil Saya',
            'active_menu' => 'akun',
            'csrf_token'  => $this->get_csrf_token(),
        ];

        $this->render('member/profile', $data);
    }

    /**
     * Update profile
     */
    public function update()
    {
        $this->validate_csrf();
        
        // Validation rules
        $this->form_validation->set_rules('nama', 'Nama', 'required|trim|min_length[3]|max_length[100]');
        $this->form_validation->set_rules('email', 'Email', 'trim|valid_email');
        $this->form_validation->set_rules('telepon', 'Nomor Telepon', 'required|trim|numeric|min_length[10]|max_length[15]');
        $this->form_validation->set_rules('jenis_kelamin', 'Jenis Kelamin', 'permit_empty|in_list[Laki-laki,Perempuan]');
        $this->form_validation->set_rules('tanggal_lahir', 'Tanggal Lahir', 'trim');
        $this->form_validation->set_rules('alamat', 'Alamat', 'trim|max_length[500]');

        if ($this->form_validation->run() == FALSE) {
            $this->set_message('error', validation_errors());
            redirect('profile');
            return;
        }

        // Check if phone already used by other member
        $telepon = $this->input->post('telepon', true);
        if ($this->Member_model->phone_exists($telepon, $this->member_id)) {
            $this->set_message('error', 'Nomor telepon sudah digunakan oleh member lain.');
            redirect('profile');
            return;
        }

        // Check if email already used by other member
        $email = $this->input->post('email', true);
        if ($email && $this->Member_model->email_exists($email, $this->member_id)) {
            $this->set_message('error', 'Email sudah digunakan oleh member lain.');
            redirect('profile');
            return;
        }

        // Prepare update data
        $data = [
            'nama' => $this->input->post('nama', true),
            'jenis_kelamin' => $this->input->post('jenis_kelamin', true),
            'tanggal_lahir' => $this->input->post('tanggal_lahir', true) ?: null,
            'alamat' => $this->input->post('alamat', true),
            'telepon' => $telepon,
            'email' => $email
        ];

        // Handle photo upload
        $foto = $this->handle_photo_upload();
        if ($foto !== false) {
            $data['foto'] = $foto;
        }

        // Update database
        if ($this->Member_model->update($this->member_id, $data)) {
            $this->set_message('success', 'Profil berhasil diperbarui.');
        } else {
            $this->set_message('error', 'Gagal memperbarui profil. Silakan coba lagi.');
        }

        redirect('profile');
    }

    /**
     * Handle photo upload
     * 
     * @return string|false|null File name if success, false if error, null if no upload
     */
    private function handle_photo_upload()
    {
        if (empty($_FILES['foto']['name']) || !is_uploaded_file($_FILES['foto']['tmp_name'])) {
            return null;
        }

        $upload_dir = FCPATH . 'uploads/foto_pelanggan/';

        // Create directory if not exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Configure upload
        $config = [
            'upload_path'   => $upload_dir,
            'allowed_types' => 'jpg|jpeg|png|webp',
            'max_size'      => 2048, // 2MB
            'max_width'     => 2000,
            'max_height'    => 2000,
            'file_ext_tolower' => true,
            'encrypt_name'  => true
        ];

        $this->upload->initialize($config);

        if ($this->upload->do_upload('foto')) {
            $upload_data = $this->upload->data();
            
            // Delete old photo
            $this->delete_old_photo();
            
            // Resize image to optimize storage
            $this->resize_photo($upload_data['full_path']);
            
            return $upload_data['file_name'];
        } else {
            $error = $this->upload->display_errors('', '');
            log_message('error', 'Photo upload failed: ' . $error);
            $this->set_message('error', 'Gagal upload foto: ' . $error);
            
            return false;
        }
    }

    /**
     * Delete old photo
     */
    private function delete_old_photo()
    {
        if (!empty($this->member['foto'])) {
            $old_file = FCPATH . 'uploads/foto_pelanggan/' . $this->member['foto'];
            if (file_exists($old_file)) {
                @unlink($old_file);
            }
        }
    }

    /**
     * Resize photo to optimize storage
     */
    private function resize_photo($file_path)
    {
        $this->load->library('image_lib');
        
        $config = [
            'image_library' => 'gd2',
            'source_image'  => $file_path,
            'maintain_ratio' => true,
            'width'         => 800,
            'height'        => 800,
            'quality'       => 85
        ];
        
        $this->image_lib->initialize($config);
        $this->image_lib->resize();
        $this->image_lib->clear();
    }

    /**
     * Change password
     */
    public function change_password()
    {
        $this->validate_csrf();
        
        $this->form_validation->set_rules('password_lama', 'Password Lama', 'required');
        $this->form_validation->set_rules('password_baru', 'Password Baru', 'required|min_length[6]');
        $this->form_validation->set_rules('password_konfirmasi', 'Konfirmasi Password', 'required|matches[password_baru]');

        if ($this->form_validation->run() == FALSE) {
            $this->set_message('error', validation_errors());
            redirect('profile');
            return;
        }

        // Verify old password
        if (!password_verify($this->input->post('password_lama'), $this->member['password'] ?? '')) {
            $this->set_message('error', 'Password lama tidak sesuai.');
            redirect('profile');
            return;
        }

        // Update password
        $data = [
            'password' => password_hash($this->input->post('password_baru'), PASSWORD_DEFAULT)
        ];

        if ($this->Member_model->update($this->member_id, $data)) {
            $this->set_message('success', 'Password berhasil diubah.');
        } else {
            $this->set_message('error', 'Gagal mengubah password.');
        }

        redirect('profile');
    }

    /**
     * Delete account
     */
    public function delete_account()
    {
        $this->validate_csrf();
        
        // Soft delete by changing status
        $data = [
            'status' => 'nonaktif',
            'deleted_at' => date('Y-m-d H:i:s')
        ];

        if ($this->Member_model->update($this->member_id, $data)) {
            $this->session->unset_userdata('member_id');
            $this->set_message('success', 'Akun Anda berhasil dihapus.');
            redirect('login');
        } else {
            $this->set_message('error', 'Gagal menghapus akun.');
            redirect('profile');
        }
    }
}
