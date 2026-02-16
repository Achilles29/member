<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Produk_model extends CI_Model {
    // Simple request-scope caches to avoid repetitive "latest stock" queries.
    private $stok_cache_bahan_baku = [];
    private $stok_cache_base = [];
    private $stok_cache_prepare = [];
    private $stok_cache_produk = [];

    private function get_latest_stok_bahan_baku($bahan_baku_id, $divisi_id)
    {
        $key = (int)$bahan_baku_id . '|' . (int)$divisi_id;
        if (array_key_exists($key, $this->stok_cache_bahan_baku)) {
            return $this->stok_cache_bahan_baku[$key];
        }

        $row = $this->db
            ->where([
                'bahan_baku_id' => (int) $bahan_baku_id,
                'divisi_id' => (int) $divisi_id,
            ])
            ->order_by('tanggal', 'DESC')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('rsp_stok_bahan_baku')
            ->row();

        $stok = (float) ($row->stok_sisa ?? 0);
        $this->stok_cache_bahan_baku[$key] = $stok;
        return $stok;
    }

    private function get_latest_stok_base($base_id)
    {
        $key = (int)$base_id;
        if (array_key_exists($key, $this->stok_cache_base)) {
            return $this->stok_cache_base[$key];
        }

        $row = $this->db
            ->where('base_id', (int) $base_id)
            ->order_by('tanggal', 'DESC')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('rsp_base_stok')
            ->row();

        $stok = (float) ($row->stok_sisa ?? 0);
        $this->stok_cache_base[$key] = $stok;
        return $stok;
    }

    private function get_latest_stok_prepare($prepare_id)
    {
        $key = (int)$prepare_id;
        if (array_key_exists($key, $this->stok_cache_prepare)) {
            return $this->stok_cache_prepare[$key];
        }

        $row = $this->db
            ->where('prepare_id', (int) $prepare_id)
            ->order_by('tanggal', 'DESC')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('rsp_prepare_stok')
            ->row();

        $stok = (float) ($row->stok_sisa ?? 0);
        $this->stok_cache_prepare[$key] = $stok;
        return $stok;
    }

    /**
     * Hitung stok tersedia produk berdasarkan resep (bahan_baku/base/prepare).
     *
     * Return:
     * - float >= 0: stok estimasi yang tersedia
     *
     * Catatan kompatibilitas:
     * - Dashboard menghitung stok produk dan menganggap produk tanpa resep sebagai stok = 0.
     *   Di member kita samakan supaya label/status stok konsisten.
     */
    private function hitung_stok_tersedia_produk($produk_id, $divisi_id)
    {
        $produk_id = (int) $produk_id;
        $divisi_id = (int) $divisi_id;
        if ($produk_id <= 0) return 0.0;
        if ($divisi_id <= 0) return 0.0;

        $cache_key = $produk_id . '|' . $divisi_id;
        if (array_key_exists($cache_key, $this->stok_cache_produk)) {
            return $this->stok_cache_produk[$cache_key];
        }

        $resep = $this->db->get_where('rsp_produk_resep', ['produk_id' => $produk_id])->row();
        if (!$resep) {
            $this->stok_cache_produk[$cache_key] = 0.0;
            return 0.0;
        }

        $detail = $this->db->get_where('rsp_produk_resep_detail', ['produk_resep_id' => (int) $resep->id])->result();
        if (empty($detail)) {
            $this->stok_cache_produk[$cache_key] = 0.0;
            return 0.0;
        }

        $hasil_jadi = (float) ($resep->hasil_jadi ?? 0);
        if ($hasil_jadi <= 0) $hasil_jadi = 1;

        $min_possible = null;
        foreach ($detail as $row) {
            $stok_sisa = 0;
            if (($row->sumber ?? '') === 'bahan_baku') {
                $stok_sisa = $this->get_latest_stok_bahan_baku((int) ($row->bahan_baku_id ?? 0), $divisi_id);
            } elseif (($row->sumber ?? '') === 'base') {
                $stok_sisa = $this->get_latest_stok_base((int) ($row->base_id ?? 0));
            } elseif (($row->sumber ?? '') === 'prepare') {
                $stok_sisa = $this->get_latest_stok_prepare((int) ($row->prepare_id ?? 0));
            } else {
                // Sumber tidak dikenal, skip agar tidak mematikan stok.
                continue;
            }

            $qty = (float) ($row->qty ?? 0);
            $qty_per_produk = ($hasil_jadi > 0) ? ($qty / $hasil_jadi) : $qty;
            if ($qty_per_produk <= 0) continue;

            $possible = $stok_sisa / $qty_per_produk;
            $min_possible = ($min_possible === null) ? $possible : min($min_possible, $possible);
        }

        $stok = (float) ($min_possible ?? 0);
        // Simpan float, view boleh flooring sesuai kebutuhan.
        $this->stok_cache_produk[$cache_key] = $stok;
        return $stok;
    }

    private function attach_stok_tersedia($rows)
    {
        foreach ((array) $rows as $p) {
            // pr_produk tidak punya pr_divisi_id; ambil dari pr_kategori.pr_divisi_id (hasil join/select).
            $divisi_id = (int) ($p->pr_divisi_id ?? 0);
            $p->stok_tersedia = $this->hitung_stok_tersedia_produk((int) ($p->id ?? 0), $divisi_id);
        }
        return $rows;
    }

    public function get_all() {
        $rows = $this->db
            ->select('pr_produk.*, pr_kategori.pr_divisi_id AS pr_divisi_id')
            ->from('pr_produk')
            ->join('pr_kategori', 'pr_kategori.id = pr_produk.kategori_id', 'left')
            ->where('pr_produk.tampil', 1)
            ->order_by('pr_produk.nama_produk', 'ASC')
            ->get()
            ->result();
        return $this->attach_stok_tersedia($rows);
    }
    public function get_by_kategori($kategori_id = null) {
        $this->db
            ->select('pr_produk.*, pr_kategori.pr_divisi_id AS pr_divisi_id')
            ->from('pr_produk')
            ->join('pr_kategori', 'pr_kategori.id = pr_produk.kategori_id', 'left')
            ->where('pr_produk.tampil', 1);
        if ($kategori_id) {
            $this->db->where('pr_produk.kategori_id', $kategori_id);
        }
        $rows = $this->db->order_by('pr_produk.nama_produk', 'ASC')->get()->result();
        return $this->attach_stok_tersedia($rows);
    }
    
	public function search($keyword = null, $kategori_id = null) {
	    $this->db
	        ->select('pr_produk.*, pr_kategori.pr_divisi_id AS pr_divisi_id')
	        ->from('pr_produk')
	        ->join('pr_kategori', 'pr_kategori.id = pr_produk.kategori_id', 'left')
	        ->where('pr_produk.tampil', 1);
	    if ($kategori_id) {
	        $this->db->where('pr_produk.kategori_id', $kategori_id);
	    }
	    if ($keyword) {
	        $this->db->like('pr_produk.nama_produk', $keyword);
	    }
	    $rows = $this->db->order_by('pr_produk.nama_produk', 'ASC')->get()->result();
	    return $this->attach_stok_tersedia($rows);
	}

}
