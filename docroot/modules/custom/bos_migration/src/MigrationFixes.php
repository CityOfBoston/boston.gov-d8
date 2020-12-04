<?php

namespace Drupal\bos_migration;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\redirect\Entity\Redirect;

/**
 * Class migrationFixes.
 *
 * Makes various migration fixes particular to COB.
 *
 * Idea is that public static functions are created that can be called by
 * drush commands at various points during migration.
 * Example:
 * lando ssh -c"/app/vendor/bin/drush php-eval ...
 * ...'\Drupal\bos_migration\migrationFixes::fixTaxonomyVocabulary();'"
 *
 * @package Drupal\bos_migration
 */
class MigrationFixes {

  use FilesystemReorganizationTrait;
  use MemoryManagementTrait;

  /**
   * An array to map d7 view + displays to d8 equivalents.
   *
   * @var array
   */
  protected static $viewListMap = [
    'events_and_notices' => [
      'block_1' => ["events_and_notices", "related"],
      'most_recent' => ["events_and_notices", "upcoming"],
    ],
    'news_and_announcements' => [
      'most_recent' => ["news_and_announcements", "upcoming"],
      'news_events' => ["news_and_announcements", "related"],
    ],
    'bos_department_listing' => [
      'listing' => ['departments_listing', 'listing'],
    ],
    'bos_news_landing' => [
      'page' => ["news_landing", 'page'],
    ],
    'calendar' => [
      'feed_1' => ["calendar", "listing"],
      'listing' => ["calendar", "listing"],
    ],
    'metrolist_affordable_housing' => [
      'page' => ["metrolist_affordable_housing", "page"],
      'page_1' => ["metrolist_affordable_housing", "page_lottery"],
    ],
    'places' => [
      'listing' => ["places", "listing"],
    ],
    'public_notice' => [
      'archive' => ["public_notice", "archive"],
      'landing' => ["public_notice", "landing"],
    ],
    'status_displays' => [
      'homepage_status' => ["status_items", "motd"],
    ],
    'topic_landing_page' => [
      'page_1' => ["topic_landing_page", "guide_page"],
    ],
    'transactions' => [
      'main_transactions' => ["transactions", "main_transactions"],
      'sticky_transactions' => ["transactions", "main_transactions"],
    ],
    'upcoming_events' => [
      'most_recent' => ["events_and_notices", "upcoming"],
      'block_1' => ["events_and_notices", "upcoming"],
    ],
  ];

