<?php

namespace Drupal\bos_migration;

use Drupal;
use Drupal\Core\Database\Database;
use PDO;

/**
 * Makes a class with the migration configs..
 */
class MigrationConfigAlter {

  /**
   * Store for the altered migration object.
   *
   * @var array
   */
  protected $migrations = [];

  /**
   * Store for messages to be logged.
   *
   * @var array
   */
  protected $logMsg = [];

  /**
   * Defines where the source files will be taken from (for d7_file).
   *
   * @var array
   */
  public $source;

  protected $fileMove;
  protected $fileCopy;
  protected $destFileExists;
  protected $destFileExistsExt;

  /**
   * Flag for storing the migrations in a state variable.
   *
   * Set default file operations for rich-text and media migrations.
   * Note: Set to FALSE in production.
   *
   * @var bool
   */
  protected $saveState = TRUE;

  /**
   * Defines list of migration IDs to filter out.
   *
   * Add entity ID matching the $this->migrations[id] field to exclude a config.
   *
   * @var array
   */
  protected static $unusedMigrationsById = [
    'd7_authmap',
    'd7_blocked_ips',
    'd7_color',
    'd7_comment',
    'd7_comment_type',
    'd7_comment_field',
    'd7_comment_field_instance',
    'd7_comment_entity_form_display',
    'd7_comment_entity_form_display_subject',
    'd7_comment_entity_display',
    'd7_custom_block',
    'd7_custom_block_translation',
    'd7_dblog_settings',
    'd7_entity_reference_translation',
    'd7_file_private',
    'd7_filter_format',
    'd7_field',
    'd7_field_collection_type',
    'd7_field_formatter_settings',
    'd7_field_group',
    'd7_field_instance',
    'd7_field_instance_widget_settings',
    'd7_filter_settings',
    'd7_global_theme_settings',
    'd7_image_settings',
    'd7_image_styles',
    'd7_language_content_settings',
    'd7_language_negotiation_settings',
    'd7_language_types',
      /* 'd7_node_revision', */
    'd7_node_settings',
    'd7_node_title_label',
    'd7_node_translation',
    'd7_node_type',
    'd7_paragraphs_type',
    'd7_pathauto_patterns',
    'd7_pathauto_settings',
    'd7_realname_settings',
    'd7_syslog_settings',
    'd7_system_authorize',
    'd7_system_cron',
    'd7_system_date',
    'd7_system_file',
    'd7_system_mail',
    'd7_system_performance',
//    'd7_taxonomy_vocabulary',
    'd7_theme_settings',
    'd7_user_flood',
    'd7_user_mail',
    'd7_view_modes',
    'd7_vote',
    'd7_rdf_mapping',
    'paragraph__upcoming_events',
  ];

  /**
   * Defines list of migrations to filter out.
   *
   * Add bundle matching the name of the bundle in the destination (i.e. D8)
   * to exclude a config.
   *
   * @var array
   */
  protected static $unusedMigrationsByBundle = [
    'entity:taxonomy_term:maps_esri_feed',
    'entity:taxonomy_term:maps_basemap',
    'entity:d7_taxonomy_term:type_of_content',
    'entity:node:metrolist_affordable_housing',
    'entity_revision:node:metrolist_affordable_housing',
    'paragraph__upcoming_events',
  ];

