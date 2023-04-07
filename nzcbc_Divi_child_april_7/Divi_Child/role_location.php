<?php
//hiding an role_location acf field
if (!function_exists('nzcbc_hide_role_location_acf')) {
    function nzcbc_hide_role_location_acf($field)
    {
        return false;
    }
}
add_filter("acf/prepare_field/name=relationship_-_role_-_location", "nzcbc_hide_role_location_acf");

// including custom jQuery
function shapeSpace_include_custom_jquery()
{
    wp_deregister_script('jquery');
    wp_enqueue_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js', array(), null, true);
}
add_action('admin_enqueue_scripts', 'shapeSpace_include_custom_jquery');


// -------------------------------add new role location section starts -----------------------------------------

// Note: this nzcbc_update_location_acf function works for both add new and edit role location page.
add_action('wp_ajax_nzcbc_update_location_acf', 'nzcbc_update_location_acf');
if (!function_exists('nzcbc_update_location_acf')) {
    function nzcbc_update_location_acf()
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

if (!function_exists('nzcbc_save_role_location_id')) {
    function nzcbc_save_role_location_id($post_id)
    {
        $post_id = get_the_ID(); //getting post id of the current post
        if (isset($_POST['acf']['field_642a609f59270'])) {
            $selected_location_id = [$_POST['acf']['field_642a609f59270']]; // getting role location field  by id
            update_post_meta($post_id, 'relationships-role-location', $selected_location_id);  //saving selected location id to post meta table
        }
    }
}
add_action('acf/save_post', 'nzcbc_save_role_location_id', 20);

if (!function_exists('nzcbc_role_location_acf')) {
    add_action('admin_footer', 'nzcbc_role_location_acf');
    function nzcbc_role_location_acf()
    {
        global $pagenow;

        //checking if post type is role
        if ($pagenow == 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'role') {
        ?>
            <script>
                jQuery(document).ready(function() {
                    //getting organzation field id
                    jQuery("#acf-field_63f5742ae9411").change(function() {
                        let selected_org_id = jQuery(this).children("option:selected").val();
                        jQuery.ajax({
                            url: ajaxurl,
                            method: 'POST',
                            data: {
                                action: 'nzcbc_update_location_acf',
                                selected_org: selected_org_id,
                            },
                            beforeSend: function(respond) {
                                // console.log('before' + respond);
                            },
                            success: function(response) {
                                // Display a success message if the title is available
                                // console.log(response.data);

                                //remove previous options
                                document.getElementById("acf-field_642a609f59270").innerHTML = "";

                                //add new options
                                var location_list = document.getElementById('acf-field_642a609f59270'); //getting role location field  by id

                                for (const [key, value] of Object.entries(response.data)) {
                                    // console.log(key, value);
                                    var objOption = document.createElement("option");
                                    objOption.value = key;
                                    objOption.text = value;
                                    location_list.options.add(objOption);
                                }
                            },
                            error: function(response) {
                                // Display an error message if the title already exists
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
                    var locationSelect = jQuery("#acf-field_642a609f59270"); //getting role location field by id

                    // Check if there is only one option in location field
                    if (locationSelect.children("options").length === 1) {

                        // If there is only one option, trigger the change event manually
                        jQuery(document).ready(function() {
                            selected_location = locationSelect.val();
                        });
                        jQuery.ajax({
                            url: ajaxurl,
                            method: 'POST',
                            data: {
                                action: 'nzcbc_save_role_location_id',
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
                                    action: 'nzcbc_save_role_location_id',
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
                        });
                    }
                });
            </script>
        <?php

        }
    }
}
// ---------------------------------add new role location section ends here -----------------------------------

//  ---------------------------------edit role location section starts here ---------------------------------

add_action('admin_init', 'nzcbc_get_selected_location_id');
if(!function_exists('nzcbc_get_selected_location_id')){
function nzcbc_get_selected_location_id()
{
    //checking if page is edit
    if (isset($_GET['action']) && $_GET['action'] == 'edit') {
        $post_id = $_GET['post']; //getting current post_id of the edit page
        $get_selected_location_id = get_post_meta($post_id, 'relationships-role-location', true); //getting role location id according to the post id 
        if (!empty($get_selected_location_id)) {
            echo "<input type='hidden' id='nzcbc_selected_loc_id' value='$get_selected_location_id[0]'>"; //passing selected location id as value in hidden field
        }
    }
}
}

add_action('admin_footer', 'nzcbc_edit_role_location_acf');
if (!function_exists('nzcbc_edit_role_location_acf')) {
    function nzcbc_edit_role_location_acf()
    {
        //checking if the page is edit
        if (isset($_GET['action']) && $_GET['action'] == 'edit') {
        ?>
            <script>
                jQuery(document).ready(function($) {
                    //geting role organization field value by field id
                    let selected_org_id = $("#acf-field_63f5742ae9411").children("option:selected").val();
                    let nzcbc_selected_location_id = $('#nzcbc_selected_loc_id').val();
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
                            // Display a success message if the title is available

                            //remove previous options
                            document.getElementById("acf-field_642a609f59270").innerHTML = "";

                            //add new options
                            var location_list = document.getElementById('acf-field_642a609f59270');  //getting role location field by id

                            for (const [key, value] of Object.entries(response.data)) {
                                // console.log(key, value);
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
                            // Display an error message if the title already exists
                        },
                        complete: function(respond) {    
                        },
                    });

                    $("#acf-field_63f5742ae9411").change(function() {
                        let selected_org_id = $("#acf-field_63f5742ae9411").children("option:selected").val();
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
                                // Display a success message if the title is available

                                //remove previous options
                                document.getElementById("acf-field_642a609f59270").innerHTML = "";

                                //add new options
                                var location_list = document.getElementById('acf-field_642a609f59270');  // getting role location field by id

                                for (const [key, value] of Object.entries(response.data)) {
                                    // console.log(key, value);
                                    var objOption = document.createElement("option");
                                    objOption.value = key;
                                    objOption.text = value;
                                    location_list.options.add(objOption);
                                }
                            },
                            error: function(response) {
                                // Display an error message if the title already exists                              
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

//  ---------------------------------edit role location section ends  here ---------------------------------
