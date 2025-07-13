<?php

class Redeem_model extends CI_Model
{
    public function get_active_redeem($jenis)
    {
        return $this->db->where('jenis', $jenis)
                        ->where('is_active', 1)
                        ->order_by('jumlah_dibutuhkan', 'asc')
                        ->get('pr_redeem_setting')->result_array();
    }

    public function get_available_redeem($customer_id)
    {
        $results = [];

        // Hitung total poin aktif
        $poin = $this->db->select_sum('jumlah_poin')
                         ->where('customer_id', $customer_id)
                         ->where('status', 'aktif')
                         ->get('pr_customer_poin')->row()->jumlah_poin ?? 0;

        // Hitung total stamp aktif
        $stamp = $this->db->select_sum('jumlah_stamp')
                          ->where('customer_id', $customer_id)
                          ->where('status', 'aktif')
                          ->get('pr_customer_stamp')->row()->jumlah_stamp ?? 0;

        // Filter redeem dari jenis poin
        $redeem_poin = $this->get_active_redeem('poin');
        foreach ($redeem_poin as $item) {
            if ($poin >= $item['jumlah_dibutuhkan']) {
                $item['jenis_display'] = 'Poin';
                $results[] = $item;
            }
        }

        // Filter redeem dari jenis stamp
        $redeem_stamp = $this->get_active_redeem('stamp');
        foreach ($redeem_stamp as $item) {
            if ($stamp >= $item['jumlah_dibutuhkan']) {
                $item['jenis_display'] = 'Stamp';
                $results[] = $item;
            }
        }

        return $results;
    }

    public function get_all_active_by_type($jenis)
    {
        return $this->db
            ->where('jenis', $jenis)
            ->where('is_active', 1)
            ->order_by('jumlah_dibutuhkan', 'asc')
            ->get('pr_redeem_setting')->result_array();
    }


    public function get_redeem_by_id($id)
    {
        return $this->db->get_where('pr_redeem_setting', ['id' => $id])->row_array();
    }

