<?php

/**
 * Plugin Name:     lGT Status
 * Plugin URI:      https://github.com/3ele-projects/lgt-status
 * Description:     Add LGT Status Field to Backend and Frontend 
 * Author:          Sebastian Weiss
 * Author URI:      https://lightweb-media.de
 * Text Domain:     author-status
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         lGT Status
 */


add_role(
    'aussteller',
    __('Aussteller'),
    array(
        'read'         => true,
        'edit_user' => true

    )
);

function check_user_role($roles, $user_id = null)
{
    global $current_user;
    if (empty($user)) return false;

    foreach ($user->roles as $role) {
        if (in_array($role, $roles)) {
            return true;
        }
    }
    return false;
}

function lw_lgt_disable_admin_bar()
{
    $user = wp_get_current_user();

    if (in_array('aussteller', $user->roles)) {

        //The user has the "aussteller" role
        add_filter('show_admin_bar', '__return_false');
    }
}


add_action('init', 'lw_lgt_disable_admin_bar', 9);

function aussteller_form_frontend_login()
{
    global $post;
    $args = array(
        'echo' => true,
        'redirect' => get_the_permalink($post->ID),
        'form_id' => 'aussteller_login',
        'label_username' => __('Username'),
        'label_password' => __('Password'),
        'label_remember' => __('Remember Me'),
        'label_log_in' => __('Log In'),
        'id_username' => 'user_login',
        'id_password' => 'user_pass',
        'id_remember' => 'rememberme',
        'id_submit' => 'wp-submit',
        'remember' => true,
        'value_username' => NULL,
        'value_remember' => false

    );
    wp_login_form($args);
}

add_action('show_user_profile', 'module_user_profile_fields');
add_action('edit_user_profile', 'module_user_profile_fields');

function module_user_profile_fields($user)
{
    if (in_array('aussteller', $user->roles)) :
        global $user_id;
?>
        <h3><?php _e('Status Meldung'); ?></h3>

        <table class="form-table">
            <tr>
                <th><label for="lgt_status">Aktueller Status</label></th>
                <td>
                    <textarea id="lgt_status" name="lgt_status" rows="4" cols="50" maxlength="400"><?php echo sanitize_textarea_field(nl2br(get_the_author_meta('lgt_status', $user_id))); ?></textarea>
                </td>
            </tr>
        </table>
    <?php endif;
}

function lgt_form_frontend_form($user_id)

{ ?>

    <form method="POST" id="logout" action="<?php the_permalink(); ?>">
        <?php
        echo '<h2>Hallo ' . get_the_author_meta('nickname', $user_id);
        ?>
        <input name="logged_out" type="submit" id="logged_out" class="submit button" style="margin-left:30px; font-size:10px" value="<?php _e('Log out', 'profile'); ?>" />
        </h2>
        <input name="action" type="hidden" id="action" value="logged_out" />
        <?php wp_nonce_field('logged_out') ?>
    </form>


    <?php if (get_the_author_meta('lgt_status', $user_id)) {
        echo '<p>Letzte Status Meldung:</p>';
        echo nl2br(get_the_author_meta('lgt_status', $user_id));
    } ?>

    <hr>

    <form method="POST" id="adduser" action="<?php the_permalink(); ?>">
        <label>Status (max. 400 Zeichenlänge)</label>
        </br>
        <textarea id="lgt_status" name="lgt_status" rows="4" cols="50" maxlength="400"><?php echo get_the_author_meta('lgt_status', $user_id); ?></textarea>
        <br>
        <input name="updateuser" type="submit" id="updateuser" class="submit button" value="<?php _e('Update Status', 'profile'); ?>" />

        <?php wp_nonce_field('update-user') ?>
        <input name="action" type="hidden" id="action" value="update-user" />

    </form>

    <?php
    if (!user_can($user_id, "aussteller")) {
        echo "<h2>Please Login to Update Status.</h2>";
        return;
    }
    

    if ('POST' == $_SERVER['REQUEST_METHOD'] && !empty($_POST['action']) && $_POST['action'] == 'update-user') {
        $nonce = $_POST['_wpnonce'];
        if (!empty($_POST['lgt_status'])) {
            update_user_meta($user_id, 'lgt_status', sanitize_textarea_field($_POST['lgt_status']));

            do_action('edit_user_profile_update', $user_id);
            wp_redirect(get_the_permalink());
            exit;
        }
    }
    if ('POST' == $_SERVER['REQUEST_METHOD'] && !empty($_POST['action']) && $_POST['action'] == 'logged_out') {
        $nonce = $_POST['_wpnonce'];
        if (!empty($_POST['logged_out'])) {
            if (!wp_verify_nonce($nonce, 'logged_out')) {
                exit; // Get out of here, the nonce is rotten!
            }
            wp_logout();
            wp_redirect(get_the_permalink());
        }
    }
}

add_shortcode('lgt_form_frontend', 'lgt_form_frontend');

function lgt_form_frontend()
{
    global $current_user;

    if (is_user_logged_in()) {
    
  
            if (in_array('aussteller', $current_user->roles)) {
                lgt_form_frontend_form($current_user->ID);
            }
      
    } else {
        aussteller_form_frontend_login(); ?>
        <style>
            #aussteller_login p label {
                min-width: 180px;
            }
        </style>
<?php
    };
}


add_shortcode('lgt_status', 'lgt_status_print');

function lgt_status_print()
{
    if (isset($_GET['user_id'])) 
    {
        $user_id = $_GET['user_id'];
        if (is_numeric($user_id)):
        if (get_the_author_meta('lgt_status', $user_id)) :
            echo nl2br(sanitize_textarea_field(get_the_author_meta('lgt_status', $user_id)));
        else :
            echo 'Kein Status';
        endif;
        else :
            echo 'Kein Status';
    endif;
    } else {
        echo 'Kein Status';
    }
}

add_action('personal_options_update', 'save_extra_user_profile_fields');
add_action('edit_user_profile_update', 'save_extra_user_profile_fields');

function save_extra_user_profile_fields($user_id)
{
    if (empty($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update-user_' . $user_id)) {
        return;
    }

    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    update_user_meta($user_id, 'lgt_status', sanitize_textarea_field($_POST['lgt_status']));
}

if (check_user_role(array('aussteller'))) {
    add_filter('show_admin_bar', '__return_false');
}
