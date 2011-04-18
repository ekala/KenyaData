<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by JetBrains PhpStorm.
 * User: ahmed
 * Date: 4/8/11
 * Time: 10:40 PM
 * To change this template use File | Settings | File Templates.
 */
 
class GeoCommons extends CI_Controller {
    function __construct()
    {
        parent::__construct();

        // Initialize the classes
        $this->load->helper('url');
        $this->load->database();
    }

    function _get_constituency_id($constituency_name)
    {
        $constituency = $this->db->get_where('constituency', array('constituency' => $constituency_name));
        $constituency = $constituency->result();

        if(count($constituency) > 0) {
            return $constituency[0]->constituency_id;
        }

        return null;
    }

    function _get_health_center_status_id($status_name)
    {
        $status = $this->db->get_where('health_center_status', array('status' => $status_name));
        $status = $status->result();

        if(count($status) > 0) {
            return $status[0]->health_status_id;
        }

        return null;
    }

    function _get_health_center_type_id($type_name)
    {
        $type = $this->db->get_where('health_center_type', array('health_center_type' => $type_name));
        $type = $type->result();

        if(count($type) > 0) {
            return $type[0]->health_center_type_id;
        }

        return null;
    }

    function segmentdata()
    {
        // Creates data segments

        set_time_limit(0);

        // Clean up data

        $this->db->query("TRUNCATE province");
        $this->db->query("TRUNCATE district");
        $this->db->query("TRUNCATE division");
        $this->db->query("TRUNCATE location");
        $this->db->query("TRUNCATE sub_location");
        $this->db->query("TRUNCATE province");

        $this->db->query("TRUNCATE constituency");

        $this->db->query("TRUNCATE health_center_type");
        $this->db->query("TRUNCATE health_center_status");

        // Constituency data
        $constituencies = $this->db->query("SELECT DISTINCT(constituency) AS constituency FROM geocommons_healthfacilities");
        $constituencies_result = $constituencies->result();

        foreach($constituencies_result as $constituency)
        {
            $this->db->insert("constituency", array("constituency" => $constituency->constituency));
        }

        // Health center status data
        $health_center_status = $this->db->query("SELECT DISTINCT(status) AS status FROM geocommons_healthfacilities");
        $health_center_status_result = $health_center_status->result();

        foreach($health_center_status_result as $status)
        {
            $this->db->insert("health_center_status", array("status" => $status->status));
        }

        // Health center type data
        $health_center_type = $this->db->query("SELECT DISTINCT(`type`) AS `type` FROM geocommons_healthfacilities");
        $health_center_type_result = $health_center_type->result();

        foreach($health_center_type_result as $type)
        {
            $this->db->insert("health_center_type", array("health_center_type" => $type->type));
        }

        $this->db->query("TRUNCATE health_facilities");
        
        // Province data
        $provinces = $this->db->query("SELECT DISTINCT(province) AS province FROM geocommons_healthfacilities");
        $provinces_result = $provinces->result();

        foreach($provinces_result as $province) {
            $this->db->insert("province", array("province" => $province->province));
            $province_id = $this->db->insert_id();

            // District data
            $districts = $this->db->query("SELECT DISTINCT(district) AS district FROM geocommons_healthfacilities
                WHERE province = '".mysql_real_escape_string($province->province)."' ");
            $districts_result = $districts->result();

            foreach($districts_result as $district) {
                $this->db->insert("district", array("province_id" => $province_id, "district" => mysql_real_escape_string($district->district)));
                $district_id = $this->db->insert_id();

                // Division data
                $divisions = $this->db->query("SELECT DISTINCT(division) AS division FROM geocommons_healthfacilities
                    WHERE province = '".mysql_real_escape_string($province->province)."' AND district = '".mysql_real_escape_string($district->district)."'");
                $divisions_result = $divisions->result();

                foreach($divisions_result as $division) {
                    $this->db->insert("division", array("district_id" => $district_id, "division" => mysql_real_escape_string($division->division)));
                    $division_id = $this->db->insert_id();

                    // Location data
                    $locations = $this->db->query("SELECT DISTINCT(location) AS location FROM geocommons_healthfacilities
                        WHERE province = '".mysql_real_escape_string($province->province)."' AND district = '".mysql_real_escape_string($district->district)."'
                        AND division = '".mysql_real_escape_string($division->division)."'");
                    $locations_result = $locations->result();

                    foreach($locations_result as $location) {
                        $this->db->insert("location", array("division_id" => $division_id, "location" => mysql_real_escape_string($location->location)));
                        $location_id = $this->db->insert_id();

                        // Sublocation data
                        $sub_locations = $this->db->query("SELECT DISTINCT(sublocation) AS sublocation FROM geocommons_healthfacilities
                            WHERE province = '".mysql_real_escape_string($province->province)."' AND district = '".mysql_real_escape_string($district->district)."'
                            AND division = '".mysql_real_escape_string($division->division)."' AND location = '".mysql_real_escape_string($location->location)."'");
                        $sub_locations_result = $sub_locations->result();

                        foreach($sub_locations_result as $sub_location) {
                            $this->db->insert("sub_location", array("location_id" => $location_id, "sub_location" => mysql_real_escape_string($sub_location->sublocation)));
                            $sub_location_id = $this->db->insert_id();

                            // Save data that is "indexed to the relevant locations"
                            $all_data = $this->db->get_where('geocommons_healthfacilities', array('province' => $province->province,
                                'district' => $district->district, 'division' => $division->division, 'location' =>$location->location,
                                'sublocation' => $sub_location->sublocation));
                            $all_data_result = $all_data->result();

                            foreach($all_data_result as $data_result) {
                                $this->db->insert("health_facilities", array("location" => $location_id,
                                     "sublocation" => $sub_location_id, "rhtc_rhdc" => $data_result->rhtc_rhdc,
                                     "plot_no" => $data_result->plot_no, "nearest_town" => $data_result->nearest_town,
                                     "status" => $this->_get_health_center_status_id($data_result->status),
                                     "constituency" => $this->_get_constituency_id($data_result->constituency),
                                     "blood_tr" => $data_result->blood_tr, "off_landline" => $data_result->off_landline,
                                     "off_fax" => $data_result->off_fax, "off_postcode" => $data_result->off_postcode,
                                     "fp" => $data_result->fp, "off_mobile" => $data_result->off_mobile,
                                     "outp" => $data_result->outp, "off_town" => $data_result->off_town,
                                     "anc" => $data_result->anc, "imci" => $data_result->imci,
                                     "off_email" => $data_result->off_email, "tb_treat" => $data_result->tb_treat,
                                     "growth" => $data_result->growth, "ceoc" => $data_result->ceoc,
                                     "division" => $division_id, "tb_labs" => $data_result->tb_labs,
                                     "district" => $district_id, "inp" => $data_result->inp,
                                     "off_pobox" => $data_result->off_pobox, "facility" => $data_result->facility,
                                     "cots" => $data_result->cots, "province" => $province_id,
                                     "incharge_name" => $data_result->incharge_name, "art" => $data_result->art,
                                     "beoc" => $data_result->beoc, "hbc" => $data_result->hbc,
                                     "tb_diag" => $data_result->tb_diag, "hct" => $data_result->hct,
                                     "c_section" => $data_result->c_section, "imm" => $data_result->imm,
                                     "beds" => $data_result->beds, "mflcode" => $data_result->mflcode,
                                     "pmtct" => $data_result->pmtct, "radiology" => $data_result->radiology,
                                     "owner" => $data_result->owner, "type" => $this->_get_health_center_type_id($data_result->type),
                                     "youth" => $data_result->youth, "lat" => $data_result->lat, "long" => $data_result->long));
                            }
                        }
                    }
                }
            }
        }

        header("Location: ".base_url()."index.php/geocommons/importsuccess");
    }

    function savetodb()
    {
        // Make sure the server does not run into a timeout

        set_time_limit(0);
        
        $row = 1;

        $this->db->query("TRUNCATE geocommons_healthfacilities");

        if (($handle = fopen("datasets/geocommons/kenhealthfacilities.csv", "r")) !== FALSE) {

            while (($data = fgetcsv($handle, 2000, ",")) !== FALSE) {
                if($row == 1) {
                    $row++;
                    continue;
                }

                // Insert data to the database
                $this->db->insert("geocommons_healthfacilities", array("location" => $data[0],
                     "sublocation" => $data[1], "rhtc_rhdc" => $data[2],
                     "plot_no" => $data[3], "nearest_town" => $data[4],
                     "status" => $data[5], "constituency" => $data[6],
                     "blood_tr" => $data[7], "off_landline" => $data[8],
                     "off_fax" => $data[9], "off_postcode" => $data[10],
                     "fp" => $data[11], "off_mobile" => $data[12],
                     "outp" => $data[13], "off_town" => $data[14],
                     "anc" => $data[15], "imci" => $data[16],
                     "off_email" => $data[17], "tb_treat" => $data[18],
                     "growth" => $data[19], "ceoc" => $data[20],
                     "division" => $data[21], "tb_labs" => $data[22],
                     "district" => $data[23], "inp" => $data[24],
                     "off_pobox" => $data[25], "facility" => $data[26],
                     "cots" => $data[27], "province" => $data[28],
                     "incharge_name" => $data[29], "art" => $data[30],
                     "beoc" => $data[31], "hbc" => $data[32],
                     "tb_diag" => $data[33], "hct" => $data[34],
                     "c_section" => $data[35], "imm" => $data[36],
                     "beds" => $data[37], "mflcode" => $data[38],
                     "pmtct" => $data[39], "radiology" => $data[40],
                     "owner" => $data[41], "type" => $data[42],
                     "youth" => $data[43], "lat" => $data[44], "long" => $data[45]));
            }

            fclose($handle);

            $row++;

            header("Location: ".base_url()."index.php/geocommons/importsuccess");
        }

        function importsuccess()
        {
            // Import to db success
        }
    }
}