  /**
   * Defines tags per migration config element in this->migrations.
   *
   * @var array
   */
  protected static $migrationTags = [
    "d7_user_role" => ["bos:initial:1"],
    "d7_user" => ["bos:initial:1"],
    "d7_url_alias" => ["bos:initial:1"],
    "d7_path_redirect" => ["bos:initial:1"],
    "d7_file" => ["bos:initial:0"],
    "d7_taxonomy_vocabulary" => ["bos:initial:0"],
    /* "d7_block" => ["bos:initial:1"], */
    "paragraph__3_column_w_image" => ["bos:paragraph:3"],
    "paragraph__bid" => ["bos:paragraph:2"],
    "paragraph__bos311" => ["bos:paragraph:1"],
    "paragraph__bos_signup_emergency_alerts" => ["bos:paragraph:2"],
    "paragraph__cabinet" => ["bos:paragraph:4"],
    "paragraph__card" => ["bos:paragraph:2"],
    "paragraph__city_score_dashboard" => ["bos:paragraph:1"],
    "paragraph__commission_contact_info" => ["bos:paragraph:1"],
    "paragraph__commission_members" => ["bos:paragraph:1"],
    "paragraph__commission_summary" => ["bos:paragraph:1"],
    "paragraph__custom_hours_text" => ["bos:paragraph:1"],
    "paragraph__daily_hours" => ["bos:paragraph:1"],
    "paragraph__discussion_topic" => ["bos:paragraph:2"],
    "paragraph__document" => ["bos:paragraph:1"],
    "paragraph__drawer" => ["bos:paragraph:2"],
    "paragraph__drawers" => ["bos:paragraph:3"],
    "paragraph__election_results" => ["bos:paragraph:1"],
    "paragraph__external_link" => ["bos:paragraph:1"],
    "paragraph__featured_topics" => ["bos:paragraph:5"],
    "paragraph__from_library" => ["bos:paragraph:5"],
    "paragraph__fyi" => ["bos:paragraph:2"],
    "paragraph__gol_list_links" => ["bos:paragraph:2"],
    "paragraph__grid_of_cards" => ["bos:paragraph:3"],
    "paragraph__grid_of_people" => ["bos:paragraph:5"],
    "paragraph__grid_of_places" => ["bos:paragraph:5"],
    "paragraph__grid_of_programs_initiatives" => ["bos:paragraph:5"],
    "paragraph__grid_of_quotes" => ["bos:paragraph:3"],
    "paragraph__grid_of_topics" => ["bos:paragraph:5"],
    "paragraph__group_of_links_grid" => ["bos:paragraph:3"],
    "paragraph__group_of_links_list" => ["bos:paragraph:3"],
    "paragraph__group_of_links_mini_grid" => ["bos:paragraph:3"],
    "paragraph__header_text" => ["bos:paragraph:2"],
    "paragraph__hero_image" => ["bos:paragraph:3"],
    "paragraph__how_to_contact_step" => ["bos:paragraph:2"],
    "paragraph__how_to_tab" => ["bos:paragraph:3"],
    "paragraph__how_to_text_step" => ["bos:paragraph:2"],
    "paragraph__iframe" => ["bos:paragraph:1"],
    "paragraph__internal_link" => ["bos:paragraph:1", "bos:paragraph:5"],
    "paragraph__lightbox_link" => ["bos:paragraph:2"],
    "paragraph__list" => ["bos:paragraph:3"],
    "paragraph__map" => ["bos:paragraph:1"],
    "paragraph__message_for_the_day" => ["bos:paragraph:2"],
    "paragraph__news_and_announcements" => ["bos:paragraph:5"],
    "paragraph__newsletter" => ["bos:paragraph:2"],
    "paragraph__photo" => ["bos:paragraph:2"],
    "paragraph__quote" => ["bos:paragraph:2"],
    "paragraph__seamless_doc" => ["bos:paragraph:1"],
    "paragraph__sidebar_item" => ["bos:paragraph:1"],
    "paragraph__sidebar_item_w_icon" => ["bos:paragraph:1"],
    "paragraph__social_media_links" => ["bos:paragraph:2"],
    "paragraph__social_networking" => ["bos:paragraph:1"],
    "paragraph__text" => ["bos:paragraph:3"],
    "paragraph__text_one_column" => ["bos:paragraph:1"],
    "paragraph__text_three_column" => ["bos:paragraph:1"],
    "paragraph__text_two_column" => ["bos:paragraph:1"],
    "paragraph__transaction_grid" => ["bos:paragraph:2"],
    "paragraph__events_notices" => ["bos:paragraph:2"],
    "paragraph__video" => ["bos:paragraph:2"],
    "d7_node:advpoll" => ["bos:node:1"],
    "d7_node:article" => ["bos:node:3"],
    "d7_node:change" => ["bos:node:1"],
    "d7_node:department_profile" => ["bos:node:1"],
    "d7_node:emergency_alert" => ["bos:node:1"],
    "d7_node:event" => ["bos:node:2"],
    "d7_node:topic_page" => ["bos:node:2"],
    "d7_node:how_to" => ["bos:node:2"],
    "d7_node:landing_page" => ["bos:node:2"],
    "d7_node:listing_page" => ["bos:node:1"],
    "d7_node:person_profile" => ["bos:node:1"],
    "d7_node:place_profile" => ["bos:node:2"],
    "d7_node:post" => ["bos:node:2"],
    "d7_node:procurement_advertisement" => ["bos:node:1"],
    "d7_node:program_initiative_profile" => ["bos:node:2"],
    "d7_node:public_notice" => ["bos:node:2"],
    "d7_node:script_page" => ["bos:node:1"],
    "d7_node:site_alert" => ["bos:node:1"],
    "d7_node:status_item" => ["bos:node:1"],
    "d7_node:tabbed_content" => ["bos:node:2"],
    "d7_node:transaction" => ["bos:node:1"],
    "d7_node_revision:advpoll" => ["bos:node_revision:1"],
    "d7_node_revision:article" => ["bos:node_revision:3"],
    "d7_node_revision:change" => ["bos:node_revision:1"],
    "d7_node_revision:department_profile" => ["bos:node_revision:1"],
    "d7_node_revision:emergency_alert" => ["bos:node_revision:1"],
    "d7_node_revision:event" => ["bos:node_revision:2"],
    "d7_node_revision:topic_page" => ["bos:node_revision:2"],
    "d7_node_revision:how_to" => ["bos:node_revision:2"],
    "d7_node_revision:landing_page" => ["bos:node_revision:2"],
    "d7_node_revision:listing_page" => ["bos:node_revision:1"],
    "d7_node_revision:person_profile" => ["bos:node_revision:1"],
    "d7_node_revision:place_profile" => ["bos:node_revision:2"],
    "d7_node_revision:post" => ["bos:node_revision:2"],
    "d7_node_revision:procurement_advertisement" => ["bos:node_revision:1"],
    "d7_node_revision:program_initiative_profile" => ["bos:node_revision:2"],
    "d7_node_revision:public_notice" => ["bos:node_revision:2"],
    "d7_node_revision:script_page" => ["bos:node_revision:1"],
    "d7_node_revision:site_alert" => ["bos:node_revision:1"],
    "d7_node_revision:status_item" => ["bos:node_revision:1"],
    "d7_node_revision:tabbed_content" => ["bos:node_revision:2"],
    "d7_node_revision:transaction" => ["bos:node_revision:1"],
    "d7_taxonomy_term:contact" => ["bos:taxonomy:2"],
    "d7_taxonomy_term:news_tags" => ["bos:taxonomy:1"],
    "d7_taxonomy_term:event_type" => ["bos:taxonomy:1"],
    "d7_taxonomy_term:neighborhoods" => ["bos:taxonomy:1"],
    "d7_taxonomy_term:political_party" => ["bos:taxonomy:1"],
    "d7_taxonomy_term:profile_type" => ["bos:taxonomy:1"],
    "d7_taxonomy_term:program_type" => ["bos:taxonomy:1"],
    "d7_taxonomy_term:place_type" => ["bos:taxonomy:1"],
    "d7_taxonomy_term:features" => ["bos:taxonomy:1"],
    "d7_taxonomy_term:icons" => ["bos:taxonomy:1"],
    "d7_taxonomy_term:topic_category" => ["bos:taxonomy:1"],
    "d7_taxonomy_term:311_request" => ["bos:taxonomy:1"],
    "d7_taxonomy_term:holidays" => ["bos:taxonomy:1"],
    "d7_taxonomy_term:public_notice_type" => ["bos:taxonomy:1"],
    "d7_taxonomy_term:newsletters" => ["bos:taxonomy:1"],
    "d7_taxonomy_term:bid_offering" => ["bos:taxonomy:1"],
    "d7_taxonomy_term:bid_type" => ["bos:taxonomy:1"],
    "d7_taxonomy_term:massachusetts_general_law" => ["bos:taxonomy:1"],
    "d7_taxonomy_term:procurement_type" => ["bos:taxonomy:1"],
    "d7_taxonomy_term:procurement_footer" => ["bos:taxonomy:1"],
    "d7_taxonomy_term:commissions" => ["bos:taxonomy:1"],
    "d7_taxonomy_term:cityscore_metrics" => ["bos:taxonomy:1"],
  ];

