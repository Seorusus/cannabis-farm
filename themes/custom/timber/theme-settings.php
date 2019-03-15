<?php

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Form\ThemeSettingsForm;
use Drupal\file\Entity\File;
use Drupal\Core\Url;

/*
 * Implement hook_form_system_theme_settings_alter().
 * Allow themes to alter the theme-specific settings form.
 */
function timber_form_system_theme_settings_alter(&$form, \Drupal\Core\Form\FormStateInterface &$form_state) {
    $form['settings'] = array(
    	'#type' => 'details',
    	'#title' => t('Timber theme settings'),
     	'#open' => TRUE,
      '#attached' => array(
        'library' =>  array(
          'timber/admin-lib'
        ),
      ),
    );
    $form['settings']['header'] = array(
      	'#type' => 'details',
      	'#title' => t('Header settings'),
      	'#open' => FALSE,
    );
    $form['settings']['header']['text_one'] = array(
      '#type' => 'textfield',
      '#title' => t('Text One'),
      '#default_value' => theme_get_setting('text_one', 'timber'),
    );
    $form['settings']['header']['text_two'] = array(
      '#type' => 'textfield',
      '#title' => t('Text Two'),
      '#default_value' => theme_get_setting('text_two', 'timber'),
    );
    $form['settings']['header']['text_three'] = array(
      '#type' => 'textfield',
      '#title' => t('Text Three'),
      '#default_value' => theme_get_setting('text_three', 'timber'),
    );
    // General Setting
  	$form['settings']['general_setting'] = array(
      	'#type' => 'details',
      	'#title' => t('General Settings'),
      	'#open' => FALSE,
  	);
    $form['settings']['general_setting']['header_bg'] = array(
      '#type' => 'details',
      '#title' => t('Header background'),
      '#open' => FALSE,
      );
    $form['settings']['general_setting']['header_bg']['page_header_bg'] = array(
      '#type' => 'textfield',
      '#title' => t('URL of the header background image'),
      '#default_value' => theme_get_setting('page_header_bg'),
      '#description' => t('Enter a URL background image.'),
      '#size' => 40,
      '#maxlength' => 512,
    );
    $form['settings']['general_setting']['header_bg']['page_header_bg_upload'] = array(
      '#type' => 'file',
      '#title' => t('Upload header background image'),
      '#size' => 40,
      '#attributes' => array('enctype' => 'multipart/form-data'),
      '#description' => t('If you don\'t jave direct access to the server, use this field to upload your background image. Uploads limited to .png .gif .jpg .jpeg .apng .svg extensions'),
      '#element_validate' => array('page_header_bg_validate'),
    );
    //page contact
    $form['settings']['contact_page'] = array(
      '#type' => 'details',
      '#title' => t('Contact page'),
      '#open' => FALSE,
    );
    $form['settings']['contact_page']['contact_title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#default_value' => theme_get_setting('contact_title', 'timber'),
    );
    $form['settings']['contact_page']['contact_subtitle'] = array(
      '#type' => 'textfield',
      '#title' => t('Subtitle'),
      '#default_value' => theme_get_setting('contact_subtitle', 'timber'),
    );
    $form['settings']['contact_page']['contact_address'] = array(
      '#type' => 'textfield',
      '#title' => t('Address'),
      '#default_value' => theme_get_setting('contact_address', 'timber'),
    );
    $form['settings']['contact_page']['contact_email'] = array(
      '#type' => 'textfield',
      '#title' => t('Email'),
      '#default_value' => theme_get_setting('contact_email', 'timber'),
    );
    $form['settings']['contact_page']['contact_phone'] = array(
      '#type' => 'textfield',
      '#title' => t('Phone'),
      '#default_value' => theme_get_setting('contact_phone', 'timber'),
    );
    $form['settings']['contact_page']['contact_skype'] = array(
      '#type' => 'textfield',
      '#title' => t('Skype'),
      '#default_value' => theme_get_setting('contact_skype', 'timber'),
    );
    $form['settings']['contact_page']['contact_twitter'] = array(
      '#type' => 'textfield',
      '#title' => t('Twitter'),
      '#default_value' => theme_get_setting('contact_twitter', 'timber'),
    );
    $form['settings']['contact_page']['contact_facebook'] = array(
      '#type' => 'textfield',
      '#title' => t('Facebook'),
      '#default_value' => theme_get_setting('contact_facebook', 'timber'),
    );
    $form['settings']['contact_page']['contact_flickr'] = array(
      '#type' => 'textfield',
      '#title' => t('Flickr'),
      '#default_value' => theme_get_setting('contact_flickr', 'timber'),
    );
    $form['settings']['contact_page']['contact_youtube'] = array(
      '#type' => 'textfield',
      '#title' => t('Youtube'),
      '#default_value' => theme_get_setting('contact_youtube', 'timber'),
    );
    //Social Details
    $form['settings']['social_details'] = array(
      '#type' => 'details',
      '#title' => t('Social Details'),
      '#open' => FALSE,
    );
    $form['settings']['social_details']['social_title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#default_value' => theme_get_setting('social_title', 'timber'),
    );
    $form['settings']['social_details']['social_subtitle'] = array(
      '#type' => 'textfield',
      '#title' => t('Subtitle'),
      '#default_value' => theme_get_setting('social_subtitle', 'timber'),
    );
    $form['settings']['social_details']['social_linkedlin'] = array(
      '#type' => 'textfield',
      '#title' => t('Linkedlin'),
      '#default_value' => theme_get_setting('social_linkedlin', 'timber'),
    );
    $form['settings']['social_details']['social_google'] = array(
      '#type' => 'textfield',
      '#title' => t('Google +'),
      '#default_value' => theme_get_setting('social_google', 'timber'),
    );
    $form['settings']['social_details']['social_skype'] = array(
      '#type' => 'textfield',
      '#title' => t('Skype'),
      '#default_value' => theme_get_setting('social_skype', 'timber'),
    );
    $form['settings']['social_details']['social_twitter'] = array(
      '#type' => 'textfield',
      '#title' => t('Twitter'),
      '#default_value' => theme_get_setting('social_twitter', 'timber'),
    );
    $form['settings']['social_details']['social_facebook'] = array(
      '#type' => 'textfield',
      '#title' => t('Facebook'),
      '#default_value' => theme_get_setting('social_facebook', 'timber'),
    );
    $form['settings']['social_details']['social_flickr'] = array(
      '#type' => 'textfield',
      '#title' => t('Flickr'),
      '#default_value' => theme_get_setting('social_flickr', 'timber'),
    );
    $form['settings']['social_details']['social_youtube'] = array(
      '#type' => 'textfield',
      '#title' => t('Youtube'),
      '#default_value' => theme_get_setting('social_youtube', 'timber'),
    );
  	//footer settings
  	$form['settings']['footer'] = array(
    	'#type' => 'details',
    	'#title' => t('Footer settings'),
    	'#open' => FALSE,
  	);
  	$form['settings']['footer']['copyright_text'] = array(
    	'#type' => 'textarea',
    	'#title' => t('Copyright text'),
    	'#default_value' => theme_get_setting('copyright_text', 'timber'),
  	);
}

/*
 * Function to validate page header.
 */
function page_header_bg_validate($element, FormStateInterface $form_state){
  global $base_url;

  $validators = array('file_validate_extensions' => array('png gif jpg jpeg apng svg'));
  $file = file_save_upload('page_header_bg_upload', $validators, "public://", NULL, FILE_EXISTS_REPLACE);

  if (!empty($file)) {
    // change file's status from temporary to permanent and update file database.
    if ((is_object($file[0]) == 1)) {
      $file[0]->status = FILE_STATUS_PERMANENT;
      $file[0]->save();
      $uri = $file[0]->getFileUri();
      $file_url = file_create_url($uri);
      $file_url = str_ireplace($base_url, '', $file_url);
      $form_state->setValue('page_header_bg', $file_url);
    }
 }
}
