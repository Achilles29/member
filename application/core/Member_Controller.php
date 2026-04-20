<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Member Base Controller
 * 
 * Base controller untuk semua fitur member area
 * Menyediakan authentication, data member, dan utility methods
 * 
 * @package    Member Application
 * @category   Core
 * @author     Namua Coffee
 */
class Member_Controller extends CI_Controller
{
    protected $member_id;
    protected $member;
    protected $data = [];

    public function __construct()
    {
        parent::__construct();
        
        $this->load->helper(['url', 'text', 'security']);
        $this->load->library(['session', 'form_validation']);
        $this->load->model('Member_model');
        
        $this->_check_authentication();
        $this->_load_member_data();
        $this->_init_base_data();
    }

    /**
     * Check if member is authenticated
     */
    private function _check_authentication()
    {
        $this->member_id = $this->session->userdata('member_id');
        
        if (!$this->member_id) {
            $current_url = current_url();
            redirect('login?redirect_to=' . urlencode($current_url));
        }
    }

    /**
     * Load member data from database
     */
    private function _load_member_data()
    {
        $this->member = $this->Member_model->get_by_id($this->member_id);
        
        if (!$this->member) {
            $this->session->unset_userdata('member_id');
            $this->session->set_flashdata('error', 'Sesi Anda telah berakhir. Silakan login kembali.');
            redirect('login');
        }
    }

    /**
     * Initialize base data for views
     */
    private function _init_base_data()
    {
        $this->data['member'] = $this->member;
        $this->data['member_id'] = $this->member_id;
    }

    /**
     * Render view dengan template
     */
    protected function render($view, $data = [])
    {
        $data = array_merge($this->data, $data);
        
        $this->load->view('templates/member/header', $data);
        $this->load->view($view, $data);
        $this->load->view('templates/member/footer', $data);
    }

    /**
     * Set flash message
     */
    protected function set_message($type, $message)
    {
        $this->session->set_flashdata($type, $message);
    }

    /**
     * Get member level based on points
     */
    protected function get_member_level($points)
    {
        if ($points >= 1000) return 'Diamond';
        if ($points >= 500) return 'Platinum';
        if ($points >= 200) return 'Gold';
        return 'Silver';
    }

    /**
     * Validate CSRF token
     */
    protected function validate_csrf()
    {
        if ($this->input->method() === 'post') {
            if (!$this->security->get_csrf_hash()) {
                show_error('Invalid CSRF token', 403);
            }
        }
    }

    /**
     * JSON Response Helper
     */
    protected function json_response($data, $status = 200)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_status_header($status)
            ->set_output(json_encode($data));
    }
}