  /**
   * Defines specific overrides to $this->migrations array.
   *
   * @var array
   */
  protected static $migrationOverride = [
    // Migrate the addressfield.
    "d7_node:department_profile" => [
      "process" => [
        "field_address" => [
          "plugin" => "addressfield",
        ],
      ],
    ],
    "d7_node_revision:department_profile" => [
      "process" => [
        "field_address" => [
          "plugin" => "addressfield",
        ],
      ],
    ],
    "d7_node_entity_translation:department_profile" => [
      "process" => [
        "field_address" => [
          "plugin" => "addressfield",
        ],
      ],
    ],
    // Need to update the process for a date_recur type.
    "d7_node_revision:public_notice" => [
      "process" => [
        "field_public_notice_date" => [
          "process" => [
            "end_value" => [
              [
                "plugin" => "format_date",
                "from_format" => "Y-m-d H:i:s",
                "to_format" => "Y-m-d\TH:i:s",
                "source" => "value2",
              ],
              [
                "plugin" => "default_value",
                "default_value" => "",
                "strict" => "true",
              ],
            ],
          ],
        ],
      ],
    ],
    "d7_node_entity_translation:public_notice" => [
      "process" => [
        "field_public_notice_date" => [
          "process" => [
            "end_value" => [
              [
                "plugin" => "format_date",
                "from_format" => "Y-m-d H:i:s",
                "to_format" => "Y-m-d\TH:i:s",
                "source" => "value2",
              ],
              [
                "plugin" => "default_value",
                "default_value" => "",
                "strict" => "true",
              ],
            ],
          ],
        ],
      ],
    ],
    "d7_node:public_notice" => [
      "process" => [
        "field_public_notice_date" => [
          "process" => [
            "end_value" => [
              [
                "plugin" => "format_date",
                "from_format" => "Y-m-d H:i:s",
                "to_format" => "Y-m-d\TH:i:s",
                "source" => "value2",
              ],
              [
                "plugin" => "default_value",
                "default_value" => "",
                "strict" => "true",
              ],
            ],
          ],
        ],
      ],
    ],
    // Add custom.
    "d7_node_revision:event" => [
      "process" => [
        "_state" => [
          "plugin" => "skip_draft_revision",
          "source" => "vid",
        ],
      ],
    ],
    // Manually adds dependency on department profile.
    "d7_taxonomy_term:contact" => [
      "migration_dependencies" => [
        "required" => [
          "d7_node:department_profile",
        ],
      ],
    ],

    // Map "entity_reference" field to "link" field in internal_links para.
    "paragraph__internal_link" => [
      'process' => [
        '_entity_type' => [
          'plugin' => 'default_value',
          'default_value' => 'entity:node',
        ],
        '_target_id' => [
          [
            'plugin' => 'migration',
            'migration' => [
              'd7_node:advpoll',
              'd7_node:article',
              'd7_node:change',
              'd7_node:department_profile',
              'd7_node:emergency_alert',
              'd7_node:event',
              'd7_node:how_to',
              'd7_node:landing_page',
              'd7_node:listing_page',
              'd7_node:metrolist_affordable_housing',
              'd7_node:person_profile',
              'd7_node:place_profile',
              'd7_node:post',
              'd7_node:procurement_advertisement',
              'd7_node:program_initiative_profile',
              'd7_node:public_notice',
              'd7_node:script_page',
              'd7_node:site_alert',
              'd7_node:status_item',
              'd7_node:tabbed_content',
              'd7_node:topic_page',
              'd7_node:transaction',
            ],
            'source' => 'field_internal_link/0/target_id',
          ],
          [
            "plugin" => "skip_on_empty",
            "method" => "row",
            "message" => "node target_id not found in lookup",
          ],
        ],
        'field_internal_link/title' => [
          [
            'plugin' => 'get',
            'source' => 'field_title/0/value',
          ],
          [
            'plugin' => 'default_value',
            'default_value' => 'Migration stub: link title',
          ],
        ],
        'field_internal_link/uri' => [
          [
            'plugin' => 'concat',
            'delimiter' => "/",
            'source' => [
              '@_entity_type',
              '@_target_id',
            ],
          ],
        ],
      ],
      'migration_dependencies' => [
        'required' => [
          'd7_node:advpoll',
          'd7_node:article',
          'd7_node:change',
          'd7_node:department_profile',
          'd7_node:emergency_alert',
          'd7_node:event',
          'd7_node:how_to',
          'd7_node:landing_page',
          'd7_node:listing_page',
          'd7_node:metrolist_affordable_housing',
          'd7_node:person_profile',
          'd7_node:place_profile',
          'd7_node:post',
          'd7_node:procurement_advertisement',
          'd7_node:program_initiative_profile',
          'd7_node:public_notice',
          'd7_node:script_page',
          'd7_node:site_alert',
          'd7_node:status_item',
          'd7_node:tabbed_content',
          'd7_node:topic_page',
          'd7_node:transaction',
        ],
      ],
    ],
    // Add a default value to field description which is changing type.
    "paragraph__newsletter" => [
      "process" => [
        "field_description/0/format" => [
          [
            "plugin" => "default_value",
            "default_value" => "full_html",
          ],
        ],
      ],
    ],
    // Adds migrations for field_list (viewfield).
    "paragraph__list" => [
      "process" => [
        "field_list" => [
          "plugin" => "iterator",
          "source" => "field_list",
          "process" => [
            "field_list_arguments" => "field_list_vargs",
            "field_list_display_id" => "field_list_vname",
            "field_list_target_id" => "entity_type",
            "bundle" => "bundle",
            "entity_id" => "entity_id",
            "revision_id" => "revision_id",
            "deleted" => "deleted",
            "langcode" => [
              "plugin" => "default_value",
              "source" => "language",
              "default_value" => "und",
              "fallback_to_site_default" => "true",
            ],
            "delta" => "delta",
          ],
        ],
      ],
    ],
    "paragraph__news_and_announcements" => [
      "process" => [
        "field_list" => [
          "plugin" => "iterator",
          "source" => "field_list",
          "process" => [
            "field_list_arguments" => "field_list_vargs",
            "field_list_display_id" => "field_list_vname",
            "field_list_target_id" => "entity_type",
            "bundle" => "bundle",
            "entity_id" => "entity_id",
            "revision_id" => "revision_id",
            "deleted" => "deleted",
            "langcode" => [
              "plugin" => "default_value",
              "source" => "language",
              "default_value" => "und",
              "fallback_to_site_default" => "true",
            ],
            "delta" => "delta",
          ],
        ],
      ],
    ],
    "paragraph__events_notices" => [
      "process" => [
        "field_list" => [
          "plugin" => "iterator",
          "source" => "field_list",
          "process" => [
            "field_list_arguments" => "field_list_vargs",
            "field_list_display_id" => "field_list_vname",
            "field_list_target_id" => "entity_type",
            "bundle" => "bundle",
            "entity_id" => "entity_id",
            "revision_id" => "revision_id",
            "deleted" => "deleted",
            "langcode" => [
              "plugin" => "default_value",
              "source" => "language",
              "default_value" => "und",
              "fallback_to_site_default" => "true",
            ],
            "delta" => "delta",
          ],
        ],
      ],
    ],
  ];

