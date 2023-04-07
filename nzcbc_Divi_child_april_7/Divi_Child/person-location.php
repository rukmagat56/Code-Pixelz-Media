<?php
add_action('wp_ajax_nzcbc_update_person_location_acf', 'nzcbc_update_person_location_acf');
if (!function_exists('nzcbc_update_person_location_acf')) {
    function nzcbc_update_person_location_acf()
    {
        $selected_org_id = (int)$_POST['selected_org'];
        $all_location_post_ids_arr = get_post_meta($selected_org_id, "relationships_-_org_-_location", true);
        $all_location_assoc_arr = [];

        foreach ($all_location_post_ids_arr as $location) {
            $all_location_assoc_arr[$location] = get_the_title($location);
        }
        /* Sending the data to the ajax success function. */
        wp_send_json_success($all_location_assoc_arr);
    }
}

// --------------------------------add new person location starts------------------------------
if (!function_exists('nzcbc_save_person_location_id')) {
    // saving selected person location id in post meta table
    function nzcbc_save_person_location_id($post_id)
    {
        $post_id = get_the_ID();
        if (isset($_POST['acf']['field_642e83ffafc49'])) {
            $selected_location_id = $_POST['acf']['field_642e83ffafc49']; // getting person location field by id
            if (!is_array($selected_location_id)) {
                $selected_location_id = array($selected_location_id);
            }
            update_post_meta($post_id, 'relationships-person-location', $selected_location_id);
        }

        //when editing person -> org
        if (isset($_POST['acf']['field_6426b34d46d2d'])) {
            $selected_org_id = $_POST['acf']['field_6426b34d46d2d']; // getting person organization field by id

            if (!is_array($selected_org_id)) {
                $selected_org_id = array($selected_org_id);
            }
            update_post_meta($post_id, 'relationships-person-org', $selected_org_id);
        }
    }
}
add_action('acf/save_post', 'nzcbc_save_person_location_id', 19);

if (!function_exists('nzcbc_person_location_acf')) {
    add_action('admin_footer', 'nzcbc_person_location_acf');
    function nzcbc_person_location_acf()
    {
        global $pagenow;
        //checking if post type is person
        if ($pagenow == 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'person') {
?>
            <script>
                jQuery(document).ready(function() {
                    //getting person organzation field  by id  in jquery
                    jQuery("#acf-field_6426b34d46d2d").change(function() {
                        let selected_org_id = jQuery(this).children("option:selected").val();
                        jQuery.ajax({
                            url: ajaxurl,
                            method: 'POST',
                            data: {
                                action: 'nzcbc_update_person_location_acf',
                                selected_org: selected_org_id,
                            },
                            beforeSend: function(respond) {
                                // console.log('before' + respond);
                            },
                            success: function(response) {
                                // Display a success message if the title is available
                                // console.log(response.data);

                                //remove previous options
                                document.getElementById("acf-field_642e83ffafc49").innerHTML = "";

                                //add new options
                                var location_list = document.getElementById('acf-field_642e83ffafc49'); // getting person location field by id

                                for (const [key, value] of Object.entries(response.data)) {
                                    var objOption = document.createElement("option");
                                    objOption.value = key;
                                    objOption.text = value;
                                    location_list.options.add(objOption);
                                }
                            },
                            error: function(response) {
                                // Display an error message 
                                // console.log(response.responseJSON.data);
                            },
                            complete: function(respond) {
                                // console.log('complete' + respond);
                            },
                        });
                    });
                });

                //for getting location id
                jQuery(document).ready(function() {
                    var locationSelect = jQuery("#acf-field_642e83ffafc49"); //getting  peroson location field by id

                    // Check if there is only one option
                    if (locationSelect.children("options").length === 1) {
                        // If there is only one option, trigger the change event manually
                        jQuery(document).ready(function() {
                            selected_location = locationSelect.val();
                        });
                        jQuery.ajax({
                            url: ajaxurl,
                            method: 'POST',
                            data: {
                                action: 'nzcbc_save_person_location_id',
                                selected_location_id: selected_location,
                            },
                            beforeSend: function(respond) {

                            },
                            success: function(response) {
                                // Display a success message if the location is saved

                            },
                            error: function(response) {
                                // Display an error message if the location is not saved

                            },
                            complete: function(respond) {

                            },
                        });
                    } else {
                        // Otherwise, attach the change event handler as before
                        locationSelect.change(function() {
                            selected_location = jQuery(this).val();
                            jQuery.ajax({
                                url: ajaxurl,
                                method: 'POST',
                                data: {
                                    action: 'nzcbc_save_person_location_id',
                                    selected_location_id: selected_location,

                                },
                                beforeSend: function(respond) {

                                },
                                success: function(response) {
                                    // Display a success message if the location is saved

                                },
                                error: function(response) {
                                    // Display an error message 

                                },
                                complete: function(respond) {

                                },
                            });
                        });
                    }
                });
            </script>
        <?php

        }
    }
}
// --------------------------------add new person location ends------------------------------

