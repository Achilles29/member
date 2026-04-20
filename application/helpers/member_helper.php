<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Member Helper Functions
 * 
 * Utility functions untuk aplikasi member
 * 
 * @package    Member Application
 * @category   Helpers
 */

if (!function_exists('format_rupiah')) {
    /**
     * Format number to Rupiah currency
     */
    function format_rupiah($number, $show_prefix = true)
    {
        $formatted = number_format($number, 0, ',', '.');
        return $show_prefix ? 'Rp ' . $formatted : $formatted;
    }
}

if (!function_exists('format_phone')) {
    /**
     * Format phone number
     */
    function format_phone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (substr($phone, 0, 2) === '62') {
            return '0' . substr($phone, 2);
        }
        
        return $phone;
    }
}

if (!function_exists('format_date_indo')) {
    /**
     * Format date to Indonesian format
     */
    function format_date_indo($date, $show_time = false)
    {
        if (!$date || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
            return '-';
        }

        $months = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        $timestamp = strtotime($date);
        $day = date('j', $timestamp);
        $month = $months[(int)date('n', $timestamp)];
        $year = date('Y', $timestamp);

        $formatted = "$day $month $year";

        if ($show_time) {
            $time = date('H:i', $timestamp);
            $formatted .= " pukul $time";
        }

        return $formatted;
    }
}

if (!function_exists('time_ago')) {
    /**
     * Get time ago in Indonesian
     */
    function time_ago($datetime)
    {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'Baru saja';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' menit yang lalu';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' jam yang lalu';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' hari yang lalu';
        } elseif ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return $weeks . ' minggu yang lalu';
        } else {
            return format_date_indo($datetime);
        }
    }
}

if (!function_exists('get_level_badge')) {
    /**
     * Get HTML badge for member level
     */
    function get_level_badge($level)
    {
        $colors = [
            'Diamond' => 'bg-info text-white',
            'Platinum' => 'bg-secondary text-white',
            'Gold' => 'bg-warning text-dark',
            'Silver' => 'bg-light text-dark'
        ];

        $icons = [
            'Diamond' => '💎',
            'Platinum' => '🏆',
            'Gold' => '👑',
            'Silver' => '⭐'
        ];

        $color = $colors[$level] ?? 'bg-light text-dark';
        $icon = $icons[$level] ?? '⭐';

        return "<span class=\"badge {$color} rounded-pill\">{$icon} {$level}</span>";
    }
}

if (!function_exists('get_status_badge')) {
    /**
     * Get HTML badge for status
     */
    function get_status_badge($status)
    {
        $badges = [
            'aktif' => '<span class="badge bg-success">Aktif</span>',
            'AKTIF' => '<span class="badge bg-success">Aktif</span>',
            'nonaktif' => '<span class="badge bg-secondary">Tidak Aktif</span>',
            'pending' => '<span class="badge bg-warning">Pending</span>',
            'digunakan' => '<span class="badge bg-info">Digunakan</span>',
            'DIGUNAKAN' => '<span class="badge bg-info">Digunakan</span>',
            'kadaluarsa' => '<span class="badge bg-danger">Kadaluarsa</span>',
            'KEDALUWARSA' => '<span class="badge bg-danger">Kadaluarsa</span>',
            'selesai' => '<span class="badge bg-success">Selesai</span>',
            'batal' => '<span class="badge bg-danger">Batal</span>',
        ];

        return $badges[strtolower($status)] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }
}

if (!function_exists('sanitize_filename')) {
    /**
     * Sanitize filename
     */
    function sanitize_filename($filename)
    {
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        $filename = preg_replace('/_+/', '_', $filename);
        return trim($filename, '_');
    }
}

if (!function_exists('generate_unique_code')) {
    /**
     * Generate unique code
     */
    function generate_unique_code($prefix = '', $length = 8)
    {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $prefix . $code;
    }
}

if (!function_exists('is_mobile')) {
    /**
     * Check if request from mobile device
     */
    function is_mobile()
    {
        $useragent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        return preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $useragent) 
            || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4));
    }
}

if (!function_exists('truncate_text')) {
    /**
     * Truncate text with ellipsis
     */
    function truncate_text($text, $length = 100, $ellipsis = '...')
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length) . $ellipsis;
    }
}

if (!function_exists('calculate_discount')) {
    /**
     * Calculate discount
     */
    function calculate_discount($original_price, $discount_percent)
    {
        return $original_price - ($original_price * $discount_percent / 100);
    }
}

if (!function_exists('get_discount_amount')) {
    /**
     * Get discount amount
     */
    function get_discount_amount($original_price, $discount_percent)
    {
        return $original_price * $discount_percent / 100;
    }
}

if (!function_exists('get_greeting')) {
    /**
     * Get time-based greeting in Indonesian
     */
    function get_greeting()
    {
        $hour = (int)date('H');

        if ($hour >= 5 && $hour < 11) {
            return 'Selamat Pagi';
        } elseif ($hour >= 11 && $hour < 15) {
            return 'Selamat Siang';
        } elseif ($hour >= 15 && $hour < 18) {
            return 'Selamat Sore';
        } else {
            return 'Selamat Malam';
        }
    }
}

if (!function_exists('array_safe_get')) {
    /**
     * Safely get array value with default
     */
    function array_safe_get($array, $key, $default = null)
    {
        return isset($array[$key]) ? $array[$key] : $default;
    }
}