  /**
   * Defines the dependencies for the processing of fields.
   *
   * @var array
   */
  protected static $fieldSubprocessDeps = [
    "paragraph" => [
      "full_list" => [
        'paragraph__3_column_w_image',
        'paragraph__bid',
        'paragraph__bos311',
        'paragraph__bos_signup_emergency_alerts',
        'paragraph__cabinet',
        'paragraph__card',
        'paragraph__city_score_dashboard',
        'd7_field_collection_columns',
        'paragraph__commission_contact_info',
        'paragraph__commission_members',
        'paragraph__commission_summary',
        'paragraph__custom_hours_text',
        'paragraph__daily_hours',
        'paragraph__discussion_topic',
        'paragraph__document',
        'paragraph__drawer',
        'paragraph__drawers',
        'paragraph__election_results',
        'paragraph__external_link',
        'paragraph__featured_topics',
        'paragraph__from_library',
        'paragraph__fyi',
        'paragraph__gol_list_links',
        'd7_field_collection_grid_links',
        'paragraph__grid_of_cards',
        'paragraph__grid_of_people',
        'paragraph__grid_of_places',
        'paragraph__grid_of_programs_initiatives',
        'paragraph__grid_of_quotes',
        'paragraph__grid_of_topics',
        'paragraph__group_of_links_grid',
        'paragraph__group_of_links_list',
        'paragraph__group_of_links_mini_grid',
        'paragraph__header_text',
        'paragraph__hero_image',
        'paragraph__how_to_contact_step',
        'paragraph__how_to_tab',
        'paragraph__how_to_text_step',
        'paragraph__iframe',
        'paragraph__internal_link',
        'paragraph__lightbox_link',
        'paragraph__list',
        'paragraph__map',
        'paragraph__message_for_the_day',
        'paragraph__news_and_announcements',
        'paragraph__newsletter',
        'paragraph__photo',
        'paragraph__quote',
        'paragraph__seamless_doc',
        'paragraph__sidebar_item',
        'paragraph__sidebar_item_w_icon',
        'paragraph__social_media_links',
        'paragraph__social_networking',
        'paragraph__text',
        'paragraph__text_one_column',
        'paragraph__text_three_column',
        'paragraph__text_two_column',
        'paragraph__transaction_grid',
        'd7_field_collection_transactions',
        'paragraph__events_and_notices',
      ],
      "field_bid" => [
        'paragraph__bid',
      ],
      "field_components" => [
        'paragraph__3_column_w_image',
        'paragraph__bos311',
        'paragraph__bos_signup_emergency_alerts',
        'paragraph__cabinet',
        'paragraph__card',
        'paragraph__commission_contact_info',
        'paragraph__commission_members',
        'paragraph__commission_summary',
        'paragraph__drawers',
        'paragraph__featured_topics',
        'paragraph__fyi',
        'paragraph__grid_of_cards',
        'paragraph__grid_of_people',
        'paragraph__grid_of_places',
        'paragraph__grid_of_programs_initiatives',
        'paragraph__grid_of_quotes',
        'paragraph__grid_of_topics',
        'paragraph__group_of_links_grid',
        'paragraph__group_of_links_list',
        'paragraph__group_of_links_mini_grid',
        'paragraph__hero_image',
        'paragraph__iframe',
        'paragraph__lightbox_link',
        'paragraph__list',
        'paragraph__map',
        'paragraph__news_and_announcements',
        'paragraph__newsletter',
        'paragraph__photo',
        'paragraph__text',
        'paragraph__transaction_grid',
        'paragraph__events_and_notices',
        'paragraph__video',
      ],
      "field_drawer" => [
        'paragraph__discussion_topic',
        'paragraph__drawer',
        'paragraph__card',
      ],
      "field_embed_list" => [
        'paragraph__seamless_doc',
      ],
      "field_grid_link" => [
        'paragraph__document',
        'paragraph__external_link',
        'paragraph__internal_link',
        'paragraph__lightbox_link',
        'paragraph__commission_contact_info',
      ],
      "field_grid_of_quotes" => [
        'paragraph__quote',
      ],
      "field_header_component" => [
        'paragraph__header_text',
      ],
      "field_how_to_steps" => [
        'paragraph__how_to_contact_step',
        'paragraph__how_to_text_step',
      ],
      "field_how_to_tabs" => [
        'paragraph__how_to_tab',
      ],
      "field_link" => [
        'paragraph__document',
        'paragraph__external_link',
        'paragraph__internal_link',
        'paragraph__lightbox_link',
        'paragraph__commission_contact_info',
      ],
      "field_links" => [
        'paragraph__grid_links',
        'd7_field_collection_grid_links',
        'paragraph__document',
        'paragraph__external_link',
        'paragraph__internal_link',
        'paragraph__lightbox_link',
        'paragraph__commission_contact_info',
      ],
      "field_list_links" => [
        'paragraph__gol_list_links',
      ],
      "field_map_default_coordinates" => [
        'paragraph__message_for_the_day',
      ],
      "field_messages" => [
        'paragraph__message_for_the_day',
      ],
      "field_operation_hours" => [
        'paragraph__custom_hours_text',
        'paragraph__daily_hours',
      ],
      "field_sidebar_components" => [
        'paragraph__sidebar_item',
        'paragraph__sidebar_item_w_icon',
        'paragraph__newsletter',
        'paragraph__social_media_links',
      ],
      "field_social_media_link" => [
        'paragraph__social_networking',
      ],
      "field_status_overrides" => [
        'paragraph__status_overrides',
      ],
      "field_tabbed_content" => [
        'paragraph__tabbed_content_tab',
      ],
      "field_text_blocks" => [
        'paragraph__text_one_column',
        'paragraph__text_three_column',
        'paragraph__text_two_column',
      ],
    ],
    "taxonomy" => [
      "field_311_request" => [
        "d7_taxonomy_term:311_request",
      ],
      "field_awarding_authority" => [
        "d7_taxonomy_term:contact",
      ],
      "field_bid_type" => [
        "d7_taxonomy_term:bid_type",
      ],
      "field_contact" => [
        "d7_taxonomy_term:contact",
      ],
      "field_contacts" => [
        "d7_taxonomy_term:contact",
      ],
      "field_event_type" => [
        "d7_taxonomy_term:event_type",
      ],
      "field_features" => [
        "d7_taxonomy_term:features",
      ],
      "field_ma_general_law" => [
        "d7_taxonomy_term:massachusetts_general_law",
      ],
      "field_mah_neighborhood" => [
        "d7_taxonomy_term:neighborhoods",
      ],
      "field_multiple_neighborhoods" => [
        "d7_taxonomy_term:neighborhoods",
      ],
      "field_news_tags" => [
        "d7_taxonomy_term:news_tags",
      ],
      "field_newsletter" => [
        "d7_taxonomy_term:newsletters",
      ],
      "field_offering" => [
        "d7_taxonomy_term:bid_offering",
      ],
      "field_place_type" => [
        "d7_taxonomy_term:place_type",
      ],
      "field_political_party" => [
        "d7_taxonomy_term:political_party",
      ],
      "field_program_type" => [
        "d7_taxonomy_term:program_type",
      ],
      "field_procurement" => [
        "d7_taxonomy_term:procurement_type",
      ],
      "field_procurement_footer" => [
        "d7_taxonomy_term:procurement_footer",
      ],
      "field_profile_type" => [
        "d7_taxonomy_term:profile_type",
      ],
      "field_single_neighborhood" => [
        "d7_taxonomy_term:neighborhoods",
      ],
      "field_topic_category" => [
        "d7_taxonomy_term:topic_category",
      ],
    ],
    "node" => [
      "all" => [
        "d7_node:advpoll",
        "d7_node:article",
        "d7_node:change",
        "d7_node:department_profile",
        "d7_node:emergency_alert",
        "d7_node:event",
        "d7_node:topic_page",
        "d7_node:how_to",
        "d7_node:landing_page",
        "d7_node:listing_page",
        "d7_node:person_profile",
        "d7_node:place_profile",
        "d7_node:post",
        "d7_node:procurement_advertisement",
        "d7_node:program_initiative_profile",
        "d7_node:public_notice",
        "d7_node:script_page",
        "d7_node:site_alert",
        "d7_node:status_item",
        "d7_node:tabbed_content",
        "d7_node:transaction",
      ],
      "field_awarded_by" => [
        "d7_node:person_profile",
      ],
      "field_department_profile" => [
        "d7_node:department_profile",
      ],
      "field_excluded_nodes" => [
        "d7_node:article",
        "d7_node:change",
        "d7_node:department_profile",
        "d7_node:event",
        "d7_node:topic_page",
        "d7_node:how_to",
        "d7_node:landing_page",
        "d7_node:listing_page",
        "d7_node:person_profile",
        "d7_node:place_profile",
        "d7_node:post",
        "d7_node:procurement_advertisement",
        "d7_node:program_initiative_profile",
        "d7_node:public_notice",
      ],
      "field_featured_item" => [
        "d7_node:event",
        "d7_node:public_notice",
      ],
      "field_featured_post" => [
        "d7_node:post",
      ],
      "field_list" => [
        "d7_node:article",
        "d7_node:department_profile",
        "d7_node:event",
        "d7_node:how_to",
        "d7_node:landing_page",
        "d7_node:person_profile",
        "d7_node:place_profile",
        "d7_node:program_initiative_profile",
        "d7_node:public_notice",
        "d7_node:script_page",
        "d7_node:topic_page",
      ],
      "field_people" => [
        "d7_node:person_profile",
      ],
      "field_person" => [
        "d7_node:person_profile",
      ],
      "field_place" => [
        "d7_node:place_profile",
      ],
      "field_program_initiative" => [
        "d7_node:program_initiative_profile",
      ],
      "field_related" => [
        "d7_node:article",
        "d7_node:department_profile",
        "d7_node:topic_page",
        "d7_node:how_to",
        "d7_node:landing_page",
        "d7_node:listing_page",
        "d7_node:person_profile",
        "d7_node:place_profile",
        "d7_node:program_initiative_profile",
        "d7_node:script_page",
        "d7_node:tabbed_content",
      ],
      "field_related_content" => [
        "d7_node:article",
        "d7_node:how_to",
        "d7_node:landing_page",
        "d7_node:script_page",
        "d7_node:tabbed_content",
      ],
      "field_related_departments" => [
        "d7_node:department_profile",
      ],
      "field_related_events_notices" => [
        "d7_node:event",
        "d7_node:public_notices",
      ],
      "field_related_guides" => [
        "d7_node:topic_page",
      ],
      "field_related_posts" => [
        "d7_node:post",
      ],
      "field_topics" => [
        "d7_node:topic_page",
      ],
    ],
    "field_collection" => [
      "field_columns" => [
        "d7_field_collection_columns",
      ],
      "field_grid_links" => [
        "d7_field_collection_grid_links",
      ],
      "field_transactions" => [
        "d7_field_collection_transactions",
      ],
    ],
    "link" => [
      'field_external_link' => ['field_external_link'],
      'field_details_link' => ['field_details_link'],
      'field_mah_lottery_url' => ['field_mah_lottery_url'],
      'field_pin_name' => ['field_pin_name'],
      'field_related_links' => ['field_related_links'],
      'field_lightbox_link' => ['field_lightbox_link'],
    ],
    "image" => [
      'field_icon' => ['field_icon'],
      'field_image' => ['field_image'],
      'field_intro_image' => ['field_intro_image'],
      'field_thumbnail' => ['field_thumbnail'],
      'field_person_photo' => ['field_person_photo'],
      'field_program_logo' => ['field_program_logo'],
    ],
    "file" => [
      'field_document' => ['field_document'],
    ]
  ];

