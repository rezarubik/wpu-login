<?php

use PhpParser\Node\Stmt\Echo_;
use phpDocumentor\Reflection\Types\This;

defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct(); // memanggil method concstruct yg ada di CI_Controller
        $this->load->library('form_validation'); // memanggil libary form_validation
    }
    // membuat method default. method untuk login
    public function index()
    {
        // jika ada session email, atau sudah login, maka tidak dapat mengakses kembali ke menu login/registrasi
        if ($this->session->userdata('email')) {
            redirect('user');
        }
        // set_rules
        $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email');
        $this->form_validation->set_rules('password', 'Password', 'required|trim');
        $this->form_validation->set_rules('password', 'Password', 'required|trim|min_length[3]', [
            'min_length' => 'Password too Short!'
        ]);
        // validasi login
        if ($this->form_validation->run() == false) {
            // untuk menjalankan fitur login. Panggil view login. Jika login gagal.
            $data['title'] = 'User Login';
            $this->load->view('templates/auth_header', $data);
            $this->load->view('auth/login');
            $this->load->view('templates/auth_footer');
        } else {
            // validasi success. kita jalankan fungsi login.
            $this->_login(); // bikin method login dicontroller ini dengan visibilty private (_private)
        }
    }
    private function _login()
    {
        // menangkap atribute untuk menangkap inputan
        $email = $this->input->post('email');
        $password = $this->input->post('password');
        // query ke db, cari user yg emailnya sesui dengan yg ditulis di login. ambil data user lalu diquery
        // select * from user where email = $email
        $user = $this->db->get_where('user', ['email' => $email])->row_array();
        // jika user ada
        if ($user) {
            // jika usernya active
            if ($user['is_active'] == 1) {
                // cek password
                if (password_verify($password, $user['password'])) { // password_verify untuk menyamakan pass yg diketikan diform dengan pass yg sudah dihash
                    // jika sama maka berhasil loginnya
                    // siapin data disession supaya dapat dihalaman user
                    $data = [
                        'email' => $user['email'],
                        'role_id' => $user['role_id']
                    ];
                    // kita simpan kedalam session
                    $this->session->set_userdata($data);
                    // nanti bisa diarahkan ke user atau admin tergantung rulesnya.
                    // conditional roles
                    if ($user['role_id'] == 1) {
                        redirect('admin');
                    } else {
                        redirect('user');
                    }
                } else {
                    // jika gagal
                    // flashmessage
                    $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                    Wrong password!</div>');
                    redirect('auth');
                }
            } else {
                // flashmessage
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                This Email has not been activated</div>');
                redirect('auth');
            }
        } else {
            // tidak ada user dengan email itu, langsung gagalkan saja dan kasih pesan error.
            // flashmessage
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
            Email is not registered!</div>');
            redirect('auth');
        }
    }
    public function registration()
    {
        // jika ada session email, atau sudah login, maka tidak dapat mengakses kembali ke menu login/registrasi
        if ($this->session->userdata('email')) {
            redirect('user');
        }

        // aturan atau roles ke tiap2 kolom inputan
        // aturan untuk full name. Yaitu gak boleh ada yg kosong, dan jika ada spasi diawal dan diakhir dan tidak masuk kedalam database, maka diberi |trim. set_rules('atribute_input', 'nama_alias', 'required'). set_rules digunakan untuk setting rulesnya.
        $this->form_validation->set_rules('name', 'Name', 'required|trim');
        // untuk email. is_unique[namaTable.namaKolom] |is_unique[user.email]
        $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|is_unique[user.email]', [
            'is_unique' => 'This Email has already registred'
        ]);
        // untuk password1
        $this->form_validation->set_rules('password1', 'Password', 'required|trim|min_length[3]|matches[password2]', [
            'matches' => 'Password Dont Match!',
            'min_length' => 'Password too Short!'
        ]);
        // untuk password2
        $this->form_validation->set_rules('password2', 'Password', 'required|trim|matches[password1]');
        // apabila form validation gagal, maka tampilkan form registrasi
        if ($this->form_validation->run() == false) {
            $data['title'] = 'User Registration';
            $this->load->view('templates/auth_header', $data);
            $this->load->view('auth/registration');
            $this->load->view('templates/auth_footer');
        } else { // jika berhasil maka akan ada pesan atau masuk ke database
            $email = $this->input->post('email', true);
            $data = [
                'name' => htmlspecialchars($this->input->post('name', true)), // untuk menghindari cross side scripting
                'email' => htmlspecialchars($email),
                'image' => 'default.jpg',
                'password' => password_hash(
                    $this->input->post('password1'),
                    PASSWORD_DEFAULT // dipilihin yg terbaik oleh phpnya.
                ),
                'role_id' => 2, // nilai default role_id adalah 2 = member
                'is_active' => 0, // otomatis active. Sementara sebelum user activation
                'date_created' => time()
            ];
            // siapkan token berupa bilangan random
            $token = base64_encode(random_bytes(32)); // base64_encode() untuk menterjemahkan random_bytes menjadi angka, huruf, atau karakter
            // table baru, table sementara
            $user_token = [
                'email' => $email,
                'token' => $token,
                'date_created' => time() // untuk memberikan limit waktu verifikasi
            ];
            // sekarang insert ke database
            $this->db->insert('user', $data); // setelah berhasil diinsert, kita tinggal pindahin ke index
            $this->db->insert('user_token', $user_token); // insert ke table sementara user_token
            // kirim email ke user yg sudah registrasi.
            // kalo method pake _ , access modifiernya adalah private
            $this->_sendEmail($token, 'verify');
            // flashmessage
            $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
            Congragulation Your Account Has Beesn Created. Please Activated Your Account!
          </div>');
            redirect('auth');
        }
    }
    // untuk send email konfirmasi pendaftaran
    private function _sendEmail($token, $type)
    {
        // beberapa config untuk mengirim email
        $config = [
            'protocol'  => 'smtp',
            'smtp_host' => 'ssl://smtp.googlemail.com',
            'smtp_user' => 'reza.pahlevi.oa@gmail.com',
            'smtp_pass' => 'reza17always',
            'smtp_port' => 465, // port smtp google
            'mailtype'  => 'html',
            'charset'   => 'utf-8',
            'newline'   => "\r\n"
        ];
        // sekarang kita panggil library emeailnya dengan parameter config diatas
        $this->load->library('email', $config);
        $this->email->initialize($config);
        // email from, to, subject, body
        $this->email->from('reza.pahlevi.oa@gmail.com', 'Muhammad Reza Pahlevi Y');
        $this->email->to($this->input->post('email')); // nanti diisi dari inputan register
        if ($type == 'verify') {
            $this->email->subject('Account Verification');
            $this->email->message('Click this link to verify your account: <a href="' . base_url() . 'auth/verify?email=' . $this->input->post('email') . '&token=' . urlencode($token) . '">Activate</a>');
        } else if ($type == 'forgot') {
            # code... untuk forgot password
            $this->email->subject('Reset Password');
            $this->email->message('Click this link to reset your password: <a href="' . base_url() . 'auth/resetpassword?email=' . $this->input->post('email') . '&token=' . urlencode($token) . '">Reset Password</a>');
        }
        // send email
        // if email terkirim
        if ($this->email->send()) {
            return true;
        } else {
            echo $this->email->print_debugger();
            die;
        }
    }
    // untuk verify link yg dikirim. Datanya bener gak ada di database
    public function verify()
    {
        // ambil email dan token yg ada di link url
        $email = $this->input->get('email');
        $token = $this->input->get('token');
        // ambil user berdasarkan emailnya. Pastikan email valid.
        $user = $this->db->get_where('user', ['email' => $email])->row_array();
        // kalau ada user
        if ($user) {
            // cek ada gak tokennya didatabase
            $user_token = $this->db->get_where('user_token', ['token' => $token])->row_array();
            // jika ada user token
            if ($user_token) {
                // waktu validasi
                if (time() - $user_token['date_created'] < (60 * 60 * 24)) {
                    // update table user
                    $this->db->set('is_active', 1);
                    $this->db->where('email', $email);
                    $this->db->update('user');
                    // delete user token
                    $this->db->delete('user_token', ['email' => $email]);
                    $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
                    ' . $email . ' has been activated! Please login.</div>');
                    redirect('auth');
                } else { // jika lebih dari sehari, maka token expired
                    // jika sudah expired, maka user dan token dihapus dari database
                    $this->db->delete('user', ['email' => $email]);
                    $this->db->delete('user_token', ['email' => $email]);
                    $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                    Account Activation Failed! Token Expired.</div>');
                    redirect('auth');
                }
            } else { // jika tidak ada token
                // flashmessage
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                Account Activation Failed! Wrong Token.</div>');
                redirect('auth');
            }
        } // jika gak ada
        else {
            // flashmessage
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
            Account Activation Failed! Wrong Email.</div>');
            redirect('auth');
        }
    }

    public function logout()
    {
        // membersihkan session sambil mengembalikan ke halaman login
        $this->session->unset_userdata('email');
        $this->session->unset_userdata('role_id');
        // flashmessage
        $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
        You have been logout</div>');
        redirect('auth');
    }
    public function blocked()
    {
        $this->load->view('auth/blocked');
    }
    public function forgotPassword()
    {
        // set rules
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
        if ($this->form_validation->run() == false) {
            $data['title'] = 'Forgot Password';
            $this->load->view('templates/auth_header', $data);
            $this->load->view('auth/forgot-password', $data);
            $this->load->view('templates/auth_footer', $data);
        } else { // kalau berhasil
            # ambil dulu email yg ada diform
            $email = $this->input->post('email');
            # cek ada emailnya gak di db
            $user = $this->db->get_where('user', ['email' => $email, 'is_active' => 1])->row_array(); # ambil satu baris
            // kalo ada isinya, bikin token baru, mirip aktivasi
            if ($user) {
                # code...
                $token = base64_encode(random_bytes(32));
                # untuk diinsert ke table user token
                $user_token = [
                    'email' => $email,
                    'token' => $token,
                    'date_created' => time() # waktu saat ini
                ];
                $this->db->insert('user_token', $user_token);
                # email email
                $this->_sendEmail($token, 'forgot');
                # kasih pesan
                $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
                Please check your email to reset your password!</div>');
                redirect('auth/forgotPassword');
            }
            // kalo gak ada, berarti usernya gak terdaftar .
            else {
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                Email is not registered or activated!</div>');
                redirect('auth/forgotPassword');
            }
        }
    }

    # untuk cek link yg dikirim valid gak
    public function resetPassword()
    {
        #ambil email dan token
        $email = $this->input->get('email');
        $token = $this->input->get('token');
        # ada gak user yg emailnya ada di db
        $user = $this->db->get_where('user', ['email' => $email])->row_array(); # ambil sebaris
        # kita cek kalo ada kita ambil tokennya.
        if ($user) {
            # jika user ada, cek tokennya
            $user_token = $this->db->get_where('user_token', ['token' => $token])->row_array();
            #  siapa tau tokennya salah, dibuat sendiir
            # kalo ada isinya ngapain
            if ($user_token) {
                $this->session->set_userdata('reset_email', $email);
                # panggil method baru
                $this->changePassword();
            } else { # kalo gak ada isinya ngapain
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                Reset password failed! Wrong Token!</div>');
                redirect('auth');
            }
        } else {
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
            Reset password failed! Wrong Email!</div>');
            redirect('auth');
        }
    }

    public function changePassword()
    {
        #tidak ada session
        if (!$this->session->userdata('reset_email')) {
            redirect('auth');
        }
        # rules
        $this->form_validation->set_rules('password1', 'Password', 'trim|required|min_length[3]|matches[password2]');
        $this->form_validation->set_rules('password2', 'Repeat Password', 'trim|required|min_length[3]|matches[password1]');
        # validation
        if ($this->form_validation->run() == false) {
            # mirip forgot passoword
            $data['title'] = 'Change Password';
            $this->load->view('templates/auth_header', $data);
            $this->load->view('auth/change-password', $data);
            $this->load->view('templates/auth_footer', $data);
        } else {
            # kalo berhasil
            # enkripsi pass dulu
            $password = password_hash($this->input->post('password1'), PASSWORD_DEFAULT);
            $email = $this->session->userdata('reset_email');
            $this->db->set('password', $password);
            $this->db->where('email', $email);
            $this->db->update('user');
            # hapus session dulu, unset
            $this->session->unset_userdata('reset_email');
            $this->db->delete('user_token', ['email' => $email]);
            $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
            Password has been change. Please login!</div>');
            redirect('auth');
        }
    }
}
