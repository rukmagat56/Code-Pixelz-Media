<?php

/**
 * This file contains all the code for the "NZCBC Exporter" settings page
 * CPM
 */


function nzcbc_exporter_style()
{
    echo "<style>

        h1{
            margin-top:2rem; 
            margin-bottom:3rem;
        }

        form div{
            margin-bottom: 20px;
        }

        label{
            font-weight:bold;
            text-transform:uppercase;
        }

        input[type='submit']{
            cursor:pointer;
            border-radius:2rem; 
            padding:10px;
            border:0;
            color:white;
            background-color:#2271b1;
            display:block;
            margin-right:auto;
            margin-top:50px;
        }

        input[type='submit']:hover{
            background-color:#164c78;
        }

        h3#exporter-heading{
            margin-top:3rem;
            font-weight:bold;
        }
        table#exporter {
            display:block;
            overflow-x:auto;
            margin-top:2rem;
            border:1px solid #b3adad;
            border-collapse:collapse;
            padding:10px;
        }
        table#exporter th {
            border:1px solid #b3adad;
            padding:10px;
            background: #f0f0f0;
            color: #313030;
        } 
        table#exporter td {
            border:1px solid #b3adad;
            text-align:center;
            padding:10px;
            background: #ffffff;
            color: #313030;
        }

    </style>";
}
add_action('admin_head', 'nzcbc_exporter_style');

/**
 * The function retrieves a list of locations associated with a selected organization or all locations
 * if no organization is selected, and sends it as a JSON response.
 */
function nzcbc_update_location_list()
{
    $all_location_assoc_arr = [];

    if($_POST['selected_org'] != "all"){
        
        $selected_org_id = (int)$_POST['selected_org'];
        $all_location_post_ids_arr = get_post_meta($selected_org_id, "relationships_-_org_-_location", true);
    
        foreach ($all_location_post_ids_arr as $location) {
            $all_location_assoc_arr[$location] = get_the_title($location);
        }
    }else{
        $cpt = "location";
        $args = array(
            'post_type' => $cpt,
            'posts_per_page' => -1,
            'post_status' => 'publish'
        );
        $query = new WP_Query($args);
        if ($query->have_posts()) :
            while ($query->have_posts()) : $query->the_post();
                $all_location_assoc_arr[get_the_ID()] = get_the_title();
            endwhile;
        endif;
    }

    wp_send_json_success($all_location_assoc_arr);
}
add_action('wp_ajax_nzcbc_update_location_list', 'nzcbc_update_location_list');

/**
 * This PHP function generates a CSV file for download based on an array of data.
 */
function nzcbc_generate_csv()
{

    $csv_array = unserialize(str_replace("\\", "", $_POST['array_csv']));
    $filename = "export.csv";
    $delimiter = ",";

    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename=" ' . $filename . '"');

    $output = fopen('php://output', 'w');
    foreach ($csv_array as $line) {
        fputcsv($output, $line, $delimiter);
    }
    fclose($output);

    die();
}
add_action('wp_ajax_nzcbc_generate_csv', 'nzcbc_generate_csv');

function nzcbc_settings_link()
{
    add_options_page('Exporter Settings', 'NZCBC Exporter', 'manage_options', 'nzcbc-settings-page', 'nzcbc_HTML');
}
add_action('admin_menu', 'nzcbc_settings_link');