  /**
   * Usual.
   *
   * @inheritDoc
   */
  public function __construct(array $migrations = [], bool $save = FALSE) {
    if (empty($migrations)) {
      \Drupal::logger('migration')
        ->error("No migrations provided to MigrationConfigAlter.");
      throw new \Exception("No migrations provided.");
    }
    $this->migrations = $migrations;
    $this->saveState = $save;
    $this->setFileOps();

    \Drupal::state()->delete("bos_migration.migrations");
  }

  /**
   * Return migrations set in this class.
   *
   * If none then try to get array saved in state.
   *
   * @return array|mixed
   *   The migrations array as best known.
   */
  public static function migrations() {
    return Drupal::state()->get("bos_migration.migrations", []);
  }

  /**
   * Perform alteration of provided migrations array.
   *
   * @return array
   *   Altered migrations array.
   */
  public function alterMigrations() {
    // Do pruning of unwanted first -significantly reduces size of $migrations.
    $this->pruneMigrations();

    // Add migration_tags so migrations can be run in sequenced groups.
    $this->tagMigrations();

    // Execute global alterations based on patterns/forumula.
    foreach ([
      'field_collection',
      'paragraph',
      'taxonomy',
      'node',
      'file',
      'link',
      'image',
    ] as $entityType) {
      $this->globalAlterations($entityType);
    }

    // Execute targeted alternations to $this->migrations.
    $this->customAlterations();
    $this->customAlteration("d7_taxonomy_term:contact");
    $this->customAlteration("d7_file");
    $this->customAlteration("node_revision");
    $this->richTextFieldAlter();
    $this->breakCyclicalDependencies();

    // Save the settings to a state object (for debug).
    if ($this->saveState) {
      \Drupal::state()->set("bos_migration.migrations", $this->migrations);
    }

    // Return the altered migration array.
    return $this->migrations;
  }

  /**
   * Outputs the $migration to the dblog.
   */
  public function dumpMigration() {
    // Log the final array that is output for debug purposes.
    // (This is a big entry so can be removed on release).
    \Drupal::logger('migrate')
      ->info("<b>Altered Migration Config Array:</b><br><pre>@output</pre>", ["@output" => print_r($this->migrations, TRUE)]);
  }

