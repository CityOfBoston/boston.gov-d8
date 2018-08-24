<?php

/**
 * @file
 * Theme settings for Boston theme.
 */

/**
 * Implements hook_form_system_theme_settings_alter().
 */
function bos_theme_form_system_theme_settings_alter(&$form,  \Drupal\Core\Form\FormStateInterface $form_state, $form_id = NULL) {
  // Work-around for a core bug affecting admin themes. See issue #943212.
  if (isset($form_id)) {
    return;
  }

  // Create the form using Forms API.
  $form['breadcrumb'] = array(
    '#type'          => 'fieldset',
    '#title'         => t('Breadcrumb settings'),

    'boston_breadcrumb' => array(
      '#type'          => 'select',
      '#title'         => t('Display breadcrumb'),
      '#default_value' => theme_get_setting('boston_breadcrumb'),
      '#options'       => array(
        'yes'   => t('Yes'),
        'admin' => t('Only in admin section'),
        'no'    => t('No'),
      ),
    ),
    'breadcrumb_options' => array(
      '#type' => 'container',
      '#states' => array(
        'invisible' => array(
          ':input[name="boston_breadcrumb"]' => array('value' => 'no'),
        ),
      ),

      'boston_breadcrumb_separator' => array(
        '#type'          => 'textfield',
        '#title'         => t('Breadcrumb separator'),
        '#description'   => t('Text only. Don’t forget to include spaces.'),
        '#default_value' => theme_get_setting('boston_breadcrumb_separator'),
        '#size'          => 5,
        '#maxlength'     => 10,
      ),
      'boston_breadcrumb_home' => array(
        '#type'          => 'checkbox',
        '#title'         => t('Show home page link in breadcrumb'),
        '#default_value' => theme_get_setting('boston_breadcrumb_home'),
      ),
      'boston_breadcrumb_trailing' => array(
        '#type'          => 'checkbox',
        '#title'         => t('Append a separator to the end of the breadcrumb'),
        '#default_value' => theme_get_setting('boston_breadcrumb_trailing'),
        '#description'   => t('Useful when the breadcrumb is placed just before the title.'),
        '#states' => array(
        'disabled' => array(
          ':input[name="boston_breadcrumb_title"]' => array('checked' => TRUE),
        ),
      ),
    ),
      'boston_breadcrumb_title' => array(
        '#type'          => 'checkbox',
        '#title'         => t('Append the content title to the end of the breadcrumb'),
        '#default_value' => theme_get_setting('boston_breadcrumb_title'),
        '#description'   => t('Useful when the breadcrumb is not placed just before the title.'),
      ),
    ),
  );

  $form['support'] = array(
    '#type'          => 'fieldset',
    '#title'         => t('Accessibility and support settings'),

    'boston_skip_link_anchor' => array(
      '#type'          => 'textfield',
      '#title'         => t('Anchor ID for the “skip link”'),
      '#default_value' => theme_get_setting('boston_skip_link_anchor'),
      '#field_prefix'  => '#',
      '#description'   => t('Specify the HTML ID of the element that the accessible-but-hidden “skip link” should link to. Note: that element should have the <code>tabindex="-1"</code> attribute to prevent an accessibility bug in webkit browsers. (<a href="@link">Read more about skip links</a>.)', array('@link' => 'https://drupal.org/node/467976')),
    ),
    'boston_skip_link_text' => array(
      '#type'          => 'textfield',
      '#title'         => t('Text for the “skip link”'),
      '#default_value' => theme_get_setting('boston_skip_link_text'),
      '#description'   => t('For example: <em>Jump to navigation</em>, <em>Skip to content</em>'),
    ),
    'boston_meta' => array(
      '#type'          => 'checkboxes',
      '#title'         => t('Add HTML5 and responsive scripts and meta tags to every page.'),
      '#default_value' => theme_get_setting('boston_meta'),
      '#options'       => array(
        'html5' => t('Add HTML5 shim JavaScript to add support to IE 6-8.'),
        'meta' => t('Add meta tags to support responsive design on mobile devices.'),
      ),
      '#description'   => t('IE 6-8 require a JavaScript polyfill solution to add basic support of HTML5. Mobile devices require a few meta tags for responsive designs.'),
    ),
  );

  $libs = \Drupal::service('library.discovery')->getLibrariesByExtension('bos_theme');
  $opts = array();
  foreach($libs as $libname => $lib) {
    if (!empty($lib['data']['name'])) {
      $opts[$libname] = $lib['data']['name'];
    }
  }

  $form['style'] = array(
    '#type'          => 'fieldset',
    '#title'         => t('Defines location of the core style assets.'),

    'asset_source'      => array(
      '#type'          => 'select',
      '#title'         => t('Core css source'),
      '#default_value' => theme_get_setting('asset_source'),
      '#options'       => $opts,
    ),
  );

  if ($form['var']['#value'] == 'theme_boston_settings') {
    $form['support']['boston_layout'] = array(
      '#type'          => 'radios',
      '#title'         => t('Layout'),
      '#options'       => array(
        'boston-responsive-sidebars' => t('Responsive sidebar layout') . ' <small>(layouts/responsive-sidebars.css)</small>',
        'boston-fixed-width' => t('Fixed width layout') . ' <small>(layouts/fixed-width.css)</small>',
      ),
      '#default_value' => theme_get_setting('boston_layout'),
    );
  }

  $form['error-pages'] = array(
    '#type'          => 'fieldset',
    '#title'         => t('Site Error Page Settings'),
    '403-page' => array(
      '#type'          => 'textarea',
      '#title'         => t('403-Page (Permission Denied)'),
      '#description'   => t('Adds text for the themes 403 page. <br/>(<i>page is defined in themes/custom/bos_theme/templates/misc/page--403.html.twig</i>)'),
      '#default_value' => theme_get_setting('403-page'),
      '#rows'          => 5,
      '#theme'         => 'textarea',
      '#resizable'     => 'vertical'
    ),
    '404-page' => array(
      '#type'          => 'textarea',
      '#title'         => t('404-Page (Page Not Found)'),
      '#description'   => t('Adds text for the themes 404 page. <br/>(<i>page is defined in themes/custom/bos_theme/templates/misc/page--404.html.twig</i>)'),
      '#default_value' => theme_get_setting('404-page'),
      '#rows'          => 5,
      '#theme'         => 'textarea',
      '#resizable'     => 'vertical'
    ),
  );

  $form['themedev'] = array(
    '#type'          => 'fieldset',
    '#title'         => t('Theme development settings'),

  );

}
