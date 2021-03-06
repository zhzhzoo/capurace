<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin extends CI_Controller {

	/**
     * Admin interface for capurace
     * view, change and export registration info.
	 *
	 * 
	 * 		http://example.com/index.php/a
	 *	- or -  
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 */

    function __construct() {
        parent::__construct();
        $this->load->helper('url');
        $this->load->library('session');
        $this->tables = array(
            'people' => array(
                'key' => array(
                    'type' => 'text'
                ),
                'name' => array(
                    'type' => 'varchar',
                    'len' => 10
                ),
                'gender' => array(
                    'type' => 'enum',
                    'enum' => $GLOBALS['GENDER']
                ),
                'id_card' => array(
                    'type' => 'varchar',
                    'len' => 18
                ),
                'school_id' => array(
                    'type' => 'foreignkey',
                    'references' => 'user',
                    'display_column' => 'school'
                ),
                'accommodation' => array(
                    'type' => 'enum',
                    'enum' => $GLOBALS['ACCOMMODATION']
                ),
                'meal16' => array(
                    'type' => 'boolean'
                ),
                'meal17' => array(
                    'type' => 'boolean',
                ),
                'race' => array(
                    'type' => 'enum',
                    'enum' => $GLOBALS['RACE']
                ),
                'shimano16' => array(
                    'type' => 'enum',
                    'enum' => $GLOBALS['SHIMANO_MTB']
                ),
                'shimano17' => array(
                    'type' => 'enum',
                    'enum' => $GLOBALS['SHIMANO_RDB']
                ),
                'ifrace' => array(
                    'type' => 'boolean'
                ),
                'ifteam' => array(
                    'type' => 'boolean'
                ),
                'tel' => array(
                    'type' => 'varchar',
                    'len' => 11
                ),
                'islam' => array(
                    'type' => 'boolean'
                )
                //,
                //'team_id' => array(
                    //'type' => 'foreignkey',
                    //'references' => 'team',
                    //'nullable' => true
                //)
            ),
            /*'team' => array(
                'order' => array(
                    'type' => 'int'
                ),
                'first' => array(
                    'type' => 'foreignkey',
                    'references' => 'people',
                    'join_alias' => 'first',
                    'display_column' => 'name'
                ),
                'second' => array(
                    'type' => 'foreignkey',
                    'references' => 'people',
                    'join_alias' => 'second',
                    'display_column' => 'name'
                ),
                'third' => array(
                    'type' => 'foreignkey',
                    'references' => 'people',
                    'join_alias' => 'third',
                    'display_column' => 'name'
                ),
                'fourth' => array(
                    'type' => 'foreignkey',
                    'references' => 'people',
                    'join_alias' => 'third',
                    'display_column' => 'name'
                ),
                'school_id' => array(
                    'type' => 'foreignkey',
                    'references' => 'user',
                    'display_column' => 'school'
                )
            ),
             */
            'user' => array(
                'school' => array(
                    'type' => 'varchar',
                    'len' => 30
                ),
                'leader' => array(
                    'type' => 'varchar',
                    'len' => 10
                ),
                'tel' => array(
                    'type' => 'varchar',
                    'len' => 11
                ),
                'mail' => array(
                    'type' => 'varchar',
                    'len' => 30
                ),
                'password' => array(
                    'type' => 'password'
                ),
                'bill' => array(
                    'type' => 'int',
                    'len' => 11
                ),
                'paid' => array(
                    'type' => 'boolean'
                ),
                'confirmed' => array(
                    'type' => 'boolean'
                ),
                'association_name' => array(
                    'type' => 'varchar',
                    'len' => 15
                ),
                'province' => array(
                    'type' => 'enum',
                    'enum' => $GLOBALS['PROVINCES']
                ),
                'address' => array(
                    'type' => 'varchar',
                    'len' => 50
                ),
                'zipcode' => array(
                    'type' => 'varchar',
                    'len' => 6
                ),
                'activated' => array(
                    'type' => 'boolean'
                ),
                'token' => array(
                    'type' => 'varchar',
                    'len' => 32
                )
            )
        );
    }

    private function get_captcha() {
        $this->load->helper('captcha');
        $vals = array(
            'img_path' => './captcha/',
            'img_url' => base_url('/captcha/') . '/'
        );
        $cap = create_captcha($vals);
        $this->session->set_userdata('captcha', $this->hash_fun($cap['word']));
        return $cap['image'];
    }

    private function hash_fun($str) {
        $salt = '呵呵哒';
        return sha1($salt.sha1($salt.$str));
    }

    private function check_key($key) {
        return $this->hash_fun($key) === 'cfd7ed5a9926a3b9ac72d63f315a82f370dd8370';
    }

    private function is_logged_in() {
        return $this->session->userdata('logged_in') === true;
    }

    private function check_permission() {
        if ($this->is_logged_in()) {
            return;
        }
        redirect('/admin/auth/');
    }

    public function auth() {
        if ($this->is_logged_in()) {
            redirect('/admin/ls/');
        }
        $secret = $this->input->post('secret');
        $captcha = $this->input->post('captcha');

        // 没有输入
        if ($this->input->server('REQUEST_METHOD') === 'GET') {
            $data = array(
                'error_no' => 0,
                'captcha_image' => $this->get_captcha()
            );
            $this->load->view('admin_auth', $data);
            return;
        }

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            // 查验证码
            if ($this->session->userdata('captcha') !== $this->hash_fun($captcha)) {
                $data = array(
                    'error_no' => 1,
                    'error_info' => '验证码错了~',
                    'captcha_image' => $this->get_captcha()
                );
                $this->load->view('admin_auth', $data);
                return;
            }

            // 查口令
            if (!$this->check_key($secret)) {
                $data = array(
                    'error_no' => 2,
                    'error_info' => '口令错了~',
                    'captcha_image' => $this->get_captcha()
                );
                $this->load->view('admin_auth', $data);
                return;
            }

            //都对了
            $this->session->set_userdata('logged_in', true);
            redirect('/admin/ls/');
        }

        show_404('');
    }

	public function index() {
        redirect('/admin/auth/', 'location', 301);
	}

    private function get_model($what) {
        switch ($what) {
            case 'group':
                $this->load->model('group_model', 'group');
                return $this->group;
            case 'people':
                $this->load->model('people_model', 'people');
                return $this->people;
            case 'team':
                $this->load->model('team_model', 'team');
                return $this->team;
            case 'user':
                $this->load->model('user_model', 'user');
                return $this->user;
            default:
                return NULL;
        }
    }

    public function ls($what = NULL) {
        $this->check_permission();
        if ($this->input->server('REQUEST_METHOD') !== 'GET') {
            show_404('');
        }
        if (is_null($what)) {
            $this->load->view('admin_ls', array('tables' => $this->tables));
            return;
        }
        $model = $this->get_model($what);
        if ($model === NULL) {
            show_404('');
        }
        $records = $model->all();
        $data = array(
            'tables' => $this->tables,
            'current' => $what,
            'records' => $records
        );
        $this->load->view('admin_ls', $data);
    }

    public function modify($what, $wid) {
        function parse_null($arr, $thiss, $what) {
            foreach($arr as $entry => $value) {
                if (isset($thiss->tables[$what][$entry])) {
                    if ($thiss->tables[$what][$entry]['type'] === 'foreignkey' && $value === 'NULL') {
                        $arr[$entry] = NULL;
                    }
                }
                else {
                    unset($arr[$entry]);
                }
            }
            return $arr;
        }
        $this->check_permission();
        $model = $this->get_model($what);
        if ($model === NULL) {
            show_404('');
        }
        $request_method = $this->input->server('REQUEST_METHOD');
        if ($request_method == 'POST') {
            $post = parse_null($this->input->post(), $this, $what);
        }
        $foreign_keys = array();
        foreach ($this->tables[$what] as $entry => $description) {
            if ($description['type'] !== 'foreignkey') {
                continue;
            }
            if (isset($foreign_keys[$entry])) {
                continue;
            }
            $foreign_model = $this->get_model($description['references']);
            $foreign_keys[$entry] = array('records' => $foreign_model->all(),
                'format' => $description);
        }
        if ($wid === 'new') {
            if ($request_method === 'GET') {
                $data = array(
                    'tables' => $this->tables,
                    'current' => $what,
                    'row' => array(),
                    'foreign_keys' => $foreign_keys,
                    'wid' => $wid
                );
                $this->load->view('admin_modify', $data);
                return;
            }
            else if ($request_method == 'POST') {
                if (!$model->insert($post)) {
                    $error_no = $this->db->_error_number();
                    $info = $this->db->_error_message();
                    $row = $post;
                }
                else {
                    $error_no = 0;
                    $info = '插入成功~';
                    $wid = $this->db->insert_id();
                    $row = $model->by_id($wid);
                    var_dump($wid);
                    var_dump($row);
                }
            }
            else {
                show_404('');
            }
        }
        else {
            if (!ctype_digit($wid)) {
                show_404('');
            }
            $wid = (int)$wid;
            $row = $model->by_id($wid);
            if (is_null($row)) {
                show_404('');
            }
            if ($request_method !== 'GET' && $request_method !== 'POST') {
                show_404('');
            }
            if ($this->input->server('REQUEST_METHOD') === 'POST') {
                if (!$model->update($wid, $post)) {
                    $error_no = $this->db->_error_number();
                    $info = $this->db->_error_message();
                }
                else {
                    $error_no = 0;
                    $info = '更新成功~';
                }
                $row = $model->by_id($wid);
            }
        }
        $data = array(
            'tables' => $this->tables,
            'current' => $what,
            'row' => $row,
            'foreign_keys' => $foreign_keys,
            'wid' => $wid
        );
        if (isset($error_no)) {
            $data['error_no'] = $error_no;
            $data['info'] = $info;
        }
        $this->load->view('admin_modify', $data);
    }

    public function resetpwd($uid) {
        $this->check_permission();
        $this->input->get();
    }
}

/* End of file admin.php */
/* Location: ./application/controllers/admin.php */
