<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Vetting_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        // $this->load->database();
    }

    public function save_students($rows) {
        $this->db->empty_table('pdf_students_table');
        foreach ($rows as $r) {
            $this->db->insert('pdf_students_table', $r);
        }
    }

    public function get_students() {
        $this->db->order_by('id', 'ASC');
        return $this->db->get('pdf_students_table')->result_array();
    }

    public function clear_all() {
        $this->db->empty_table('pdf_students_table');
    }
}