    public function process_redeem($customer_id, $redeem)
    {
        // Simpan log redeem
        $this->db->insert('pr_redeem_log', [
            'redeem_setting_id' => $redeem['id'],
            'customer_id' => $customer_id,
            'jumlah_dibutuhkan' => $redeem['jumlah_dibutuhkan'],
            'jenis' => $redeem['jenis'],
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Buat voucher hasil redeem
        $this->db->insert('pr_voucher', [
//            'kode_voucher' => 'RDM-' . strtoupper(substr(md5(uniqid()), 0, 6)),
            'jenis' => $redeem['jenis_voucher'],
            'nilai' => $redeem['nilai_voucher'],
            'min_pembelian' => $redeem['min_pembelian'],
            'produk_id' => $redeem['produk_id'],
            'jumlah_gratis' => $redeem['jumlah_gratis'],
            'max_diskon' => $redeem['max_diskon'],
            'maksimal_voucher' => 1,
            'sisa_voucher' => 1,
            'status' => 'aktif',
            'customer_id' => $customer_id,
            'tanggal_mulai' => date('Y-m-d'),
            'tanggal_berakhir' => date('Y-m-d', strtotime("+{$redeem['masa_berlaku']} days")),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // public function get_active_stamp($customer_id) {
    //     $this->db->select('pcs.promo_stamp_id, ps.nama_promo, SUM(pcs.jumlah_stamp) as jumlah_stamp, ps.total_stamp_target');
    //     $this->db->from('pr_customer_stamp pcs');
    //     $this->db->join('pr_promo_stamp ps', 'pcs.promo_stamp_id = ps.id');
    //     $this->db->where('pcs.customer_id', $customer_id);
    //     $this->db->where('pcs.status', 'aktif');
    //     $this->db->group_by('pcs.promo_stamp_id');
    //     $this->db->order_by('pcs.updated_at', 'desc');
    //     return $this->db->get()->result_array();
    // }
    public function get_active_stamp($customer_id)
    {
        $this->db->select('pcs.promo_stamp_id, ps.nama_promo, SUM(pcs.jumlah_stamp) as jumlah_stamp, ps.total_stamp_target, ps.aktif');
        $this->db->from('pr_customer_stamp pcs');
        $this->db->join('pr_promo_stamp ps', 'pcs.promo_stamp_id = ps.id');
        $this->db->where('pcs.customer_id', $customer_id);
        $this->db->where('pcs.status', 'aktif');
        $this->db->where('ps.aktif', 1); // ⬅️ Hanya promo yang aktif
        $this->db->group_by('pcs.promo_stamp_id');
        $this->db->order_by('pcs.updated_at', 'desc');
        return $this->db->get()->result_array();
    }

    public function get_active_redeem_by_type($customer_id, $jenis)
    {
        $data = [];
        $available = $this->get_active_redeem($jenis);

        if ($jenis == 'poin') {
            $jumlah = $this->db->select_sum('jumlah_poin')
                ->where('customer_id', $customer_id)
                ->where('status', 'aktif')
                ->get('pr_customer_poin')->row()->jumlah_poin ?? 0;
        } else {
            $jumlah = $this->db->select_sum('jumlah_stamp')
                ->where('customer_id', $customer_id)
                ->where('status', 'aktif')
                ->get('pr_customer_stamp')->row()->jumlah_stamp ?? 0;
        }

        foreach ($available as $item) {
            if ($jumlah >= $item['jumlah_dibutuhkan']) {
                $item['jenis_display'] = ucfirst($jenis);
                $data[] = $item;
            }
        }

        return $data;
    }

    public function get_setting($id)
    {
        return $this->db->get_where('pr_redeem_setting', ['id' => $id, 'is_active' => 1])->row_array();
    }

    public function potong_saldo($customer_id, $redeem)
    {
        if ($redeem['jenis'] == 'poin') {
            return $this->potong_poin($customer_id, $redeem['jumlah_dibutuhkan']);
        } else {
            return $this->potong_stamp($customer_id, $redeem['jumlah_dibutuhkan']);
        }
    }

    private function potong_poin($customer_id, $jumlah)
    {
        // Hitung total poin aktif tanpa filter tanggal
        $total = $this->db->select_sum('jumlah_poin')
        ->where('customer_id', $customer_id)
        ->where('status', 'aktif')
        ->get('pr_customer_poin')->row('jumlah_poin') ?? 0;

        log_message('error', "DEBUG: total poin aktif untuk customer_id {$customer_id} = $total");

        // Jika jumlah tidak cukup

        if ($total < $jumlah) {
            return false;
        }

        // Ambil semua data poin aktif secara berurutan
        $list = $this->db->order_by('created_at')
            ->where('customer_id', $customer_id)
            ->where('status', 'aktif')
            ->get('pr_customer_poin')->result();

        return $this->_proses_split($list, $jumlah, 'pr_customer_poin', 'jumlah_poin');
    }


    private function potong_stamp($customer_id, $jumlah)
    {
        $total = $this->db->select_sum('jumlah_stamp')->where(['customer_id' => $customer_id, 'status' => 'aktif'])->get('pr_customer_stamp')->row('jumlah_stamp') ?? 0;
        if ($total < $jumlah) {
            return false;
        }

        $list = $this->db->order_by('created_at')->get_where('pr_customer_stamp', ['customer_id' => $customer_id, 'status' => 'aktif'])->result();
        return $this->_proses_split($list, $jumlah, 'pr_customer_stamp', 'jumlah_stamp');
    }

    private function _proses_split($list, $jumlah, $table, $kolom)
    {
        $sisa = $jumlah;

        foreach ($list as $row) {
            if ($sisa <= 0) {
                break;
            }

            // Ambil properti khusus jika tersedia
            $extra = [
                'created_at' => $row->created_at,
                'updated_at' => date('Y-m-d H:i:s'),
                'customer_id' => $row->customer_id,
            ];

            if ($table == 'pr_customer_poin') {
                $extra['transaksi_id'] = property_exists($row, 'transaksi_id') ? $row->transaksi_id : null;
                $extra['tanggal_kedaluwarsa'] = property_exists($row, 'tanggal_kedaluwarsa') ? $row->tanggal_kedaluwarsa : null;
            } elseif ($table == 'pr_customer_stamp') {
                $extra['pr_transaksi_id'] = property_exists($row, 'pr_transaksi_id') ? $row->pr_transaksi_id : null;
                $extra['promo_stamp_id'] = property_exists($row, 'promo_stamp_id') ? $row->promo_stamp_id : null;
                $extra['last_stamp_at'] = property_exists($row, 'last_stamp_at') ? $row->last_stamp_at : null;
                $extra['masa_berlaku'] = property_exists($row, 'masa_berlaku') ? $row->masa_berlaku : null;
            }

            if ($row->$kolom <= $sisa) {
                // Semua dipakai
                $this->db->where('id', $row->id)->update($table, [
                    'status' => ($table == 'pr_customer_poin' ? 'digunakan' : 'ditukar')
                ]);
                $sisa -= $row->$kolom;
            } else {
                // Split sebagian
                $this->db->where('id', $row->id)->update($table, [
                    $kolom => $sisa,
                    'status' => ($table == 'pr_customer_poin' ? 'digunakan' : 'ditukar')
                ]);

                // Insert sisa ke baris baru
                $insert = array_merge($extra, [
                    $kolom => $row->$kolom - $sisa,
                    'status' => 'aktif'
                ]);
                $this->db->insert($table, $insert);
                $sisa = 0;
            }
        }

        return true;
    }


    public function simpan_voucher($customer_id, $redeem)
    {
        $kode = $this->_generate_kode($redeem['nama_redeem']);

        $jenis = $redeem['jenis_voucher'] == 'produk' ? 'gratis_produk' : ($redeem['tipe_diskon'] == 'nominal' ? 'nominal' : 'persentase');

        $data = [
            'kode_voucher'     => $kode,
            'jenis'            => $jenis,
            'nilai'            => $redeem['nilai_voucher'],
            'min_pembelian'    => null,
            'produk_id'        => $redeem['produk_id'],
            'jumlah_gratis'    => null,
            'max_diskon'       => $redeem['max_diskon'],
            'maksimal_voucher' => 1,
            'sisa_voucher'     => 1,
            'status'           => 'aktif',
            'tanggal_mulai'    => date('Y-m-d'),
            'tanggal_berakhir' => date('Y-m-d', strtotime("+{$redeem['masa_berlaku']} days")),
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
            'customer_id'      => $customer_id
        ];
        $this->db->insert('pr_voucher', $data);
        return $this->db->insert_id();
    }

    public function log_redeem($customer_id, $redeem, $voucher_id)
    {
        $this->db->insert('pr_redeem_log', [
            'customer_id'       => $customer_id,
            'redeem_setting_id' => $redeem['id'],
            'jenis'             => $redeem['jenis'],
            'jumlah_digunakan'  => $redeem['jumlah_dibutuhkan'],
            'voucher_id'        => $voucher_id,
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s')
        ]);
    }

    private function _generate_kode($nama)
    {
        return strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $nama), 0, 3)) . strtoupper(substr(uniqid(), -5));
    }


}
