<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Manager to CSV</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
        }
        .done {
            color: green;
        }
    </style>
</head>

<body>
    <h3>Contact Manager to CSV</h3>
    <?php

    /*******************************************************************************/
    /******************************* Customization *********************************/
    /*******************************************************************************/
    $contact_manager_group = isset($_GET['cgroup']) ? $_GET['cgroup'] : "MyPhoneBook"; //Edit "MyPhoneBook" to make your own default
    $use_e164 = 1;                      //Edit 0 to 1 to use the E164 formatted numbers by default
    $ctype['internal'] = "Extension";   //Edit the right side to display what you want
    $ctype['cell'] = "Mobile";          //Edit the right side to display what you want
    $ctype['work'] = "Work";            //Edit the right side to display what you want
    $ctype['home'] = "Home";            //Edit the right side to display what you want
    $ctype['other'] = "Other";          //Edit the right side to display what you want

    /*******************************************************************************/
    /************** End Customization - Be careful while editing below *************/
    /*******************************************************************************/

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
            //The if statements provide the ability to re-lable the phone number type as you wish.
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
            $contacts[$i] = $contact;   //Put the changes back into $contacts
        }

        $csv = fopen('contact-manager.csv', 'w');

        // Loop through file pointer and a line
        foreach ($contacts as $contact) {
            fputcsv($csv, $contact);
        }
        fclose($csv);
    }
    ?>
    <div>------------------------------------</div>
    <div><span>Extracting from Contact Manager&nbsp;</span><span>[<i class="done">OK</i>]</span></div>
    <div><a href="contact-manager.csv"><button>Download</button></a></div>
</body>

</html>
