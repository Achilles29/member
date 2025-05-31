<?php
class Poin_model extends CI_Model {

    public function get_active_poin($member_id) {
        $this->db->select_sum('jumlah_poin');
        $this->db->where('customer_id', $member_id);
        $this->db->where('status', 'aktif');
        $this->db->where('tanggal_kadaluarsa >=', date('Y-m-d'));
        $result = $this->db->get('pr_customer_poin')->row_array();

        return $result['jumlah_poin'] ?? 0;
    }
    public function get_riwayat_poin($member_id) {
        return $this->db->where('customer_id', $member_id)
                        ->order_by('created_at', 'DESC')
                        ->get('pr_customer_poin')
                        ->result_array();
    }

    public function get_total_poin($member_id) {
        $this->db->select_sum('jumlah_poin');
        $this->db->where([
            'customer_id' => $member_id,
            'status' => 'AKTIF',
            'tanggal_kedaluwarsa >=' => date('Y-m-d')
        ]);
        $result = $this->db->get('pr_customer_poin')->row();
        return (int) ($result->jumlah_poin ?? 0);
    }

    public function get_kedaluwarsa_segera($member_id) {
        $now = date('Y-m-d');
        $soon = date('Y-m-d', strtotime('+7 days'));
        return $this->db->where('customer_id', $member_id)
                        ->where('status', 'AKTIF')
                        ->where('tanggal_kedaluwarsa >=', $now)
                        ->where('tanggal_kedaluwarsa <=', $soon)
                        ->get('pr_customer_poin')->result_array();
    }
    public function get_summary($member_id) {
        $today = date('Y-m-d');
        $next_month = date('Y-m-d', strtotime('+30 days'));

        // Aktif
        $aktif = $this->db->select_sum('jumlah_poin')
            ->where('customer_id', $member_id)
            ->where('status', 'AKTIF')
            ->where('tanggal_kedaluwarsa >=', $today)
            ->get('pr_customer_poin')->row()->jumlah_poin ?? 0;

        // Digunakan
        $digunakan = $this->db->select_sum('jumlah_poin')
            ->where('customer_id', $member_id)
            ->where('status', 'DIGUNAKAN')
            ->get('pr_customer_poin')->row()->jumlah_poin ?? 0;

        // Kedaluwarsa
        $kedaluwarsa = $this->db->select_sum('jumlah_poin')
            ->where('customer_id', $member_id)
            ->where('status', 'KEDALUWARSA')
            ->get('pr_customer_poin')->row()->jumlah_poin ?? 0;

        // Akan kedaluwarsa
        $akan = $this->db->select_sum('jumlah_poin')
            ->where('customer_id', $member_id)
            ->where('status', 'AKTIF')
            ->where("tanggal_kedaluwarsa BETWEEN '{$today}' AND '{$next_month}'")
            ->get('pr_customer_poin')->row()->jumlah_poin ?? 0;

        return [
            'aktif' => (int) $aktif,
            'digunakan' => (int) $digunakan,
            'kedaluwarsa' => (int) $kedaluwarsa,
            'akan_kedaluwarsa' => (int) $akan,
        ];
    }

    public function get_riwayat($customer_id, $start_date, $end_date, $limit = null)
    {
        $this->db->select('cp.*, t.no_transaksi');
        $this->db->from('pr_customer_poin cp');
        $this->db->join('pr_transaksi t', 't.id = cp.transaksi_id', 'left');
        $this->db->where('cp.customer_id', $customer_id);
        $this->db->where('cp.created_at >=', $start_date);
        $this->db->where('cp.created_at <=', $end_date);
        $this->db->order_by('cp.created_at', 'desc');
        if ($limit !== null) $this->db->limit($limit);
        return $this->db->get()->result_array();
    }
    public function get_pagination($customer_id, $start_date, $end_date, $limit, $offset) {
        $this->db->select('p.*, t.no_transaksi');
        $this->db->from('pr_customer_poin p');
        $this->db->join('pr_transaksi t', 't.id = p.transaksi_id', 'left');
        $this->db->where('p.customer_id', $customer_id);
        $this->db->where('p.created_at >=', $start_date);
        $this->db->where('p.created_at <=', $end_date);
        $this->db->order_by('p.created_at', 'DESC');
    
        if ($limit !== 'semua') {
            $this->db->limit($limit, $offset);
        }
    
        return $this->db->get()->result_array();
    }
    
    public function get_pagination_count($customer_id, $start_date, $end_date) {
        $this->db->from('pr_customer_poin');
        $this->db->where('customer_id', $customer_id);
        $this->db->where('created_at >=', $start_date);
        $this->db->where('created_at <=', $end_date);
        return $this->db->count_all_results();
    }
    
}
