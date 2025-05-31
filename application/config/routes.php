<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'member';

$route['admin'] = 'admin/login';
$route['customer/(:num)'] = 'Customer/show/$1';
$route['admin/members'] = 'Admin/members'; // Halaman daftar member
$route['admin/stamps/(:num)'] = 'Admin/stamps/$1'; // Halaman stamp per member
$route['admin/add_stamp/(:num)'] = 'Admin/add_stamp/$1'; // Tambah stamp
$route['admin/remove_stamp/(:num)'] = 'Admin/remove_stamp/$1'; // Kurang stamp
$route['admin/reset_stamp/(:num)'] = 'Admin/reset_stamp/$1'; // Reset stamp
$route['admin/search_members'] = 'Admin/search_members';
$route['admin/add_member'] = 'Admin/add_member';
$route['admin/save_member'] = 'Admin/save_member';
$route['admin/edit_member/(:num)'] = 'Admin/edit_member/$1';
$route['admin/update_member/(:num)'] = 'Admin/update_member/$1';
$route['admin/delete_member/(:num)'] = 'Admin/delete_member/$1';
$route['admin/delete_transaction/(:num)'] = 'Admin/delete_transaction/$1';
$route['member/get_stamps/(:num)'] = 'Member/get_stamps/$1';
$route['admin/member_card/(:num)'] = 'admin/member_card/$1';
$route['admin/add_voucher/(:num)'] = 'admin/add_voucher/$1';
$route['member/get_voucher/(:num)'] = 'member/get_voucher/$1';
$route['voucher'] = 'voucher';
$route['voucher/add'] = 'voucher/add';
$route['voucher/edit/(:num)'] = 'voucher/edit/$1';
$route['voucher/delete/(:num)'] = 'voucher/delete/$1';


$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
