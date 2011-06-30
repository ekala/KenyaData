<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ahmedmaawy
 * Date: 6/30/11
 * Time: 5:53 PM
 * To change this template use File | Settings | File Templates.
 */

function get_constituency_id($constituency_name, $conn_huduma_db) {
    $constituency_result = mysql_query("SELECT `id` FROM boundary WHERE LOWER(boundary_name) = '".trim(strtolower($constituency_name))."'", $conn_huduma_db);

    if($constituency_result) {
        if($my_array = mysql_fetch_array($constituency_result)) {
            return $my_array[0];
        }

        return null;
    }

    return null;
}

$conn_huduma_db = mysql_connect("localhost", "root", "root");
mysql_select_db("huduma", $conn_huduma_db);

if (($handle = fopen("mpigs2.csv", "r")) !== FALSE) {
    $mp_master_row = mysql_query("INSERT INTO boundary_metadata (metadata_title, metadata_columns) VALUES ('MPs', '[\"Name\", \"Party\"]')");
    $metadata_id = mysql_insert_id($conn_huduma_db);
    
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        // Number of columns
        $num = count($data);

        $mp_name = $data[0];
        $party = $data[1];
        $constituency = $data[2];

        $constituency_id = get_constituency_id($constituency, $conn_huduma_db);

        $mp_detail_row = mysql_query("INSERT INTO boundary_metadata_items (boundary_metadata_id, boundary_id, data_items)
            VALUES($metadata_id, $constituency_id, '{\"0\":\"$mp_name\", \"1\":\"$party\"}')", $conn_huduma_db);
    }
    fclose($handle);
}

mysql_close($conn_huduma_db);
?>

