<?php
/*
Plugin Name: BitBucket Trigger Pipeline
Plugin URI: https://github.com/kmturley/wordpress-bitbucket-trigger-pipeline
Description: Wordpress Plugin triggers a BitBucket Pipeline when user publishes a post (using environment variables BITBUCKET_PROJECT, BITBUCKET_USERNAME and BITBUCKET_PASSWORD)
Version: 0.1
Author: Kim T
Author URI: https://github.com/kmturley
License: GPL
Copyright: Kim T
*/


/**
 * Add a page to the dashboard menu.
 */
function wpdocs_plugin_menu()
{
  add_dashboard_page(__('Deploy to Development', 'textdomain'), __('Deploy to Development', 'textdomain'), 'read', 'deploy-to-development', 'publish_static_hook_development');
}
add_action('admin_menu', 'wpdocs_plugin_menu');

include_once(ABSPATH . 'wp-includes/pluggable.php');

function check_if_is_admin()
{
  if (!is_admin()) {
    return false;
  }
  if (!is_user_logged_in()) {
    return false;
  }
  if (!current_user_can('administrator')) {
    return false;
  }
  return true;
}


function publish_static_hook_development()
{

  $bitbucket_project = get_option('option_project_development');
  $bitbucket_branch = get_option('option_branch_development');
  $bitbucket_username = get_option('option_username_development');
  $bitbucket_app_password = get_option('option_password_development');

  // if variables are set, then trigger static build
  if ($bitbucket_project && $bitbucket_branch && $bitbucket_username && $bitbucket_app_password) {
    $data = array(
      'target' => array(
        'ref_type' => 'branch',
        'type' => 'pipeline_ref_target',
        'ref_name' => $bitbucket_branch
      )
    );
    $checkPipelineStatus = wp_remote_get(
      'https://api.bitbucket.org/2.0/repositories/' . $bitbucket_project . '/<reponame>/pipelines/?sort=-created_on',
      array(
        'headers' => array(
          'Authorization' => 'Basic ' . base64_encode($bitbucket_username . ':' . $bitbucket_app_password),
          'Content-Type' => 'application/json'
        ),
      )

    );
    $formatData = json_decode($checkPipelineStatus['body'], true);
    if (strtoupper($formatData['values'][0]['state']['name']) !== 'IN_PROGRESS') {
      wp_remote_post('https://api.bitbucket.org/2.0/repositories/' . $bitbucket_project . '/<reponame>/pipelines/', array(
        'body' => json_encode($data),
        'headers' => array(
          'Authorization' => 'Basic ' . base64_encode($bitbucket_username . ':' . $bitbucket_app_password),
          'Content-Type' => 'application/json'
        ),
      ));
      echo 'Pipeline has been triggered';
    }
    echo 'Pipeline is still in progress, please wait.';
  }
}
if (check_if_is_admin()) {
  function publish_static_hook_production()
  {

    $bitbucket_project = get_option('option_project_production');
    $bitbucket_branch = get_option('option_branch_production');
    $bitbucket_username = get_option('option_username_production');
    $bitbucket_app_password = get_option('option_password_production');

    // if variables are set, then trigger static build
    if ($bitbucket_project && $bitbucket_branch && $bitbucket_username && $bitbucket_app_password) {
      $data = array(
        'target' => array(
          'ref_type' => 'branch',
          'type' => 'pipeline_ref_target',
          'ref_name' => $bitbucket_branch
        )
      );

      $checkPipelineStatus = wp_remote_get(
        'https://api.bitbucket.org/2.0/repositories/' . $bitbucket_project . '/<reponame>/pipelines/?sort=-created_on',
        array(
          'headers' => array(
            'Authorization' => 'Basic ' . base64_encode($bitbucket_username . ':' . $bitbucket_app_password),
            'Content-Type' => 'application/json'
          ),
        )
      );

      $formatData = json_decode($checkPipelineStatus['body'], true);
      if (strtoupper($formatData['values'][0]['state']['name']) !== 'IN_PROGRESS') {
        wp_remote_post('https://api.bitbucket.org/2.0/repositories/' . $bitbucket_project . '/pipelines/', array(
          'body' => json_encode($data),
          'headers' => array(
            'Authorization' => 'Basic ' . base64_encode($bitbucket_username . ':' . $bitbucket_app_password),
            'Content-Type' => 'application/json'
          ),
        ));
        echo 'Pipeline has been triggered';
      }
      echo 'Pipeline is still in progress, please wait.';
    }
  }
}

