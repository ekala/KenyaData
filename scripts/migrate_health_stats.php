<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ahmedmaawy
 * Date: 6/29/11
 * Time: 5:26 PM
 * To change this template use File | Settings | File Templates.
 */

$conn_kenya_data = mysql_connect("localhost", "root", "root");
$conn_huduma_db = mysql_connect("localhost", "root", "root");

mysql_select_db("kenya_data", $conn_kenya_data);
mysql_select_db("huduma", $conn_huduma_db);

function get_constituency_id($constituency_name, $conn_huduma_db) {
    $constituency_result = mysql_query("SELECT `id` FROM boundary WHERE boundary_name = '".trim($constituency_name)."'", $conn_huduma_db);

    if($constituency_result) {
        if($my_array = mysql_fetch_array($constituency_result)) {
            return $my_array[0];
        }

        return null;
    }

    return null;
}

function get_count_of_yes($field_name, $constituency_id, $conn_kenya_data) {
    $constituency_result = mysql_query("SELECT COUNT(*) FROM health_facilities WHERE `$field_name` LIKE '%Y%'
        AND constituency = $constituency_id", $conn_kenya_data);
    $my_array = mysql_fetch_array($constituency_result);
    
    return $my_array[0];
}

$constituency_list_result = mysql_query("SELECT constituency_id, constituency FROM constituency");
$constituencies = array();
$constituencies_id = array();
$constituencies_kenya_data_id = array();

// Get a list of constituencies

$current_index = 0;

while($constituency_array = mysql_fetch_array($constituency_list_result)) {
    $constituencies_kenya_data_id[$current_index] = $constituency_array[0];
    $constituencies[$current_index] = $constituency_array[1];

    $current_index ++;
}

// Get a list of constituency ids from huduma

$current_index = 0;

foreach($constituencies as $constituency) {
    $constituencies_id[$current_index] = get_constituency_id($constituency, $conn_huduma_db);

    $current_index ++;
}

// Build statistics

$statistics = array();

$current_index = 0;

foreach($constituencies_kenya_data_id as $constituency_id) {
    $statistics[$current_index]->constituency_id = $constituencies_id[$current_index];
    $statistics[$current_index]->fields->fp = get_count_of_yes('fp', $constituency_id, $conn_kenya_data);
    $statistics[$current_index]->fields->outp = get_count_of_yes('outp', $constituency_id, $conn_kenya_data);
    $statistics[$current_index]->fields->anc = get_count_of_yes('anc', $constituency_id, $conn_kenya_data);
    $statistics[$current_index]->fields->imci = get_count_of_yes('imci', $constituency_id, $conn_kenya_data);
    $statistics[$current_index]->fields->tb_treat = get_count_of_yes('tb_treat', $constituency_id, $conn_kenya_data);
    $statistics[$current_index]->fields->growth = get_count_of_yes('growth', $constituency_id, $conn_kenya_data);
    $statistics[$current_index]->fields->coec = get_count_of_yes('coec', $constituency_id, $conn_kenya_data);
    $statistics[$current_index]->fields->tb_labs = get_count_of_yes('tb_labs', $constituency_id, $conn_kenya_data);
    $statistics[$current_index]->fields->inp = get_count_of_yes('inp', $constituency_id, $conn_kenya_data);
    $statistics[$current_index]->fields->art = get_count_of_yes('art', $constituency_id, $conn_kenya_data);
    $statistics[$current_index]->fields->beoc = get_count_of_yes('beoc', $constituency_id, $conn_kenya_data);
    $statistics[$current_index]->fields->hbc = get_count_of_yes('hbc', $constituency_id, $conn_kenya_data);
    $statistics[$current_index]->fields->tb_diag = get_count_of_yes('tb_diag', $constituency_id, $conn_kenya_data);
    $statistics[$current_index]->fields->hct = get_count_of_yes('hct', $constituency_id, $conn_kenya_data);
    $statistics[$current_index]->fields->c_section = get_count_of_yes('c_section', $constituency_id, $conn_kenya_data);
    $statistics[$current_index]->fields->imm = get_count_of_yes('imm', $constituency_id, $conn_kenya_data);
    $statistics[$current_index]->fields->youth = get_count_of_yes('youth', $constituency_id, $conn_kenya_data);
    $statistics[$current_index]->fields->pmtct = get_count_of_yes('pmtct', $constituency_id, $conn_kenya_data);
    $statistics[$current_index]->fields->radiology = get_count_of_yes('radiology', $constituency_id, $conn_kenya_data);
}

$object_vars = get_object_vars($statistics[0]->fields);
$fields = "[";

foreach($object_vars as $var) {
    $fields.='"'.$var.'",';
}

$fields = rtrim($fields, ",");
$fields.="]";

// Insert statistics to the database

$insert_master_record = mysql_query("INSERT INTO boundary_meta_data (meta_data_title, meta_data_column) VALUES ('Health Facilities', '$fields')", $conn_huduma_db);
$boundary_metadata_id = mysql_insert_id($conn_huduma_db);

foreach($statistics as $statistic) {
    $boundary_id = $statistic->constituency_id;
    $field_list = $statistic->fields;
    $new_field_list = null;

    $current_index = 0;

    foreach($object_vars as $var) {
        $new_field_list->$current_index = $field_list->$var;
    }

    $insert_detail_record = mysql_query("INSERT INTO boundary_meta_data_items (boundary_metadata_id, boundary_id, data_items) VALUES ($boundary_metadata_id, $boundary_id, '".json_encode($field_list)."')", $conn_huduma_db);
}

// Close all connections

mysql_close($conn_huduma_db);
mysql_close($conn_kenya_data);