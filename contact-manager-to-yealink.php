<?php

/*******************************************************************************/
/******************************* Customization *********************************/
/*******************************************************************************/
$contact_manager_group = isset($_GET['cgroup']) ? $_GET['cgroup'] : "MyPhoneBook"; //Edit "MyPhoneBook" to make your own default
$use_e164 = isset($_GET['e164']) ? $_GET['e164'] : 0; //Edit 0 to 1 to use the E164 formatted numbers by default
$ctype['internal'] = "Extension";   //Edit the right side to display what you want
$ctype['cell'] = "Mobile";          //Edit the right side to display what you want
$ctype['work'] = "Work";            //Edit the right side to display what you want
$ctype['home'] = "Home";            //Edit the right side to display what you want
$ctype['other'] = "Other";          //Edit the right side to display what you want

/*******************************************************************************/
/************** End Customization - Be careful while editing below *************/
/*******************************************************************************/

header("Content-Type: text/xml");

//Loading FreePBX Bootstrap Environment
require_once('/etc/freepbx.conf');

//Database Connection Initialization
global $db;

//This pulls every number in contact mananger that is part of the group specified by $contact_manager_group
$sql = "SELECT cen.number, cge.displayname, cen.type, cen.E164, 0 AS 'sortorder' FROM contactmanager_group_entries AS cge LEFT JOIN contactmanager_entry_numbers AS cen ON cen.entryid = cge.id WHERE cge.groupid = (SELECT cg.id FROM contactmanager_groups AS cg WHERE cg.name = '$contact_manager_group') ORDER BY cge.displayname, cen.number;";

//SQL Statement Execution
$res = $db->prepare($sql);
$res->execute();
//Returned Values Check
if (DB::IsError($res)) {
    error_log("There was an error attempting to query contactmanager<br>($sql)<br>\n" . $res->getMessage() . "\n<br>\n");
} else {
    $contacts = $res->fetchAll(PDO::FETCH_ASSOC);

    foreach ($contacts as $i => $contact) {
        //The if staements provide the ability to re-lable the phone number type as you wish.
        //It also allows for setting the number display order to be changed for multi-number contacts.
        //$contact['type'] will be used as the label
        //$contact['sortorder'] will be used as the sort order
        if ($contact['type'] == "cell") {
            $contact['type'] = $ctype['cell'];
            $contact['sortorder'] = 3;
        }
        if ($contact['type'] == "internal") {
            $contact['type'] = $ctype['internal'];
            $contact['sortorder'] = 1;
        }
        if ($contact['type'] == "work") {
            $contact['type'] = $ctype['work'];
            $contact['sortorder'] = 2;
        }
        if ($contact['type'] == "other") {
            $contact['type'] = $ctype['other'];
            $contact['sortorder'] = 4;
        }
        if ($contact['type'] == "home") {
            $contact['type'] = $ctype['home'];
            $contact['sortorder'] = 5;
        }
        $contact['displayname'] = htmlspecialchars($contact['displayname']);
        $contacts[$i] = $contact;                               //Put the changes back into $contacts
    }

    $xw = xmlwriter_open_memory();
    xmlwriter_set_indent($xw, 1);
    $res = xmlwriter_set_indent_string($xw, ' ');
    xmlwriter_start_document($xw, '1.0', 'UTF-8');

    xmlwriter_start_element($xw, 'CompanyIPPhoneDirectory');    //Root Tag -- Opening [You may change the word Company, but no other part of the root tag]
    //Root Tag must be in the format XXXIPPhoneDirectory
    xmlwriter_start_attribute($xw, 'clearlight');               //Root Tag Attribute -- Opening
    xmlwriter_text($xw, 'true');                                //Root Tag Attribute -- Setting Value
    xmlwriter_end_attribute($xw);                               //Root Tag Attribute -- Closing

    //Loop through the results and parse them into XML
    $previousname = "";
    foreach ($contacts as $contact) {
        if ($contact['displayname'] != $previousname) {
            xmlwriter_start_element($xw, 'DirectoryEntry');     //DirectoryEntry Tag -- Opening

            xmlwriter_start_element($xw, 'Name');               //Name Tag -- Opening
            xmlwriter_text($xw, $contact['displayname']);       //Name Tag -- Adding text
            xmlwriter_end_element($xw);                         //Name Tag -- Closing

            if ($use_e164 == 0 || ($use_e164 == 1 && $contact['type'] == $ctype['internal'])) {
                //Not using E164 or it is an internal extension
                xmlwriter_start_element($xw, 'Telephone');      //Telephone Tag -- Opening
                xmlwriter_start_attribute($xw, 'label');        //Telephone Tag Attribute -- Opening
                xmlwriter_text($xw, $contact['type']);          //Telephone Tag Attribute -- Setting Value
                xmlwriter_end_attribute($xw);                   //Telephone Tag Attribute -- Closing
                xmlwriter_text($xw, $contact['number']);        //Telephone Tag -- Adding text
                xmlwriter_end_element($xw);                     //Telephone Tag -- Closing
            } else {
                //Using E164
                xmlwriter_start_element($xw, 'Telephone');      //Telephone Tag -- Opening
                xmlwriter_start_attribute($xw, 'label');        //Telephone Tag Attribute -- Opening
                xmlwriter_text($xw, $contact['type']);          //Telephone Tag Attribute -- Setting Value
                xmlwriter_end_attribute($xw);                   //Telephone Tag Attribute -- Closing
                xmlwriter_text($xw, $contact['E164']);          //Telephone Tag -- Adding text
                xmlwriter_end_element($xw);                     //Telephone Tag -- Closing
            }
            xmlwriter_end_element($xw);                         //DirectoryEntry Tag -- Closing
            $previousname = $contact['displayname'];            //Set the current name to the previous one
        }
    }
    xmlwriter_end_element($xw);                                 //Root tag -- Closing [If you changed it above, make sure you change it here as well]
    xmlwriter_end_document($xw);
    echo xmlwriter_output_memory($xw);
}