  /**
   * Alters configuration for fields of the type paragraph, taxonomy and node.
   *
   * For each $migration defined in $migrations.
   * (provided the $migration is a migration of a node, taxonomy or paragraph).
   *
   * @param string $entityType
   *   The entity type of fields in $migration to scan & update.
   */
  private function globalAlterations(string $entityType) {
    $logging = ["warning" => [], "notice" => []];

    $fields = $this::getFieldsOfEntityType($entityType);

    // If nothing found, then exit here.
    if (empty($fields)) {
      return;
    }

    // Re-organize the fields we have found in D7 for this $entityType.
    $fields = array_keys($fields);
    $fields = array_flip($fields);

    // Cycle through all defined migrations.
    foreach ($this->migrations as $mkey => &$migration) {
      $dependencies = ["required" => [], "optional" => []];

      // Update the grouping if its not yet set (i.e when built by a definer).
      // Enables `drush mim --group` option to import groups in a single
      // command.
      if (empty($migration['migration_group'])) {
        $migration['migration_group'] = $migration['id'];
      }

      // Only need to process para's, nodes and taxonomies because they are
      // the only entities which contain entity fields which need to be
      // overridden.
      if (in_array($migration["id"], [
        "d7_node",
        "d7_node_revision",
        "d7_node_entity_translation",
        "d7_taxonomy_term",
        "d7_taxonomy_term_entity_translation",
      ]) || $migration["source"]["plugin"] == "d7_paragraphs_item"
      || $migration["source"]["plugin"] == "d7_field_collection_item"
      ) {

        // Create dependencies for translations and revisions.
        switch ($migration["id"]) {
          case "d7_taxonomy_term_entity_translation":
            $dependencies["required"][] = str_replace($migration["id"], "d7_taxonomy_term", $mkey);
            break;

          case "d7_node_entity_translation":
            $dependencies["required"][] = str_replace($migration["id"], "d7_node", $mkey);
            break;

          case "d7_node_revision":
            $dependencies["required"][] = str_replace($migration["id"], "d7_node", $mkey);
            break;
        }

        // Cycle through the fields we have made manual process overrides for.
        // If this $migration contains any of the fields, then update the
        // process and dependency array elements of the $migration.
        foreach ($fields as $fieldname => $field) {
          if (!empty($migration["process"][$fieldname])) {
            // Use $entityType so that same-named fields on different entities
            // are not mixed up.
            // Fetch a global process definition for fields of this type.
            if ($process = $this->getProcessDefinition($entityType, $fieldname)) {

              // Substitute the altered process array in here now.
              $migration["process"][$fieldname] = $process;

              // Record the process's migration field values so we can add as
              // a dependency for this $migration later.
              switch ($entityType) {
                case "node":
                case "paragraph":
                  $dependencies["required"] += $process["process"]["target_id"][1]["migration"];
                  break;

                case "taxonomy":
                  $dependencies["required"] += $process["process"]["target_id"][1]["migration"];
                  break;

                case "field_collection":
                  $dependencies["required"] += [$process["process"]["target_id"][1]["migration"]];
                  break;
              }
            }
            else {
              // Useful if using drush ...
              $logging["warning"][] = "Missing field definition: " . $fieldname . " (" . $entityType . ") in " . $mkey;
            }
          }
        }

        // Cull any unwanted field/field operations.
        if (isset($migration['process']['field_type_of_content'])) {
          $logging["notice"][] = $mkey . " contains reference to deprecated taxonomy 'field_type_of_content': Check entity defintion/config.";
          unset($migration['process']['field_type_of_content']);
        }
        foreach ($migration['process'] as $fieldname => $map) {
          if ($map == "comment") {
            $logging["notice"][] = $mkey . " contains reference to deprecated field 'comment': Check entity defintion/config.";
            unset($migration['process'][$fieldname]);
          }
        }

        // Add in paragraph dependencies for this entity migration.
        /* if (!empty($dependencies["required"]) ||
        !empty($dependencies["optional"])) {
        $migration["migration_dependencies"] =
        array_merge($migration["migration_dependencies"], $dependencies);
        }*/
      }

      // Make sure the parent_id defaults to zero if nothing found.
      if ($entityType == "taxonomy"
        && in_array($migration["id"], [
          "d7_taxonomy_term",
          "d7_taxonomy_term_entity_translation",
        ])
        && isset($migration["process"]["parent_id"])) {
        $migration["process"]["parent_id"][0] = [
          "plugin" => "default_value",
          "default_value" => "0",
          "source" => "parent",
        ];
      }

      // Regardless of entity type, Update langcode to set itself sensibly.
      if (isset($migration["process"]["langcode"])) {
        if (is_array($migration["process"]["langcode"]) && $migration["process"]["langcode"]["plugin"] == "default_value") {
          $migration["process"]["langcode"]["fallback_to_site_default"] = TRUE;
        }
        elseif (!isset($migration["process"]["langcode"]["plugin"]) || $migration["process"]["langcode"]["plugin"] != "default_value") {
          $migration["process"]["langcode"] = [
            "plugin" => "default_value",
            "source" => "language",
            "default_value" => "und",
            "fallback_to_site_default" => TRUE,
          ];
        }
      }
    }


    // Finally, make log-report entry.
    foreach (["notice", "warning"] as $logType) {
      if (!empty($logging[$logType])) {
        $msg = implode("<br> \n", $logging[$logType]);
        Drupal::logger('migrate')->{$logType}(trim($msg));
      }
    }
  }

  /**
   * Run a targetted specific change to a migration.
   *
   * @param string $migration
   *   The name of a migration which matches a key in $this::migration_override
   *   and $this->migrations.
   */
  private function customAlteration(string $migration = NULL) {
    $defVals = [
      "d7_taxonomy_term:contact" => "contact",
      "node_revision" => "node_revision",
    ];

    switch ($migration) {
      case "d7_taxonomy_term:contact":
        $tmp = $this->migrations[$migration]["process"]["vid"];
        $this->migrations[$migration]["process"]["field_department_profile"] = "field_department_profile";
        break;

      case "d7_file":
        // For file migrations replace the core Drupal source plugin with our
        // customized plugin and also the core Drupal process plugin (fileCopy)
        // with our customized plugin.
        $this->migrations[$migration]['migration_group'] = "bos_media";
        $this->migrations[$migration]['source'] = [
          'plugin' => 'managed_files',
          'key' => 'migrate',
        ];
        $this->migrations[$migration]['process']['uri']['plugin'] = "file_copy_ext";
        // Adds directives to copy or move. (Cannot move remote files)
        $this->migrations[$migration]['process']['uri']['copy'] = $this->fileCopy;
        $this->migrations[$migration]['process']['uri']['move'] = $this->fileMove;
        // Adds directive to point at remote URL from which to download content.
        $this->migrations[$migration]['process']['uri']['remote_source'] = $this->source;
        $this->migrations[$migration]['process']['uri']['file_exists'] = $this->destFileExists;
        $this->migrations[$migration]['process']['uri']['file_exists_ext'] = $this->destFileExistsExt;
        $this->migrations[$migration]['process']['uri']['source'] = [
          "source_base_path",
          "uri",
        ];
        $this->migrations[$migration]['process']['rh_actions'] = 'rh_actions';
        $this->migrations[$migration]['process']['rh_redirect'] = 'rh_redirect';
        $this->migrations[$migration]['process']['rh_redirect_response'] = 'rh_redirect_response';
        $this->migrations[$migration]['migration_dependencies']['required'] = [];
        break;

      case "node_revision":
        // Adds the skip_draft process plugin to node revision migrations.
        foreach ($this->migrations as $mkey => &$migration) {
          if ($migration["id"] == "d7_node_revision") {
            $migration["process"]["_state"] = [
              "plugin" => "skip_draft_revision",
              "source" => "vid",
            ];
          }
        }
        break;
    }
  }