//-------------------------------------edit person location starts---------------------------------
add_action('admin_init', 'nzcbc_get_selected_person_location_id');
if (!function_exists('nzcbc_get_selected_person_location_id')) {
    function nzcbc_get_selected_person_location_id()
    {

        if (isset($_GET['action']) && $_GET['action'] == 'edit') {
            $post_id = $_GET['post'];
            $get_selected_person_location_id = get_post_meta($post_id, 'relationships-person-location', true); //getting selected person location id
            if (!empty($get_selected_person_location_id)) {
                echo "<input type='hidden' id='nzcbc_selected_per_loc_id' value='$get_selected_person_location_id[0]'>"; // passing selected person location id in hidden field
            }
        }
    }
}

add_action('admin_footer', 'nzcbc_edit_person_location_acf');
if (!function_exists('nzcbc_edit_person_location_acf')) {
    function nzcbc_edit_person_location_acf()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'edit') {
        ?>
            <script>
                jQuery(document).ready(function($) {
                    let selected_org_id = $("#acf-field_6426b34d46d2d").children("option:selected").val();
                    let nzcbc_selected_location_id = $('#nzcbc_selected_per_loc_id').val(); //getting selelcted person location value from hidden field
                    jQuery.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'nzcbc_update_location_acf',
                            selected_org: selected_org_id,
                        },
                        beforeSend: function(respond) {

                        },
                        success: function(response) {
                            // Display a success message 

                            //remove previous options
                            document.getElementById("acf-field_642e83ffafc49").innerHTML = "";

                            //add new options
                            var location_list = document.getElementById('acf-field_642e83ffafc49'); //getting person location field by id

                            for (const [key, value] of Object.entries(response.data)) {
                                var objOption = document.createElement("option");
                                objOption.value = key;
                                objOption.text = value;
                                if (key == nzcbc_selected_location_id) {
                                    objOption.selected = true;
                                }
                                location_list.options.add(objOption);
                            }
                        },
                        error: function(response) {
                            // Display an error message

                        },
                        complete: function(respond) {
                        },
                    });
                    
                 //getting person organization field by id and using it on jquery change function
                    $("#acf-field_6426b34d46d2d").change(function() {
                        let selected_org_id = $("#acf-field_6426b34d46d2d").children("option:selected").val();
                        jQuery.ajax({
                            url: ajaxurl,
                            method: 'POST',
                            data: {
                                action: 'nzcbc_update_location_acf',
                                selected_org: selected_org_id,
                            },
                            beforeSend: function(respond) {
                            },
                            success: function(response) {
                                // Display a success message

                                //remove previous options
                                document.getElementById("acf-field_642e83ffafc49").innerHTML = "";

                                //add new options
                                var location_list = document.getElementById('acf-field_642e83ffafc49'); // getting person location field by id

                                for (const [key, value] of Object.entries(response.data)) {

                                    var objOption = document.createElement("option");
                                    objOption.value = key;
                                    objOption.text = value;
                                    location_list.options.add(objOption);
                                }
                            },
                            error: function(response) {
                            // Display an error message 
                            },
                            complete: function(respond) {
                            },
                        });
                    });
                });
            </script>
<?

        }
    }
}
