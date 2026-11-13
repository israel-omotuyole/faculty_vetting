<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Vetting extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper(array('form', 'url'));
        $this->load->library('session');
        $this->load->model('Vetting_model');
    }

    // public function index() {
    //     $data['students'] = $this->Vetting_model->get_students();
    //     $this->load->view('vetting_view', $data);
    // }
    public function index() {
        // We will pass our extracted database records here later
        $data = array(); 
        
        // Load the new split dashboard layout
        $this->load->view('header');
        $this->load->view('sidebar');
        $this->load->view('dashboard', $data); // The main content
        $this->load->view('footer');
    }
    
    public function upload_pdf() {
        if (empty($_FILES['pdf_file']['name'])) {
            $this->session->set_flashdata('msg', 'Please select a PDF file first.');
            redirect('vetting');
        }

        $upload_path = FCPATH . 'uploads/';
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0777, true);
        }

        $temp_file = $upload_path . basename($_FILES['pdf_file']['name']);
        if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $temp_file)) {
            
            // Parse PDF text using Smalot
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($temp_file);
            $raw_text = $pdf->getText();

            // Extract rows into tabular format
            $extracted_rows = $this->extract_tabular_data($raw_text);

            if (!empty($extracted_rows)) {
                $this->Vetting_model->save_students($extracted_rows);
                $this->session->set_flashdata('msg', 'PDF successfully read and loaded into table! Extracted ' . count($extracted_rows) . ' students.');
            } else {
                $this->session->set_flashdata('msg', 'Could not detect student rows in this PDF format.');
            }
        } else {
            $this->session->set_flashdata('msg', 'File upload failed.');
        }

        redirect('vetting');
    }

    public function clear() {
        $this->Vetting_model->clear_all();
        $this->session->set_flashdata('msg', 'Table cleared.');
        redirect('vetting');
    }

   // Extracts student rows cleanly without dragging in footers or next-page headers
    private function extract_tabular_data($text) {
        // Fix broken matric slashes from PDF extraction
        $clean_text = preg_replace('/(?<!RUN)\/(IFT|CMP|PHT|CSC)\//i', 'RUN/$1/', $text);
        
        // Match Redeemer's matric numbers
        $matric_pattern = '/(RUN\/(?:IFT|CMP|PHT|CSC)\/[0-9\/]{7,10})/i';
        preg_match_all($matric_pattern, $clean_text, $matches, PREG_OFFSET_CAPTURE);

        if (empty($matches[0])) {
            return array();
        }

        $students = array();
        $seen = array();
        $total = count($matches[0]);

        for ($i = 0; $i < $total; $i++) {
            $raw_matric = trim($matches[0][$i][0], " /");
            $matric = $this->format_matric($raw_matric);

            if (isset($seen[$matric])) {
                continue;
            }

            $start = $matches[0][$i][1];
            $end = ($i + 1 < $total) ? $matches[0][$i + 1][1] : strlen($clean_text);
            $chunk = substr($clean_text, $start, $end - $start);

            // HARD STOP: Prevent bleeding into footers and next-page headers
            $stop_words = [
                'Class Performance Summary', 
                'Course Code', 
                'SN Course', 
                'REDEEMER\'S UNIVERSITY', 
                'MASTER SHEET',
                'INTERPRETATION OF TERMS'
            ];
            foreach ($stop_words as $word) {
                if (($pos = stripos($chunk, $word)) !== false) {
                    $chunk = substr($chunk, 0, $pos);
                }
            }

            // Clean up the text stream for this specific student
            $body = trim(substr($chunk, strlen($matches[0][$i][0])));
            $body = preg_replace('/[\r\n]+/', ' ', $body);

            $name = 'UNKNOWN STUDENT';
            $tur = '-';
            $extra_info = '';

            // Match everything up to the FIRST number to cleanly isolate the Name
            if (preg_match('/^([A-Za-z\-\s\']+?)\s+(\d.*)$/u', $body, $parts)) {
                $name = trim(preg_replace('/\b(RUN|GSD|PRB)\b/i', '', $parts[1]));
                $data_stream = trim($parts[2]);
                
                // STRICT SPLIT: Force exactly the first sequence of digits as TUR, rest as Scores
                if (preg_match('/^(\d+)\s+(.*)$/', $data_stream, $num_parts)) {
                    $tur = $num_parts[1];             // ONLY the TUR
                    $extra_info = trim($num_parts[2]); // ONLY the scores
                } else {
                    $tur = $data_stream;
                }
            }
            // Remove the stray word 'SN' if it bleeds in from the table header
            $extra_info = preg_replace('/\s+SN$/i', '', $extra_info);

            // Calculate the EXACT Serial Number of the next student to safely remove it
            $next_sn = $i + 2; 
            $extra_info = preg_replace('/\s+(' . $next_sn . ')$/', '', $extra_info);

            $students[] = array(
                'matric_no'    => $matric,
                'student_name' => ucwords(strtolower($name)),
                'tur'          => $tur,
                'extra_info'   => $extra_info
            );

            $seen[$matric] = true;
        }

        return $students;
    }

    private function format_matric($raw) {
        $parts = explode('/', strtoupper($raw));
        $dept = isset($parts[1]) ? $parts[1] : 'IFT';
        $digits = preg_replace('/[^0-9]/', '', $raw);
        if (strlen($digits) == 7) {
            if (substr($digits, 0, 2) == '21' || substr($digits, 0, 2) == '22' || substr($digits, 0, 2) == '23') {
                $yy = substr($digits, 0, 2);
                $num = substr($digits, 2, 5);
            } else {
                $num = substr($digits, 0, 5);
                $yy = substr($digits, 5, 2);
            }
            return "RUN/$dept/$yy/$num";
        }
        return strtoupper($raw);
    }
}