  /**
   * Overrides $migration element settings with those in $migrationOverrides.
   *
   * (This is a targetted/specific element substitution rather than the process
   * in $this->  which overrides using patterns.)
   */
  private function customAlterations() {
    // Make substitutions from override array.
    foreach ($this::$migrationOverride as $migration => $new_element) {
      $this->migrations[$migration] = array_replace_recursive($this->migrations[$migration], $new_element);
    }
  }

  /**
   * Cleans up the list of migrations to only display those we need.
   */
  private function pruneMigrations() {
    $this->migrations = array_filter($this->migrations, function (array $migration) {
      $tags = isset($migration['migration_tags']) ? (array) $migration['migration_tags'] : [];
      if (in_array('Drupal 6', $tags)) {
        return FALSE;
      }
      if (in_array($migration['id'], $this::$unusedMigrationsById)) {
        return FALSE;
      }
      if (isset($migration['destination']['default_bundle'])) {
        $entity = trim($migration['destination']['plugin'] . ":" . $migration['destination']['default_bundle']);
        if (in_array($entity, $this::$unusedMigrationsByBundle)) {
          return FALSE;
        }
      }
      return TRUE;
    });
  }

  /**
   * Adds custom tags to the migration for better grouping.
   */
  private function tagMigrations() {
    // Tag up migrations.
    $custom_tags = $this::$migrationTags;
    foreach ($this->migrations as $id => &$migration) {
      if (isset($custom_tags[$id])) {
        $migration["migration_tags"] = array_merge($migration["migration_tags"], $custom_tags[$id]);
      }
    }
  }

  /**
   * Insert rich_text_to_media_embed plugin into pipeline of rich tech fields.
   */
  private function richTextFieldAlter() {

    $result = $this::getFieldsOfEntityType("rich-text");

    if (!empty($result)) {

      $rich_text_fields = array_keys($result);
      $rich_text_fields = array_flip($rich_text_fields);

      $process_to_insert = ['plugin' => 'rich_text_to_media_embed'];

      foreach ($this->migrations as $key => $value) {

        // We do not want to convert rich-text-embeds for older revisions.
        if ($value['id'] == "d7_node_revision") {
          continue;
        }

        $matches = array_intersect_key($value['process'], $rich_text_fields);

        if (!empty($matches)) {
          foreach ($matches as $destination => $process) {

            if (is_string($process)) {
              $current_source = $this->migrations[$key]['process'][$destination];
              $this->migrations[$key]['process'][$destination] = [
                '0' => [
                  'plugin' => 'get',
                  'source' => $current_source,
                ],
                '1' => $process_to_insert,
              ];
              unset($current_source);

            }

            elseif (is_array($process)) {

              if (empty($this->migrations[$key]['process']['0'])) {
                $current_process = $this->migrations[$key]['process'][$destination];
                $this->migrations[$key]['process'][$destination] = [
                  '0' => $current_process,
                  '1' => $process_to_insert,
                ];
                unset($current_process);
              }

              else {
                $this->migrations[$key]['process'][$destination][] = $process_to_insert;
              }
            }
          }
        }
      }
    }
  }

  /**
   * Breaks cyclical dependencies stopping migration scripts.
   */
  private function breakCyclicalDependencies() {
    // Break cyclical dependency.
    if (!empty($this->migrations['d7_taxonomy:contact']['migration_dependencies']['required'])) {
      $this->migrations['d7_taxonomy:contact']['migration_dependencies']['optional'] = $this->migrations['d7_taxonomy:contact']['migration_dependencies']['required'];
      $this->migrations['d7_taxonomy:contact']['migration_dependencies']['required'] = [];
    }
    if (!empty($this->migrations['d7_node:department_profile']['migration_dependencies']['required'])) {
      $this->migrations['d7_node:department_profile']['migration_dependencies']['optional'] = $this->migrations['d7_node:department_profile']['migration_dependencies']['required'];
      $this->migrations['d7_node:department_profile']['migration_dependencies']['required'] = [];
    }
  }

  /**
   * Reads whether files should be copied, moved or not touched.
   *
   * Used when migrating media and rich-text objects.
   */
  private function setFileOps() {
    $file_ops = \Drupal::state()->get("bos_migration.fileOps", "copy");
    $msg = "Migration will " . $file_ops . " file entities and embedded files.";
    if ($file_ops == "none") {
      $msg = "Migration does not move or copy file entities and embedded files.";
    }
    \Drupal::logger("migrate")->info($msg);
    $this->fileCopy = $file_ops == "copy" ? "true" : "false";
    $this->fileMove = $file_ops == "move" ? "true" : "false";
    $this->source = \Drupal::state()->get("bos_migration.remoteSource", "https://www.boston.gov/");
    $this->destFileExists = \Drupal::state()->get("bos_migration.dest_file_exists", "use existing");
    $this->destFileExistsExt = \Drupal::state()->get("bos_migration.dest_file_exists_ext", "skip");
  }

  /**
   * Returns the tags for a given migration.
   *
   * @param string $migration
   *   NULL, empty string or the name/id of a migration.
   *
   * @return array|bool|mixed
   *   The tags for this igration id, or false if not found.
   */
  protected static function getMigrationTags(string $migration = "") {
    $tags = self::$migrationTags;
    if (empty($migration)) {
      return $tags;
    }
    return isset($tags[$migration]) ? $tags[$migration] : FALSE;
  }

