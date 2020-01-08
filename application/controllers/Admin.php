<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Admin extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        // if (!$this->session->userdata('email')) {
        //     redirect('auth');
        // }
        // karena ketika ada sessionnya, maka masih bisa akses, sekarang tinggal kita rubah menjadi
        is_logged_in();
    }
    public function index()
    {
        $data['title'] = 'Dashboard';
        // ambil data dari session. berdasarkan email yg ada disession.
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        // echo 'Selamat datang ' . $data['user']['name'];
        // sekarang memanggil view untuk user
        $this->load->view('templates/header', $data); // memanggil user
        $this->load->view('templates/sidebar', $data); // memanggil user
        $this->load->view('templates/topbar', $data); // memanggil user
        $this->load->view('admin/index', $data); // memanggil user
        $this->load->view('templates/footer'); // memanggil user
    }
    public function role() // menampilkan semua role
    {
        $data['title'] = 'Role';
        // ambil data dari session. berdasarkan email yg ada disession.
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();

        $data['role'] = $this->db->get('user_role')->result_array(); // result_array digunakan untuk memilih semuanya.

        // echo 'Selamat datang ' . $data['user']['name'];
        // sekarang memanggil view untuk user
        $this->load->view('templates/header', $data); // memanggil user
        $this->load->view('templates/sidebar', $data); // memanggil user
        $this->load->view('templates/topbar', $data); // memanggil user
        $this->load->view('admin/role', $data); // memanggil user
        $this->load->view('templates/footer'); // memanggil user
    }
    public function roleAccess($role_id) // hanya menampilkan role yg idnya adalah ini
    {
        $data['title'] = 'Role Access';
        // ambil data dari session. berdasarkan email yg ada disession.
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();

        $data['role'] = $this->db->get_where('user_role', ['id' => $role_id])->row_array(); // result_array digunakan untuk memilih semuanya.]

        $this->db->where('id != ', 1);
        $data['menu'] = $this->db->get('user_menu')->result_array();

        // echo 'Selamat datang ' . $data['user']['name'];
        // sekarang memanggil view untuk user
        $this->load->view('templates/header', $data); // memanggil user
        $this->load->view('templates/sidebar', $data); // memanggil user
        $this->load->view('templates/topbar', $data); // memanggil user
        $this->load->view('admin/role-access', $data); // memanggil user
        $this->load->view('templates/footer'); // memanggil user
    }
    public function changeaccess()
    {
        // mengambil data dari ajax
        $menu_id = $this->input->post('menuId');
        $role_id = $this->input->post('roleId');
        // siapkan data untuk memanggil querynya.
        $data = [
            'role_id' => $role_id,
            'menu_id' => $menu_id
        ];
        // query ke dbnya
        $result = $this->db->get_where('user_access_menu', $data);
        // jika tidak ada, maka tidak diceklits
        if ($result->num_rows() < 1) {
            // input cekboxnya
            $this->db->insert('user_access_menu', $data);
        }
        // kalo daa, hapus
        else {
            $this->db->delete('user_access_menu', $data);
        }
        // meeninggalkan pesan
        $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
        Access Changed</div>');
    }
}