add_action('admin_init', 'developmentSection');
if (check_if_is_admin()) {
  add_action('admin_init', 'productionSection');
}
function developmentSection()
{
  add_settings_section(
    'my_settings_section_development',
    'Bitbucket Settings Development',
    'my_section_options_callback_development',
    'general'
  );

  add_settings_field(
    'option_project',
    'BITBUCKET PROJECT DEVELOPMENT',
    'my_textbox_callback',
    'general',
    'my_settings_section_development',
    array(
      'option_project_development'
    )
  );

  add_settings_field(
    'option_branch',
    'BITBUCKET BRANCH DEVELOPMENT',
    'my_textbox_callback',
    'general',
    'my_settings_section_development',
    array(
      'option_branch_development'
    )
  );

  add_settings_field(
    'option_username',
    'BITBUCKET USERNAME DEVELOPMENT',
    'my_textbox_callback',
    'general',
    'my_settings_section_development',
    array(
      'option_username_development'
    )
  );

  add_settings_field(
    'option_password',
    'BITBUCKET APP PASSWORD DEVELOPMENT',
    'my_password_callback',
    'general',
    'my_settings_section_development',
    array(
      'option_password_development'
    )
  );

  register_setting('general', 'option_project', 'esc_attr');
  register_setting('general', 'option_branch', 'esc_attr');
  register_setting('general', 'option_username', 'esc_attr');
  register_setting('general', 'option_password', 'esc_attr');
}

function productionSection()
{
  add_settings_section(
    'my_settings_section_production',
    'Bitbucket Settings Production',
    'my_section_options_callback_production',
    'general'
  );

  add_settings_field(
    'option_project_production',
    'BITBUCKET PROJECT PRODUCTION',
    'my_textbox_callback',
    'general',
    'my_settings_section_production',
    array(
      'option_project_production'
    )
  );

  add_settings_field(
    'option_branch_production',
    'BITBUCKET BRANCH PRODUCTION',
    'my_textbox_callback',
    'general',
    'my_settings_section_production',
    array(
      'option_branch_production'
    )
  );

  add_settings_field(
    'option_username_production',
    'BITBUCKET USERNAME PRODUCTION',
    'my_textbox_callback',
    'general',
    'my_settings_section_production',
    array(
      'option_username_production'
    )
  );

  add_settings_field(
    'option_password_production',
    'BITBUCKET APP PASSWORD PRODUCTION',
    'my_password_callback',
    'general',
    'my_settings_section_production',
    array(
      'option_password_production'
    )
  );

  register_setting('general', 'option_project_development', 'esc_attr');
  register_setting('general', 'option_branch_development', 'esc_attr');
  register_setting('general', 'option_username_development', 'esc_attr');
  register_setting('general', 'option_password_development', 'esc_attr');
  if (check_if_is_admin()) {
    register_setting('general', 'option_project_production', 'esc_attr');
    register_setting('general', 'option_branch_production', 'esc_attr');
    register_setting('general', 'option_username_production', 'esc_attr');
    register_setting('general', 'option_password_production', 'esc_attr');
  }
}

function my_section_options_callback_development()
{
  echo '<p>Settings for Bitbucket Development Section</p>';
}

function my_section_options_callback_production()
{
  echo '<p>Settings for Bitbucket Production Section</p>';
}

function my_textbox_callback($args)
{
  $option = get_option($args[0]);
  echo '<input type="text" id="' . $args[0] . '" name="' . $args[0] . '" value="' . $option . '" />';
}

function my_password_callback($args)
{
  $option = get_option($args[0]);
  echo '<input type="password" id="' . $args[0] . '" name="' . $args[0] . '" value="' . $option . '" />';
}

// add_action('admin_post_publish_static_hook_development', 'publish_static_hook_development');

// function my_button_development()
// {
//   echo "
//   <div>
//     <form>
//     </form>
//     <form method='post' action='" . admin_url('admin-post.php') . "'>
//     <input type='submit' value='Submit' class='button'>
//     <input type='hidden' name='action' value='publish_static_hook_development'>
//     </form>
//     </div>
// ";
// }

// add_action('admin_init', 'my_development_deploy_button');

// function my_development_deploy_button()
// {
//   add_settings_field(
//     'development_button',
//     'DEVELOPMENT DEPLOY',
//     'my_button_development',
//     'general',
//     'my_settings_section_development',
//   );

//   register_setting('general', 'development_button', 'esc_attr');
// }

if (check_if_is_admin()) {
  add_action('admin_post_publish_static_hook_production', 'publish_static_hook_production');
}
if (check_if_is_admin()) {
  function my_button_production()
  {
    echo "
    <form></form>
      <form method='post' name='production_button' action='" . admin_url('admin-post.php') . "'>
          <span>
                <input type='hidden' name='action' value='publish_static_hook_production'>
                <input type='submit' value='Submit' class='button' >
          </span>
      </form>
  ";
  }
}

if (check_if_is_admin()) {
  add_action('admin_init', 'my_production_deploy_button');
}


function my_production_deploy_button()
{
  if (check_if_is_admin()) {
    add_settings_field(
      'production_button',
      'PRODUCTION DEPLOY',
      'my_button_production',
      'general',
      'my_settings_section_production',
    );
    register_setting('general', 'production_button', 'esc_attr');
  }
}