  /**
   * Array to map D7 loaded svg icons to new icon assets.
   *
   * @var array
   */
  public static $svgMapping = [
    'public://img/program/logo/2016/07/experiential_icons_home_center.svg' => '//assets.boston.gov/icons/experiential_icons/triple_decker.svg',
    'public://img/program/intro_images/2016/08/experiential_icons_home_sability.svg' => '//assets.boston.gov/icons/experiential_icons/neighborhood.svg',
    'public://img/post/thumbnails/2017/06/experiential_icons_house_0.svg' => '//assets.boston.gov/icons/experiential_icons/house_2.svg',
    'public://img/icons/transactions/2019/07/plastic_container.svg' => '//assets.boston.gov/icons/experiential_icons/plastic_container.svg',
    'public://img/icons/transactions/2019/07/hearing.svg' => '//assets.boston.gov/icons/experiential_icons/hearing.svg',
    'public://img/icons/transactions/2019/07/guide.svg' => '//assets.boston.gov/icons/experiential_icons/guide.svg',
    'public://img/icons/transactions/2019/07/gasmask.svg' => '//assets.boston.gov/icons/experiential_icons/gasmask.svg',
    'public://img/icons/transactions/2019/07/conversation.svg' => '//assets.boston.gov/icons/experiential_icons/conversation.svg',
    'public://img/icons/transactions/2019/07/construction.svg' => '//assets.boston.gov/icons/experiential_icons/construction.svg',
    'public://img/icons/transactions/2019/05/text.svg' => '//assets.boston.gov/icons/experiential_icons/text.svg',
    'public://img/icons/transactions/2019/05/neighborhood.svg' => '//assets.boston.gov/icons/experiential_icons/neighborhood.svg',
    'public://img/icons/transactions/2019/05/mayors_office_-_logo.svg' => '//assets.boston.gov/icons/dept_icons/mayors_office_logo',
    'public://img/icons/transactions/2019/05/economic_development_-_icon.svg' => '//assets.boston.gov/icons/dept_icons/economic_development_icon.svg',
    'public://img/icons/transactions/2019/05/download_2.svg' => '//assets.boston.gov/icons/experiential_icons/download_2.svg',
    'public://img/icons/transactions/2019/04/search_.svg' => '//assets.boston.gov/icons/experiential_icons/search.svg',
    'public://img/icons/transactions/2019/04/neighborhood.svg' => '//assets.boston.gov/icons/experiential_icons/neighborhood.svg',
    'public://img/icons/transactions/2019/04/money.svg' => '//assets.boston.gov/icons/experiential_icons/money.svg',
    'public://img/icons/transactions/2019/04/mayoral_letter.svg' => '//assets.boston.gov/icons/experiential_icons/mayoral_letter',
    'public://img/icons/transactions/2019/04/group_1.svg' => '//assets.boston.gov/icons/experiential_icons/group.svg',
    'public://img/icons/transactions/2019/04/bar_graph.svg' => '//assets.boston.gov/icons/experiential_icons/bar_graph.svg',
    'public://img/icons/transactions/2019/03/tripple-decker.svg' => '//assets.boston.gov/icons/experiential_icons/triple_decker.svg',
    'public://img/icons/transactions/2019/03/trash_truck.svg' => '//assets.boston.gov/icons/experiential_icons/trash_truck.svg',
    'public://img/icons/transactions/2019/03/search_bar_1.svg' => '//assets.boston.gov/icons/experiential_icons/search_bar.svg',
    'public://img/icons/transactions/2019/03/recycle_cart.svg' => '//assets.boston.gov/icons/experiential_icons/recycle_cart.svg',
    'public://img/icons/transactions/2019/03/property_violations.svg' => '//assets.boston.gov/icons/experiential_icons/property_violations.svg',
    'public://img/icons/transactions/2019/03/paint_recycle_.svg' => '//assets.boston.gov/icons/experiential_icons/paint_recycle.svg',
    'public://img/icons/transactions/2019/03/money_1.svg' => '//assets.boston.gov/icons/experiential_icons/money.svg',
    'public://img/icons/transactions/2019/03/hazardous_waste.svg' => '//assets.boston.gov/icons/experiential_icons/hazardous_waste.svg',
    'public://img/icons/transactions/2019/03/electronics_recycle_.svg' => '//assets.boston.gov/icons/experiential_icons/electronics_recycle.svg',
    'public://img/icons/transactions/2019/03/download_recycle_app.svg' => '//assets.boston.gov/icons/experiential_icons/download_recycle_app.svg',
    'public://img/icons/transactions/2019/03/compost_sprout.svg' => '//assets.boston.gov/icons/experiential_icons/compost_sprout.svg',
    'public://img/icons/transactions/2019/03/clothes.svg' => '//assets.boston.gov/icons/experiential_icons/clothes.svg',
    'public://img/icons/transactions/2019/03/car_payment_.svg' => '//assets.boston.gov/icons/experiential_icons/car_payment.svg',
    'public://img/icons/transactions/2019/03/can_recycling_1.svg' => '//assets.boston.gov/icons/experiential_icons/can_recycling.svg',
    'public://img/icons/transactions/2019/03/can_recycling_.svg' => '//assets.boston.gov/icons/experiential_icons/can_recycling.svg',
    'public://img/icons/transactions/2019/03/camera.svg' => '//assets.boston.gov/icons/experiential_icons/camera.svg',
    'public://img/icons/transactions/2019/03/building_permit_1.svg' => '//assets.boston.gov/icons/experiential_icons/building_permit.svg',
    'public://img/icons/transactions/2019/02/neighborhoods.svg' => '//assets.boston.gov/icons/experiential_icons/neighborhoods.svg',
    'public://img/icons/transactions/2019/02/group.svg' => '//assets.boston.gov/icons/experiential_icons/group.svg',
    'public://img/icons/transactions/2019/02/document_4.svg' => '//assets.boston.gov/icons/experiential_icons/document.svg',
    'public://img/icons/transactions/2019/02/document_3.svg' => '//assets.boston.gov/icons/experiential_icons/document.svg',
    'public://img/icons/transactions/2019/02/car.svg' => '//assets.boston.gov/icons/experiential_icons/car.svg',
    'public://img/icons/transactions/2019/01/money_bills.svg' => '//assets.boston.gov/icons/experiential_icons/money_bills.svg',
    'public://img/icons/transactions/2019/01/meeting.svg' => '//assets.boston.gov/icons/experiential_icons/meeting.svg',
    'public://img/icons/transactions/2019/01/information.svg' => '//assets.boston.gov/icons/experiential_icons/document.svg',
    'public://img/icons/transactions/2019/01/housing.svg' => '//assets.boston.gov/icons/experiential_icons/building.svg',
    'public://img/icons/transactions/2019/01/handshake.svg' => '//assets.boston.gov/icons/experiential_icons/handshake.svg',
    'public://img/icons/transactions/2019/01/calender_1.svg' => '//assets.boston.gov/icons/experiential_icons/calendar.svg',
    'public://img/icons/transactions/2019/01/calender_.svg' => '//assets.boston.gov/icons/experiential_icons/calendar.svg',
    'public://img/icons/transactions/2019/01/bus_location.svg' => '//assets.boston.gov/icons/experiential_icons/bus_location.svg',
    'public://img/icons/transactions/2019/01/apple.svg' => '//assets.boston.gov/icons/experiential_icons/apple.svg',
    'public://img/icons/transactions/2019/01/adoption_2.svg' => '//assets.boston.gov/icons/experiential_icons/adoption.svg',
    'public://img/icons/transactions/2019/01/adoption.svg' => '//assets.boston.gov/icons/experiential_icons/adoption.svg',
    'public://img/icons/transactions/2018/12/search_forms_.svg' => '//assets.boston.gov/icons/experiential_icons/search_forms.svg',
    'public://img/icons/transactions/2018/12/money.svg' => '//assets.boston.gov/icons/experiential_icons/money.svg',
    'public://img/icons/transactions/2018/12/id_.svg' => '//assets.boston.gov/icons/experiential_icons/id.svg',
    'public://img/icons/transactions/2018/12/buildings.svg' => '//assets.boston.gov/icons/experiential_icons/buildings.svg',
    'public://img/icons/transactions/2018/12/building_permit.svg' => '//assets.boston.gov/icons/experiential_icons/building_permit.svg',
    'public://img/icons/transactions/2018/11/document_1.svg' => '//assets.boston.gov/icons/experiential_icons/document.svg',
    'public://img/icons/transactions/2018/11/crowd.svg' => '//assets.boston.gov/icons/experiential_icons/crowd.svg',
    'public://img/icons/transactions/2018/10/real_estate_taxes.svg' => '//assets.boston.gov/icons/experiential_icons/real_estate_taxes.svg',
    'public://img/icons/transactions/2018/10/paint_bucket.svg' => '//assets.boston.gov/icons/experiential_icons/paint_bucket.svg',
    'public://img/icons/transactions/2018/10/neighborhood.svg' => '//assets.boston.gov/icons/experiential_icons/neighborhood.svg',
    'public://img/icons/transactions/2018/10/group.svg' => '//assets.boston.gov/icons/experiential_icons/group.svg',
    'public://img/icons/transactions/2018/10/contours.svg' => '//assets.boston.gov/icons/experiential_icons/contours.svg',
    'public://img/icons/transactions/2018/10/compost_sprout.svg' => '//assets.boston.gov/icons/experiential_icons/compost_sprout.svg',
    'public://img/icons/transactions/2018/10/calender_.svg' => '//assets.boston.gov/icons/experiential_icons/calendar.svg',
    'public://img/icons/transactions/2018/10/book.svg' => '//assets.boston.gov/icons/experiential_icons/book.svg',
    'public://img/icons/transactions/2018/09/water.svg' => '//assets.boston.gov/icons/experiential_icons/water.svg',
    'public://img/icons/transactions/2018/09/voting_location.svg' => '//assets.boston.gov/icons/experiential_icons/voting_location.svg',
    'public://img/icons/transactions/2018/08/tax_deferral_program_for_seniors.svg' => '//assets.boston.gov/icons/experiential_icons/55+_forms.svg',
    'public://img/icons/transactions/2018/08/sea_level_rise_plus_7_5_feet.svg' => '//assets.boston.gov/icons/experiential_icons/sea_level_7.5.svg',
    'public://img/icons/transactions/2018/08/report_0denergy_water_usage_.svg' => '//assets.boston.gov/icons/experiential_icons/water_and_energy_report.svg',
    'public://img/icons/transactions/2018/08/landmark_design_review_process.svg' => '//assets.boston.gov/icons/experiential_icons/landmark_design_review_process.svg',
    'public://img/icons/transactions/2018/08/global_warming_.svg' => '//assets.boston.gov/icons/experiential_icons/weather.svg',
    'public://img/icons/transactions/2018/08/flooding_1.svg' => '//assets.boston.gov/icons/experiential_icons/flooded_building.svg',
    'public://img/icons/transactions/2018/08/file_a_medical_registration_.svg' => '//assets.boston.gov/icons/experiential_icons/submit_for_certificates.svg',
    'public://img/icons/transactions/2018/08/emergency_alerts.svg' => '//assets.boston.gov/icons/experiential_icons/alert_2.svg',
    'public://img/icons/transactions/2018/08/65_0.svg' => '//assets.boston.gov/icons/experiential_icons/65+.svg',
    'public://img/icons/transactions/2018/07/start_a_resturant.svg' => '//assets.boston.gov/icons/experiential_icons/plate.svg',
    'public://img/icons/transactions/2018/07/food_assistance0a.svg' => '//assets.boston.gov/icons/experiential_icons/fruit_basket.svg',
    'public://img/icons/transactions/2018/07/boston_public_schools.svg' => '//assets.boston.gov/icons/experiential_icons/graduation_cap.svg',
    'public://img/icons/transactions/2018/07/books.svg' => '//assets.boston.gov/icons/experiential_icons/book.svg',
    'public://img/icons/transactions/2018/06/sun_black_and_white.svg' => '//assets.boston.gov/icons/experiential_icons/sun.svg',
    'public://img/icons/transactions/2018/06/graph.svg' => '//assets.boston.gov/icons/experiential_icons/chart.svg',
    'public://img/icons/transactions/2018/06/document_-_pdf.svg' => '//assets.boston.gov/icons/experiential_icons/document.svg',
    'public://img/icons/transactions/2018/06/connect_with_an_expert.svg' => '//assets.boston.gov/icons/experiential_icons/conversation_2.svg',
    'public://img/icons/transactions/2018/06/community_centers_0.svg' => '//assets.boston.gov/icons/experiential_icons/fmaily_house.svg',
    'public://img/icons/transactions/2018/06/community_center_pools.svg' => '//assets.boston.gov/icons/experiential_icons/pool.svg',
    'public://img/icons/transactions/2018/06/city_council_legislation.svg' => '//assets.boston.gov/icons/experiential_icons/city_council_legislation.svg',
    'public://img/icons/transactions/2018/06/bathroom.svg' => '//assets.boston.gov/icons/experiential_icons/bathroom.svg',
    'public://img/icons/transactions/2018/05/watch_boston_city_tv_.svg' => '//assets.boston.gov/icons/experiential_icons/video.svg',
    'public://img/icons/transactions/2018/05/pay_your_real_estate_taxes.svg' => '//assets.boston.gov/icons/experiential_icons/real_estate_taxes.svg',
    'public://img/icons/transactions/2018/05/money_1.svg' => '//assets.boston.gov/icons/experiential_icons/money.svg',
    'public://img/icons/transactions/2018/05/money.svg' => '//assets.boston.gov/icons/experiential_icons/money.svg',
    'public://img/icons/transactions/2018/05/information_for_taxpayers_1.svg' => '//assets.boston.gov/icons/experiential_icons/id.svg',
    'public://img/icons/transactions/2018/05/house.svg' => '//assets.boston.gov/icons/experiential_icons/house_2.svg',
    'public://img/icons/transactions/2018/05/download_.svg' => '//assets.boston.gov/icons/experiential_icons/download.svg',
    'public://img/icons/transactions/2018/05/creative_objects_0.svg' => '//assets.boston.gov/icons/experiential_icons/art_supplies.svg',
    'public://img/icons/transactions/2018/05/construction_vehicle_-_excavator.svg' => '//assets.boston.gov/icons/experiential_icons/excavator.svg',
    'public://img/icons/transactions/2018/05/construction_vehicle_-_bulldozer.svg' => '//assets.boston.gov/icons/experiential_icons/bulldozer.svg',
    'public://img/icons/transactions/2018/04/search_the_boston_food_truck_schedule.svg' => '//assets.boston.gov/icons/experiential_icons/calendar.svg',
    'public://img/icons/transactions/2018/04/plus_sign.svg' => '//assets.boston.gov/icons/experiential_icons/plus_sign.svg',
    'public://img/icons/transactions/2018/04/explore_our_collections_.svg' => '//assets.boston.gov/icons/experiential_icons/search_forms.svg',
    'public://img/icons/transactions/2018/04/car.svg' => '//assets.boston.gov/icons/experiential_icons/car.svg',
    'public://img/icons/transactions/2018/04/alert.svg' => '//assets.boston.gov/icons/experiential_icons/alert.svg',
    'public://img/icons/transactions/2018/03/watch_boston_city_tv_.svg' => '//assets.boston.gov/icons/experiential_icons/video.svg',
    'public://img/icons/transactions/2018/03/start_a_resturant.svg' => '//assets.boston.gov/icons/experiential_icons/plate.svg',
    'public://img/icons/transactions/2018/03/search_1.svg' => '//assets.boston.gov/icons/experiential_icons/search.svg',
    'public://img/icons/transactions/2018/03/renew_a_permit.svg' => '//assets.boston.gov/icons/experiential_icons/parking_pass.svg',
    'public://img/icons/transactions/2018/03/online_registration_1.svg' => '//assets.boston.gov/icons/experiential_icons/web_persona.svg',
    'public://img/icons/transactions/2018/03/mbta_pass.svg' => '//assets.boston.gov/icons/experiential_icons/t_pass.svg',
    'public://img/icons/transactions/2018/03/locate_on_a_map_1.svg' => '//assets.boston.gov/icons/experiential_icons/maps.svg',
    'public://img/icons/transactions/2018/03/license_plate.svg' => '//assets.boston.gov/icons/experiential_icons/license_plate.svg',
    'public://img/icons/transactions/2018/03/how_to_file_for_a_residential_exemption.svg' => '//assets.boston.gov/icons/experiential_icons/residential_exemption.svg',
    'public://img/icons/transactions/2018/03/guest.svg' => '//assets.boston.gov/icons/experiential_icons/guest_parking.svg',
    'public://img/icons/transactions/2018/03/food_truck.svg' => '//assets.boston.gov/icons/experiential_icons/food_truck.svg',
    'public://img/icons/transactions/2018/03/flooding.svg' => '//assets.boston.gov/icons/experiential_icons/flooded_building.svg',
    'public://img/icons/transactions/2018/03/fire_truck_0.svg' => '//assets.boston.gov/icons/experiential_icons/fire_truck.svg',
    'public://img/icons/transactions/2018/03/fire_truck.svg' => '//assets.boston.gov/icons/experiential_icons/fire_truck.svg',
    'public://img/icons/transactions/2018/03/district_change.svg' => '//assets.boston.gov/icons/experiential_icons/district_change.svg',
    'public://img/icons/transactions/2018/03/connect_with_an_expert.svg' => '//assets.boston.gov/icons/experiential_icons/conversation_2.svg',
    'public://img/icons/transactions/2018/03/computer_.svg' => '//assets.boston.gov/icons/experiential_icons/web_persona.svg',
    'public://img/icons/transactions/2018/03/building_list.svg' => '//assets.boston.gov/icons/experiential_icons/building_list.svg',
    'public://img/icons/transactions/2018/03/ballot_or_ticket.svg' => '//assets.boston.gov/icons/experiential_icons/ballot_ticket.svg',
    'public://img/icons/transactions/2018/03/archaeological_dig_1.svg' => '//assets.boston.gov/icons/experiential_icons/dig_alert.svg',
    'public://img/icons/transactions/2018/02/how_to_file_for_a_residential_exemption.svg' => '//assets.boston.gov/icons/experiential_icons/residential_exemption.svg',
    'public://img/icons/transactions/2018/01/public-meetings.svg' => '//assets.boston.gov/icons/experiential_icons/meeting.svg',
    'public://img/icons/transactions/2018/01/experiential_icons_1_1_pay_your_real_estate_taxes.svg' => '//assets.boston.gov/icons/experiential_icons/real_estate_taxes.svg',
    'public://img/icons/transactions/2018/01/calendar.svg' => '//assets.boston.gov/icons/experiential_icons/calendar.svg',
    'public://img/icons/transactions/2018/01/building-icon.svg' => '//assets.boston.gov/icons/experiential_icons/building_permit.svg',
    'public://img/icons/transactions/2017/12/non-emergency.svg' => '//assets.boston.gov/icons/experiential_icons/emergency_medical_kit.svg',
    'public://img/icons/transactions/2017/12/experiential_icons_monum_fellow_1.svg' => '//assets.boston.gov/icons/experiential_icons/du_monum_fellow.svg',
    'public://img/icons/transactions/2017/12/experiential_icons_help_during_the_winter_heating_season.svg' => '//assets.boston.gov/icons/experiential_icons/cold_temp.svg',
    'public://img/icons/transactions/2017/12/experiential_icons_city_hall.svg' => '//assets.boston.gov/icons/experiential_icons/city_hall.svg',
    'public://img/icons/transactions/2017/12/experiential_icons_1_3_pdf_doc_1.svg' => '//assets.boston.gov/icons/experiential_icons/document.svg',
    'public://img/icons/transactions/2017/12/emergency.svg' => '//assets.boston.gov/icons/experiential_icons/ambulance.svg',
    'public://img/icons/transactions/2017/11/rentals.svg' => '//assets.boston.gov/icons/experiential_icons/triple_decker.svg',
    'public://img/icons/transactions/2017/11/experiential_icons_1_3_ticket_1.svg' => '//assets.boston.gov/icons/experiential_icons/ballot_ticket.svg',
    'public://img/icons/transactions/2017/10/small-business-center.svg' => '//assets.boston.gov/icons/experiential_icons/job_search.svg',
    'public://img/icons/transactions/2017/10/physician_0.svg' => '//assets.boston.gov/icons/experiential_icons/submit_for_certificates.svg',
    'public://img/icons/transactions/2017/10/notices.svg' => '//assets.boston.gov/icons/experiential_icons/document.svg',
    'public://img/icons/transactions/2017/10/icon.svg' => '//assets.boston.gov/icons/experiential_icons/mayoral_letter.svg',
    'public://img/icons/transactions/2017/10/contracting-list.svg' => '//assets.boston.gov/icons/experiential_icons/handshake.svg',
    'public://img/icons/transactions/2017/10/contacting-city.svg' => '//assets.boston.gov/icons/experiential_icons/neighborhoods.svg',
    'public://img/icons/transactions/2017/10/contable.svg' => '//assets.boston.gov/icons/experiential_icons/veterans_certificate.svg',
    'public://img/icons/transactions/2017/09/supplier-portal.svg' => '//assets.boston.gov/icons/experiential_icons/web_persona.svg',
    'public://img/icons/transactions/2017/09/state-bid-contracts.svg' => '//assets.boston.gov/icons/experiential_icons/historic_building_permit.svg',
    'public://img/icons/transactions/2017/09/rentsmart-boston.svg' => '//assets.boston.gov/icons/experiential_icons/neighborhood.svg',
    'public://img/icons/transactions/2017/09/information-networks.svg' => '//assets.boston.gov/icons/experiential_icons/meeting.svg',
    'public://img/icons/transactions/2017/09/federal-grants.svg' => '//assets.boston.gov/icons/experiential_icons/document.svg',
    'public://img/icons/transactions/2017/09/experiential_icons_vote.svg' => '//assets.boston.gov/icons/experiential_icons/voting_ballot.svg',
    'public://img/icons/transactions/2017/09/experiential_icons_ticket.svg' => '//assets.boston.gov/icons/experiential_icons/ballot_ticket.svg',
    'public://img/icons/transactions/2017/09/experiential_icons_schools_1.svg' => '//assets.boston.gov/icons/experiential_icons/school.svg',
    'public://img/icons/transactions/2017/09/experiential_icons_map.svg' => '//assets.boston.gov/icons/experiential_icons/maps.svg',
    'public://img/icons/transactions/2017/09/experiential_icons_boston_public_schools.svg' => '//assets.boston.gov/icons/experiential_icons/graduation_cap.svg',
    'public://img/icons/transactions/2017/09/business-opportunities.svg' => '//assets.boston.gov/icons/experiential_icons/online_purchase.svg',
    'public://img/icons/transactions/2017/09/bids-and-contracts.svg' => '//assets.boston.gov/icons/experiential_icons/mayoral_proclamation.svg',
    'public://img/icons/transactions/2017/08/money.svg' => '//assets.boston.gov/icons/experiential_icons/SVG/money.svg',
    'public://img/icons/transactions/2017/08/experiential_icons_food_assistance-.svg' => '//assets.boston.gov/icons/experiential_icons/fruit_basket.svg',
    'public://img/icons/transactions/2017/08/experiential_icon-_recycle_electronics.svg' => '//assets.boston.gov/icons/experiential_icons/electronics_recycle.svg',
    'public://img/icons/transactions/2017/07/experiential_icons_tripple_decker.svg' => '//assets.boston.gov/icons/experiential_icons/triple_decker.svg',
    'public://img/icons/transactions/2017/07/experiential_icons_rent_rights_0.svg' => '//assets.boston.gov/icons/experiential_icons/tennant_rights.svg',
    'public://img/icons/transactions/2017/07/experiential_icons_rent_rights.svg' => '//assets.boston.gov/icons/experiential_icons/tennant_rights.svg',
    'public://img/icons/transactions/2017/07/experiential_icons_important.svg' => '//assets.boston.gov/icons/experiential_icons/alert.svg',
    'public://img/icons/transactions/2017/07/experiential_icons_housing_questions.svg' => '//assets.boston.gov/icons/experiential_icons/housing_questions.svg',
    'public://img/icons/transactions/2017/07/experiential_icons_house_1.svg' => '//assets.boston.gov/icons/experiential_icons/house_2.svg',
    'public://img/icons/transactions/2017/07/experiential_icons_community_centers.svg' => '//assets.boston.gov/icons/experiential_icons/fmaily_house.svg',
    'public://img/icons/transactions/2017/07/experiential_icon_how_to_file_for_a_property_tax_abatement.svg' => '//assets.boston.gov/icons/experiential_icons/building_permit.svg',
    'public://img/icons/transactions/2017/07/experiential-icons_candidate_list_0.svg' => '//assets.boston.gov/icons/experiential_icons/mayoral_letter.svg',
    'public://img/icons/transactions/2017/06/icons-pills_0.svg' => '//assets.boston.gov/icons/experiential_icons/pills.svg',
    'public://img/icons/transactions/2017/06/icons-needle_0.svg' => '//assets.boston.gov/icons/experiential_icons/syringe.svg',
    'public://img/icons/transactions/2017/06/experiential_icons_housing_questions.svg' => '//assets.boston.gov/icons/experiential_icons/housing_questions.svg',
    'public://img/icons/transactions/2017/06/experiential_icons-43.svg' => '//assets.boston.gov/icons/experiential_icons/house.svg',
    'public://img/icons/transactions/2017/05/icons_tranportation.svg' => '//assets.boston.gov/icons/experiential_icons/transportation_locations.svg',
    'public://img/icons/transactions/2017/05/icons_sun.svg' => '//assets.boston.gov/icons/experiential_icons/SUN.svg',
    'public://img/icons/transactions/2017/05/icons_speach.svg' => '//assets.boston.gov/icons/experiential_icons/speach_bubble.svg',
    'public://img/icons/transactions/2017/05/icons_sound.svg' => '//assets.boston.gov/icons/dept_icons/public_information_logo_black.svg',
    'public://img/icons/transactions/2017/05/icons_paper.svg' => '//assets.boston.gov/icons/dept_icons/archives_and_records_icon_black.svg',
    'public://img/icons/transactions/2017/05/icons_housing.svg' => '//assets.boston.gov/icons/dept_icons/home_center_logo_black.svg',
    'public://img/icons/transactions/2017/05/icons_heart.svg' => '//assets.boston.gov/icons/experiential_icons/heart.svg',
    'public://img/icons/transactions/2017/05/icons_health.svg' => '//assets.boston.gov/icons/dept_icons/health_and_human_services_logo_black.svg',
    'public://img/icons/transactions/2017/05/experiential_icons_food_truck.svg' => '//assets.boston.gov/icons/experiential_icons/food_truck.svg',
    'public://img/icons/transactions/2017/05/experiential_icons_search.svg' => '//assets.boston.gov/icons/experiential_icons/search.svg',
    'public://img/icons/transactions/2017/04/experiential_icons_parks_and_playgrounds.svg' => '//assets.boston.gov/icons/experiential_icons/playground.svg',
    'public://img/icons/transactions/2017/04/experiential_icons-29.svg' => '//assets.boston.gov/icons/experiential_icons/calendar.svg',
    'public://img/icons/transactions/2017/03/vulnerability_assessment.svg' => '//assets.boston.gov/icons/experiential_icons/police_interrogation.svg',
    'public://img/icons/transactions/2017/03/trash_and_recycling_guide_0.svg' => '//assets.boston.gov/icons/experiential_icons/trash_truck.svg',
    'public://img/icons/transactions/2017/03/tips_for_using_career_center.svg' => '//assets.boston.gov/icons/experiential_icons/click.svg',
    'public://img/icons/transactions/2017/03/tips_for_recycling_in_boston.svg' => '//assets.boston.gov/icons/experiential_icons/recycle_cart.svg',
    'public://img/icons/transactions/2017/03/salary-info.svg' => '//assets.boston.gov/icons/experiential_icons/personal_tax.svg',
    'public://img/icons/transactions/2017/03/recycling_paint_and_motor_oil.svg' => '//assets.boston.gov/icons/experiential_icons/paint_supplies.svg',
    'public://img/icons/transactions/2017/03/outline_of_actions_and_roadmap.svg' => '//assets.boston.gov/icons/experiential_icons/maps.svg',
    'public://img/icons/transactions/2017/03/labor_service_jobs_0.svg' => '//assets.boston.gov/icons/experiential_icons/construction_tool.svg',
    'public://img/icons/transactions/2017/03/get_rid_of_household_hazardous_waste.svg' => '//assets.boston.gov/icons/experiential_icons/hazardous_waste.svg',
    'public://img/icons/transactions/2017/03/future_surveys.svg' => '//assets.boston.gov/icons/experiential_icons/surveilance.svg',
    'public://img/icons/transactions/2017/03/experiential_icons_ticket.svg' => '//assets.boston.gov/icons/experiential_icons/ballot_ticket.svg',
    'public://img/icons/transactions/2017/03/experiential_icons_domestic_partnership_1.svg' => '//assets.boston.gov/icons/experiential_icons/domestic_partnership.svg',
    'public://img/icons/transactions/2017/03/experiential_icons_board_of_trustees.svg' => '//assets.boston.gov/icons/experiential_icons/meeting.svg',
    'public://img/icons/transactions/2017/03/experiential_icons_bike_helmit.svg' => '//assets.boston.gov/icons/experiential_icons/helmet.svg',
    'public://img/icons/transactions/2017/03/experiential_icons_bike.svg' => '//assets.boston.gov/icons/experiential_icons/bike.svg',
    'public://img/icons/transactions/2017/03/executive_summary.svg' => '//assets.boston.gov/icons/experiential_icons/document.svg',
    'public://img/icons/transactions/2017/03/climate_projections.svg' => '//assets.boston.gov/icons/experiential_icons/chart.svg',
    'public://img/icons/transactions/2017/03/city_of_boston_scholarship_fund_0.svg' => '//assets.boston.gov/icons/experiential_icons/certificates.svg',
    'public://img/icons/transactions/2017/03/city_of_boston_scholarship_fund.svg' => '//assets.boston.gov/icons/experiential_icons/certificates.svg',
    'public://img/icons/transactions/2017/03/career-center.svg' => '//assets.boston.gov/icons/experiential_icons/web_persona.svg',
    'public://img/icons/transactions/2017/03/build_bps_0.svg' => '//assets.boston.gov/icons/experiential_icons/historic_building_permit.svg',
    'public://img/icons/transactions/2017/03/build_bps.svg' => '//assets.boston.gov/icons/experiential_icons/historic_building_permit.svg',
    'public://img/icons/transactions/2017/03/boston_basics.svg' => '//assets.boston.gov/icons/experiential_icons/SVG/birth_certifcate.svg',
    'public://img/icons/transactions/2017/03/benefits-available.svg' => '//assets.boston.gov/icons/experiential_icons/id.svg',
    'public://img/icons/transactions/2017/03/become_a_firefighter.svg' => '//assets.boston.gov/icons/experiential_icons/fire_truck.svg',
    'public://img/icons/transactions/2017/03/5000_questions.svg' => '//assets.boston.gov/icons/experiential_icons/search_forms.svg',
    'public://img/icons/transactions/2017/03/3700_ideas.svg' => '//assets.boston.gov/icons/experiential_icons/lightbulb.svg',
    'public://img/icons/transactions/2017/02/icons_bra.svg' => '//assets.boston.gov/icons/dept_icons/planning_and_development_agency_logo.svg',
    'public://img/icons/transactions/2017/02/experiential_icons_find_your_boston_school_transcript.svg' => '//assets.boston.gov/icons/experiential_icons/certificate.svg',
    'public://img/icons/transactions/2017/02/experiential_icons_clean.svg' => '//assets.boston.gov/icons/experiential_icons/house_cleaning.svg',
    'public://img/icons/transactions/2017/01/experiential_icons_search_license.svg' => '//assets.boston.gov/icons/experiential_icons/certificate_search.svg',
    'public://img/icons/transactions/2017/01/experiential_icons_schools_1.svg' => '//assets.boston.gov/icons/experiential_icons/school.svg',
    'public://img/icons/transactions/2017/01/experiential_icons_parks_and_playgrounds.svg' => '//assets.boston.gov/icons/experiential_icons/playground.svg',
    'public://img/icons/transactions/2017/01/experiential_icons_important.svg' => '//assets.boston.gov/icons/experiential_icons/alert.svg',
    'public://img/icons/transactions/2017/01/experiential_icons_city_of_boston_owned_property.svg' => '//assets.boston.gov/icons/experiential_icons/city_of_boston_owned_property.svg',
    'public://img/icons/transactions/2017/01/experiential_icons_board_of_trustees.svg' => '//assets.boston.gov/icons/experiential_icons/meeting.svg',
    'public://img/icons/transactions/2016/11/experiential_icons_what_to_do_with_your_trash_when_it_snows.svg' => '//assets.boston.gov/icons/experiential_icons/snow_trash.svg',
    'public://img/icons/transactions/2016/11/experiential_icons_what_to_do_with_your_car_when_it_snows.svg' => '//assets.boston.gov/icons/experiential_icons/snow_parking.svg',
    'public://img/icons/transactions/2016/11/experiential_icons_view_your_collection_schedule.svg' => '//assets.boston.gov/icons/experiential_icons/calendar.svg',
    'public://img/icons/transactions/2016/11/experiential_icons_view_leaf_and_yard_waste_schedule.svg' => '//assets.boston.gov/icons/experiential_icons/leaf.svg',
    'public://img/icons/transactions/2016/11/experiential_icons_snow_removal_rules_in_boston.svg' => '//assets.boston.gov/icons/experiential_icons/shovel.svg',
    'public://img/icons/transactions/2016/11/experiential_icons_learn_about_recycling.svg' => '//assets.boston.gov/icons/experiential_icons/recycle_cart.svg',
    'public://img/icons/transactions/2016/11/experiential_icons_important_winter_phone_numbers.svg' => '//assets.boston.gov/icons/experiential_icons/snow_numbers.svg',
    'public://img/icons/transactions/2016/11/experiential_icons_help_during_the_winter_heating_season.svg' => '//assets.boston.gov/icons/experiential_icons/cold_temp.svg',
    'public://img/icons/transactions/2016/11/experiential_icons_get_rid_of_hazardous_waste.svg' => '//assets.boston.gov/icons/experiential_icons/hazardous_waste.svg',
    'public://img/icons/transactions/2016/11/experiential_icons_cold_weather_safety_tips.svg' => '//assets.boston.gov/icons/experiential_icons/snow_alert.svg',
    'public://img/icons/transactions/2016/10/experiential_icons_vote.svg' => '//assets.boston.gov/icons/experiential_icons/voting_ballot.svg',
    'public://img/icons/transactions/2016/10/experiential_icons_search.svg' => '//assets.boston.gov/icons/experiential_icons/search.svg',
    'public://img/icons/transactions/2016/10/experiential_icons_certificate.svg' => '//assets.boston.gov/icons/experiential_icons/certificates.svg',
    'public://img/icons/transactions/2016/10/experiential_icons_2_hurricane.svg' => '//assets.boston.gov/icons/experiential_icons/hurricane.avg',
    'public://img/icons/transactions/2016/09/experiential_icons_important.svg' => '//assets.boston.gov/icons/experiential_icons/alert.svg',
    'public://img/icons/transactions/2016/09/experiential_icons_base_ball.svg' => '//assets.boston.gov/icons/experiential_icons/baseball',
    'public://img/icons/transactions/2016/08/experiential_icons_tripple_decker.svg' => '//assets.boston.gov/icons/experiential_icons/triple_decker.svg',
    'public://img/icons/transactions/2016/08/experiential_icons_boat.svg' => '//assets.boston.gov/icons/experiential_icons/boat.svg',
    'public://img/icons/transactions/2016/08/experiential_icons_bike.svg' => '//assets.boston.gov/icons/experiential_icons/bike.svg',
    'public://img/icons/transactions/2016/08/experiential_icons_2_rent_rights.svg' => '//assets.boston.gov/icons/experiential_icons/tennant_rights.svg',
    'public://img/icons/transactions/2016/08/experiential_icons_2_housing_questions.svg' => '//assets.boston.gov/icons/experiential_icons/housing_questions.svg',
    'public://img/icons/transactions/2016/08/experiential_icons-cal.svg' => '//assets.boston.gov/icons/experiential_icons/calendar.svg',
    'public://img/icons/transactions/2016/08/5experiential_icons_find_a_park_3.svg' => '//assets.boston.gov/icons/experiential_icons/park_location.svg',
    'public://img/icons/transactions/2016/08/5experiential_icons_find_a_park_2.svg' => '//assets.boston.gov/icons/experiential_icons/park_location.svg',
    'public://img/icons/transactions/2016/08/5experiential_icons_find_a_park_1.svg' => '//assets.boston.gov/icons/experiential_icons/park_location.svg',
    'public://img/icons/transactions/2016/08/5experiential_icons_find_a_park.svg' => '//assets.boston.gov/icons/experiential_icons/park_location.svg',
    'public://img/icons/transactions/2016/08/3experiential_icons_mass_value_pass.svg' => '//assets.boston.gov/icons/experiential_icons/mass_value_pass.svg',
    'public://img/icons/transactions/2016/07/experiential_icons_tripple_decker.svg' => '//assets.boston.gov/icons/experiential_icons/triple_decker.svg',
    'public://img/icons/transactions/2016/07/experiential_icons_repair.svg' => '//assets.boston.gov/icons/experiential_icons/construction_tool.svg',
    'public://img/icons/transactions/2016/07/experiential_icons_pay_your_real_estate_taxes-.svg' => '//assets.boston.gov/icons/experiential_icons/real_estate_taxes.svg',
    'public://img/icons/transactions/2016/07/experiential_icons_home_repairs.svg' => '//assets.boston.gov/icons/experiential_icons/repair_your_home.svg',
    'public://img/icons/transactions/2016/07/experiential_icons_2_311.svg' => '//assets.boston.gov/icons/dept_icons/bos_311_icon_black.svg',
    'public://img/icons/status/trash-recycling.svg' => '//assets.boston.gov/icons/circle_icons/trash_and_recycling.svg',
    'public://img/icons/status/tow-lot.svg' => '//assets.boston.gov/icons/circle_icons/tow_lot.svg',
    'public://img/icons/status/street_sweeping.svg' => '//assets.boston.gov/icons/circle_icons/street_sweeping.svg',
    'public://img/icons/status/small-circle-icons_base_ball_0.svg' => '//assets.boston.gov/icons/circle_icons/base_ball.svg',
    'public://img/icons/status/parking-meters.svg' => '//assets.boston.gov/icons/circle_icons/parking_meters.svg',
    'public://img/icons/status/experiential_icons_fact_sheet.svg' => '//assets.boston.gov/icons/experiential_icons/report.svg',
    'public://img/icons/status/2018/03/small-circle-icons_t_one_circle_1.svg' => '//assets.boston.gov/icons/circle_icons/du_t_one_circle.svg',
    'public://img/icons/status/2017/10/small-circle-icons_building.svg' => '//assets.boston.gov/icons/circle_icons/building.svg',
    'public://img/icons/status/2017/10/slice_1.svg' => '//assets.boston.gov/icons/circle_icons/building.svg',
    'public://img/icons/status/2017/10/new-building-icon2-01.svg' => '//assets.boston.gov/icons/circle_icons/building.svg',
    'public://img/icons/status/2017/02/snow-parking.svg' => '//assets.boston.gov/icons/circle_icons/snow_parking.svg',
    'public://img/icons/status/2017/02/school.svg' => '//assets.boston.gov/icons/circle_icons/schools.svg',
    'public://img/icons/status/2016/10/state-offices.svg' => '//assets.boston.gov/icons/circle_icons/state_offices.svg',
    'public://img/icons/status/2016/10/libraries.svg' => '//assets.boston.gov/icons/circle_icons/libraries.svg',
    'public://img/icons/status/2016/10/community-centers.svg' => '//assets.boston.gov/icons/circle_icons/community_centers.svg',
    'public://img/icons/fyi/2017/01/small-circle-icons_snow_red.svg' => '//assets.boston.gov/icons/circle_icons/snow_red.svg',
    'public://img/icons/fyi/2016/08/small-circle-icons_alert.svg' => '//assets.boston.gov/icons/circle_icons/alert.svg',
    'public://img/icons/feature/small-circle-icons_yarn.svg' => '//assets.boston.gov/icons/circle_icons/yarn.svg',
    'public://img/icons/feature/small-circle-icons_track.svg' => '//assets.boston.gov/icons/circle_icons/track.svg',
    'public://img/icons/feature/small-circle-icons_tennis_court-.svg' => '//assets.boston.gov/icons/circle_icons/tennis_court.svg',
    'public://img/icons/feature/small-circle-icons_teen_center_.svg' => '//assets.boston.gov/icons/circle_icons/teen_center.svg',
    'public://img/icons/feature/small-circle-icons_stage.svg' => '//assets.boston.gov/icons/circle_icons/stage.svg',
    'public://img/icons/feature/small-circle-icons_sports_facility.svg' => '//assets.boston.gov/icons/circle_icons/sports_facility.svg',
    'public://img/icons/feature/small-circle-icons_socker.svg' => '//assets.boston.gov/icons/circle_icons/socker.svg',
    'public://img/icons/feature/small-circle-icons_sauna_-_steam_room.svg' => '//assets.boston.gov/icons/circle_icons/sauna_steam_room.svg',
    'public://img/icons/feature/small-circle-icons_rock_wall.svg' => '//assets.boston.gov/icons/circle_icons/rock_wall.svg',
    'public://img/icons/feature/small-circle-icons_public_art-.svg' => '//assets.boston.gov/icons/circle_icons/public_art.svg',
    'public://img/icons/feature/small-circle-icons_playground.svg' => '//assets.boston.gov/icons/circle_icons/playground.svg',
    'public://img/icons/feature/small-circle-icons_outdoor_pool.svg' => '//assets.boston.gov/icons/circle_icons/outdoor_pool.svg',
    'public://img/icons/feature/small-circle-icons_music_studio-_1.svg' => '//assets.boston.gov/icons/circle_icons/music_studio.svg',
    'public://img/icons/feature/small-circle-icons_music_studio-.svg' => '//assets.boston.gov/icons/circle_icons/music_studio.svg',
    'public://img/icons/feature/small-circle-icons_kitchen.svg' => '//assets.boston.gov/icons/circle_icons/kitchen.svg',
    'public://img/icons/feature/small-circle-icons_indoor_pool.svg' => '//assets.boston.gov/icons/circle_icons/indoor_pool.svg',
    'public://img/icons/feature/small-circle-icons_handball.svg' => '//assets.boston.gov/icons/circle_icons/handball.svg',
    'public://img/icons/feature/small-circle-icons_gym.svg' => '//assets.boston.gov/icons/circle_icons/gym.svg',
    'public://img/icons/feature/small-circle-icons_garden-.svg' => '//assets.boston.gov/icons/circle_icons/garden.svg',
    'public://img/icons/feature/small-circle-icons_football.svg' => '//assets.boston.gov/icons/circle_icons/foorball.svg',
    'public://img/icons/feature/small-circle-icons_dance_studio-.svg' => '//assets.boston.gov/icons/circle_icons/dance_studio.svg',
    'public://img/icons/feature/small-circle-icons_computer.svg' => '//assets.boston.gov/icons/circle_icons/computer.svg',
    'public://img/icons/feature/small-circle-icons_community_room-.svg' => '//assets.boston.gov/icons/circle_icons/community_room.svg',
    'public://img/icons/feature/small-circle-icons_boxing_room.svg' => '//assets.boston.gov/icons/circle_icons/bosing_room.svg',
    'public://img/icons/feature/small-circle-icons_beach.svg' => '//assets.boston.gov/icons/circle_icons/beach.svg',
    'public://img/icons/feature/small-circle-icons_batting_cage.svg' => '//assets.boston.gov/icons/circle_icons/batting_cage.svg',
    'public://img/icons/feature/small-circle-icons_basketball.svg' => '//assets.boston.gov/icons/circle_icons/basketball.svg',
    'public://img/icons/feature/small-circle-icons_base_ball_0.svg' => '//assets.boston.gov/icons/circle_icons/baseball.svg',
    'public://img/icons/feature/small-circle-icons_base_ball.svg' => '//assets.boston.gov/icons/circle_icons/baseball.svg',
    'public://img/icons/feature/small-circle-icons-69.svg' => '//assets.boston.gov/icons/circle_icons/artboard_69.svg',
    'public://img/icons/department/svg_labor_relations_.svg' => '//assets.boston.gov/icons/dept_icons/labor_relations_logo.svg',
    'public://img/icons/department/svg_economic_development_.svg' => '//assets.boston.gov/icons/dept_icons/economic_development_icon.svg',
    'public://img/icons/department/neighborhood_development.svg' => '//assets.boston.gov/icons/dept_icons/neighborhood_development_logo.svg',
    'public://img/icons/department/icons_youth_empowerment.svg' => '//assets.boston.gov/icons/dept_icons/youth_employment_and_engagement_logo.svg',
    'public://img/icons/department/icons_womens.svg' => '//assets.boston.gov/icons/dept_icons/womens_advancement_logo.svg',
    'public://img/icons/department/icons_water_and_sewer.svg' => '//assets.boston.gov/icons/dept_icons/water_and_sewer_commission_logo.svg',
    'public://img/icons/department/icons_veterans.svg' => '//assets.boston.gov/icons/dept_icons/veterans_services_logo.svg',
    'public://img/icons/department/icons_treasury_0.svg' => '//assets.boston.gov/icons/dept_icons/finance_logo.svg',
    'public://img/icons/department/icons_treasury.svg' => '//assets.boston.gov/icons/dept_icons/finance_logo.svg',
    'public://img/icons/department/icons_transportation_transportation.svg' => '//assets.boston.gov/icons/dept_icons/transportation_logo.svg',
    'public://img/icons/department/icons_tourism.svg' => '//assets.boston.gov/icons/dept_icons/tourism_sports_and_entertainment_logo.svg',
    'public://img/icons/department/icons_purchasing.svg' => '//assets.boston.gov/icons/dept_icons/purchasing.svg',
    'public://img/icons/department/icons_public_works.svg' => '//assets.boston.gov/icons/dept_icons/public_works_logo.svg',
    'public://img/icons/department/icons_public_safty_0.svg' => '//assets.boston.gov/icons/dept_icons/public_health_commission_logo.svg',
    'public://img/icons/department/icons_public_safety.svg' => '//assets.boston.gov/icons/dept_icons/public_safety_logo.svg',
    'public://img/icons/department/icons_prop_management.svg' => '//assets.boston.gov/icons/dept_icons/propertt_and_construction_management_logo.svg',
    'public://img/icons/department/icons_police.svg' => '//assets.boston.gov/icons/dept_icons/police.svg',
    'public://img/icons/department/icons_parks.svg' => '//assets.boston.gov/icons/dept_icons/parks_and_recreation_logo.svg',
    'public://img/icons/department/icons_parking.svg' => '//assets.boston.gov/icons/dept_icons/parking_clerk_logo.svg',
    'public://img/icons/department/icons_new_urban_mechanics.svg' => '//assets.boston.gov/icons/dept_icons/new_urban_mechanics_logo.svg',
    'public://img/icons/department/icons_new_bostonians.svg' => '//assets.boston.gov/icons/dept_icons/new_bostonians_logo.svg',
    'public://img/icons/department/icons_mayor.svg' => '//assets.boston.gov/icons/dept_icons/mayors_office_logo.svg',
    'public://img/icons/department/icons_library.svg' => '//assets.boston.gov/icons/dept_icons/library_logo.svg',
    'public://img/icons/department/icons_hr.svg' => '//assets.boston.gov/icons/dept_icons/human_resources_logo.svg',
    'public://img/icons/department/icons_housing.svg' => '//assets.boston.gov/icons/dept_icons/housing_authority_logo.svg',
    'public://img/icons/department/icons_environment.svg' => '//assets.boston.gov/icons/dept_icons/environment_logo.svg',
    'public://img/icons/department/icons_engagment__311_-_ons.svg' => '//assets.boston.gov/icons/dept_icons/bos_311_icon.svg',
    'public://img/icons/department/icons_ems_0.svg' => '//assets.boston.gov/icons/dept_icons/emergency_medical_services_logo.svg',
    'public://img/icons/department/icons_ems.svg' => '//assets.boston.gov/icons/dept_icons/emergency_medical_services_logo.svg',
    'public://img/icons/department/icons_elections.svg' => '//assets.boston.gov/icons/dept_icons/elections_logo.svg',
    'public://img/icons/department/icons_doit.svg' => '//assets.boston.gov/icons/dept_icons/innovation_and_technology_logo.svg',
    'public://img/icons/department/icons_disabilities_disabilities.svg' => '//assets.boston.gov/icons/dept_icons/disabilities_commission_icon.svg',
    'public://img/icons/department/icons_disabilities.svg' => '//assets.boston.gov/icons/dept_icons/disabilities_commission_icon.svg',
    'public://img/icons/department/icons_consumer_affairs_0.svg' => '//assets.boston.gov/icons/dept_icons/consumer_affairs_and_licensing_logo.svg',
    'public://img/icons/department/icons_consumer_affairs.svg' => '//assets.boston.gov/icons/dept_icons/consumer_affairs_and_licensing_logo.svg',
    'public://img/icons/department/icons_city_council.svg' => '//assets.boston.gov/icons/dept_icons/city_council_icon.svg',
    'public://img/icons/department/icons_city_clerk.svg' => '//assets.boston.gov/icons/dept_icons/city_clerk_icon.svg',
    'public://img/icons/department/icons_cable.svg' => '//assets.boston.gov/icons/dept_icons/broadband_and_cable_icon.svg',
    'public://img/icons/department/icons_budget_0.svg' => '//assets.boston.gov/icons/dept_icons/budget_icon.svg',
    'public://img/icons/department/icons_bra.svg' => '//assets.boston.gov/icons/dept_icons/planning_and_development_agency_logo.svg',
    'public://img/icons/department/icons_blue_retirement.svg' => '//assets.boston.gov/icons/dept_icons/retirement_logo.svg',
    'public://img/icons/department/icons_blue_neighborhood_services.svg' => '//assets.boston.gov/icons/dept_icons/neighborhood_development_logo.svg',
    'public://img/icons/department/icons_bikes_bikes_0.svg' => '//assets.boston.gov/icons/dept_icons/boston_bikes_icon.svg',
    'public://img/icons/department/icons_bikes_bikes.svg' => '//assets.boston.gov/icons/dept_icons/boston_bikes_icon.svg',
    'public://img/icons/department/icons_auditing.svg' => '//assets.boston.gov/icons/dept_icons/auditing_icon.svg',
    'public://img/icons/department/icons_assessing.svg' => '//assets.boston.gov/icons/dept_icons/assessing_icon.svg',
    'public://img/icons/department/icons_arts.svg' => '//assets.boston.gov/icons/dept_icons/arts_and_culture_icon.svg',
    'public://img/icons/department/icons_animal_care.svg' => '//assets.boston.gov/icons/dept_icons/animal_care_and_control_icon.svg',
    'public://img/icons/department/experiential_icons_isd.svg' => '//assets.boston.gov/icons/dept_icons/inspectional_services_logo.svg',
    'public://img/icons/department/deapartment_icons_food.svg' => '//assets.boston.gov/icons/dept_icons/food_access_logo.svg',
    'public://img/icons/department/2019/04/new_urban_mechanics_-_logo_3_1.svg' => '//assets.boston.gov/icons/dept_icons/new_urban_mechanics_logo.svg',
    'public://img/icons/department/2019/04/new_urban_mechanics_-_logo_2_0.svg' => '//assets.boston.gov/icons/dept_icons/new_urban_mechanics_logo.svg',
    'public://img/icons/department/2019/04/new_urban_mechanics_-_logo_1.svg' => '//assets.boston.gov/icons/dept_icons/new_urban_mechanics_logo.svg',
    'public://img/icons/department/2019/04/new_urban_mechanics_-_logo.svg' => '//assets.boston.gov/icons/dept_icons/new_urban_mechanics_logo.svg',
    'public://img/icons/department/2019/01/age-strong-final.svg' => '//assets.boston.gov/icons/dept_icons/age_strong_logo.svg',
    'public://img/icons/department/2018/10/yee-icon.svg' => '//assets.boston.gov/icons/dept_icons/mayors_youth_council_logo.svg',
    'public://img/icons/department/2017/11/returnign_citizens-05_0.svg' => '//assets.boston.gov/icons/dept_icons/returning_citizens_logo.svg',
    'public://img/icons/department/2017/11/logos-05.svg' => '//assets.boston.gov/icons/dept_icons/returning_citizens_logo.svg',
    'public://img/icons/department/2017/11/artboard_5.svg' => '//assets.boston.gov/icons/dept_icons/returning_citizens_logo.svg',
    'public://img/icons/department/2017/10/procurement-icon.svg' => '//assets.boston.gov/icons/dept_icons/procurement_logo.svg',
    'public://img/icons/department/2017/10/icons_mayors_youth_council_1.svg' => '//assets.boston.gov/icons/dept_icons/mayors_youth_council_logo.svg',
    'public://img/icons/department/2017/09/police.svg' => '//assets.boston.gov/icons/dept_icons/police_logo.svg',
    'public://img/icons/department/2017/06/recovery_services_i_con.svg' => '//assets.boston.gov/icons/dept_icons/recovery_services_logo.svg',
    'public://img/icons/department/2017/02/public_records.svg' => '//assets.boston.gov/icons/dept_icons/public_records_logo.svg',
    'public://img/icons/department/2017/01/icons_isd.svg' => '//assets.boston.gov/icons/dept_icons/inspectional_services_logo.svg',
    'public://img/icons/department/2016/10/icons_doit.svg' => '//assets.boston.gov/icons/dept_icons/innovation_and_technology_logo.svg',
    'public://img/icons/department/2016/10/icons_archives_treasury.svg' => '//assets.boston.gov/icons/dept_icons/treasury_logo.svg',
    'public://img/icons/department/2016/10/icons_archives_tourism.svg' => '//assets.boston.gov/icons/dept_icons/tourism_sports_and_entertainment_logo.svg',
    'public://img/icons/department/2016/10/icons_archives_taxcollection.svg' => '//assets.boston.gov/icons/dept_icons/tax_collection_logo.svg',
    'public://img/icons/department/2016/10/icons_archives_smallbusiness.svg' => '//assets.boston.gov/icons/dept_icons/small_business_development_logo.svg',
    'public://img/icons/department/2016/10/icons_archives_schools.svg' => '//assets.boston.gov/icons/dept_icons/schools_logo.svg',
    'public://img/icons/department/2016/10/icons_archives_resilience.svg' => '//assets.boston.gov/icons/dept_icons/resilience_and_racial_equity_logo.svg',
    'public://img/icons/department/2016/10/icons_archives_publicfacilities.svg' => '//assets.boston.gov/icons/dept_icons/public_facilities_logo.svg',
    'public://img/icons/department/2016/10/icons_archives_procurement.svg' => '//assets.boston.gov/icons/dept_icons/procurement_logo.svg',
    'public://img/icons/department/2016/10/department_icons_new_public_safty.svg' => '//assets.boston.gov/icons/dept_icons/public_safety_logo.svg',
    'public://img/icons/department/2016/10/department_icons_new_fire_prevention.svg' => '//assets.boston.gov/icons/dept_icons/fire_prevention_logo.svg',
    'public://img/icons/department/2016/10/department_icons_new_analytics.svg' => '//assets.boston.gov/icons/dept_icons/analytics_team_icon.svg',
    'public://img/icons/department/2016/09/icons_jobs_policy.svg' => '//assets.boston.gov/icons/dept_icons/workforce_development_logo.svg',
    'public://img/icons/department/2016/08/icons_youth_empowerment.svg' => '//assets.boston.gov/icons/dept_icons/youth_employment_and_engagement_logo.svg',
    'public://img/icons/department/2016/08/icons_public_facilities.svg' => '//assets.boston.gov/icons/dept_icons/small_business_enterprise_office.svg',
    'public://img/icons/department/2016/08/icons_landmarks.svg' => '//assets.boston.gov/icons/dept_icons/landmarks_commission_logo.svg',
    'public://img/icons/department/2016/08/icons_labor_relations.svg' => '//assets.boston.gov/icons/dept_icons/labor_relations_logo.svg',
    'public://img/icons/department/2016/08/icons_digital.svg' => '//assets.boston.gov/icons/dept_icons/digital_team_icon.svg',
    'public://img/icons/department/2016/08/department_icons_emergency_management.svg' => '//assets.boston.gov/icons/dept_icons/emergency_management_logo.svg',
    'public://img/icons/department/2016/07/assessing_logo.svg' => '//assets.boston.gov/icons/dept_icons/assessing_icon.svg',
    'public://img/how_to/intro_images/default-hero-image_9.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_8.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_7.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_6.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_5.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_4.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_30.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_3.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_29.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_28.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_27.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_26.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_25.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_24.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_23.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_22.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_21.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_19.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_17.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_16.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_15.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_14.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_13.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_12.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_11.svg' => 'public://icons/default-hero-image.svg',
    'public://img/how_to/intro_images/default-hero-image_1.svg' => 'public://icons/default-hero-image.svg',
    'public://img/2018/e/experiential_icons_real_estate_taxes.svg' => '//assets.boston.gov/icons/experiential_icons/real_estate_taxes.svg',
    'public://img/2018/e/experiential_icons_census_1.svg' => '//assets.boston.gov/icons/experiential_icons/family.svg',
    'public://img/2018/d/department_icons_emergency_management_1_1.svg' => '//assets.boston.gov/icons/dept_icons/emergency_management__logo.svg',
    'public://img/2018/d/department_icons_emergency_management_1_0.svg' => '//assets.boston.gov/icons/dept_icons/emergency_management__logo.svg',
    'public://img/2017/s/svg_hosuing_authority_.svg' => '//assets.boston.gov/icons/dept_icons/housing_authority_logo.svg',
    'public://img/2017/e/experiential_icons_important_6.svg' => '//assets.boston.gov/icons/experiential_icons/alert.svg',
    'public://img/2017/e/experiential_icons_fire_operations.svg' => '//assets.boston.gov/icons/dept_icons/fire_operations_logo.svg',
    'public://img/2017/e/experiential_icon_how_to_file_for_a_property_tax_abatement_0.svg' => '//assets.boston.gov/icons/experiential_icons/building_permit.svg',
    'public://img/2017/e/experiential_icon_how_to_file_for_a_property_tax_abatement.svg' => '//assets.boston.gov/icons/experiential_icons/building_permit.svg',
    'public://img/2017/d/department_icons_emergency_management_0.svg' => '//assets.boston.gov/icons/dept_icons/emergency_management__logo.svg',
    'public://img/2016/e/experientialicon_pay_or_view_your_bills_online.svg' => '//assets.boston.gov/icons/experiential_icons/online_purchase.svg',
    'public://img/2016/e/experiential_video_library.svg' => '//assets.boston.gov/icons/experiential_icons/video_search.svg',
    'public://img/2016/e/experiential_icons_wifi.svg' => '//assets.boston.gov/icons/experiential_icons/wifi.svg',
    'public://img/2016/e/experiential_icons_who_is_my_city_councilor_2.svg' => '//assets.boston.gov/icons/experiential_icons/city_council_question.svg',
    'public://img/2016/e/experiential_icons_vote_1.svg' => '//assets.boston.gov/icons/experiential_icons/voting_ballot.svg',
    'public://img/2016/e/experiential_icons_trash_downlaod_0.svg' => '//assets.boston.gov/icons/experiential_icons/download_recycle_app.svg',
    'public://img/2016/e/experiential_icons_trash_downlaod.svg' => '//assets.boston.gov/icons/experiential_icons/download_recycle_app.svg',
    'public://img/2016/e/experiential_icons_trash.svg' => '//assets.boston.gov/icons/experiential_icons/cart.svg',
    'public://img/2016/e/experiential_icons_tpass_1.svg' => '//assets.boston.gov/icons/experiential_icons/t_pass.svg',
    'public://img/2016/e/experiential_icons_tow_info.svg' => '//assets.boston.gov/icons/experiential_icons/tow_truck_updates.svg',
    'public://img/2016/e/experiential_icons_ticket_2.svg' => '//assets.boston.gov/icons/experiential_icons/ballot_ticket.svg',
    'public://img/2016/e/experiential_icons_ticket_1.svg' => '//assets.boston.gov/icons/experiential_icons/ballot_ticket.svg',
    'public://img/2016/e/experiential_icons_thermostat.svg' => '//assets.boston.gov/icons/experiential_icons/temperature.svg',
    'public://img/2016/e/experiential_icons_testify.svg' => '//assets.boston.gov/icons/experiential_icons/testify_at_a_city_council.svg',
    'public://img/2016/e/experiential_icons_temporary_public_art.svg' => '//assets.boston.gov/icons/experiential_icons/paint_supplies.svg',
    'public://img/2016/e/experiential_icons_snow_plow_0.svg' => '//assets.boston.gov/icons/experiential_icons/plow.svg',
    'public://img/2016/e/experiential_icons_smoke_detector_1.svg' => '//assets.boston.gov/icons/experiential_icons/fire_alarm.svg',
    'public://img/2016/e/experiential_icons_smoke_detector.svg' => '//assets.boston.gov/icons/experiential_icons/fire_alarm.svg',
    'public://img/2016/e/experiential_icons_search_license_1.svg' => '//assets.boston.gov/icons/experiential_icons/certificate_search.svg',
    'public://img/2016/e/experiential_icons_search_license.svg' => '//assets.boston.gov/icons/experiential_icons/SVG/certificate_search.svg',
    'public://img/2016/e/experiential_icons_search_1.svg' => '//assets.boston.gov/icons/experiential_icons/search.svg',
    'public://img/2016/e/experiential_icons_search_0.svg' => '//assets.boston.gov/icons/experiential_icons/search.svg',
    'public://img/2016/e/experiential_icons_search.svg' => '//assets.boston.gov/icons/experiential_icons/search.svg',
    'public://img/2016/e/experiential_icons_reserve_parking_1.svg' => '//assets.boston.gov/icons/experiential_icons/no_parking_moving.svg',
    'public://img/2016/e/experiential_icons_repair.svg' => '//assets.boston.gov/icons/experiential_icons/construction_tool.svg',
    'public://img/2016/e/experiential_icons_renew_an_accesible_parking_0.svg' => '//assets.boston.gov/icons/experiential_icons/renew_accessible_parking_spot.svg',
    'public://img/2016/e/experiential_icons_poll_worker.svg' => '//assets.boston.gov/icons/experiential_icons/vote.svg',
    'public://img/2016/e/experiential_icons_phone_0.svg' => '//assets.boston.gov/icons/experiential_icons/phone.svg',
    'public://img/2016/e/experiential_icons_phone.svg' => '//assets.boston.gov/icons/experiential_icons/phone.svg',
    'public://img/2016/e/experiential_icons_pdf_doc.svg' => '//assets.boston.gov/icons/experiential_icons/document.svg',
    'public://img/2016/e/experiential_icons_pay_your_real_estate_taxes-_0.svg' => '//assets.boston.gov/icons/experiential_icons/real_estate_taxes.svg',
    'public://img/2016/e/experiential_icons_pay_your_real_estate_taxes-.svg' => '//assets.boston.gov/icons/experiential_icons/real_estate_taxes.svg',
    'public://img/2016/e/experiential_icons_parking_pass_3.svg' => '//assets.boston.gov/icons/experiential_icons/parking_pass.svg',
    'public://img/2016/e/experiential_icons_parking_pass_1.svg' => '//assets.boston.gov/icons/experiential_icons/parking_pass.svg',
    'public://img/2016/e/experiential_icons_parking_pass_0.svg' => '//assets.boston.gov/icons/experiential_icons/parking_pass.svg',
    'public://img/2016/e/experiential_icons_parking_pass.svg' => '//assets.boston.gov/icons/experiential_icons/parking_pass.svg',
    'public://img/2016/e/experiential_icons_online_reg.svg' => '//assets.boston.gov/icons/experiential_icons/web_persona.svg',
    'public://img/2016/e/experiential_icons_online_payments_0.svg' => '//assets.boston.gov/icons/experiential_icons/online_purchase.svg',
    'public://img/2016/e/experiential_icons_noparking_moving.svg' => '//assets.boston.gov/icons/experiential_icons/no_parking_moving.svg',
    'public://img/2016/e/experiential_icons_noparking_filming.svg' => '//assets.boston.gov/icons/experiential_icons/no_parking_filming.svg',
    'public://img/2016/e/experiential_icons_no_ticket.svg' => '//assets.boston.gov/icons/experiential_icons/no_ticket.svg',
    'public://img/2016/e/experiential_icons_neighborhoods_0.svg' => '//assets.boston.gov/icons/experiential_icons/du_neighborhoods_info.svg',
    'public://img/2016/e/experiential_icons_neighborhoods.svg' => '//assets.boston.gov/icons/experiential_icons/neighborhoods.svg',
    'public://img/2016/e/experiential_icons_meet_archaeologist.svg' => '//assets.boston.gov/icons/experiential_icons/schedule.svg',
    'public://img/2016/e/experiential_icons_medical_registration__1.svg' => '//assets.boston.gov/icons/experiential_icons/submit_for_certificates.svg',
    'public://img/2016/e/experiential_icons_medical_registration__0.svg' => '//assets.boston.gov/icons/experiential_icons/submit_for_certificates.svg',
    'public://img/2016/e/experiential_icons_medical_registration_.svg' => '//assets.boston.gov/icons/experiential_icons/submit_for_certificates.svg',
    'public://img/2016/e/experiential_icons_mayor_proclamation_.svg' => '//assets.boston.gov/icons/experiential_icons/mayoral_proclamation.svg',
    'public://img/2016/e/experiential_icons_mayor_greeting_letter_.svg' => '//assets.boston.gov/icons/experiential_icons/mayoral_letter.svg',
    'public://img/2016/e/experiential_icons_marriage_certificate.svg' => '//assets.boston.gov/icons/experiential_icons/marriage_application.svg',
    'public://img/2016/e/experiential_icons_map_1.svg' => '//assets.boston.gov/icons/experiential_icons/maps.svg',
    'public://img/2016/e/experiential_icons_map_0.svg' => '//assets.boston.gov/icons/experiential_icons/maps.svg',
    'public://img/2016/e/experiential_icons_map.svg' => '//assets.boston.gov/icons/experiential_icons/maps.svg',
    'public://img/2016/e/experiential_icons_mail_6.svg' => '//assets.boston.gov/icons/experiential_icons/envelope.svg',
    'public://img/2016/e/experiential_icons_mail_4.svg' => '//assets.boston.gov/icons/experiential_icons/envelope.svg',
    'public://img/2016/e/experiential_icons_mail_3.svg' => '//assets.boston.gov/icons/experiential_icons/envelope.svg',
    'public://img/2016/e/experiential_icons_mail_2.svg' => '//assets.boston.gov/icons/experiential_icons/envelope.svg',
    'public://img/2016/e/experiential_icons_mail_1.svg' => '//assets.boston.gov/icons/experiential_icons/envelope.svg',
    'public://img/2016/e/experiential_icons_mail_0.svg' => '//assets.boston.gov/icons/experiential_icons/envelope.svg',
    'public://img/2016/e/experiential_icons_important_3.svg' => '//assets.boston.gov/icons/experiential_icons/alert.svg',
    'public://img/2016/e/experiential_icons_important_10.svg' => '//assets.boston.gov/icons/experiential_icons/alert.svg',
    'public://img/2016/e/experiential_icons_important_0.svg' => '//assets.boston.gov/icons/experiential_icons/alert.svg',
    'public://img/2016/e/experiential_icons_important.svg' => '//assets.boston.gov/icons/experiential_icons/alert.svg',
    'public://img/2016/e/experiential_icons_how_to_watch_a_city_council_hearing_0.svg' => '//assets.boston.gov/icons/experiential_icons/watch_city_council.svg',
    'public://img/2016/e/experiential_icons_house_7.svg' => '//assets.boston.gov/icons/experiential_icons/house_2.svg',
    'public://img/2016/e/experiential_icons_house_6.svg' => '//assets.boston.gov/icons/experiential_icons/house_2.svg',
    'public://img/2016/e/experiential_icons_house_5.svg' => '//assets.boston.gov/icons/experiential_icons/house_2.svg',
    'public://img/2016/e/experiential_icons_house_4.svg' => '//assets.boston.gov/icons/experiential_icons/house_2.svg',
    'public://img/2016/e/experiential_icons_house_3.svg' => '//assets.boston.gov/icons/experiential_icons/house_2.svg',
    'public://img/2016/e/experiential_icons_house_0.svg' => '//assets.boston.gov/icons/experiential_icons/house_2.svg',
    'public://img/2016/e/experiential_icons_house.svg' => '//assets.boston.gov/icons/experiential_icons/house_2.svg',
    'public://img/2016/e/experiential_icons_game_of_the_week.svg' => '//assets.boston.gov/icons/experiential_icons/football.svg',
    'public://img/2016/e/experiential_icons_foreclosure.svg' => '//assets.boston.gov/icons/experiential_icons/foreclosure.svg',
    'public://img/2016/e/experiential_icons_food_truck.svg' => '//assets.boston.gov/icons/experiential_icons/food_truck.svg',
    'public://img/2016/e/experiential_icons_food_assistance-.svg' => '//assets.boston.gov/icons/experiential_icons/fruit_basket.svg',
    'public://img/2016/e/experiential_icons_find_a_career_center.svg' => '//assets.boston.gov/icons/experiential_icons/job_search.svg',
    'public://img/2016/e/experiential_icons_find_a_aprk.svg' => '//assets.boston.gov/icons/experiential_icons/park_location.svg',
    'public://img/2016/e/experiential_icons_find_a_park.svg' => '//assets.boston.gov/icons/experiential_icons/park_location.svg',
    'public://img/2016/e/experiential_icons_feed_back.svg' => '//assets.boston.gov/icons/experiential_icons/report.svg',
    'public://img/2016/e/experiential_icons_emergency_kit.svg' => '//assets.boston.gov/icons/experiential_icons/emergency_kit.svg',
    'public://img/2016/e/experiential_icons_download_0.svg' => '//assets.boston.gov/icons/experiential_icons/cell_phone_download.svg',
    'public://img/2016/e/experiential_icons_download2.svg' => '//assets.boston.gov/icons/experiential_icons/download.svg',
    'public://img/2016/e/experiential_icons_download.svg' => '//assets.boston.gov/icons/experiential_icons/cell_phone_download.svg',
    'public://img/2016/e/experiential_icons_domestic_partnership.svg' => '//assets.boston.gov/icons/experiential_icons/domestic_partnership.svg',
    'public://img/2016/e/experiential_icons_digital_print.svg' => '//assets.boston.gov/icons/experiential_icons/digital_print.svg',
    'public://img/2016/e/experiential_icons_cpr.svg' => '//assets.boston.gov/icons/experiential_icons/cpr.svg',
    'public://img/2016/e/experiential_icons_city_tv_.svg' => '//assets.boston.gov/icons/experiential_icons/video.svg',
    'public://img/2016/e/experiential_icons_city_councle_2.svg' => '//assets.boston.gov/icons/experiential_icons/city_council.svg',
    'public://img/2016/e/experiential_icons_city_council_enacts_laws_1.svg' => '//assets.boston.gov/icons/experiential_icons/city_council_legislation.svg',
    'public://img/2016/e/experiential_icons_city_council_enacts_laws_0.svg' => '//assets.boston.gov/icons/experiential_icons/city_council_legislation.svg',
    'public://img/2016/e/experiential_icons_city_council_enacts_laws.svg' => '//assets.boston.gov/icons/experiential_icons/city_council_legislation.svg',
    'public://img/2016/e/experiential_icons_certificate_1.svg' => '//assets.boston.gov/icons/experiential_icons/certificates.svg',
    'public://img/2016/e/experiential_icons_certificate_0.svg' => '//assets.boston.gov/icons/experiential_icons/certificates.svg',
    'public://img/2016/e/experiential_icons_certificate.svg' => '//assets.boston.gov/icons/experiential_icons/certificates.svg',
    'public://img/2016/e/experiential_icons_census.svg' => '//assets.boston.gov/icons/experiential_icons/family.svg',
    'public://img/2016/e/experiential_icons_census_0.svg' => '//assets.boston.gov/icons/experiential_icons/family.svg',
    'public://img/2016/e/experiential_icons_car_1.svg' => '//assets.boston.gov/icons/experiential_icons/car.svg',
    'public://img/2016/e/experiential_icons_car_0.svg' => '//assets.boston.gov/icons/experiential_icons/car.svg',
    'public://img/2016/e/experiential_icons_car.svg' => '//assets.boston.gov/icons/experiential_icons/car.svg',
    'public://img/2016/e/experiential_icons_birth_cert.svg' => '//assets.boston.gov/icons/experiential_icons/birth_certifcate.svg',
    'public://img/2016/e/experiential_icons_bike_0.svg' => '//assets.boston.gov/icons/experiential_icons/bike.svg',
    'public://img/2016/e/experiential_icons_bike.svg' => '//assets.boston.gov/icons/experiential_icons/bike.svg',
    'public://img/2016/e/experiential_icons_archaeological_dig_2.svg' => '//assets.boston.gov/icons/experiential_icons/archaeological_dig_questions.svg',
    'public://img/2016/e/experiential_icons_archaeological_dig_1.svg' => '//assets.boston.gov/icons/experiential_icons/archaeological_dig_questions.svg',
    'public://img/2016/e/experiential_icons_archaeological_dig.svg' => '//assets.boston.gov/icons/experiential_icons/dig_alert.svg',
    'public://img/2016/e/experiential_icons_accesible_parking_0.svg' => '//assets.boston.gov/icons/experiential_icons/accessible_parking_spot.svg',
    'public://img/2016/e/experiential_icons_2_ems_detial.svg' => '//assets.boston.gov/icons/experiential_icons/ambulance.svg',
    'public://img/2016/e/experiential_icons_2-16.svg' => '//assets.boston.gov/icons/experiential_icons/ems.svg',
    'public://img/2016/e/experiential_icons-cal_1.svg' => '//assets.boston.gov/icons/experiential_icons/calendar.svg',
    'public://img/2016/e/experiential_icons-cal_0.svg' => '//assets.boston.gov/icons/experiential_icons/calendar.svg',
    'public://img/2016/e/experiential_icons-31.svg' => '//assets.boston.gov/icons/experiential_icons/weddingrings.svg',
    'public://img/2016/d/deapartment_icons_emergency_management.svg' => '//assets.boston.gov/icons/dept_icons/emergency_management__logo.svg',
    'public://img/2016/5/5icons_home_repairs.svg' => '//assets.boston.gov/icons/experiential_icons/repair_your_home.svg',
    'public://img/2016/3/3icons_get_a_home_energy_assessment.svg' => '//assets.boston.gov/icons/experiential_icons/energy_report.svg',
    'public://embed/e/experiential_icons_who_is_my_city_councilor-.svg' => '//assets.boston.gov/icons/experiential_icons/city_council_question.svg',
    'public://img/icons/transactions/2019/03/tripple_decker_icon.png' => '//assets.boston.gov/icons/experiential_icons/triple_decker.svg',
    'public://img/icons/transactions/2019/05/tripple_decker_-_at_home_renters.png' => '//assets.boston.gov/icons/experiential_icons/triple_decker.svg',
    'public://img/icons/transactions/2019/06/tripple_decker_.png' => '//assets.boston.gov/icons/experiential_icons/triple_decker.svg',
    'public://img/icons/transactions/2019/06/tripple_decker__0.png' => '//assets.boston.gov/icons/experiential_icons/triple_decker.svg',
    "public://img/icons/department/icons_engagment_-_311_-_ons.svg" => '//assets.boston.gov/icons/dept_icons/bos_311_icon.svg',
    "public://img/icons/department/2018/08/asset_332.svg" => '//assets.boston.gov/icons/dept_icons/emergency_management_logo.svg',
    "public://img/icons/department/2018/05/pm_logo.svg" => '//assets.boston.gov/icons/dept_icons/property_and_construction_management_logo.svg',
    "public://img/icons/department/icons_bcyf.png" => '//assets.boston.gov/icons/dept_icons/mayors_youth_council_logo.svg',
    "public://img/icons/department/icons_archaeology_0.png" => '//assets.boston.gov/icons/dept_icons/archaeology_icon.svg',
    "public://img/icons/department/icons_archives.png" => '//assets.boston.gov/icons/dept_icons/archives_and_records_icon.svg',
    "public://img/icons/department/icons_fair_housing.png" => '//assets.boston.gov/icons/dept_icons/fair_housing_and_equity_logo.svg',
    "public://img/icons/department/icons_fire_0.png" => '//assets.boston.gov/icons/dept_icons/fire_operations_logo.svg',
    "public://img/icons/department/icons_new_bosonians.png" => '//assets.boston.gov/icons/dept_icons/immigrant_advancement_logo.svg',
    "public://img/icons/department/icons_intergovermental_relations.png" => '//assets.boston.gov/icons/dept_icons/intergovernmental_relations_logo.svg',
    "public://img/icons/department/icons_law.png" => '//assets.boston.gov/icons/dept_icons/law_department_logo.svg',
    "public://img/icons/department/mechanics-logo-final.png" => '//assets.boston.gov/icons/dept_icons/new_urban_mechanics_logo.svg',
    "public://img/icons/department/icon_registry.png" => '//assets.boston.gov/icons/dept_icons/registry_logo.svg',
  ];

