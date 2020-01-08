<?php

function is_logged_in()
{
    // memanggil libary instance yg ada di CI
    $ci = get_instance();
    // jika tidak ada session
    if (!$ci->session->userdata('email')) {
        redirect('auth');
    }
    // jika ada session
    else {
        $role_id = $ci->session->userdata('role_id');
        $menu = $ci->uri->segment(1);

        $queryMenu = $ci->db->get_where(
            'user_menu',
            [
                'menu' => $menu
            ]
        )->row_array();
        $menu_id = $queryMenu['id'];
        $userAccess = $ci->db->get_where(
            'user_access_menu',
            [
                'role_id' => $role_id,
                'menu_id' => $menu_id
            ]
        );
        // tidak bisa akses < 1
        if ($userAccess->num_rows() < 1) {
            redirect('auth/blocked');
        }
    }
}

function check_access($role_id, $menu_id)
{
    $ci = get_instance();
    $ci->db->where('role_id', $role_id);
    $ci->db->where('menu_id', $menu_id);
    $result = $ci->db->get('user_access_menu');

    // bisa juga quernya menjadi seperti ini
    // $result = $ci->db->get(
    //     'user_access_menu',
    //     [
    //         ['role_id'] => $role_id,
    //         ['menu_id'] => $menu_id
    //     ]
    // );
    if ($result->num_rows() > 0) {
        # code...
        return " checked = 'checked' ";
    }
}
