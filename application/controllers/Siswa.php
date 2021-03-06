<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Siswa extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        // is_logged_in();
        $this->load->model('Admin_model');
    }

    public function index()
    {
        $data['title'] = 'My Profile';
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        $data['pengumuman'] = $this->Admin_model->Pengumuman();

        $this->load->view('wrapper/header', $data);
        $this->load->view('siswa/layout/sidebar', $data);
        $this->load->view('wrapper/topbar', $data);
        $this->load->view('siswa/index', $data);
        $this->load->view('wrapper/footer');
    }

    public function Profile()
    {
        $data['title'] = 'Edit Profile';
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();

        $this->form_validation->set_rules('nis', 'NIS', 'required|trim');
        $this->form_validation->set_rules('name', 'Nama Lengkap', 'required|trim');
        if ($this->form_validation->run() == false) {
            $this->load->view('wrapper/header', $data);
            $this->load->view('siswa/layout/sidebar', $data);
            $this->load->view('wrapper/topbar', $data);
            $this->load->view('siswa/edit-profile', $data);
            $this->load->view('wrapper/footer');
        } else {
            $nis = $this->input->post('nis');
            $name = $this->input->post('name');
            $email = $this->input->post('email');
            $jk = $this->input->post('jk');

            //cek jika ada gambar
            $upload_image = $_FILES['image']['name'];
            if ($upload_image) {
                $config['allowed_types'] = 'gif|jpg|png';
                $config['max_size']     = '2048';
                $config['upload_path']  = './assets/img/foto';

                $this->load->library('upload', $config);

                if ($this->upload->do_upload('image')) {
                    $old_image = $data['user']['image'];
                    if ($old_image != 'default.png') {
                        unlink(FCPATH . 'assets/img/foto' . $old_image);
                    }

                    $new_image = $this->upload->data('file_name');
                    $this->db->set('image', $new_image);
                } else {
                    echo $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">' . $this->upload->display_errors() . '</div>');
                    redirect('siswa');
                }
            }
            //update user
            $this->db->set('nis', $nis);
            $this->db->set('name', $name);
            $this->db->where('email', $email);
            $this->db->update('user');
            //update master
            $this->db->set('nis', $nis);
            $this->db->set('name', $name);
            $this->db->set('image', $new_image);
            $this->db->where('email', $email);
            $this->db->update('master');

            $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
            Your profile has ben updated!</div>');
            redirect('siswa');
        }
    }

    public function changepassword()
    {
        $data['title'] = 'Change Password';
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();

        $this->form_validation->set_rules('current_password', 'Password lama', 'required|trim');
        $this->form_validation->set_rules('password1', 'Password baru', 'required|trim|min_length[8]|matches[password2]');
        $this->form_validation->set_rules('password2', 'Ulangi password', 'required|trim|min_length[8]|matches[password1]');

        if ($this->form_validation->run() == false) {
            $this->load->view('wrapper/header', $data);
            $this->load->view('siswa/layout/sidebar', $data);
            $this->load->view('wrapper/topbar', $data);
            $this->load->view('siswa/change-password', $data);
            $this->load->view('wrapper/footer');
        } else {
            $current_password = $this->input->post('current_password');
            $new_password = $this->input->post('password1');
            if (!password_verify($current_password, $data['user']['password'])) {
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Password lama salah!!!</div>');
                redirect('siswa/changepassword');
            } else {
                if ($current_password == $new_password) {
                    $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Password baru sama dengan yang lama!!!</div>');
                    redirect('siswa/changepassword');
                } else {
                    //password ok
                    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

                    $this->db->set('password', $password_hash);
                    $this->db->where('email', $this->session->userdata('email'));
                    $this->db->update('user');

                    $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Password baru sama dengan yang lama!!!</div>');
                    redirect('siswa/changepassword');
                }
            }
        }
    }

    public function IDCard($nis)
    {
        $data['title'] = 'ID CARD';
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        $data['siswa'] = $this->db->get_where('master', ['nis' => $nis])->row_array();
        $data['tgl'] = $this->db->get_where('tbl_surat', ['id' => 1])->row_array();

        $this->load->view('wrapper/header', $data);
        $this->load->view('siswa/layout/sidebar', $data);
        $this->load->view('wrapper/topbar', $data);
        $this->load->view('siswa/id-card', $data);
        $this->load->view('wrapper/footer');

        $mpdf = new \Mpdf\Mpdf(
            [
                'mode' => 'utf-8',
                'format' => 'A4',
                'setAutoTopMargin' => false
            ]
        );

        $html = $this->load->view('siswa/id-card', [], true);
        $mpdf->WriteHTML($html);
        $mpdf->Output('ID-CARD.pdf', \Mpdf\Output\Destination::INLINE);
    }

    public function data($nis)
    {
        $data['title'] = 'Data';
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        $data['siswa'] = $this->db->get_where('master', ['nis' => $nis])->row_array();
        $data['data'] = $this->db->get_where('tbl_iduka', ['jurusan' => 'TKRO'])->result_array();
        $data['tp'] = $this->Admin_model->getTP();
        $data['kelas'] = $this->Admin_model->getKelas();
        $data['iduka'] = $this->db->get_where('tbl_jurusan')->result_array();
        $data['guru'] = $this->Admin_model->Guru();
        $this->form_validation->set_rules('nis', 'NIS', 'required|trim');
        $this->form_validation->set_rules('name', 'Nama Lengkap', 'required|trim');
        if ($this->form_validation->run() == false) {
            $this->load->view('wrapper/header', $data);
            $this->load->view('siswa/layout/sidebar', $data);
            $this->load->view('wrapper/topbar', $data);
            $this->load->view('siswa/edit-data', $data);
            $this->load->view('wrapper/footer');
        } else {
            $data = [
                'tp' => $this->input->post('tp'),
                'name' => $this->input->post('name'),
                'jk' => $this->input->post('jk'),
                'kelas' => $this->input->post('kelas'),
                'jurusan' => $this->input->post('jurusan'),
                'guru_pendamping' => $this->input->post('guru_pendamping'),
                'hp_pendamping' => $this->input->post('hp_pendamping'),
                'email_pendamping' => $this->input->post('email_pendamping'),
                'nama_instansi' => $this->input->post('nama_instansi'),
                'alamat_instansi' => $this->input->post('alamat_instansi'),
                'email_website_instansi' => $this->input->post('email_website_instansi'),
                'telp_instansi' => $this->input->post('telp_instansi'),
                'nama_pejabat' => $this->input->post('nama_pejabat'),
                'no_pejabat' => $this->input->post('no_pejabat'),
                'jabatan' => $this->input->post('jabatan'),
                'telp_pejabat' => $this->input->post('telp_pejabat'),
                'email_pejabat' => $this->input->post('email_pejabat'),
                'no_sertifikat' => $this->input->post('no_sertifikat'),
                'jurusan' => $this->input->post('jurusan'),
                'verifikasi' => 'Telah Verifikasi'
            ];
            $this->db->where('nis', $this->input->post('nis'));
            $this->db->update('master', $data);
            $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
            Data berhasil diverifikasi!!!</div>');
            redirect('siswa');
        }
    }
    public function surat($nis)
    {
        $data['title'] = 'Surat Balasan';
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        $data['pengumuman'] = $this->Admin_model->Pengumuman();
        $data['siswa'] = $this->db->get_where('master', ['nis' => $nis])->row_array();

        $this->form_validation->set_rules('nis', 'NIS', 'required|trim');
        $this->form_validation->set_rules('status', 'Status', 'required|trim');
        // $this->form_validation->set_rules('file', 'Foto', 'required|trim');
        if ($this->form_validation->run() == false) {
            $this->load->view('wrapper/header', $data);
            $this->load->view('siswa/layout/sidebar', $data);
            $this->load->view('wrapper/topbar', $data);
            $this->load->view('siswa/surat-balasan', $data);
            $this->load->view('wrapper/footer');
        } else {
            $upload_image = $_FILES['file']['name'];
            if ($upload_image) {
                $config['allowed_types'] = 'gif|jpg|png|jpeg|pdf|PNG|JPG|JPEG';
                $config['max_size']     = '10024';
                $config['upload_path']  = './assets/img/surat balasan';

                $this->load->library('upload', $config);
                if ($_FILES['file']['name'] != null) {
                    if ($this->upload->do_upload('file')) {
                        $nis = $this->input->post('nis');
                        $status = $this->input->post('status');
                        $foto = $this->upload->data('file_name');
                        $data = array(
                            'status' => $status,
                            'file' => $foto
                        );
                        //update
                        $this->db->where('nis', $nis);
                        $this->db->update('master', $data);
                        $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Surat balasan berhasil ditambahkan!</div>');
                        redirect('siswa/surat/' . $nis);
                    } else {
                        $error = array('error' => $this->upload->display_errors());
                        $this->load->view('wrapper/header', $data);
                        $this->load->view('siswa/layout/sidebar', $data);
                        $this->load->view('wrapper/topbar', $data);
                        $this->load->view('siswa/surat-balasan', $error);
                        $this->load->view('wrapper/footer');
                    }
                }
            }
        }
    }

    public function laporan($nis)
    {
        $data['title'] = 'Laporan Kegiatan PKL';
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        $data['jurusan'] = $this->Admin_model->getJurusan();
        $jurusan = $this->input->get('jurusan');
        $data['laporan'] = $this->db->get_where('tbl_laporan', ['nis' => $nis, 'jurusan' => $jurusan])->result_array();
        $data['t1'] = $this->db->get_where('tbl_tabel_laporan', ['kelompok' => $jurusan, 'id_tabel' => 1])->row_array();
        $data['t2'] = $this->db->get_where('tbl_tabel_laporan', ['kelompok' => $jurusan, 'id_tabel' => 2])->row_array();
        $data['t3'] = $this->db->get_where('tbl_tabel_laporan', ['kelompok' => $jurusan, 'id_tabel' => 3])->row_array();
        $data['t4'] = $this->db->get_where('tbl_tabel_laporan', ['kelompok' => $jurusan, 'id_tabel' => 4])->row_array();

        $this->load->view('wrapper/header', $data);
        $this->load->view('siswa/layout/sidebar', $data);
        $this->load->view('wrapper/topbar', $data);
        $this->load->view('siswa/laporan', $data);
        $this->load->view('wrapper/footer');
    }
    public function inputlaporan($nis)
    {
        $data['title'] = 'Input Laporan Kegiatan PKL';
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        $data['jurusan'] = $this->Admin_model->getJurusan();
        $jurusan = $this->input->get('jurusan');
        $data['tabel'] = $this->db->get_where('tbl_tabel_laporan', ['kelompok' => $jurusan])->result_array();
        $data['data'] = $this->db->get_where('master', ['nis' => $nis])->row_array();
        $data['t2'] = $this->db->get_where('tbl_tabel_laporan', ['kelompok' => $jurusan, 'id_tabel' => 2])->row_array();
        $data['t3'] = $this->db->get_where('tbl_tabel_laporan', ['kelompok' => $jurusan, 'id_tabel' => 3])->row_array();
        $data['t4'] = $this->db->get_where('tbl_tabel_laporan', ['kelompok' => $jurusan, 'id_tabel' => 4])->row_array();

        $this->form_validation->set_rules('laporan1', 'Ini', 'required|trim');
        $this->form_validation->set_rules('laporan2', 'Ini', 'required|trim');
        $this->form_validation->set_rules('jurusan', 'jurusan', 'required|trim');
        if ($this->form_validation->run() == false) {
            $this->load->view('wrapper/header', $data);
            $this->load->view('siswa/layout/sidebar', $data);
            $this->load->view('wrapper/topbar', $data);
            $this->load->view('siswa/input-laporan', $data);
            $this->load->view('wrapper/footer');
        } else {

            $upload_image = $_FILES['foto']['name'];
            if ($upload_image) {
                $config['allowed_types'] = 'gif|jpg|png|jpeg';
                $config['max_size']     = '5012';
                $config['upload_path']  = './assets/img/gambar';

                $this->load->library('upload', $config);

                if ($this->upload->do_upload('foto')) {
                    $old_image = $data['tbl_laporan']['foto'];
                    if ($old_image != 'default.png') {
                        unlink(FCPATH . 'assets/img/foto/gambar' . $old_image);
                    }

                    $new_image = $this->upload->data('file_name');
                    $this->db->set('foto', $new_image);
                } else {
                    echo $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">' . $this->upload->display_errors() . '</div>');
                    redirect('siswa');
                }
            }

            $data = [
                'nis' => $this->input->post('nis'),
                'nama_siswa' => $this->input->post('name'),
                'laporan1' => $this->input->post('laporan1'),
                'laporan2' => $this->input->post('laporan1'),
                'jurusan' => $this->input->post('jurusan'),
                'guru_pendamping' => $this->input->post('guru_pendamping'),
                'tp' => $this->input->post('tp'),
                'kelas' => $this->input->post('kelas')
            ];

            $this->db->insert('tbl_laporan', $data);
            $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
        Laporan berhasil ditambahkan!!!</div>');
            redirect('siswa');
        }
    }

    public function editlaporan($id)
    {
        $data['title'] = 'Edit Laporan';
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        $data['data'] = $this->db->get_where('tbl_laporan', ['id' => $id])->row_array();
        $data['jurusan'] = $this->Admin_model->getJurusan();

        $this->form_validation->set_rules('laporan1', 'Ini', 'required|trim');
        $this->form_validation->set_rules('laporan2', 'Ini', 'required|trim');
        $this->form_validation->set_rules('jurusan', 'jurusan', 'required|trim');
        if ($this->form_validation->run() == false) {
            $this->load->view('wrapper/header', $data);
            $this->load->view('siswa/layout/sidebar', $data);
            $this->load->view('wrapper/topbar', $data);
            $this->load->view('siswa/edit-laporan', $data);
            $this->load->view('wrapper/footer');
        } else {
            $config['allowed_types'] = 'gif|jpg|png';
            $config['max_size']     = '2048';
            $config['upload_path']  = './assets/img/gambar';

            $this->load->library('upload', $config);
            if ($_FILES['foto']['name'] != null) {
                if ($this->upload->do_upload('foto')) {
                    $laporan1 = $this->input->post('laporan1');
                    $laporan2 = $this->input->post('laporan2');
                    $foto = $this->upload->data('file_name');
                    $data = array(
                        'laporan1' => $laporan1,
                        'laporan2' => $laporan2,
                        'foto' => $foto
                    );
                    //update
                    $this->db->where('id', $id);
                    $this->db->update('tbl_laporan', $data);

                    $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
        Your profile has ben updated!</div>');
                    redirect('siswa');
                } else {
                    $error = array('error' => $this->upload->display_errors());
                    $this->load->view('wrapper/header', $data);
                    $this->load->view('pendamping/sidebar', $data);
                    $this->load->view('wrapper/topbar', $data);
                    $this->load->view('siswa/laporan', $error);
                    $this->load->view('wrapper/footer');
                }
            }
        }
    }
}
