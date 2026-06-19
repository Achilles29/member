<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once BASEPATH . 'helpers/url_helper.php';
/**
 * CodeIgniter URL Helpers
 *
 * @package     CodeIgniter
 * @subpackage  Helpers
 * @category    Helpers
 * @author      EllisLab Dev Team (dengan tambahan dari Namua Dev)
 */

/**
 * Dashboard URL
 *
 * Menghasilkan URL lengkap ke dashboard untuk file atau gambar
 * Contoh: dashboard_url('uploads/promo1.jpg') ->
 *         https://dashboard.namuacoffee.com/uploads/promo1.jpg
 */
if (!function_exists('dashboard_url')) {
    function dashboard_url($path = '')
    {
        $path = is_string($path) ? $path : '';
        return 'https://dashboard.namuacoffee.com/' . ltrim($path, '/');
    }
}

/**
 * Product Photo URL
 *
 * photo_path di DB menyimpan full relative path, mis: uploads/produk/abc.jpg
 * Dev (localhost): gunakan URL finance lokal
 * Prod: gunakan https://core.namuacoffee.com/
 */
if (!function_exists('product_url')) {
    function product_url($path = '')
    {
        $path = is_string($path) ? ltrim((string) $path, '/') : '';
        if (!$path) return '';
        // Sudah absolute URL, kembalikan apa adanya
        if (preg_match('/^https?:\/\//', $path)) return $path;

        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $is_local = (
            strpos($host, 'localhost') !== false
            || strpos($host, '127.0.0.1') !== false
            || strpos($host, '::1') !== false
        );

        if ($is_local) {
            // Finance app berjalan di /finance/ di server yang sama
            $finance_base = rtrim(str_replace('/member/', '/finance/', rtrim(base_url(), '/')), '/');
            return $finance_base . '/' . $path;
        }

        return 'https://core.namuacoffee.com/' . $path;
    }
}


/**
 * Base URL
 * Wrapper dari get_instance()->config->base_url()
 */
if (!function_exists('base_url')) {
    function base_url($uri = '', $protocol = null)
    {
        $CI = & get_instance();
        return $CI->config->base_url($uri, $protocol);
    }
}

/**
 * Site URL
 * Wrapper dari get_instance()->config->site_url()
 */
if (!function_exists('site_url')) {
    function site_url($uri = '', $protocol = null)
    {
        $CI = & get_instance();
        return $CI->config->site_url($uri, $protocol);
    }
}

/**
 * Current URL
 */
if (!function_exists('current_url')) {
    function current_url()
    {
        $CI = & get_instance();
        return $CI->config->site_url($CI->uri->uri_string());
    }
}

/**
 * Uri String
 */
if (!function_exists('uri_string')) {
    function uri_string()
    {
        $CI = & get_instance();
        return $CI->uri->uri_string();
    }
}
