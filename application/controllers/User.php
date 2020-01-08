<?php

use PhpParser\Node\Stmt\Else_;

defined('BASEPATH') or exit('No direct script access allowed');

class User extends CI_Controller
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
        $data['title'] = 'My Profile';
        // ambil data dari session. berdasarkan email yg ada disession.
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        // echo 'Selamat datang ' . $data['user']['name'];
        // sekarang memanggil view untuk user
        $this->load->view('templates/header', $data); // memanggil user
        $this->load->view('templates/sidebar', $data); // memanggil user
        $this->load->view('templates/topbar', $data); // memanggil user
        $this->load->view('user/index', $data); // memanggil user
        $this->load->view('templates/footer'); // memanggil user
    }
    public function edit()
    {
        $data['title'] = "Edit Profile";
        $data['user'] = $this->db->get_where(
            'user',
            [
                'email' => $this->session->userdata('email')
            ]
        )->row_array();
        $this->form_validation->set_rules('name', 'Full Name', 'required|trim');
        if ($this->form_validation->run() == false) {
            $this->load->view('templates/header', $data);
            $this->load->view('templates/sidebar', $data);
            $this->load->view('templates/topbar', $data);
            $this->load->view('user/edit', $data);
            $this->load->view('templates/footer');
        } else {
            # ngambil data nama dan email
            $name = $this->input->post('name');
            $email = $this->input->post('email');
            # cek jika ada gambar yg diupload
            $upload_image = $_FILES['image']['name'];
            // jika ada upload_image
            if ($upload_image) {
                # beberapa pengaturan pada CI image
                $config['allowed_types'] = 'gif|jpg|png';
                $config['max_size']      = '2048';
                $config['upload_path']   = './assets/img/profile/'; # gambar akan ditaro didalam folder ini.
                $this->load->library('upload', $config);
                # untuk upload
                if ($this->upload->do_upload('image')) {
                    # jika ganti foto, maka foto sebelumnya akan terhapus, kecuali foto defaultnya.
                    $old_image = $data['user']['image'];
                    if ($old_image != 'default.jpg') {
                        # foto sebelumnya dihapus
                        unlink(FCPATH . 'assets/img/profile/' . $old_image);
                    } # FCPATH untuk menemukan lokasi file projek. Front Controller PATH
                    $new_image = $this->upload->data('file_name');
                    $this->db->set('image', $new_image);
                }
                # jika gagal upload 
                else {
                    echo $this->upload->display_errors();
                }
            }

            // query update
            $this->db->set('name', $name);
            $this->db->where('email', $email);
            $this->db->update('user');
            // flashmessage
            $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
                 Your profile has been updated!
               </div>');
            redirect('user');
        }
    }

    public function changePassword()
    {
        $data['title'] = 'Change Password';
        // ambil data dari session. berdasarkan email yg ada disession.
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        // set_rules
        $this->form_validation->set_rules('current_password', 'Current Password', 'required|trim');
        $this->form_validation->set_rules('new_password1', 'New Password', 'required|trim|min_length[3]|matches[new_password2]');
        $this->form_validation->set_rules('new_password2', 'New Password', 'required|trim|min_length[3]|matches[new_password1]');
        if ($this->form_validation->run() == false) {
            // templates
            $this->load->view('templates/header', $data); // memanggil user
            $this->load->view('templates/sidebar', $data); // memanggil user
            $this->load->view('templates/topbar', $data); // memanggil user
            $this->load->view('user/changepassword', $data); // memanggil user
            $this->load->view('templates/footer'); // memanggil user
        } else {
            $current_password = $this->input->post('current_password');
            $new_password = $this->input->post('new_password1');
            if (!password_verify($current_password, $data['user']['password'])) {
                // flashmessage
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                Wrong Current Password!</div>');
                redirect('user/changepassword');
            } else {
                // cek dulu apakah password baru = password lama
                if ($current_password == $new_password) {
                    // flashmessage
                    $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                    New Passowrd cannot be the same as current password!</div>');
                    redirect('user/changepassword');
                } else {
                    // password sudah ok
                    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $this->db->set('password', $password_hash);
                    $this->db->where('email', $this->session->userdata['email']);
                    $this->db->update('user');
                    // flashmessage
                    $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
                    Password Changed!</div>');
                    redirect('user/changepassword');
                }
            }
        }
    }
}