  /**
   * Makes the allowed formats array to be publicly accessed.
   *
   * @return array
   *   An array of allowed file formats indexed by type.
   */
  public static function allowedFormats() {
    return self::$allowedFormats;
  }

  /**
   * This updates the taxonomy_vocab migration map.
   *
   * Required so that taxonomy entries can later be run with --update flag set.
   */
  public static function fixTaxonomyVocabulary() {
    printf("[action] Will update the taxonomy vocabularly.\n");
    $d7_connection = Database::getConnection("default", "migrate");
    $query = $d7_connection->select("taxonomy_vocabulary", "v")
      ->fields("v", ["vid", "machine_name"]);
    $source = $query->execute()->fetchAllAssoc("vid");

    if (!empty($source)) {
      $d8_connection = Database::getConnection("default", "default");
      foreach ($source as $vid => $row) {
        $d8_connection->update("migrate_map_d7_taxonomy_vocabulary")
          ->fields([
            "destid1" => $row->machine_name,
            "source_row_status" => 0,
          ])
          ->condition("sourceid1", $vid)
          ->execute();
      }
      $d8_connection->truncate("migrate_message_d7_taxonomy_vocabulary")
        ->execute();
    }

    printf("Updated Drupal 8 taxonomy_vocab table.\n\n");

  }

