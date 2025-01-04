<?php

//create necessary database table

function create_table() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $schema = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}emailcollect`(
    	`id` int(10) NOT NULL AUTO_INCREMENT,
    	`name` varchar(100) NOT NULL,
    	`email`	varchar(100) NOT NULL,
    	`created_at` datetime NOT NULL,
    	PRIMARY KEY(`id`)
    )$charset_collate;";

    if ( ! function_exists( 'dbDelta' ) ) {
    	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    }

    dbDelta($schema);
}

add_action('after_setup_theme', 'create_table');

//email collection table & show shortcode
function email_collections_shortcode() {
    global $wpdb;

    // Check for edit action
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $edit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    // Fetch record for editing
    if ($edit_id > 0) {
        $record = $wpdb->get_row("SELECT * FROM `{$wpdb->prefix}emailcollect` WHERE `id` = {$edit_id}");
    } else {
        $record = null;
    }

    ob_start();

    
    $errors = get_transient('errors');
    $success = get_transient('success');

    if ($errors) {
        delete_transient('errors');
    }
    if ($success) {
        delete_transient('success');
    }

    
    ?>
    <div class="container card p-3 mt-5">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><?php echo esc_html($errors); ?></div>            
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo esc_html($success); ?></div>            
        <?php endif; ?>
        <form action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ); ?>" method="post">
            <div class="mb-3">
                <label for="n" class="form-label">Your Name</label>
                <input type="text" name="name" class="form-control" value="<?php echo $record ? esc_attr($record->name) : ''; ?>" id="n">                       
            </div>
            <div class="mb-3">
                <label for="e" class="form-label">Your Email</label>
                <input type="email" name="email" class="form-control" id="e" value="<?php echo $record ? esc_attr($record->email) : ''; ?>">
            </div>
            <input type="hidden" name="edit_id" value="<?php echo $record ? esc_attr($record->id) : ''; ?>">
            <?php wp_nonce_field('email_nonce'); ?>
            <?php submit_button($record ? 'update' : 'submit', 'btn btn-primary', 'action'); ?>
        </form>
    </div>

    <?php 
        $results = $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}emailcollect` ORDER BY `id` DESC" );
    ?>
    <div class="container card p-3 mt-5">
        <h2>Email Listing</h2>
        <table class="table table-striped table-hover table-bordered">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($results)) : ?>
                    <?php foreach ($results as $result) : ?>
                        <tr>
                            <td><?php echo esc_html($result->id); ?></td>
                            <td><?php echo esc_html($result->name); ?></td>
                            <td><?php echo esc_html($result->email); ?></td>
                            <td>
                                <a href="?action=edit&id=<?php echo $result->id; ?>" class="btn btn-sm btn-info">Edit</a>
                                <a href="?action=delete&id=<?php echo $result->id; ?>" class="btn btn-sm btn-danger">Delete</a> 
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="4">No records found.</td>
                    </tr>
                <?php endif; ?>           
            </tbody>
        </table>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('email_collections', 'email_collections_shortcode');

function email_collect_form_handler() {
    global $wpdb;

    // Handle form submission (either add or update)
    if (isset($_POST['action'])) {

        
        if ($_POST['action'] === 'update' && isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {

            // Edit
            $edit_id = intval($_POST['edit_id']);
            $name    = sanitize_text_field($_POST['name']);
            $email   = sanitize_email($_POST['email']);

            // Validate input
            if (empty($name)) {
                set_transient('errors', 'Name is required.', 60);
            } elseif (empty($email)) {
                set_transient('errors', 'Email is required.', 60);
            } elseif (!is_email($email)) {
                set_transient('errors', 'Invalid email format.', 60);
            } else {
                // Update record in database
                $wpdb->update(
                    "{$wpdb->prefix}emailcollect",
                    ['name' => $name, 'email' => $email],
                    ['id' => $edit_id],
                    ['%s', '%s'],
                    ['%d']
                );
                set_transient('success', 'Record updated successfully!', 60);
            }

        } else if ($_POST['action'] === 'submit') {
            
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'email_nonce')) {
                set_transient('errors', 'Security check failed. Please try again.', 60);
                wp_redirect(remove_query_arg(['action', 'id'], $_SERVER['REQUEST_URI']));
                exit;
            }

            $name  = sanitize_text_field($_POST['name']);
            $email = sanitize_email($_POST['email']);

            // Validate input
            if (empty($name)) {
                set_transient('errors', 'Name is required.', 60);
            } elseif (empty($email)) {
                set_transient('errors', 'Email is required.', 60);
            } elseif (!is_email($email)) {
                set_transient('errors', 'Invalid email format.', 60);
            } else {
                // Insert new record
                $wpdb->insert(
                    "{$wpdb->prefix}emailcollect",
                    ['name' => $name, 'email' => $email, 'created_at' => current_time('mysql')],
                    ['%s', '%s', '%s']
                );
                set_transient('success', 'New record added successfully!', 60);
            }
        }

        wp_redirect(esc_url($_SERVER['REQUEST_URI']));
        exit;
    }

    // Handle delete action
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {

        $delete_id = intval($_GET['id']);
        $wpdb->delete("{$wpdb->prefix}emailcollect", ['id' => $delete_id], ['%d']);
        set_transient('success', 'Record deleted successfully!', 60);

        wp_redirect(esc_url($_SERVER['REQUEST_URI']));
        exit;
    }
}
add_action('init', 'email_collect_form_handler');