function nzcbc_HTML()
{ ?>
    <div>
        <h1>Exporter Settings</h1>

        <form id="org_location" action="" method="POST">
            <?php

            //getting all the items from custom post type (i.e role, org and location) and putting them in a drop down menu

            $custom_post_type_arr = ["role", "organistation", "location"];

            foreach ($custom_post_type_arr as $cpt) {

                $args = array(
                    'post_type' => $cpt,
                    'posts_per_page' => 20,
                    'post_status' => 'publish'
                );

                $query = new WP_Query($args);

                if ($query->have_posts()) :
                    echo '<div>';
                        echo '<label for="q_' . $cpt . '" >' . $cpt . ': </label>';
                        echo '<select name="q_' . $cpt . '" id="type_' . $cpt . '">';

                        echo '<option value="all">All</option>';
                        while ($query->have_posts()) : $query->the_post();
                            if (!empty(get_the_title())) {

                                if (isset($_POST['generate_table'])) {
                                    $is_selected = isset($_POST['generate_table']) && (get_the_ID() == $_POST["q_" . $cpt]) ? "selected" : "";

                                    echo '<option id="q_' . get_the_ID() . '" value="' . get_the_ID() . '" ' . $is_selected . '>' . get_the_title() . '</option>';
                                } else {
                                    echo '<option id="q_' . get_the_ID() . '" value="' . get_the_ID() . '">' . get_the_title() . '</option>';
                                }
                            }
                        endwhile;
                        echo '</select>';
                    echo '</div>';
                endif;
            }?>

            <input type="submit" name="generate_table" value="GENERATE">
        </form>

        <script>
            jQuery(document).ready(function($) {
                //changing location options based on organization selected
                $("#type_organistation").change(function() {

                    var selected_org_id = $('#type_organistation').find(":selected").val();

                    jQuery.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'nzcbc_update_location_list',
                            selected_org: selected_org_id
                        },
                        beforeSend: function(respond) {
                        },
                        success: function(response) {

                            //remove previous options
                            document.getElementById("type_location").innerHTML = "";

                            //add new options
                            var location_list = document.getElementById('type_location');
                            var ind = 0;
                            for (const [key, value] of Object.entries(response.data)) {
                                var objOption = document.createElement("option");

                                //adding "All" option at the top of the options list
                                if(ind == 0){
                                    objOption.value = "all";
                                    objOption.text = "All";
                                    location_list.options.add(objOption);
                                }

                                objOption.value = key;
                                objOption.text = value;
                                location_list.options.add(objOption);

                                ind++;
                            }
                        },
                        error: function(response) {
                        },
                        complete: function(respond) {
                        },
                    });
                });
            });
        </script>

        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate_table'])) {

            $role_id = $_POST['q_role'];
            $org_id = $_POST['q_organistation'];
            $location_id = $_POST['q_location'];

            $query_args = [];
            $all_person_id = []; //will contain all id of people to be looped through
            
            if ($role_id != "all" && $org_id != "all" && $location_id != "all") { //specific value selected in all fields
                $query_args = [
                    'post_type' => 'person',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        'relation' => 'AND',
                        [
                            'key'     => 'relationships-person-role',
                            'value' => 's:' . strlen($role_id) . ':"' . $role_id . '";',
                            'compare' => 'LIKE'
                        ],
                        [
                            'key'     => 'relationships-person-org',
                            'value' => 's:' . strlen($org_id) . ':"' . $org_id . '";',
                            'compare' => 'LIKE'
                        ],
                        [
                            'key'     => 'relationships-person-location',
                            'value' => 's:' . strlen($location_id) . ':"' . $location_id . '";',
                            'compare' => 'LIKE'
                        ]
                    ],
                ];
            }else if ($role_id != "all" && $org_id == "all" && $location_id == "all" ) { //only role
                $query_args = [
                    'post_type' => 'person',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        'relation' => 'AND',
                        [
                            'key'     => 'relationships-person-role',
                            'value' => 's:' . strlen($role_id) . ':"' . $role_id . '";',
                            'compare' => 'LIKE'
                        ]
                    ],
                ];
            } else if ($role_id == "all" && $org_id != "all" && $location_id == "all") { //only org
                $query_args = [
                    'post_type' => 'person',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        [
                            'key'     => 'relationships-person-org',
                            'value' => 's:' . strlen($org_id) . ':"' . $org_id . '";',
                            'compare' => 'LIKE'
                        ]
                    ],
                ];
            }else if ($role_id == "all" && $org_id == "all" && $location_id != "all") { //only location
                $query_args = [
                    'post_type' => 'person',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        [
                            'key'     => 'relationships-person-location',
                            'value' => 's:' . strlen($location_id) . ':"' . $location_id . '";',
                            'compare' => 'LIKE'
                        ]
                    ],
                ];
            }else if ($role_id != "all" && $org_id != "all" && $location_id == "all") { //role and org
                $query_args = [
                    'post_type' => 'person',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        'relation' => 'AND',
                        [
                            'key'     => 'relationships-person-role',
                            'value' => 's:' . strlen($role_id) . ':"' . $role_id . '";',
                            'compare' => 'LIKE'
                        ],
                        [
                            'key'     => 'relationships-person-org',
                            'value' => 's:' . strlen($org_id) . ':"' . $org_id . '";',
                            'compare' => 'LIKE'
                        ]
                    ],
                ];
            }else if ($role_id != "all" && $org_id == "all" && $location_id != "all") { //role and location
                $query_args = [
                    'post_type' => 'person',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        'relation' => 'AND',
                        [
                            'key'     => 'relationships-person-role',
                            'value' => 's:' . strlen($role_id) . ':"' . $role_id . '";',
                            'compare' => 'LIKE'
                        ],
                        [
                            'key'     => 'relationships-person-location',
                            'value' => 's:' . strlen($location_id) . ':"' . $location_id . '";',
                            'compare' => 'LIKE'
                        ]
                    ],
                ];
            }else if ($role_id == "all" && $org_id != "all" && $location_id != "all") { //org and location
                $query_args = [
                    'post_type' => 'person',
                    'posts_per_page' => -1,

                    'meta_query' => [
                        'relation' => 'AND',
                        [
                            'key'     => 'relationships-person-org',
                            'value' => 's:' . strlen($org_id) . ':"' . $org_id . '";',
                            'compare' => 'LIKE'
                        ],
                        [
                            'key'     => 'relationships-person-location',
                            'value' => 's:' . strlen($location_id) . ':"' . $location_id . '";',
                            'compare' => 'LIKE'
                        ]
                    ],
                ];
            }else if($role_id == "all" && $org_id == "all" && $location_id == "all") { //no specific values selected
                $query_args = [
                    'post_type' => 'person',
                    'posts_per_page' => -1,
                ];
            }

            $person_query = new WP_Query($query_args);
            while ($person_query->have_posts()) : $person_query->the_post();
                array_push($all_person_id, get_the_ID());
            endwhile;

            $csv_arr = []; //will contains individual lines of the csv to be exported

            //adding csv headers
            $headers_arr = ['Title', 'First name', 'Last name', 'Email', 'Mobile', 'Phone', 'Role', 'Org name', 'Org email', 'Org phone', 'Loc name', 'Loc address_1', 'Loc address_2', 'Loc suburb', 'Loc city', 'Loc postcode'];
            array_push($csv_arr, $headers_arr);

        ?>
            <h3 id="exporter-heading">RESULT</h3>

            <!-- table that displays the list of results -->
            <table id="exporter">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>First name</th>
                        <th>Last name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Org name</th>
                        <th>Org email</th>
                        <th>Org phone</th>
                        <th>Loc name</th>
                        <th>Loc address_1</th>
                        <th>Loc address_2</th>
                        <th>Loc suburb</th>
                        <th>Loc city</th>
                        <th>Loc postcode</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($all_person_id as $person_id) {

                        $person_title = get_post_meta($person_id, 'personal_title', true);
                        $person_first_name = get_post_meta($person_id, 'first_name', true);
                        $person_last_name = get_post_meta($person_id, 'surname', true);
                        $person_email = get_post_meta($person_id, 'email_address', true);
                        $person_mobile = get_post_meta($person_id, 'mobile_number', true);
                        $person_phone = get_post_meta($person_id, 'phone_number', true);

                        $temp_role_id = (int)(get_post_meta($person_id, 'relationships-person-role', true)[0]);
                        $role_title = get_post_meta($temp_role_id, 'role_title', true);

                        $temp_org_id = (int)(get_post_meta($person_id, 'relationships-person-org', true)[0]);
                        $org_name = get_post_meta($temp_org_id, 'organisation_nmae', true);
                        $org_email = get_post_meta($temp_org_id, 'organisation_primary_email_address', true);
                        $org_phone = get_post_meta($temp_org_id, 'organisation_primary_phone_number', true);

                        $temp_loc_id = (int)(get_post_meta($person_id, 'relationships-person-location', true)[0]);
                        $loc_name = get_post_meta($temp_loc_id,'location_name', true);
                        $loc_address_1 = get_post_meta($temp_loc_id, 'location_address_1', true);
                        $loc_address_2 = get_post_meta($temp_loc_id, 'location_address_2', true);
                        $loc_suburb = get_post_meta($temp_loc_id, 'location_suburb', true);
                        $loc_name_city = get_post_meta($temp_loc_id, 'location_city', true);
                        $loc_postcode = get_post_meta($temp_loc_id, 'location_postcode', true);

                        echo "
                        <tr>
                            <td>" . $person_title . "</td>
                            <td>" . $person_first_name . "</td>
                            <td>" . $person_last_name . "</td>
                            <td>" . $person_email . "</td>
                            <td>" . $person_mobile . "</td>
                            <td>" . $person_phone . "</td>
                            <td>" . $role_title . "</td>
                            <td>" . $org_name . "</td>
                            <td>" . $org_email . "</td>
                            <td>" . $org_phone . "</td>
                            <td>" . $loc_name . "</td>
                            <td>" . $loc_address_1 . "</td>
                            <td>" . $loc_address_2 . "</td>
                            <td>" . $loc_suburb . "</td>
                            <td>" . $loc_name_city . "</td>
                            <td>" . $loc_postcode . "</td>
                        </tr>";

                        array_push($csv_arr, [$person_title, $person_first_name, $person_last_name, $person_email, $person_mobile, $person_phone, $role_title, $org_name, $org_email, $org_phone, $loc_name, $loc_address_1, $loc_address_2, $loc_suburb, $loc_name_city, $loc_postcode]);
                    } ?>
                </tbody>
            </table>
            
            <!-- button to generate the csv -->
            <input type="hidden" id="gen_csv" value='<?php echo serialize($csv_arr); ?>'>
            <input type="submit" name="generate_csv" value="EXPORT CSV" class="cpm_generate_csv">

            <script>
                jQuery(document).ready(function($) {
                    jQuery(document).on("click", ".cpm_generate_csv", function(event) {

                        event.preventDefault();

                        var array_for_csv = $('#gen_csv').val();

                        jQuery.ajax({
                            url: ajaxurl,
                            method: 'POST',
                            data: {
                                action: 'nzcbc_generate_csv',
                                array_csv: array_for_csv
                            },
                            beforeSend: function(respond) {
                            },
                            success: function(response) {
                                if (jQuery.trim(response) == "") {
                                    alert("There is no Comments For this Feed");
                                } else {
                                    var downloadLinkElement = document.createElement("a");
                                    var fileData = ["\ufeff" + response];

                                    var blobObject = new Blob(fileData, {
                                        type: "text/csv;charset=utf-8;",
                                    });

                                    var download_url = URL.createObjectURL(blobObject);
                                    var csv_file_name = "nzcbc_people_information";
                                    downloadLinkElement.href = download_url;
                                    downloadLinkElement.download = csv_file_name + ".csv";

                                    document.body.appendChild(downloadLinkElement);
                                    downloadLinkElement.click();
                                    document.body.removeChild(downloadLinkElement);
                                }
                            },
                            error: function(response) {
                            },
                            complete: function(respond) {
                            },
                        });
                    });
                });
            </script>

        <?php } ?>
    </div>
<?php }