  /**
   * This updates the paragraph__field_list table.
   *
   * Translates D7 view names and displays to the D8 equivalents.
   */
  public static function fixListViewField() {
    // Fetch all the list records into a single object.
    printf("[action] Maps newly named view displays into list components.\n");

    foreach (["paragraph__field_list", "paragraph_revision__field_list"] as $table) {

      $d8_connection = Database::getConnection("default", "default");
      $query = $d8_connection->select($table, "list")
        ->fields("list", ["field_list_target_id", "field_list_display_id"]);
      $query = $query->groupBy("field_list_target_id");
      $query = $query->groupBy("field_list_display_id");
      $row = $query->execute()->fetchAll();

      $count = count($row);
      printf("[info] Will change %d references in %s.\n", $count, $table);

      // Process each row, making substitutions from map array $viewListMap.
      foreach ($row as $display) {
        $map = self::$viewListMap;
        if (isset($map[$display->field_list_target_id][$display->field_list_display_id])) {

          $entry = $map[$display->field_list_target_id][$display->field_list_display_id];
          printf("[info] Change %s/%s to %s/%s", $display->field_list_target_id ?: "--", $display->field_list_display_id ?: "--", $entry[0], $entry[1]);

          $d8_connection->update($table)
            ->fields([
              "field_list_target_id" => $entry[0],
              "field_list_display_id" => $entry[1],
            ])
            ->condition("field_list_target_id", $display->field_list_target_id)
            ->condition("field_list_display_id", $display->field_list_display_id)
            ->execute();

          printf("[success] component updated.\n");
        }
        else {
          sprintf("[warning] %s/%s: Not found.", $display->field_list_target_id ?: "--", $display->field_list_display_id ?: "--");
        }
      }
    }
    printf("\n");
  }