  /**
   * Get an assoc array of all fields in the DB of an EntityType.
   *
   * @param string $entityType
   *   The entity type to find fields for.
   * @param string $dbTarget
   *   The target from $database setting array ($databases[target][key]).
   * @param string $dbKey
   *   The key from $database setting array ($databases[target][key]).
   *
   * @return array
   *   Array of fields in the (source) DB of this entity Type
   */
  protected static function getFieldsOfEntityType(string $entityType, string $dbTarget = "default", string $dbKey = "migrate") {
    try {
      if (NULL == ($con = Database::getConnection($dbTarget, $dbKey))) {
        return [];
      }
    }
    catch (\Exception $e) {
      return [];
    }

    switch ($entityType) {
      case "paragraph":
        return $con->query("SELECT field_name FROM field_config WHERE type IN ('paragraphs')")
          ->fetchAllAssoc('field_name', PDO::FETCH_ASSOC);

      case "field_collection":
        return $con->query("SELECT field_name FROM field_config WHERE type IN ('field_collection')")
          ->fetchAllAssoc('field_name', PDO::FETCH_ASSOC);

      case "taxonomy":
        return $con->query("SELECT field_name FROM field_config c where c.type='entityreference' and INSTR(data, 'taxonomy_term') > 0")
          ->fetchAllAssoc('field_name', PDO::FETCH_ASSOC);

      case "node":
        return $con->query("SELECT field_name FROM field_config c where c.type='entityreference' and INSTR(data, 'node') > 0;")
          ->fetchAllAssoc('field_name', PDO::FETCH_ASSOC);

      case "rich-text":
        return $con->query("SELECT field_name FROM field_config WHERE type IN ('text_long', 'text_with_summary')")
          ->fetchAllAssoc('field_name', PDO::FETCH_ASSOC);

      case "link":
        return $con->query("SELECT field_name FROM field_config WHERE type IN ('link_field')")
          ->fetchAllAssoc('field_name', PDO::FETCH_ASSOC);

      case "image":
        return $con->query("SELECT field_name FROM field_config WHERE type IN ('image')")
          ->fetchAllAssoc('field_name', PDO::FETCH_ASSOC);

      case "file":
        return $con->query("SELECT field_name FROM field_config WHERE type IN ('file')")
          ->fetchAllAssoc('field_name', PDO::FETCH_ASSOC);

      default:
        return [];
    }
  }

  /**
   * Defines the process array for a requested entityreference/revisions field.
   *
   * @param string $entityType
   *   The entity type being fetched.
   * @param string $fieldName
   *   The fieldname.
   *
   * @return array|bool
   *   The process array or FALSE.
   */
  protected function getProcessDefinition(string $entityType, string $fieldName) {
    if (!($entity_field_deps = $this->getFieldDependencies($entityType, $fieldName))) {
      // The $entityType field has no "manually" defined dependencies.
      return FALSE;
    }

    // Build the process array using the dependencies found.
    switch ($entityType) {
      // Creates a process element for node (entityreference) fields which are
      // embedded within a node, taxonomy and paragraph (migration) entities.
      case "node":
        $process = [
          "plugin" => "sub_process",
          "source" => $fieldName,
          "process" => [
            "target_id" => [
              [
                "plugin" => "skip_on_empty",
                "method" => "process",
                'source' => "target_id",
              ],
              [
                'plugin' => 'migration_lookup',
                'migration' => $entity_field_deps,
              ],
            ],
          ],
        ];
        break;

      // Creates a process element for paragraph (entityreferencerevision)
      // fields which are embedded within node, taxonomy and paragraph
      // (migration) entities.
      case "paragraph":
        // Grab the migration lookup keys for the fields being processed.
        $process = [
          "plugin" => "sub_process",
          "source" => $fieldName,
          "process" => [
            "target_id" => [
              [
                "plugin" => "skip_on_empty",
                "method" => "process",
                'source' => "value",
              ],
              [
                'plugin' => 'migration_lookup',
                'migration' => $entity_field_deps,
              ],
              [
                "plugin" => "skip_on_empty",
                "method" => "process",
              ],
              [
                'plugin' => 'extract_ext',
                'index' => [0],
              ],
            ],
            "target_revision_id" => [
              [
                "plugin" => "skip_on_empty",
                "method" => "process",
                'source' => "value",
              ],
              [
                'plugin' => 'migration_lookup',
                'migration' => $entity_field_deps,
              ],
              [
                "plugin" => "skip_on_empty",
                "method" => "process",
              ],
              [
                'plugin' => 'extract_ext',
                'index' => [1],
              ],

            ],
          ],
        ];
        break;

      case "taxonomy":
        // Creates a process element for taxonomy (entityreference) fields which
        // are embedded within node, taxonomy & paragraph (migration) entities.
        $process = [
          "plugin" => "sub_process",
          "source" => $fieldName,
          "process" => [
            "target_id" => [
              [
                "plugin" => "skip_on_empty",
                "method" => "process",
                'source' => "target_id",
              ],
              [
                "plugin" => "migration_lookup",
                "migration" => $entity_field_deps,
              ],
            ],
          ],
        ];
        break;

      case "field_collection":

        $process = [
          "plugin" => "sub_process",
          "source" => $fieldName,
          "process" => [
            "target_id" => [
              [
                "plugin" => "skip_on_empty",
                "method" => "process",
                'source' => "value",
              ],
              [
                "plugin" => "migration_lookup",
                "migration" => $entity_field_deps[0],
              ],
              [
                'plugin' => 'extract',
                'index' => [0],
              ],
            ],
            "target_revision_id" => [
              [
                "plugin" => "skip_on_empty",
                "method" => "process",
                'source' => "value",
              ],
              [
                "plugin" => "migration_lookup",
                "migration" => $entity_field_deps[0],
              ],
              [
                'plugin' => 'extract',
                'index' => [1],
              ],
            ],
          ],
        ];
        break;

      case "link":
        // Note: There are no dependencies of this process type.
        $process = [
          "plugin" => "iterator",
          "source" => $fieldName,
          "process" => [
            'uri' => [
              "plugin" => "fix_uri",
              'source' => 'url',
            ],
            'title' => 'title',
            'options' => 'attributes',
          ],
        ];
        break;

      case "image":
        $process = [
          "plugin" => "sub_process",
          "source" => $fieldName,
          "process" => [
            "target_id" => 'fid',
            "alt" => 'alt',
            "title" => 'title',
            "width" => 'width',
            "height" => 'height',
          ],
        ];
        break;

      case "file":
        $process = [
          "plugin" => "sub_process",
          "source" => $fieldName,
          "process" => [
            "target_id" => [
              [
                'plugin' => "migration_lookup",
                'source' => "fid",
                'migration' => "d7_file"
              ],
            ],
            "description" => "@field_title",
          ],
        ];
        break;

      default:
        return FALSE;
    }

    return $process;
  }

  /**
   * Finds dependencies for a specific entity:field.
   *
   * @param string $entityType
   *   The entity type to be searched.
   * @param string $fieldName
   *   The name of the entityType field to be returned.
   *
   * @return bool|mixed
   *   An array of dependencies.
   */
  protected function getFieldDependencies(string $entityType, string $fieldName) {
    // Define lists of dependencies for entityType fields.
    $entity_field_deps = $this::$fieldSubprocessDeps;

    // If an undefined type is reqested, return FALSE.
    // Note: this is not necessarily an error, just a fact that the entity type
    // field has no dependencies defined in this function.
    if (!isset($entity_field_deps[$entityType][$fieldName])) {
      return FALSE;
    }

    return $entity_field_deps[$entityType][$fieldName];

  }

}
