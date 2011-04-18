<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by JetBrains PhpStorm.
 * User: ahmed
 * Date: 4/9/11
 * Time: 3:12 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Misc extends CI_Controller {
    function __construct()
    {
        parent::__construct();

        $this->load->helper('url');
        $this->load->database();
    }

    function _get_province_id($province_name)
    {
        $province = $this->db->get_where('province', array('province' => $province_name));
        $province = $province->result();

        if(count($province) > 0) {
            return $province[0]->province_id;
        }

        return null;
    }

    function _get_district_id($district_name)
    {
        $district = $this->db->get_where('district', array('district' => $district_name));
        $district = $district->result();

        if(count($district) > 0) {
            return $district[0]->district_id;
        }

        return null;
    }

    function _create_district($district, $province_id)
    {
        $this->db->insert("district", array("district" => $district, "province_id" => $province_id));
        return $this->db->insert_id();
    }

    function importnursedb()
    {
        $this->db->query('TRUNCATE nurse');
        
        if (($handle = fopen("datasets/misc/esp_nurses_wth_province.csv", "r")) !== FALSE) {

            while (($data = fgetcsv($handle, 2000, ",")) !== FALSE) {
                // Read the data items
                
                $province = $data[0];
                $district = $data[1];
                $name = $data[2];
                $id_number = $data[3];

                $province = strtolower($province);
                $province = strtoupper(substr($province, 0, 1)).substr($province, 1, (strlen($province) - 1));

                $province_id = $this->_get_province_id($province);

                $district = strtolower($district);
                $district = strtoupper(substr($district, 0, 1)).substr($district, 1, (strlen($district) - 1));

                $district_id = $this->_get_district_id($district);

                if(is_null($district_id))
                {
                    $district_id = $this->_create_district($district, $province_id);
                }

                $this->db->insert('nurse', array('province_id' => $province_id, 'district_id' => $district_id,
                                           'nurse_name' => $name, 'nurse_id_card' => $id_number));
            }
        }

        fclose($handle);
    }
}