  /**
   * This makes sure the filename is set properly from the uri.
   */
  public static function fixFilenames() {
    printf("[action] Fixes filenames in table file_managed.\n");
    Database::getConnection()
      ->query("
        UPDATE file_managed
        SET filename = SUBSTRING_INDEX(uri, '/', -1)
        WHERE locate('.', filename) = 0 and fid > 0;
      ")
      ->execute();
    printf("[success] Done.\n");
    printf("\n");
  }

  /**
   * Updates the install config files based on the current DB entries.
   *
   * Sort of super CEX for config_update.
   */
  public static function updateModules() {
    printf("[action] Will export configs for all custom modules (super-cde).\n");
    _bos_core_global_update_configs();
    printf("[success] Done.\n");
    printf("\n");
  }

  /**
   * Updates the D7 svg icons to the new D8 located icons.
   */
  public static function updateSvgPaths() {
    printf("[action] Will map old svg path/filename to new path/filenames.\n");
    $cnt = 0;
    $svgs = \Drupal::database()->query("
        SELECT distinct f.fid, f.uri
        FROM file_managed f
          LEFT JOIN media m ON f.fid = m.mid
        WHERE f.filemime = 'image/svg+xml'
            AND m.mid IS NULL
            AND f.status = 1;")->fetchAll();

    if (!empty($svgs)) {
      foreach ($svgs as $svg) {
        $svg->file = File::load($svg->fid);
        if (!empty($svg->file) && NULL != ($svg->new_uri = self::$svgMapping[$svg->uri]) && strpos($svg->uri, ".svg")) {
          $svg->filename = self::cleanFilename($svg->new_uri);
          $svg->new_uri = str_replace(["https:", "http:"], "", $svg->new_uri);

          // Set the new uri and an appropriate filename on the file_managed
          // record.
          if ($svg->new_uri == "public://icons/") {
            $svg->filename .= " (MISSING)";
          }
          $result = \Drupal::database()->update("file_managed")
            ->fields([
              "uri" => $svg->new_uri,
              "filename" => strtolower($svg->filename),
            ])
            ->condition("fid", $svg->fid)
            ->execute();

          // Remove the original file from local file system.
          if ($result) {
            $old = \Drupal::service('stream_wrapper_manager')
              ->getViaUri($svg->uri);
            if (($old = $old->realpath()) && file_exists($old)) {
              // TODO: should also delete any styles loaded .... :(.
              unlink($old);
            }
          }

          // Create a media entry.
          $svg->type = "icon";
          $svg->media_library = TRUE;
          $svg->image = ["height" => 64, "width" => 64];
          $svg->thumbnail = ["height" => 100, "width" => 100];
          $svg->uri = $svg->new_uri;
          $svg->new_uri = NULL;

          if (self::makeMediaEntity($svg)) {
            $cnt++;
          }

        }
      }
      printf("[success] Updated %d media entries.\n", $cnt);

    }
    else {
      printf("[warning] no svgs found !!.\n");
    }
    // Need to flush the image/files + views caches.
    if ($cnt > 0) {
      printf("[info] Flushing caches.\n", $cnt);
      drupal_flush_all_caches();
    }
    printf("\n");
  }

  /**
   * Updates the D7 svg icons to the new D8 located icons.
   */
  public static function forceUpdateSvgPaths() {
    printf("[action] Will map all old image path/filename to new path/filenames.\n");
    $cnt = 0;
    $mf = new MigrationFixes();

    foreach (self::$svgMapping as $oldUri => $newUri) {
      $mimetype = $mf->getMimeFromFile($newUri);

      // See if there are file entities linked to this uri.
      $files = Database::getConnection()->select("file_managed", "f")
        ->fields("f", ["fid"])
        ->condition("uri", $oldUri, "=")
        ->execute()
        ->fetchAllAssoc("fid");

      if ($files) {
        // Process each file entity in turn.
        // First update the new uri and the new mime in the managed files table.
        $result = Database::getConnection()->update("file_managed")
          ->fields([
            "uri" => $newUri,
            "filemime" => trim($mimetype),
          ])
          ->condition("uri", $oldUri, "=")
          ->execute();
        // If the uri has changed and now is an ion, need to update the bundle
        // in all associated media entities.
        if (pathinfo($oldUri)['extension'] != pathinfo($newUri)['extension']
          && pathinfo($newUri)['extension'] == "svg") {
          // There may be multiple files with this uri.
          foreach ($files as $file) {
            // Get the media ids which are linked to these file ids.
            $mids = Database::getConnection()->select("media__image", "mi")
              ->fields("mi", ["entity_id"])
              ->condition("image_target_id", $file->fid, "=")
              ->execute()
              ->fetchAllAssoc("entity_id");
            // Now update the media tables.
            // There may be multiple media entities linked to this file entity.
            foreach ($mids as $mid) {
              Database::getConnection()->update("media")
                ->fields(["bundle" => "media"])
                ->condition("mid", $mid->entity_id, "=")
                ->execute();
              Database::getConnection()->update("media_field_data")
                ->fields(["bundle" => "media"])
                ->condition("mid", $mid->entity_id, "=")
                ->execute();
              Database::getConnection()->update("media__image")
                ->fields(["bundle" => "media"])
                ->condition("entity_id", $mid->entity_id, "=")
                ->execute();
              Database::getConnection()->update("media_revision__image")
                ->fields(["bundle" => "media"])
                ->condition("entity_id", $mid->entity_id, "=")
                ->execute();
            }
          }
        }
      }
      if (!empty($result)) {
        // Delete the old uri as we have now linked to a new path.
        $file = \Drupal::service('file_system')->realpath($oldUri);
        if (file_exists($file)) {
          @unlink($file);
        }
        $cnt++;
      }
    }

    // Need to flush the image/files + views caches.
    if ($cnt > 0) {
      printf("[info] Flushing caches.\n", $cnt);
      drupal_flush_all_caches();
    }

    printf("[success] Remapped uri's for %d media entries.\n", $cnt);
  }

  /**
   * Creates media entities for files, and loads some into the media library.
   */
  public static function createMediaFromFiles() {
    printf("[action] Will create media entities for select managed files.\n");
    // Only files with these MIME will loaded as media entities.
    $mimes = [
      "file" => [
        "application/pdf" => "pdf",
        "application/msword" => "doc",
        "text/plain" => "txt",
        "application/rtf" => "rtf",
      ],
      "image" => [
        "image/tiff" => "tif",
        "image/gif" => "gif",
        "image/jpg" => "jpg",
        "image/jpeg" => "jpg",
        'image/x-photoshop' => 'jpg',
        "image/png" => "png",
      ],
    ];
    // Only files referenced by these entity fields will be loaded into the
    // media library.
    $media_library = [
      "node" => [
        "field_intro_image",
        "field_thumbnail",
      ],
      "paragraph" => [
        "field_image",
        "field_person_photo",
        "field_thumbnail",
      ],
    ];
    $ocnt = 0;
    (new MigrationFixes)->checkStatus();
    foreach (["file", "image"] as $media_type) {
      $cnt = 0;
      foreach ($mimes[$media_type] as $mime => $file_extension) {
        $files = \Drupal::database()->query("
          SELECT distinct f.fid, f.uri
            FROM file_managed f
            	LEFT JOIN media m ON f.fid = m.mid
            WHERE f.filemime = '" . $mime . "'
              AND m.mid IS NULL
              AND f.status = 1;")->fetchAll();

        if (!empty($files)) {
          $tot = count($files);
          printf("[info] Will create %d %s (%s) media entries.\n  executing .", $tot, $mime, $media_type);
          $icnt = $runner = 0;
          foreach ($files as $file) {
            $file->file = File::load($file->fid);
            if (!empty($file->file)) {
              $file->filename = self::cleanFilename($file->uri);
              $file->type = ($media_type == "file" ? "document" : "image");
              if ($media_type == "file") {
                $file->media_library = TRUE;
                $file->filename .= " (" . $file_extension . ")";
              }
              else {
                $file->media_library = self::isInTables($file->fid, $media_library);
                $file->thumbnail = ["height" => 100, "width" => 100];
              }
              if (self::makeMediaEntity($file)) {
                $icnt++;
                $calc = intval((($tot - ($tot - $icnt)) / $tot) * 100) / 2;
                if ($calc > $runner) {
                  printf(".");
                  $runner = $calc;
                }
              }
            }
          }
          $cnt += $icnt;
          printf("\n");
          printf("[success] -> %d %s (%s) media entities created.\n", $icnt, $mime, $media_type);
        }
        else {
          printf("[notice] -> There were no %s (%s) files to create media entities for.\n", $mime, $media_type);
        }
        (new MigrationFixes)->clearEntityCache("file");
      }
      $ocnt += $cnt;
      printf("[success] => Created %d %s media entries.\n\n", $cnt, $media_type);

    }
    printf("[success] Created %d media entries in total.\n\n", $ocnt);
    // Need to flush the image/files + views caches.
    printf("\n");
  }

  /**
   * Determine if the fid_id provided has been referenced in the tables given.
   *
   * @param string $fid
   *   The file_id.
   * @param array $tables
   *   A nested array of enityt types and field names.
   *
   * @return bool
   *   If the fid is found in any table/field combo.
   */
  protected static function isInTables($fid, array $tables) {
    foreach ($tables as $node => $fields) {
      foreach ($fields as $field) {
        $table = $node . "__" . $field;
        $column = $field . "_target_id";
        if (\Drupal::database()->query("
          SELECT distinct f.entity_id
            FROM $table f
            WHERE $column = $fid;")->fetchAll()) {
          return TRUE;
        }
        return FALSE;
      }
    }
  }

  /**
   * Creates a media entity linked to a supplied file entity.
   *
   * @param object $file
   *   Object contining information for new media item.
   *
   * @return bool
   *   True if made false if not.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private static function makeMediaEntity($file) {
    // Try to find this file_id in the media table.
    $test = "image.target_id";
    if ($file->type == "document") {
      $test = "mid";
    }
    if (NULL == ($mid = \Drupal::entityQuery("media")
      ->condition($test, $file->fid, "=")
      ->execute())) {
      // Not there, so create a new one.
      // First create a human-friendly name for the file.
      $file->filename = str_replace(["_"], " ", $file->filename);
      $file->filename = str_replace([" and "], " & ", $file->filename);
      // If local, see if the actual file exists, if not make note in filename.
      if (strpos($file->uri, "lic://") > 0) {
        $physical = \Drupal::service('stream_wrapper_manager')
          ->getViaUri($file->uri);
        if (isset($physical) && ($physical = $physical->realpath()) && !file_exists($physical)) {
          $file->filename .= " (MISSING)";
        }
      }
      // Create the media entity.
      $param = [
        "bundle" => $file->type,
        "mid" => $file->fid,
        'uid' => ($file->file->uid->target_id ?? 1),
        'name' => ($file->filename ?? "City of Boston stock media item"),
        'status' => ($file->file->status->value ?? 1),
        'created' => $file->get("created")->value,
        'changed' => $file->get("changed")->value,
        'field_media_in_library' => ($file->media_library ?? FALSE),
      ];
      if ($file->type != "document") {
        $param['thumbnail'] = [
          'title' => ($file->filename ?? "City of Boston stock icon"),
          'alt' => ucwords($file->type) . " for " . ($file->filename ?? "City of Boston media item"),
          'width' => $file->thumbnail['width'],
          'height' => $file->thumbnail['height'],
        ];
        $param['image'] = [
          'target_id' => $file->fid,
          'title' => ($file->filename ?? "City of Boston media item"),
          'alt' => ucwords($file->type) . " for " . ($file->filename ?? "City of Boston media item"),
          'width' => $file->image['width'],
          'height' => $file->image['height'],
        ];
      }
      $media = Media::create($param);
      // Don't create a new revision, so the mid supplied is retained in saving.
      $media->setNewRevision(FALSE);
      try {
        // Save the new media entity.
        $result = $media->save();

        if (($file->media_library ?? FALSE) && $media->field_media_in_library->value != 1) {
          $media->field_media_in_library->value = 1;
          $media->setNewRevision(FALSE);
          $result = $media->save();
        }

        if ($result && !empty($param['thumbnail'])) {
          // After saving new media, a new thumbnail will have been created in
          // managed_files table.  We need to makes sure the uri for this
          // is pointing to the new uri location as well.
          $thumb_id = $media->get("thumbnail")->target_id;
          if ($thumb_id != $file->fid) {
            $thumbnail = File::load($thumb_id);
            $rem_location = $thumbnail->getFileUri();
            $thumbnail->setFileUri($file->uri);
            // Remove the original thumbnail file from local file system.
            if ($thumbnail->save()) {
              return TRUE;
            }
          }
        }
      }
      catch (Exception $e) {
        printf("Issue with " . $file->fid . "(filename) - " . $e->getMessage());
        return FALSE;
      }
    }

    return TRUE;

  }

  /**
   * Manually create the media entity for the map background image.
   */
  public static function fixMap() {
    printf("[action] Will ensure map default image is loaded properly.\n");
    // Copy map module icons into expected location.
    _bos_core_install_icons("bos_map", FALSE);
    // Install the map default background image.
    bos_map_rebuild();
    printf("[info] Finished.\n");
    printf("\n");
  }

  /**
   * Manually migrate the message_for_the_day content.
   */
  public static function migrateMessages() {
    // Fetch rows from D7.
    printf("[action] Will manually copy message_for_the_day entities because migration can't handle them properly.\n");

    $migrate_tables = [
      "field_revision_field_date" => "paragraph_revision__field_recurrence",
      "field_data_field_date" => "paragraph__field_recurrence",
    ];

    Database::getConnection("default", "default")
      ->truncate("date_recur__paragraph__field_recurrence")
      ->execute();

    foreach ($migrate_tables as $source_table => $dest_table) {
      $d7_connection = Database::getConnection("default", "migrate");
      $query_string = "SELECT  i.start start_date, i.end end_date, d.*
        FROM $source_table d
          INNER JOIN (
            SELECT entity_id, min(field_date_value) start, max(field_date_value) end
            FROM $source_table
            GROUP BY entity_id
          ) i ON i.entity_id = d.entity_id
        WHERE d.bundle = 'message_for_the_day'
            AND d.delta = 0";
      $source_rows = $d7_connection->query($query_string)->fetchAll();

      // Migrate them into D8.
      if (count($source_rows)) {
        $cnt = 0;
        printf("[info] %d message_for_the_day records found to be migrated from %s.\n", count($source_rows), $source_table);
        foreach ($source_rows as $source_row) {
          $infinite = NULL;
          $enabled = 1;
          $start_date = strtotime($source_row->start_date);
          $end_date = strtotime("+ 1 day", $start_date);
          $start_date = format_date($start_date, "html_date");

          $rrule = $source_row->field_date_rrule;
          $exceptions = explode("\r\n", $rrule);
          if (isset($exceptions[1])) {
            $rrule = $exceptions[0];
          }
          $rules = explode(";", str_replace("RRULE:", "", $rrule));

          foreach ($rules as $key => &$rule) {
            $keypair = explode("=", $rule);
            if (!isset($keypair[0]) || empty($keypair[1])) {
              unset($rules[$key]);
            }
            else {
              if ($keypair[0] == "FREQ" && $keypair[1] == "ONCE") {
                $infinite = 0;
              }
              elseif ($keypair[0] == "UNTIL") {
                $edate = strtotime($keypair[1]);
                if ($edate > strtotime("+1 year")) {
                  $infinite = ($infinite ?? 1);
                  unset($rules[$key]);
                  $keypair = NULL;
                }
                if ($edate < strtotime("-1 month")) {
                  $infinite = ($infinite ?? 0);
                  $enabled = 0;
                  unset($rules[$key]);
                  $keypair = NULL;
                }
              }
              elseif ($keypair[0] == "COUNT" && intval($keypair[1]) >= 500) {
                $infinite = ($infinite ?? 1);
                unset($rules[$key]);
                $keypair = NULL;
              }
              elseif ($keypair[0] == "WKST") {
                unset($rules[$key]);
                $keypair = NULL;
              }
              elseif ($keypair[0] == "BYDAY" && substr($keypair[1], 0, 1) == '+') {
                $rules[] = "BYSETPOS=" . substr($keypair[1], 1, 1);
                $keypair[1] = substr($keypair[1], 2);
              }

              if (isset($keypair)) {
                $rule = implode("=", $keypair);
              }
            }
          }

          $end_date = format_date($end_date, "html_date");
          $rules = implode(";", $rules);
          if (!empty($exceptions[1])) {
            $exdates = explode(",", str_replace([
              "EXDATE:",
              "RDATE:",
            ], "", $exceptions[1]));
            foreach ($exdates as &$exdate) {
              $dt = new \DateTime($exdate);
              $exdate = date_format($dt, "Ymd");
            }
            $exceptions[1] = implode(",", $exdates);
            $rules = "RRULE:" . $rules . "\r\nEXDATE:" . $exceptions[1];
          }

          if (empty($rules)) {
            $rules = NULL;
          }
          $infinite = ($infinite ?? 0);

          $paragraph = \Drupal::entityTypeManager()->getStorage("paragraph");
          if ($source_table == "field_revision_field_date") {
            $paragraph = $paragraph->loadRevision($source_row->revision_id);
          }
          else {
            $paragraph = $paragraph->load($source_row->entity_id);
          }
          if (!empty($paragraph)) {
            $paragraph->field_enabled = $enabled;
            $paragraph->field_recurrence->value = $start_date;
            $paragraph->field_recurrence->end_value = $end_date;
            $paragraph->field_recurrence->rrule = $rules;
            $paragraph->field_recurrence->infinite = $infinite;
            $paragraph->save();
            $cnt++;
          }
        }
        printf("[success] %d message_for_the_day records were migrated to %s.\n", $cnt, $dest_table);
      }
      else {
        printf("[warning] No message_for_the_day records to migrate.\n");
      }
    }

    // Update the new status fields.
    $nodes = \Drupal::entityTypeManager()->getStorage("node")
      ->loadByProperties(["type" => "status_item"]);
    if (!empty($nodes)) {
      $cnt = 0;
      foreach ($nodes as $node) {
        $node_status_item = \Drupal::entityTypeManager()->getStorage("node")
          ->load($node->id());
        if (!empty($node_status_item) && !isset($node_status_item->field_enabled->value)) {
          $node_status_item->field_weight = 0;
          $node_status_item->field_enabled = 1;
          $node_status_item->save();
          $cnt++;
        }
      }
      printf("[success] Set active flag on %d un-assigned status_item nodes.\n\n", $cnt);
    }
    printf("\n");
  }

  /**
   * Ensure node items which dont have correct revision are updated.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function fixRevisions() {
    printf("[action] Will update node records with known revision issues from migration.\n");
    $revisionSync = [
      "person_profile" => [
        "type" => "node",
        "table" => "node__field_position_title",
      ],
      "program_initiative_profile" => [
        "type" => "node",
        "table" => "node__body",
      ],
    ];

    foreach ($revisionSync as $type => $data) {
      $cnt = 0;
      if ($data["type"] == "node") {
        $table = $data["table"];
        $sql = "SELECT n.vid FROM node_field_data n
                LEFT JOIN $table t
                    ON (n.nid = t.entity_id AND n.vid = t.revision_id)
                WHERE type = '$type'
                    AND n.status =  1
                    AND t.revision_id is null;";
      }
      $nids = Database::getConnection()->query($sql)->fetchAll();
      if (count($nids)) {
        foreach ($nids as $nid) {
          \Drupal::entityTypeManager()->getStorage("node")
            ->loadRevision($nid->vid)
            ->save();
          $cnt++;
        }
        printf("[success] Processed %d %s records in %s\n\n", $cnt, $type, $table);
      }
      else {
        printf("[warning] No revisions to process for %s in %s\n\n", $type, $table);
      }
    }
  }

  /**
   * Updates published status for major node.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function fixPublished() {
    $sql = "
      SELECT d8.nid, d8.vid, d7.status d7_status, d8.status d8_status, d8.title, d8.type, w.published, w.is_current
        FROM bostond8dev.node_field_data d8
        INNER JOIN bostond8ddb289903.node d7 ON d8.nid = d7.nid and d8.vid = d8.vid and d8.status <> d7.status
        INNER JOIN bostond8ddb289903.workbench_moderation_node_history w on d8.vid = w.vid and w.published = 1
    ";

    $cnt = 0;
    $nids = Database::getConnection()->query($sql)->fetchAll();
    if (count($nids)) {
      printf("[action] Will publish %d unpublished nodes.\n", count($nids));
      foreach ($nids as $nid) {
        $node = \Drupal::entityTypeManager()->getStorage("node")
          ->loadRevision($nid->vid);
        $node->setPublished(1);
        $node->set("moderation_state", "published");
        $node->setNewRevision(FALSE);
        $node->save();
        $cnt++;
        if ($cnt < 25) {
          printf("[info] Published node #%d (%s). [vid was changed from %d to %d].\n", $node->id(), $node->get("title")->value, $nid->vid, $node->get("vid")->value);
        }
        elseif ($cnt == 25) {
          printf("[info] Further publish messages supressed.\n");
        }
      }
      printf("[success] Published %d nodes.\n\n", $cnt);
    }
    else {
      printf("[warning] No unpublished nodes to process.\n\n");
    }
  }

  /**
   * Handles duplicate files.
   */
  public static function fixDuplicateFiles() {
    // Find where multiple entries for the same physical file.
    $query = "SELECT min(fid) remapfid, uri, count(*) num FROM drupal.file_managed
              where filemime = 'image/svg+xml'
              group by uri
              having num > 1;";
    $duplist = \Drupal::database()->query($query)->execute()->fetchAll();

    // Find where these have been actually used.
    foreach ($duplist as $dup_file_id => $dup_file) {
      // Get a list of the files duplicated.
      $remap_fid = $dup_file["remapfid"];
      $files_managed = \Drupal::database()
        ->select("file_managed", "f")
        ->fields("f", ["fid"])
        ->condition("uri", $dup_file_id);

      foreach ($files_managed as $fid => $file_managed) {
        // Find the places this file has been used.
        $files_managed = \Drupal::database()
          ->select("file_managed", "f")
          ->fields("f", ["fid"])
          ->condition("uri", $dup_file_id);

      }
    }
  }

  /**
   * Recreate deleted url aliases.
   *
   * This will update the redirects table with url aliases which were deleted
   * during migration and nort re-created.
   *
   * @param string $d8
   *   The schema name for the d8 database.
   * @param string $d7
   *   The schema name for the d7 database.
   */
  public static function fixUnMappedUrlAliases(string $d8 = "", string $d7 = "") {
    // Find url aliases from D7 that dont exist in D8 and insert
    // them as redirects into the redirect table.
    if (empty($d7)) {
      $d7 = "bostond8ddb289903";
    }
    if (empty($d8)) {
      $d8 = "bostond8dev";
    }
    $query_string = "
      INSERT INTO $d8.redirect (
        type, uuid, language, hash,
        uid, redirect_source__path,
        redirect_source__query, redirect_redirect__uri, redirect_redirect__title,
        redirect_redirect__options, status_code, created)
      SELECT
        'redirect', uuid(), 'und', concat('unhashed-', sha(concat(unix_timestamp(),'internal:/', d7.alias))),
        '0', d7.alias,
        'N', concat('internal:/', d7.source), '',
        'a:0:{}', 301, unix_timestamp()
      FROM $d8.url_alias d8
        RIGHT OUTER JOIN $d7.url_alias d7 ON d8.source = concat('/', d7.source) and d8.alias = concat('/', d7.alias)
      WHERE d8.pid IS NULL
        AND d7.source LIKE 'node%'";
    \Drupal::database()->query($query_string);

    // Now find these and generate and update the correct hash's.
    $urls = \Drupal::database()->select("redirect", "r")
      ->fields("r", [
        "rid",
        "redirect_source__path",
        "redirect_redirect__uri",
        "language",
      ])
      ->condition("hash", "unhashed-%", "LIKE")
      ->execute()
      ->fetchAllAssoc("rid");
    foreach ($urls as $rid => $url) {
      $hash = Redirect::generateHash($url->redirect_source__path, [], $url->language);
      try {
        $result = \Drupal::database()->update("redirect")
          ->fields([
            "hash" => $hash ?: $url->redirect_redirect__uri,
          ])
          ->condition("rid", $rid, "=")
          ->execute();
      }
      catch (\Exception $e) {
        if ($e->getCode() == 23000) {
          // Remove this duplicate.
          \Drupal::database()->delete("redirect")
            ->condition("rid", $rid, "=")
            ->execute();
        }
      }
    }
  }

  /**
   * Updates the date of img/media entities to match the underlying file entity.
   */
  public static function syncMediaDates() {
    \Drupal::database()->query("
      UPDATE media_field_data media
        INNER JOIN media__image img ON media.mid = img.entity_id
        INNER JOIN file_managed file ON img.image_target_id = file.fid
          SET media.created = file.created,
              media.changed = file.changed
        WHERE media.created <> file.created;")->execute();

    \Drupal::database()->query("
      UPDATE media_field_data media
        INNER JOIN media__image img ON media.mid = img.entity_id
        INNER JOIN file_managed file ON img.image_target_id = file.fid
          SET media.name = file.filename
        WHERE media.name <> file.filename;")->execute();
  }

  /**
   * Update anchors.
   *
   * @param bool $commit
   *   Should changes be saved.
   */
  public static function fixPdfLinks($commit = FALSE) {
    printf("Will Search and repair broken links from migration..\n");
    $path_maps = self::$folderMappings;
    $cnt = 0;
    $fnd = 0;
    // Define the fields and tables to scan.
    // Array table=>field.
    $eligible = [
      "paragraph__field_column_description" => "field_column_description_value",
      "paragraph__field_description" => "field_description_value",
      "paragraph__field_right_column" => "field_right_column_value",
      "paragraph__field_left_column" => "field_left_column_value",
      "paragraph__field_middle_column" => "field_middle_column_value",
      "paragraph__field_short_description" => "field_short_description_value",
      "paragraph__field_intro_text" => "field_intro_text_value",
      "paragraph__field_keep_in_mind" => "field_keep_in_mind_value",
      "paragraph__field_sidebar_text" => "field_sidebar_text_value",
      "paragraph__field_step_details" => "field_step_details_value",
    /*    "paragraph_revision__field_column_description"
      => "field_column_description_value",
      "paragraph_revision__field_description" => "field_description_value",
      "paragraph_revision__field_right_column" => "field_right_column_value",
      "paragraph_revision__field_left_column" => "field_left_column_value",
      "paragraph_revision__field_middle_column" => "field_middle_column_value",
      "paragraph_revision__field_short_description"
      => "field_short_description_value",
      "paragraph_revision__field_intro_text" => "field_intro_text_value",
      "paragraph_revision__field_keep_in_mind" => "field_keep_in_mind_value",
      "paragraph_revision__field_sidebar_text" => "field_sidebar_text_value",
      "paragraph_revision__field_step_details" => "field_step_details_value",*/
      "node__body" => "body_value",
      "node__field_description" => "field_description_value",
      "node__field_did_you_know" => "field_did_you_know_value",
      "node__field_extra_info" => "field_extra_info_value",
      "node__field_intro_text" => "field_intro_text_value",
      "node__field_need_to_know" => "field_need_to_know_value",
    /*    "node_revision__body" => "body_value",
      "node_revision__field_description" => "field_description_value",
      "node_revision__field_did_you_know" => "field_did_you_know_value",
      "node_revision__field_extra_info" => "field_extra_info_value",
      "node_revision__field_intro_text" => "field_intro_text_value",
      "node_revision__field_need_to_know" => "field_need_to_know_value",*/
    ];
    // Extract the main search array into two single column arrays for use
    // in preg_replace.
    $search = array_keys(self::$folderMappings);
    $replace = array_values($path_maps);
    $publicStream = \Drupal::service('stream_wrapper_manager');
    // Scan the fields looking for anchor tags.
    foreach ($eligible as $table => $field) {
      printf("\nProcessing $field in $table.\n");
      $table_count = 0;
      // Fetch all records.
      $row = \Drupal::database()->select($table, "t")
        ->fields("t", ["entity_id", "revision_id", $field])
        ->execute()
        ->fetchAll();
      // Process all rows.
      foreach ($row as $value) {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $html = '<?xml encoding="UTF-8">' . $value->{$field};
        try {
          $document->loadHTML($html, LIBXML_NOERROR);
          $xpath = new \DOMXPath($document);
          $save = FALSE;
          // Replace the path using the map.
          foreach ($xpath->query('//a[starts-with(@href, "/sites/default/files") or contains(@href, "boston.gov/sites/default/files")]') as $link_node) {
            $fnd++;
            $matches = [];
            // Extract the file path and check if it exists.
            $href = preg_replace("~[^\p{L}\p{N} _\./:]+~u", '', urldecode($link_node->getAttribute('href')));

            if (preg_match(self::$cobDomainREGEX . "i", $href, $matches)
              || stripos($href, "/sites/default/files") === 0) {
              // This is on our sub/domain, strip to get rel link.
              $href_rel = preg_replace(self::$cobDomainREGEX . "i", "/", $href);
              $public = str_ireplace("/sites/default/files/", "public://", $href_rel);
              $fileStream = $publicStream->getViaUri($public);
              if (!($file = $fileStream->realpath()) || !@file_exists($file)) {
                // If we dont have a file in that location, then attempt to
                // map it in ...
                $exists = FALSE;
                $href_parts = pathinfo($href_rel);
                if ($href_parts["dirname"] == "/sites/default/files") {
                  // This file was originally on the root of the public folder.
                  $test = "/sites/default/files/embed/" . substr($href_parts["basename"], 0, 1) . "/" . $href_parts['basename'];
                  if (TRUE == ($exists = file_exists($test))) {
                    $href = $test;
                    $save = TRUE;
                  }
                }
                if (!$exists) {
                  // We should be able to engineer a non-root path for this.
                  $hreft = str_ireplace("/sites/default/files/", "//", $href_rel);
                  // Substitute the path.
                  $href = preg_replace($search, $replace, $hreft);

                  // Just check this link is valid, before committing.
                  $fileStream = $publicStream->getViaUri("public:" . $href);
                  $orig = urldecode($link_node->getAttribute('href'));
                  if (($file = $fileStream->realpath()) && @file_exists($file)) {
                    // Build back out to an absolute Url.
                    $href = str_replace("//", "https://www.boston.gov/sites/default/files/", $href);
                    $save = TRUE;
                    $link_node->setAttribute("href", $href);
                    printf("Fixed link from %s to %s\n", $orig, $href);
                  }
                  else {
                    // See if we can find this file on the old website.
                    $url = "https://boston.prod.acquia-sites.com" . $href_rel;
                    $file_headers = @get_headers($url);
                    $orig = pathinfo($orig);
                    if (!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
                      // Hmm very broken.  Report.
                      printf("[WARNING] %s could not be found at %s\n", $orig["basename"], $orig["dirname"]);
                      printf(" - or its map (%s)\n", str_replace(" ", "%20", $href));
                      printf(" - in %s (id #%s - rev: #%s)\n", $table, $value->entity_id, $value->revision_id);
                    }
                    else {
                      // OK so it was missed in the migration, copy now.
                      printf("[INFO] %s could not be found at %s\n", $orig["basename"], $orig["dirname"]);
                      printf(" - or its map (%s)\n", str_replace(" ", "%20", $href));
                      $outFileName = str_replace("//", DRUPAL_ROOT . "/sites/default/files/", $href);
                      if ($commit) {
                        $options = [
                          CURLOPT_FILE => fopen($outFileName, 'w'),
                          CURLOPT_TIMEOUT => 28800,
                          // Set to 8 hours so we dont timeout on big files.
                          CURLOPT_URL => $url,
                        ];
                        $ch = curl_init();
                        curl_setopt_array($ch, $options);
                        curl_exec($ch);
                        curl_close($ch);
                        printf(" - was found at %s and copied to %s\n", str_replace(" ", "%20", $url), $outFileName);
                      }
                      else {
                        printf(" - was found at %s BUT NOT copied to %s\n", str_replace(" ", "%20", $url), $outFileName);
                      }
                      printf(" - in %s (id #5s - rev: #%s)\n", $table, $value->entity_id, $value->revision_id);
                      $href = str_replace("//", "https://www.boston.gov/sites/default/files/", $href);
                      $save = TRUE;
                      $link_node->setAttribute("href", $href);
                      printf(" - Fixed link from %s to %s\n", $orig, $href);
                    }
                  }
                }
              }
            }
          }
          if ($save) {
            if ($commit) {
              $result = Html::serialize($document);
              // Update the table.
              if (\Drupal::database()->update($table)
                ->fields([$field => $result])
                ->condition("revision_id", $row->revision_id)
                ->execute()) {
                $cnt++;
              }
              printf("\n");
            }
            else {
              printf(" not saved in DB\n");
              $cnt++;
            }
          }
        }
        catch (\Exception $e) {
        }
        $table_count++;
      }
      printf("=> Processed %s rows in %s\n", $table_count, $table);
    }

    printf("Found %s and Repaired %s broken links from migration.\n", $fnd, $cnt);

  }

  /**
   * This renames the file_managed filename back to the physical filename.
   */
  public static function resetFilename() {
    printf("Will Reset all media::document and entity::file filenames.\n");

    $conn = \Drupal::database();
    $result = $conn->query("
      UPDATE {media__field_document} d
        INNER JOIN {file_managed} f on d.field_document_target_id = f.fid
          SET d.field_document_description = f.filename
        WHERE d.field_document_description IS NULL AND d.entity_id IS NOT NULL;
    ");
    $result->allowRowCount = TRUE;
    $result->execute();
    printf("Updated filenames for %s media documents.\n", $result->rowCount());

    $result = \Drupal::database()->query("
      UPDATE {file_managed}
        SET filename = substring_index(uri, '/', -1);
    ");
    $result->allowRowCount = TRUE;
    $result->execute();
    printf("Updated filenames for %s file entities.\n", $result->rowCount());
  }

  /**
   * This identifies broken links in the files_managed table.
   *
   * @param string $type
   *   The file type: document | image | audio | video | undefined.
   */
  public static function findBrokenFileEntities(string $type = 'document') {
    printf("Will find broken %ss.\n", $type);
    $cnt = $missing = $bad = 0;
    $conn = \Drupal::database();
    $rows = $conn->query("
      SELECT * FROM {file_managed} WHERE type = '$type';
    ")
      ->fetchAll();
    $tot = count($rows);
    printf("Checking %s %ss.\n", $tot, $type);

    foreach ($rows as $row) {
      $cnt++;
      if (($tot > 40 && (($cnt % intval($tot / 40)) == 0)) || $tot <= 40) {
        sprintf(".");
      }
      $uri = $row->uri;
      $uri = str_replace("public:/", "https://www.boston.gov/sites/default/files", $uri);
      if (!@file_get_contents($uri, NULL, NULL, NULL, 128)) {
        $bad++;
        $path = pathinfo($uri);
        printf("\nCould not find file %s on new site (path: %s) (fid: %s).\n", $path["basename"], str_replace(" ", "%20", $uri), $row->fid);
        $uri = str_replace("public:/", "https://boston.prod.acquia-sites.com/sites/default/files", $uri);
        if (!@file_get_contents($uri, NULL, NULL, NULL, 128)) {
          $missing++;
          printf("  - ! also not on old site (%s).\n", str_replace(" ", "%20", $uri));
          $bad--;
        }
      }
    }
    sprintf("\nChecked %s of %s files.\n", $cnt, $tot);
  }

  /**
   * Provides some diagnostics of file and media entities.
   *
   * @param int $duplicate_threshold
   *   What determines a duplicate (defaults to 2 records.)
   */
  public static function fileInfo(int $duplicate_threshold = 2) {
    $getData = function ($query) {
      return \Drupal::database()->query($query)->fetchAll();
    };
    $plotData = function ($rows, $title) {
      printf("%s", $title);
      printf("===============================.\n");
      foreach ($rows as $row) {
        foreach ($row as $col) {
          printf("\s", $col);
        }
        printf("\n");
      }
      printf("\n\n");
    };

    $rows = $getData("SELECT type, count(*) FROM drupal.file_managed group by type;");
    $plotData($rows, "Summary of managed file content.\n");

    $rows = $getData("SELECT filename, uri, count(*) FROM drupal.file_managed group by filename having count(*) > " . $duplicate_threshold);
    $plotData($rows, "Summary of duplicated file content.\n");
  }

}
