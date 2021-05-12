<?php
/**
 * Plugin Name: Banner
 * Plugin URI: 
 * Description: JNP Banner Plugin
 * Version: 1.0
 * Author: Jesper Nissen-Pedersen
 * Author URI: https://jespernpedersen.dk
 */


// Initialize backend menu item
if( is_admin() ) {
    function banner_options() {
        add_menu_page( 'Settings', 'Banner', 'manage_options', 'jnp-banner', 'banner_settings', 'dashicons-feedback' );
        add_submenu_page('jnp-banner', 'Settings', 'Settings', 'manage_options', 'jnp-banner' );
        if(check_api_validation()) {
            add_submenu_page('jnp-banner', 'Appearance', 'Appearance', 'manage_options', 'banner-appearance', 'banner_appearance' );
        }
    }
    add_action('admin_menu', 'banner_options');
}

// Output frontend
add_filter( 'wp_body_open', 'banner_frontend' );
function banner_frontend ( $content ) {
    $banner_frontend_options = get_option('banner_plugin_options_appearance');
    $banner_output = '<div class="banner">' . $banner_frontend_options['banner_text'] . '</div>';
    $banner = print($banner_output);
    $output = $banner . $content;
}

// Output frontend CSS
add_filter('wp_footer', 'banner_style');
function banner_style () {
    $banner_frontend_options = get_option('banner_plugin_options_appearance');
    // Style options
    $style = '';
    $style .= 'text-align: center;';
    $style .= 'background-color: ' . $banner_frontend_options['bgcolor'] . ';';
    $style .= 'color: ' . $banner_frontend_options['textcolor'] . ';';

    // Style output
    echo '<style> .banner {' . $style . '}</style>';
}

// Register backend view for API key
function banner_settings() {
    ?>
    <h1>Banner</h1>
    <form action="options.php" method="post">
        <?php 
            settings_fields( 'banner_plugin_options' );
            do_settings_sections( 'banner_plugin' ); 
        ?>
        <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save' ); ?>" />
    </form>
    <?php
}

function banner_appearance() {
    ?>
    <h1>Appearance</h1>
    <form action="options.php" method="post">
        <?php 
            settings_fields( 'banner_plugin_options_appearance' );
            do_settings_sections( 'banner_frontend' ); 
        ?>
        <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save' ); ?>" />
    </form>
    <?php
}

// Register settings
function banner_customization() {
    // API Settings
    register_setting( 'banner_plugin_options', 'banner_plugin_options', 'banner_plugin_options_validate');
    add_settings_section( 'api_settings', 'API Settings', 'banner_plugin_section_text', 'banner_plugin' );

    add_settings_field( 'banner_plugin_enable', 'Enable Banner', 'banner_plugin_setting_enable', 'banner_plugin', 'api_settings');
    add_settings_field( 'banner_plugin_email', 'Email', 'banner_plugin_setting_email', 'banner_plugin', 'api_settings');
    add_settings_field( 'banner_plugin_apikey', 'API Key', 'banner_plugin_setting_api_key', 'banner_plugin', 'api_settings' );

    // Appearance Settings
    register_setting( 'banner_plugin_options_appearance', 'banner_plugin_options_appearance');
    add_settings_section( 'banner_frontend_settings', 'Basic Settings', 'banner_appearance_section_text', 'banner_frontend' );

    add_settings_field( 'banner_text', 'Banner Text', 'banner_plugin_setting_text', 'banner_frontend', 'banner_frontend_settings');
    add_settings_field( 'bg_color', 'Background Color', 'banner_plugin_bgcolor', 'banner_frontend', 'banner_frontend_settings');
    add_settings_field( 'text_color', 'Text Color', 'banner_plugin_textcolor', 'banner_frontend', 'banner_frontend_settings');
}

add_action('admin_init', 'banner_customization');


function check_api_validation() {
    $validation = get_option( 'banner_plugin_options' );
    $body = array(
        'email'   => sanitize_email( $validation['email'] ),
        'key'     => $validation['api_key']
    );
    $args = array(
        'body' => $body,
    );
    $response = wp_remote_post( 'http://127.0.0.1:8000/api/key/check', $args );
    $code = $response['response']['code'];
    $status = json_decode($response['body']);
    if($status->code == 6 && $status->message == 'Key is active') {
        return true;
    }
    else {
        return false;
    }
}


function banner_plugin_options_validate( $input ) {
    $body = array(
        'email'   => sanitize_email( $input['email'] ),
        'key'     => $input['api_key']
    );
    $args = array(
        'body' => $body,
    );
    $response = wp_remote_post( 'http://127.0.0.1:8000/api/key/check', $args );
    $code = $response['response']['code'];
    $status = json_decode($response['body']);
    if($status->code == 6 && $status->message == 'Key is active') {
        return $input;
    }
    else {
        wp_die("Your key could not be validated");
    }
}

// Set API section text
function banner_plugin_section_text() {
    echo '<p>Here you can set all the options for using the API</p>';
    if(!check_api_validation()) {
        echo "Your plugin do not have a <span style='color:  red;'>validated key</span>. Please buy one and activate it here.";
    }
}

// Set options for API input field
function banner_plugin_setting_enable() {
    $options = get_option( 'banner_plugin_options' );
    echo '<input value="0" name="banner_plugin_options[enable]" type="hidden">';
    echo '<input value="1" name="banner_plugin_options[enable]" id="banner_plugin_setting_enable" ' . checked ( TRUE, esc_attr( $options['enable'] ), false ) . ' type="checkbox">';
}

function banner_plugin_setting_email() {
    $options = get_option( 'banner_plugin_options' );
    if(isset($options['email'])) {
        echo "<input id='banner_plugin_setting_email' name='banner_plugin_options[email] type='email' value='" . esc_attr( $options['email'] ) . "' />";
    }
    else {
        echo "<input id='banner_plugin_setting_email' name='banner_plugin_options[email] type='email' value='' />";

    }
}

// Set options for API input field
function banner_plugin_setting_api_key() {
    $options = get_option( 'banner_plugin_options' );
    if(isset($options['api_key'])) {
        echo "<input id='banner_plugin_setting_api_key' name='banner_plugin_options[api_key]' type='text' value='" . esc_attr( $options['api_key'] ) . "' />";
    }
    else {
        echo "<input id='banner_plugin_setting_api_key' name='banner_plugin_options[api_key]' type='text' value='' />";
    }
}



// Set API section text
function banner_appearance_section_text() {
    echo '<p>here you can customize the appearance of the banner</p>';
}

// Set options for banner text input field
function banner_plugin_setting_text() {
    $options = get_option( 'banner_plugin_options_appearance' );
    echo '<input id="banner_plugin_setting_bannertext" name="banner_plugin_options_appearance[banner_text]" type="text" value="' . esc_attr( $options['banner_text'] ) . '" />';
}

// Set options for banner text input field
function banner_plugin_bgcolor() {
    $options = get_option( 'banner_plugin_options_appearance' );
    echo '<input id="banner_plugin_setting_bgcolor" name="banner_plugin_options_appearance[bgcolor]" type="color" value="' . esc_attr( $options['bgcolor'] ) . '" />';
}

// Set options for banner text input field
function banner_plugin_textcolor() {
    $options = get_option( 'banner_plugin_options_appearance' );
    echo '<input id="banner_plugin_setting_textcolor" name="banner_plugin_options_appearance[textcolor]" type="color" value="' . esc_attr( $options['textcolor'] ) . '" />';
}

?>
