<?php

namespace Drupal\bos_migration;

use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use spec\Behat\MinkExtension\Listener\SessionsListenerSpec;

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

  /**
   * An array to map d7 view + displays to d8 equivalents.
   *
   * @var array
   */
  protected static $viewListMap = [
    'bos_department_listing' => [
      'listing' => ['departments_listing', 'page_1'],
    ],
    'bos_news_landing' => [
      'page' => ["news_landing", 'page_1'],
    ],
    'calendar' => [
      'feed_1' => ["calendar", "page_1"],
      'listing' => ["calendar", "page_1"],
    ],
    'metrolist_affordable_housing' => [
      'page' => ["metrolist_affordable_housing", "page_1"],
      'page_1' => ["metrolist_affordable_housing", "page_1"],
    ],
    'news_and_announcements' => [
      'departments' => ["news_and_announcements", "related"],
      'events' => ["news_and_announcements", "related"],
      'guides' => ["news_and_announcements", "related"],
      'most_recent' => ["news_and_announcements", "upcoming"],
      'news_events' => ["news_and_announcements", "related"],
      'places' => ["news_and_announcements", "related"],
      'posts' => ["news_and_announcements", "related"],
      'programs' => ["news_and_announcements", "related"],
    ],
    'places' => [
      'listing' => ["places", "page_1"],
    ],
    'public_notice' => [
      'archive' => ["public_notice", "page_1"],
      'landing' => ["public_notice", "page_2"],
    ],
    'status_displays' => [
      'homepage_status' => ["status_items", "motd"],
    ],
    'topic_landing_page' => [
      'page_1' => ["topic_landing_page", "page_1"],
    ],
    'transactions' => [
      'main_transactions' => ["transactions", "page_1"],
    ],
    'upcoming_events' => [
      'most_recent' => ["upcoming_events", "block_1"],
    ],
  ];

  /**
   * Array to map D7 loaded svg icons to new icon assets.
   *
   * @var array
   */
  protected static $svgMapping = [
    'public://img/program/logo/2016/07/experiential_icons_home_center.svg' => 'public://icons/experiential/tripple_decker.svg',
    'public://img/program/intro_images/2016/08/experiential_icons_home_sability.svg' => 'public://icons/experiential/neighborhood.svg',
    'public://img/post/thumbnails/2017/06/experiential_icons_house_0.svg' => 'public://icons/experiential/house_2.svg',
    'public://img/icons/transactions/2019/07/plastic_container.svg' => 'public://icons/experiential/plastic_container.svg',
    'public://img/icons/transactions/2019/07/hearing.svg' => 'public://icons/experiential/hearing.svg',
    'public://img/icons/transactions/2019/07/guide.svg' => 'public://icons/experiential/guide.svg',
    'public://img/icons/transactions/2019/07/gasmask.svg' => 'public://icons/experiential/gasmask.svg',
    'public://img/icons/transactions/2019/07/conversation.svg' => 'public://icons/experiential/conversation.svg',
    'public://img/icons/transactions/2019/07/construction.svg' => 'public://icons/experiential/construction.svg',
    'public://img/icons/transactions/2019/05/text.svg' => 'public://icons/experiential/text.svg',
    'public://img/icons/transactions/2019/05/neighborhood.svg' => 'public://icons/experiential/neighborhood.svg',
    'public://img/icons/transactions/2019/05/mayors_office_-_logo.svg' => 'public://icons/department/mayor_s_office_logo',
    'public://img/icons/transactions/2019/05/economic_development_-_icon.svg' => 'public://icons/department/economic_development_icon.svg',
    'public://img/icons/transactions/2019/05/download_2.svg' => 'public://icons/experiential/download_2.svg',
    'public://img/icons/transactions/2019/04/search_.svg' => 'public://icons/experiential/search.svg',
    'public://img/icons/transactions/2019/04/neighborhood.svg' => 'public://icons/experiential/neighborhood.svg',
    'public://img/icons/transactions/2019/04/money.svg' => 'public://icons/experiential/money.svg',
    'public://img/icons/transactions/2019/04/mayoral_letter.svg' => 'public://icons/experiential/mayoral_letter',
    'public://img/icons/transactions/2019/04/group_1.svg' => 'public://icons/experiential/group.svg',
    'public://img/icons/transactions/2019/04/bar_graph.svg' => 'public://icons/experiential/bar_graph.svg',
    'public://img/icons/transactions/2019/03/tripple-decker.svg' => 'public://icons/experiential/tripple_decker.svg',
    'public://img/icons/transactions/2019/03/trash_truck.svg' => 'public://icons/experiential/trash_truck.svg',
    'public://img/icons/transactions/2019/03/search_bar_1.svg' => 'public://icons/experiential/search_bar.svg',
    'public://img/icons/transactions/2019/03/recycle_cart.svg' => 'public://icons/experiential/recycle_cart.svg',
    'public://img/icons/transactions/2019/03/property_violations.svg' => 'public://icons/experiential/property_violations.svg',
    'public://img/icons/transactions/2019/03/paint_recycle_.svg' => 'public://icons//experiential/paint_recycle.svg',
    'public://img/icons/transactions/2019/03/money_1.svg' => 'public://icons/experiential/money.svg',
    'public://img/icons/transactions/2019/03/hazardous_waste.svg' => 'public://icons/experiential/hazardous_waste.svg',
    'public://img/icons/transactions/2019/03/electronics_recycle_.svg' => 'public://icons/experiential/electronics_recycle.svg',
    'public://img/icons/transactions/2019/03/download_recycle_app.svg' => 'public://icons/experiential/download_recycle_app.svg',
    'public://img/icons/transactions/2019/03/compost_sprout.svg' => 'public://icons/experiential/compost_sprout.svg',
    'public://img/icons/transactions/2019/03/clothes.svg' => 'public://icons/experiential/clothes.svg',
    'public://img/icons/transactions/2019/03/car_payment_.svg' => 'public://icons/experiential/car_paymentsvg',
    'public://img/icons/transactions/2019/03/can_recycling_1.svg' => 'public://icons/experiential/can_recycling.svg',
    'public://img/icons/transactions/2019/03/can_recycling_.svg' => 'public://icons/experiential/can_recycling.svg',
    'public://img/icons/transactions/2019/03/camera.svg' => 'public://icons/experiential/camera.svg',
    'public://img/icons/transactions/2019/02/neighborhoods.svg' => 'public://icons/experiential/neighborhoods.svg',
    'public://img/icons/transactions/2019/02/group.svg' => 'public://icons/experiential/group.svg',
    'public://img/icons/transactions/2019/02/document_4.svg' => 'public://icons/experiential/document.svg',
    'public://img/icons/transactions/2019/02/document_3.svg' => 'public://icons/experiential/document.svg',
    'public://img/icons/transactions/2019/02/car.svg' => 'public://icons/experiential/car.svg',
    'public://img/icons/transactions/2019/01/money_bills.svg' => 'public://icons/experiential/money_bills.svg',
    'public://img/icons/transactions/2019/01/meeting.svg' => 'public://icons/experiential/meeting.svg',
    'public://img/icons/transactions/2019/01/information.svg' => 'public://icons/experiential/document.svg',
    'public://img/icons/transactions/2019/01/housing.svg' => 'public://icons/experiential/building.svg',
    'public://img/icons/transactions/2019/01/handshake.svg' => 'public://icons/experiential/handshake.svg',
    'public://img/icons/transactions/2019/01/calender_1.svg' => 'public://icons/experiential/calender.svg',
    'public://img/icons/transactions/2019/01/calender_.svg' => 'public://icons/experiential/calender.svg',
    'public://img/icons/transactions/2019/01/bus_location.svg' => 'public://icons/experiential/bus_location.svg',
    'public://img/icons/transactions/2019/01/apple.svg' => 'public://icons/experiential/apple.svg',
    'public://img/icons/transactions/2019/01/adoption_2.svg' => 'public://icons/experiential/adoption.svg',
    'public://img/icons/transactions/2019/01/adoption.svg' => 'public://icons/experiential/adoption.svg',
    'public://img/icons/transactions/2018/12/search_forms_.svg' => 'public://icons/experiential/search_forms.svg',
    'public://img/icons/transactions/2018/12/money.svg' => 'public://icons/experiential/money.svg',
    'public://img/icons/transactions/2018/12/id_.svg' => 'public://icons/experiential/id.svg',
    'public://img/icons/transactions/2018/12/buildings.svg' => 'public://icons/experiential/buildings.svg',
    'public://img/icons/transactions/2018/12/building_permit.svg' => 'public://icons/experiential/building_permit.svg',
    'public://img/icons/transactions/2018/11/document_1.svg' => 'public://icons/experiential/document.svg',
    'public://img/icons/transactions/2018/11/crowd.svg' => 'public://icons/experiential/crowd.svg',
    'public://img/icons/transactions/2018/10/real_estate_taxes.svg' => 'public://icons/experiential/real_estate_taxes.svg',
    'public://img/icons/transactions/2018/10/paint_bucket.svg' => 'public://icons/experiential/paint_bucket.svg',
    'public://img/icons/transactions/2018/10/neighborhood.svg' => 'public://icons/experiential/neighborhood.svg',
    'public://img/icons/transactions/2018/10/group.svg' => 'public://icons/experiential/group.svg',
    'public://img/icons/transactions/2018/10/contours.svg' => 'public://icons/experiential/contours.svg',
    'public://img/icons/transactions/2018/10/compost_sprout.svg' => 'public://icons/experiential/compost_sprout.svg',
    'public://img/icons/transactions/2018/10/calender_.svg' => 'public://icons/experiential/calender.svg',
    'public://img/icons/transactions/2018/10/book.svg' => 'public://icons/experiential/book.svg',
    'public://img/icons/transactions/2018/09/water.svg' => 'public://icons/experiential/water.svg',
    'public://img/icons/transactions/2018/09/voting_location.svg' => 'public://icons/experiential/voting_location.svg',
    'public://img/icons/transactions/2018/08/tax_deferral_program_for_seniors.svg' => 'public://icons/experiential/55+_forms.svg',
    'public://img/icons/transactions/2018/08/sea_level_rise_plus_7_5_feet.svg' => 'public://icons/experiential/sea_level_7.5.svg',
    'public://img/icons/transactions/2018/08/report_0denergy_water_usage_.svg' => 'public://icons/experiential/water_and_energy_report.svg',
    'public://img/icons/transactions/2018/08/landmark_design_review_process.svg' => 'public://icons/experiential/landmark_design_review_process.svg',
    'public://img/icons/transactions/2018/08/global_warming_.svg' => 'public://icons/experiential/weather.svg',
    'public://img/icons/transactions/2018/08/flooding_1.svg' => 'public://icons/experiential/flooded_building.svg',
    'public://img/icons/transactions/2018/08/file_a_medical_registration_.svg' => 'public://icons/experiential/sbmitt_for_certificates.svg',
    'public://img/icons/transactions/2018/08/emergency_alerts.svg' => 'public://icons/experiential/alert_2.svg',
    'public://img/icons/transactions/2018/08/65_0.svg' => 'public://icons/',
    'public://img/icons/transactions/2018/07/start_a_resturant.svg' => 'public://icons/experiential/plate.svg',
    'public://img/icons/transactions/2018/07/food_assistance0a.svg' => 'public://icons/experiential/fruit_basket.svg',
    'public://img/icons/transactions/2018/07/boston_public_schools.svg' => 'public://icons/experiential/graduation_cap.svg',
    'public://img/icons/transactions/2018/07/books.svg' => 'public://icons/experiential/book.svg',
    'public://img/icons/transactions/2018/06/sun_black_and_white.svg' => 'public://icons/experiential/sun.svg',
    'public://img/icons/transactions/2018/06/graph.svg' => 'public://icons/experiential/chart.svg',
    'public://img/icons/transactions/2018/06/document_-_pdf.svg' => 'public://icons/experiential/document.svg',
    'public://img/icons/transactions/2018/06/connect_with_an_expert.svg' => 'public://icons/experiential/conversation_2.svg',
    'public://img/icons/transactions/2018/06/community_centers_0.svg' => 'public://icons/experiential/fmaily_house.svg',
    'public://img/icons/transactions/2018/06/community_center_pools.svg' => 'public://icons/experiential/pool.svg',
    'public://img/icons/transactions/2018/06/city_council_legislation.svg' => 'public://icons/experiential/city_council_legislation.svg',
    'public://img/icons/transactions/2018/06/bathroom.svg' => 'public://icons/experiential/bathroom.svg',
    'public://img/icons/transactions/2018/05/watch_boston_city_tv_.svg' => 'public://icons/experiential/video.svg',
    'public://img/icons/transactions/2018/05/pay_your_real_estate_taxes.svg' => 'public://icons/experiential/real_estate_taxes.svg',
    'public://img/icons/transactions/2018/05/money_1.svg' => 'public://icons/experiential/money.svg',
    'public://img/icons/transactions/2018/05/money.svg' => 'public://icons/experiential/money.svg',
    'public://img/icons/transactions/2018/05/information_for_taxpayers_1.svg' => 'public://icons/experiential/id.svg',
    'public://img/icons/transactions/2018/05/house.svg' => 'public://icons/experiential/house_2.svg',
    'public://img/icons/transactions/2018/05/download_.svg' => 'public://icons/experiential/download.svg',
    'public://img/icons/transactions/2018/05/creative_objects_0.svg' => 'public://icons/experiential/art_supplies.svg',
    'public://img/icons/transactions/2018/05/construction_vehicle_-_excavator.svg' => 'public://icons/experiential/excavator.svg',
    'public://img/icons/transactions/2018/05/construction_vehicle_-_bulldozer.svg' => 'public://icons/experiential/bulldozer.svg',
    'public://img/icons/transactions/2018/04/search_the_boston_food_truck_schedule.svg' => 'public://icons/experiential/calender.svg',
    'public://img/icons/transactions/2018/04/plus_sign.svg' => 'public://icons/experiential/plus_sign.svg',
    'public://img/icons/transactions/2018/04/explore_our_collections_.svg' => 'public://icons/experiential/search_forms.svg',
    'public://img/icons/transactions/2018/04/car.svg' => 'public://icons/experiential/car.svg',
    'public://img/icons/transactions/2018/04/alert.svg' => 'public://icons/experiential/alert.svg',
    'public://img/icons/transactions/2018/03/watch_boston_city_tv_.svg' => 'public://icons/experiential/video.svg',
    'public://img/icons/transactions/2018/03/start_a_resturant.svg' => 'public://icons/experiential/plate.svg',
    'public://img/icons/transactions/2018/03/search_1.svg' => 'public://icons/experiential/search.svg',
    'public://img/icons/transactions/2018/03/renew_a_permit.svg' => 'public://icons/experiential/parking_pass.svg',
    'public://img/icons/transactions/2018/03/online_registration_1.svg' => 'public://icons/experiential/web_persona.svg',
    'public://img/icons/transactions/2018/03/mbta_pass.svg' => 'public://icons/experiential/t_pass.svg',
    'public://img/icons/transactions/2018/03/locate_on_a_map_1.svg' => 'public://icons/experiential/maps.svg',
    'public://img/icons/transactions/2018/03/license_plate.svg' => 'public://icons/experiential/license_plate.svg',
    'public://img/icons/transactions/2018/03/how_to_file_for_a_residential_exemption.svg' => 'public://icons/experiential/residential_exemption.svg',
    'public://img/icons/transactions/2018/03/guest.svg' => 'public://icons/',
    'public://img/icons/transactions/2018/03/food_truck.svg' => 'public://icons/experiential/food_truck.svg',
    'public://img/icons/transactions/2018/03/flooding.svg' => 'public://icons/experiential/flooded_building.svg',
    'public://img/icons/transactions/2018/03/fire_truck_0.svg' => 'public://icons/experiential/fire_truck.svg',
    'public://img/icons/transactions/2018/03/fire_truck.svg' => 'public://icons/experiential/fire_truck.svg',
    'public://img/icons/transactions/2018/03/district_change.svg' => 'public://icons/experiential/district_change.svg',
    'public://img/icons/transactions/2018/03/connect_with_an_expert.svg' => 'public://icons/experiential/conversation_2.svg',
    'public://img/icons/transactions/2018/03/computer_.svg' => 'public://icons/experiential/web_persona.svg',
    'public://img/icons/transactions/2018/03/building_list.svg' => 'public://icons/experiential/building_list.svg',
    'public://img/icons/transactions/2018/03/ballot_or_ticket.svg' => 'public://icons/experiential/ballot-ticket.svg',
    'public://img/icons/transactions/2018/03/archaeological_dig_1.svg' => 'public://icons/experiential/dig_alert.svg',
    'public://img/icons/transactions/2018/02/how_to_file_for_a_residential_exemption.svg' => 'public://icons/experiential/residential_exemption.svg',
    'public://img/icons/transactions/2018/01/public-meetings.svg' => 'public://icons/experiential/meeting.svg',
    'public://img/icons/transactions/2018/01/experiential_icons_1_1_pay_your_real_estate_taxes.svg' => 'public://icons/experiential/real_estate_taxes.svg',
    'public://img/icons/transactions/2018/01/calendar.svg' => 'public://icons/experiential/calander.svg',
    'public://img/icons/transactions/2018/01/building-icon.svg' => 'public://icons/experiential/building_permit.svg',
    'public://img/icons/transactions/2017/12/non-emergency.svg' => 'public://icons/experiential/emergency_medical_kit.svg',
    'public://img/icons/transactions/2017/12/experiential_icons_monum_fellow_1.svg' => 'public://icons/experiential/du_monum_fellow.svg',
    'public://img/icons/transactions/2017/12/experiential_icons_help_during_the_winter_heating_season.svg' => 'public://icons/experiential/cold_temp.svg',
    'public://img/icons/transactions/2017/12/experiential_icons_city_hall.svg' => 'public://icons/experiential/city_hall.svg',
    'public://img/icons/transactions/2017/12/experiential_icons_1_3_pdf_doc_1.svg' => 'public://icons/experiential/document.svg',
    'public://img/icons/transactions/2017/12/emergency.svg' => 'public://icons/experiential/ambulance.svg',
    'public://img/icons/transactions/2017/11/rentals.svg' => 'public://icons/experiential/tripple_decker.svg',
    'public://img/icons/transactions/2017/11/experiential_icons_1_3_ticket_1.svg' => 'public://icons/experiential/ballot-ticket.svg',
    'public://img/icons/transactions/2017/10/small-business-center.svg' => 'public://icons/experiential/job_search.svg',
    'public://img/icons/transactions/2017/10/physician_0.svg' => 'public://icons/experiential/sbmitt_for_certificates.svg',
    'public://img/icons/transactions/2017/10/notices.svg' => 'public://icons/experiential/document.svg',
    'public://img/icons/transactions/2017/10/icon.svg' => 'public://icons/experiential/mayoral_letter.svg',
    'public://img/icons/transactions/2017/10/contracting-list.svg' => 'public://icons/experiential/handshake.svg',
    'public://img/icons/transactions/2017/10/contacting-city.svg' => 'public://icons/experiential/neighborhoods.svg',
    'public://img/icons/transactions/2017/10/contable.svg' => 'public://icons/experiential/ veteran_s_benefit_verification.svg',
    'public://img/icons/transactions/2017/09/supplier-portal.svg' => 'public://icons/experiential/web_persona.svg',
    'public://img/icons/transactions/2017/09/state-bid-contracts.svg' => 'public://icons/experiential/historic_building_permit.svg',
    'public://img/icons/transactions/2017/09/rentsmart-boston.svg' => 'public://icons/experiential/neighborhood.svg',
    'public://img/icons/transactions/2017/09/information-networks.svg' => 'public://icons/experiential/meeting.svg',
    'public://img/icons/transactions/2017/09/federal-grants.svg' => 'public://icons/experiential/document.svg',
    'public://img/icons/transactions/2017/09/experiential_icons_vote.svg' => 'public://icons/experiential/voting_ballot.svg',
    'public://img/icons/transactions/2017/09/experiential_icons_ticket.svg' => 'public://icons/experiential/ballot-ticket.svg',
    'public://img/icons/transactions/2017/09/experiential_icons_schools_1.svg' => 'public://icons/experiential/school.svg',
    'public://img/icons/transactions/2017/09/experiential_icons_map.svg' => 'public://icons/experiential/maps.svg',
    'public://img/icons/transactions/2017/09/experiential_icons_boston_public_schools.svg' => 'public://icons/experiential/graduation_cap.svg',
    'public://img/icons/transactions/2017/09/business-opportunities.svg' => 'public://icons/experiential/online_purchase.svg',
    'public://img/icons/transactions/2017/09/bids-and-contracts.svg' => 'public://icons/experiential/mayoral_proclamation.svg',
    'public://img/icons/transactions/2017/08/money.svg' => 'public://icons/experiential/money.svg',
    'public://img/icons/transactions/2017/08/experiential_icons_food_assistance-.svg' => 'public://icons/experiential/fruit_basket.svg',
    'public://img/icons/transactions/2017/08/experiential_icon-_recycle_electronics.svg' => 'public://icons/experiential/electronics_recycle.svg',
    'public://img/icons/transactions/2017/07/experiential_icons_tripple_decker.svg' => 'public://icons/experiential/tripple_decker.svg',
    'public://img/icons/transactions/2017/07/experiential_icons_rent_rights_0.svg' => 'public://icons/experiential/tennant_rights.svg',
    'public://img/icons/transactions/2017/07/experiential_icons_rent_rights.svg' => 'public://icons/experiential/tennant_rights.svg',
    'public://img/icons/transactions/2017/07/experiential_icons_important.svg' => 'public://icons/experiential/alert.svg',
    'public://img/icons/transactions/2017/07/experiential_icons_housing_questions.svg' => 'public://icons/experiential/housing_questions.svg',
    'public://img/icons/transactions/2017/07/experiential_icons_house_1.svg' => 'public://icons/experiential/house_2.svg',
    'public://img/icons/transactions/2017/07/experiential_icons_community_centers.svg' => 'public://icons/experiential/fmaily_house.svg',
    'public://img/icons/transactions/2017/07/experiential_icon_how_to_file_for_a_property_tax_abatement.svg' => 'public://icons/experiential/building_permit.svg',
    'public://img/icons/transactions/2017/07/experiential-icons_candidate_list_0.svg' => 'public://icons/experiential/mayoral_letter.svg',
    'public://img/icons/transactions/2017/06/icons-pills_0.svg' => 'public://icons/experiential/pills.svg',
    'public://img/icons/transactions/2017/06/icons-needle_0.svg' => 'public://icons/experiential/syringe.svg',
    'public://img/icons/transactions/2017/06/experiential_icons_housing_questions.svg' => 'public://icons/experiential/housing_questions.svg',
    'public://img/icons/transactions/2017/06/experiential_icons-43.svg' => 'public://icons/experiential/house.svg',
    'public://img/icons/transactions/2017/05/icons_tranportation.svg' => 'public://icons/experiential/transportation_locations.svg',
    'public://img/icons/transactions/2017/05/icons_sun.svg' => 'public://icons/experiential/SUN.svg',
    'public://img/icons/transactions/2017/05/icons_speach.svg' => 'public://icons/experiential/speach_bubble.svg',
    'public://img/icons/transactions/2017/05/icons_sound.svg' => 'public://icons/department/public_information_logo_black.svg',
    'public://img/icons/transactions/2017/05/icons_paper.svg' => 'public://icons/department/archives_and_records_icon_black.svg',
    'public://img/icons/transactions/2017/05/icons_housing.svg' => 'public://icons/department/home_center_logo_black.svg',
    'public://img/icons/transactions/2017/05/icons_heart.svg' => 'public://icons/experiential/heart.svg',
    'public://img/icons/transactions/2017/05/icons_health.svg' => 'public://icons/department/health_and_human_services_logo_black.svg',
    'public://img/icons/transactions/2017/05/experiential_icons_food_truck.svg' => 'public://icons/experiential/food_truck.svg',
    'public://img/icons/transactions/2017/05/experiential_icons_search.svg' => 'public://icons/experiential/search.svg',
    'public://img/icons/transactions/2017/04/experiential_icons_parks_and_playgrounds.svg' => 'public://icons/experiential/playground.svg',
    'public://img/icons/transactions/2017/04/experiential_icons-29.svg' => 'public://icons/experiential/calendar.svg',
    'public://img/icons/transactions/2017/03/vulnerability_assessment.svg' => 'public://icons/experiential/police_interrogation.svg',
    'public://img/icons/transactions/2017/03/trash_and_recycling_guide_0.svg' => 'public://icons/experiential/trash_truck.svg',
    'public://img/icons/transactions/2017/03/tips_for_using_career_center.svg' => 'public://icons/experiential/click.svg',
    'public://img/icons/transactions/2017/03/tips_for_recycling_in_boston.svg' => 'public://icons/experiential/recycle_cart.svg',
    'public://img/icons/transactions/2017/03/salary-info.svg' => 'public://icons/experiential/personal_tax.svg',
    'public://img/icons/transactions/2017/03/recycling_paint_and_motor_oil.svg' => 'public://icons/experiential/paint_supplies.svg',
    'public://img/icons/transactions/2017/03/outline_of_actions_and_roadmap.svg' => 'public://icons/experiential/maps.svg',
    'public://img/icons/transactions/2017/03/labor_service_jobs_0.svg' => 'public://icons/experiential/construction_tool.svg',
    'public://img/icons/transactions/2017/03/get_rid_of_household_hazardous_waste.svg' => 'public://icons/experiential/hazardous_waste.svg',
    'public://img/icons/transactions/2017/03/future_surveys.svg' => 'public://icons/experiential/surveilance.svg',
    'public://img/icons/transactions/2017/03/experiential_icons_ticket.svg' => 'public://icons/experiential/ballot-ticket.svg',
    'public://img/icons/transactions/2017/03/experiential_icons_domestic_partnership_1.svg' => 'public://icons/experiential/domestic_partnership.svg',
    'public://img/icons/transactions/2017/03/experiential_icons_board_of_trustees.svg' => 'public://icons/experiential/meeting.svg',
    'public://img/icons/transactions/2017/03/experiential_icons_bike_helmit.svg' => 'public://icons/experiential/helmet.svg',
    'public://img/icons/transactions/2017/03/experiential_icons_bike.svg' => 'public://icons/experiential/bike.svg',
    'public://img/icons/transactions/2017/03/executive_summary.svg' => 'public://icons/experiential/document.svg',
    'public://img/icons/transactions/2017/03/climate_projections.svg' => 'public://icons/experiential/chart.svg',
    'public://img/icons/transactions/2017/03/city_of_boston_scholarship_fund_0.svg' => 'public://icons/experiential/certificates.svg',
    'public://img/icons/transactions/2017/03/city_of_boston_scholarship_fund.svg' => 'public://icons/experiential/certificates.svg',
    'public://img/icons/transactions/2017/03/career-center.svg' => 'public://icons/experiential/web_persona.svg',
    'public://img/icons/transactions/2017/03/build_bps_0.svg' => 'public://icons/experiential/historic_building_permit.svg',
    'public://img/icons/transactions/2017/03/build_bps.svg' => 'public://icons/experiential/historic_building_permit.svg',
    'public://img/icons/transactions/2017/03/boston_basics.svg' => 'public://icons/experiential/birth_certifcate.svg',
    'public://img/icons/transactions/2017/03/benefits-available.svg' => 'public://icons/experiential/id.svg',
    'public://img/icons/transactions/2017/03/become_a_firefighter.svg' => 'public://icons/experiential/fire_truck.svg',
    'public://img/icons/transactions/2017/03/5000_questions.svg' => 'public://icons/experiential/search_forms.svg',
    'public://img/icons/transactions/2017/03/3700_ideas.svg' => 'public://icons/experiential/lightbulb.svg',
    'public://img/icons/transactions/2017/02/icons_bra.svg' => 'public://icons/department/planning_and_development_agency_logo.svg',
    'public://img/icons/transactions/2017/02/experiential_icons_find_your_boston_school_transcript.svg' => 'public://icons/experiential/certificate.svg',
    'public://img/icons/transactions/2017/02/experiential_icons_clean.svg' => 'public://icons/experiential/house_cleaning.svg',
    'public://img/icons/transactions/2017/01/experiential_icons_search_license.svg' => 'public://icons/experiential/certificate_search.svg',
    'public://img/icons/transactions/2017/01/experiential_icons_schools_1.svg' => 'public://icons/experiential/school.svg',
    'public://img/icons/transactions/2017/01/experiential_icons_parks_and_playgrounds.svg' => 'public://icons/experiential/playground.svg',
    'public://img/icons/transactions/2017/01/experiential_icons_important.svg' => 'public://icons/experiential/alert.svg',
    'public://img/icons/transactions/2017/01/experiential_icons_city_of_boston_owned_property.svg' => 'public://icons/experiential/city_of_boston_owned_property.svg',
    'public://img/icons/transactions/2017/01/experiential_icons_board_of_trustees.svg' => 'public://icons/experiential/meeting.svg',
    'public://img/icons/transactions/2017/01/boston_childrens_hospital_logo.svg.png' => 'public://icons/experiential/',
    'public://img/icons/transactions/2016/11/experiential_icons_what_to_do_with_your_trash_when_it_snows.svg' => 'public://icons/experiential/snow_trash.svg',
    'public://img/icons/transactions/2016/11/experiential_icons_what_to_do_with_your_car_when_it_snows.svg' => 'public://icons/experiential/snow_parking.svg',
    'public://img/icons/transactions/2016/11/experiential_icons_view_your_collection_schedule.svg' => 'public://icons/experiential/calender.svg',
    'public://img/icons/transactions/2016/11/experiential_icons_view_leaf_and_yard_waste_schedule.svg' => 'public://icons/experiential/leaf.svg',
    'public://img/icons/transactions/2016/11/experiential_icons_snow_removal_rules_in_boston.svg' => 'public://icons/experiential/shovel.svg',
    'public://img/icons/transactions/2016/11/experiential_icons_learn_about_recycling.svg' => 'public://icons/experiential/recycle_cart.svg',
    'public://img/icons/transactions/2016/11/experiential_icons_important_winter_phone_numbers.svg' => 'public://icons/experiential/snow_numbers.svg',
    'public://img/icons/transactions/2016/11/experiential_icons_help_during_the_winter_heating_season.svg' => 'public://icons/experiential/cold_temp.svg',
    'public://img/icons/transactions/2016/11/experiential_icons_get_rid_of_hazardous_waste.svg' => 'public://icons/experiential/hazardous_waste.svg',
    'public://img/icons/transactions/2016/11/experiential_icons_cold_weather_safety_tips.svg' => 'public://icons/experiential/snow_alert.svg',
    'public://img/icons/transactions/2016/10/experiential_icons_vote.svg' => 'public://icons/experiential/voting_ballot.svg',
    'public://img/icons/transactions/2016/10/experiential_icons_search.svg' => 'public://icons/experiential/search.svg',
    'public://img/icons/transactions/2016/10/experiential_icons_certificate.svg' => 'public://icons/experiential/certificates.svg',
    'public://img/icons/transactions/2016/10/experiential_icons_2_hurricane.svg' => 'public://icons/experiential/hurricane.avg',
    'public://img/icons/transactions/2016/09/experiential_icons_important.svg' => 'public://icons/experiential/alert.svg',
    'public://img/icons/transactions/2016/09/experiential_icons_base_ball.svg' => 'public://icons/experiential/baseball',
    'public://img/icons/transactions/2016/08/experiential_icons_tripple_decker.svg' => 'public://icons/experiential/tripple_decker.svg',
    'public://img/icons/transactions/2016/08/experiential_icons_boat.svg' => 'public://icons/experiential/boat.svg',
    'public://img/icons/transactions/2016/08/experiential_icons_bike.svg' => 'public://icons/experiential/bike.svg',
    'public://img/icons/transactions/2016/08/experiential_icons_2_rent_rights.svg' => 'public://icons/experiential/tennant_rights.svg',
    'public://img/icons/transactions/2016/08/experiential_icons_2_housing_questions.svg' => 'public://icons/experiential/housing_questions.svg',
    'public://img/icons/transactions/2016/08/experiential_icons-cal.svg' => 'public://icons/experiential/calender.svg',
    'public://img/icons/transactions/2016/08/5experiential_icons_find_a_park_3.svg' => 'public://icons/experiential/park_location.svg',
    'public://img/icons/transactions/2016/08/5experiential_icons_find_a_park_2.svg' => 'public://icons/experiential/park_location.svg',
    'public://img/icons/transactions/2016/08/5experiential_icons_find_a_park_1.svg' => 'public://icons/experiential/park_location.svg',
    'public://img/icons/transactions/2016/08/5experiential_icons_find_a_park.svg' => 'public://icons/experiential/park_location.svg',
    'public://img/icons/transactions/2016/08/3experiential_icons_mass_value_pass.svg' => 'public://icons/experiential/mass_value_pass.svg',
    'public://img/icons/transactions/2016/07/experiential_icons_tripple_decker.svg' => 'public://icons/experiential/tripple_decker.svg',
    'public://img/icons/transactions/2016/07/experiential_icons_repair.svg' => 'public://icons/experiential/construction_tool.svg',
    'public://img/icons/transactions/2016/07/experiential_icons_pay_your_real_estate_taxes-.svg' => 'public://icons/experiential/real_estate_taxes.svg',
    'public://img/icons/transactions/2016/07/experiential_icons_home_repairs.svg' => 'public://icons/experiential/repair_your_home.svg',
    'public://img/icons/transactions/2016/07/experiential_icons_2_311.svg' => 'public://icons/department/bos_311_black',
    'public://img/icons/status/trash-recycling.svg' => 'public://icons/circle/trash_and_recycling.svg',
    'public://img/icons/status/tow-lot.svg' => 'public://icons/circle/tow_lot.svg',
    'public://img/icons/status/street_sweeping.svg' => 'public://icons/circle/street_sweeping.svg',
    'public://img/icons/status/small-circle-icons_base_ball_0.svg' => 'public://icons/circle/base_ball.svg',
    'public://img/icons/status/parking-meters.svg' => 'public://icons/circle/parking_meters.svg',
    'public://img/icons/status/experiential_icons_fact_sheet.svg' => 'public://icons/experiential/report.svg',
    'public://img/icons/status/2018/03/small-circle-icons_t_one_circle_1.svg' => 'public://icons/circle/du_t_one_circle.svg',
    'public://img/icons/status/2017/10/small-circle-icons_building.svg' => 'public://icons/circle/building.svg',
    'public://img/icons/status/2017/10/slice_1.svg' => 'public://icons/circle/building.svg',
    'public://img/icons/status/2017/10/new-building-icon2-01.svg' => 'public://icons/circle/building.svg',
    'public://img/icons/status/2017/02/snow-parking.svg' => 'public://icons/circle/snow_parking.svg',
    'public://img/icons/status/2017/02/school.svg' => 'public://icons/circle/schools.svg',
    'public://img/icons/status/2016/10/state-offices.svg' => 'public://icons/circle/state_offices.svg',
    'public://img/icons/status/2016/10/libraries.svg' => 'public://icons/circel/libraries.svg',
    'public://img/icons/status/2016/10/community-centers.svg' => 'public://icons/circle/community_centers.svg',
    'public://img/icons/fyi/2017/01/small-circle-icons_snow_red.svg' => 'public://icons/',
    'public://img/icons/fyi/2016/08/small-circle-icons_alert.svg' => 'public://icons/',
    'public://img/icons/feature/small-circle-icons_yarn.svg' => 'public://icons/circle/yarn.svg',
    'public://img/icons/feature/small-circle-icons_track.svg' => 'public://icons/circle/track.svg',
    'public://img/icons/feature/small-circle-icons_tennis_court-.svg' => 'public://icons/circle/tennis_court.svg',
    'public://img/icons/feature/small-circle-icons_teen_center_.svg' => 'public://icons/circle/teen_center.svg',
    'public://img/icons/feature/small-circle-icons_stage.svg' => 'public://icons/circle/stage.svg',
    'public://img/icons/feature/small-circle-icons_sports_facility.svg' => 'public://icons/circle/sports_facility.svg',
    'public://img/icons/feature/small-circle-icons_socker.svg' => 'public://icons/circle/socker.svg',
    'public://img/icons/feature/small-circle-icons_sauna_-_steam_room.svg' => 'public://icons/circle/sauna_steam_room.svg',
    'public://img/icons/feature/small-circle-icons_rock_wall.svg' => 'public://icons/circle/rock_wall.svg',
    'public://img/icons/feature/small-circle-icons_public_art-.svg' => 'public://icons/circle/public_art.svg',
    'public://img/icons/feature/small-circle-icons_playground.svg' => 'public://icons/circle/playground.svg',
    'public://img/icons/feature/small-circle-icons_outdoor_pool.svg' => 'public://icons/circle/outdoor_pool.svg',
    'public://img/icons/feature/small-circle-icons_music_studio-_1.svg' => 'public://icons/circle/music_studio.svg',
    'public://img/icons/feature/small-circle-icons_music_studio-.svg' => 'public://icons/circle/music_studio.svg',
    'public://img/icons/feature/small-circle-icons_kitchen.svg' => 'public://icons/circle/kitchen.svg',
    'public://img/icons/feature/small-circle-icons_indoor_pool.svg' => 'public://icons/circle/indoor_pool.svg',
    'public://img/icons/feature/small-circle-icons_handball.svg' => 'public://icons/circle/handball.svg',
    'public://img/icons/feature/small-circle-icons_gym.svg' => 'public://icons/circle/gym.svg',
    'public://img/icons/feature/small-circle-icons_garden-.svg' => 'public://icons/circle/garden.svg',
    'public://img/icons/feature/small-circle-icons_football.svg' => 'public://icons/circle/foorball.svg',
    'public://img/icons/feature/small-circle-icons_dance_studio-.svg' => 'public://icons/circle/dance_studio.svg',
    'public://img/icons/feature/small-circle-icons_computer.svg' => 'public://icons/circle/computer.svg',
    'public://img/icons/feature/small-circle-icons_community_room-.svg' => 'public://icons/circle/community_room.svg',
    'public://img/icons/feature/small-circle-icons_boxing_room.svg' => 'public://icons/circle/bosing_room.svg',
    'public://img/icons/feature/small-circle-icons_beach.svg' => 'public://icons/circle/beach.svg',
    'public://img/icons/feature/small-circle-icons_batting_cage.svg' => 'public://icons/circle/batting_cage.svg',
    'public://img/icons/feature/small-circle-icons_basketball.svg' => 'public://icons/circle/basketball.svg',
    'public://img/icons/feature/small-circle-icons_base_ball_0.svg' => 'public://icons/circle/baseball.svg',
    'public://img/icons/feature/small-circle-icons_base_ball.svg' => 'public://icons/circle/baseball.svg',
    'public://img/icons/feature/small-circle-icons-69.svg' => 'public://icons/circle/artboard_69.svg',
    'public://img/icons/department/svg_labor_relations_.svg' => 'public://icons/department/labor_relations_logo.svg',
    'public://img/icons/department/svg_economic_development_.svg' => 'public://icons/department/economic_development_icon.svg',
    'public://img/icons/department/neighborhood_development.svg' => 'public://icons/department/neighborhood_development_logo.svg',
    'public://img/icons/department/icons_youth_empowerment.svg' => 'public://icons/department/youth_employment_and_engagement_logo.svg',
    'public://img/icons/department/icons_womens.svg' => 'public://icons/department/womens_advancement_logo.svg',
    'public://img/icons/department/icons_water_and_sewer.svg' => 'public://icons/department/water_and_sewer_commission_logo.svg',
    'public://img/icons/department/icons_veterans.svg' => 'public://icons/department/veterans_services_logo.svg',
    'public://img/icons/department/icons_treasury_0.svg' => 'public://icons/department/finance_logo.svg',
    'public://img/icons/department/icons_treasury.svg' => 'public://icons/department/finance_logo.svg',
    'public://img/icons/department/icons_transportation_transportation.svg' => 'public://icons/department/transportation_logo.svg',
    'public://img/icons/department/icons_tourism.svg' => 'public://icons/',
    'public://img/icons/department/icons_purchasing.svg' => 'public://icons/department/purchasing.svg',
    'public://img/icons/department/icons_public_works.svg' => 'public://icons/department/public_works_logo.svg',
    'public://img/icons/department/icons_public_safty_0.svg' => 'public://icons/department/public_health_commission_logo.svg',
    'public://img/icons/department/icons_public_safety.svg' => 'public://icons/department/public_safety_logo.svg',
    'public://img/icons/department/icons_prop_management.svg' => 'public://icons/department/propertt_and_construction_management_logo.svg',
    'public://img/icons/department/icons_police.svg' => 'public://icons/department/police.svg',
    'public://img/icons/department/icons_parks.svg' => 'public://icons/department/parks_and_recreation_logo.svg',
    'public://img/icons/department/icons_parking.svg' => 'public://icons/department/parking_clerk_logo.svg',
    'public://img/icons/department/icons_new_urban_mechanics.svg' => 'public://icons/department/new_urban_mechanics_logo.svg',
    'public://img/icons/department/icons_new_bostonians.svg' => 'public://icons/department/new_bostonians_logo.svg',
    'public://img/icons/department/icons_mayor.svg' => 'public://icons/department/mayor_s_office.svg',
    'public://img/icons/department/icons_library.svg' => 'public://icons/department/library_logo.svg',
    'public://img/icons/department/icons_hr.svg' => 'public://icons/department/human_resources_logo.svg',
    'public://img/icons/department/icons_housing.svg' => 'public://icons/department/housing_authority_logo.svg',
    'public://img/icons/department/icons_environment.svg' => 'public://icons/department/environment_logo.svg',
    'public://img/icons/department/icons_engagment__311_-_ons.svg' => 'public://icons/department/bos_311_icon.svg',
    'public://img/icons/department/icons_ems_0.svg' => 'public://icons/department/emergency_medical_services_logo.svg',
    'public://img/icons/department/icons_ems.svg' => 'public://icons/department/emergency_medical_services_logo.svg',
    'public://img/icons/department/icons_elections.svg' => 'public://icons/department/elections_logo.svg',
    'public://img/icons/department/icons_economic_development_0.svg' => 'public://icons/',
    'public://img/icons/department/icons_doit.svg' => 'public://icons/department/innovation_and_technology_logo.svg',
    'public://img/icons/department/icons_disabilities_disabilities.svg' => 'public://icons/department/disabilities__commission__icon.svg',
    'public://img/icons/department/icons_disabilities.svg' => 'public://icons/department/disabilities__commission__icon.svg',
    'public://img/icons/department/icons_consumer_affairs_0.svg' => 'public://icons/',
    'public://img/icons/department/icons_consumer_affairs.svg' => 'public://icons/',
    'public://img/icons/department/icons_city_council.svg' => 'public://icons/department/city_council_icon.svg',
    'public://img/icons/department/icons_city_clerk.svg' => 'public://icons/department/city_clerk_icon.svg',
    'public://img/icons/department/icons_cable.svg' => 'public://icons/department/broadband_and_cable_icon.svg',
    'public://img/icons/department/icons_budget_0.svg' => 'public://icons/department/budget_icon.svg',
    'public://img/icons/department/icons_bra.svg' => 'public://icons/department/icons_bra.svg',
    'public://img/icons/department/icons_blue_retirement.svg' => 'public://icons/department/retirement_logo.svg',
    'public://img/icons/department/icons_blue_neighborhood_services.svg' => 'public://icons/department/neighborhood_development_logo_1.svg',
    'public://img/icons/department/icons_bikes_bikes_0.svg' => 'public://icons/department/boston_bikes_icon.svg',
    'public://img/icons/department/icons_bikes_bikes.svg' => 'public://icons/department/boston_bikes_icon.svg',
    'public://img/icons/department/icons_auditing.svg' => 'public://icons/department/auditing_icon.svg',
    'public://img/icons/department/icons_assessing.svg' => 'public://icons/',
    'public://img/icons/department/icons_arts.svg' => 'public://icons/department/arts_and_culture_icon.svg',
    'public://img/icons/department/icons_animal_care.svg' => 'public://icons/department/animal_care_and_control_icon.svg',
    'public://img/icons/department/experiential_icons_isd.svg' => 'public://icons/department/inspectional_services_logo.svg',
    'public://img/icons/department/deapartment_icons_food.svg' => 'public://icons/department/food_access_logo.svg',
    'public://img/icons/department/2019/04/new_urban_mechanics_-_logo_3_1.svg' => 'public://icons/',
    'public://img/icons/department/2019/04/new_urban_mechanics_-_logo_2_0.svg' => 'public://icons/',
    'public://img/icons/department/2019/04/new_urban_mechanics_-_logo_1.svg' => 'public://icons/',
    'public://img/icons/department/2019/04/new_urban_mechanics_-_logo.svg' => 'public://icons/',
    'public://img/icons/department/2019/01/age-strong-final.svg' => 'public://icons/',
    'public://img/icons/department/2018/10/yee-icon.svg' => 'public://icons/department/mayor_s_youth_council.svg',
    'public://img/icons/department/2018/08/asset_332.svg' => 'public://icons/',
    'public://img/icons/department/2018/05/pm_logo.svg' => 'public://icons/',
    'public://img/icons/department/2017/11/returnign_citizens-05_0.svg' => 'public://icons/department/returning_citizens_logo.svg',
    'public://img/icons/department/2017/11/logos-05.svg' => 'public://icons/department/returning_citizens_logo.svg',
    'public://img/icons/department/2017/11/artboard_5.svg' => 'public://icons/department/returning_citizens_logo.svg',
    'public://img/icons/department/2017/10/procurement-icon.svg' => 'public://icons/department/procurement_logo.svg',
    'public://img/icons/department/2017/10/icons_mayors_youth_council_1.svg' => 'public://icons/department/mayor_s_youth_council.svg',
    'public://img/icons/department/2017/09/police.svg' => 'public://icons/department/police.svg',
    'public://img/icons/department/2017/06/recovery_services_i_con.svg' => 'public://icons/department/recovery_services_logo.svg',
    'public://img/icons/department/2017/02/public_records.svg' => 'public://icons/department/public_records_logo.svg',
    'public://img/icons/department/2017/01/icons_isd.svg' => 'public://icons/department/inspectional_services_logo.svg',
    'public://img/icons/department/2016/10/icons_doit.svg' => 'public://icons/department/innovation_and_technology_logo.svg',
    'public://img/icons/department/2016/10/icons_archives_treasury.svg' => 'public://icons/department/treasury_logo.svg',
    'public://img/icons/department/2016/10/icons_archives_tourism.svg' => 'public://icons/department/tourism_sports_and_entertainment_logo.svg',
    'public://img/icons/department/2016/10/icons_archives_taxcollection.svg' => 'public://icons/department/tax_collection_logo.svg',
    'public://img/icons/department/2016/10/icons_archives_smallbusiness.svg' => 'public://icons/department/small_business_development_logo.svg',
    'public://img/icons/department/2016/10/icons_archives_schools.svg' => 'public://icons/department/schools_logo.svg',
    'public://img/icons/department/2016/10/icons_archives_resilience.svg' => 'public://icons/department/resilience_and_racial_equity_logo.svg',
    'public://img/icons/department/2016/10/icons_archives_publicfacilities.svg' => 'public://icons/department/public_facilities_logo.svg',
    'public://img/icons/department/2016/10/icons_archives_procurement.svg' => 'public://icons/department/procurement_logo.svg',
    'public://img/icons/department/2016/10/department_icons_new_public_safty.svg' => 'public://icons/department/public_safety_logo.svg',
    'public://img/icons/department/2016/10/department_icons_new_fire_prevention.svg' => 'public://icons/department/fire_prevention_logo.svg',
    'public://img/icons/department/2016/10/department_icons_new_analytics.svg' => 'public://icons/department/analytics_team_icon.svg',
    'public://img/icons/department/2016/09/icons_jobs_policy.svg' => 'public://icons/department/workforce_development_logo.svg',
    'public://img/icons/department/2016/08/icons_youth_empowerment.svg' => 'public://icons/department/youth_employment_and_engagement_logo.svg',
    'public://img/icons/department/2016/08/icons_public_facilities.svg' => 'public://icons/department/small_business_enterprise_office.svg',
    'public://img/icons/department/2016/08/icons_landmarks.svg' => 'public://icons/department/landmarks_commission_logo.svg',
    'public://img/icons/department/2016/08/icons_labor_relations.svg' => 'public://icons/department/labor_relations_logo.svg',
    'public://img/icons/department/2016/08/icons_digital.svg' => 'public://icons/department/digital_team_icon.svg',
    'public://img/icons/department/2016/08/department_icons_emergency_management.svg' => 'public://icons/department/emergency_management__logo.svg',
    'public://img/icons/department/2016/07/assessing_logo.svg' => 'public://icons/department/assessing_icon.svg',
    'public://img/how_to/intro_images/default-hero-image_9.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_8.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_7.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_6.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_5.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_4.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_30.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_3.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_29.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_28.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_27.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_26.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_25.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_24.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_23.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_22.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_21.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_19.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_17.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_16.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_15.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_14.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_13.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_12.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_11.svg' => 'public://icons/',
    'public://img/how_to/intro_images/default-hero-image_1.svg' => 'public://icons/',
    'public://img/2019/w/warren_webpage_3.svg' => 'public://icons/',
    'public://img/2019/s/sr_3.svg' => 'public://icons/',
    'public://img/2019/o/our_partners_-_warren_street_4.svg' => 'public://icons/',
    'public://img/2019/o/our_partners_-_warren_street_3.svg' => 'public://icons/',
    'public://img/2019/o/our_partners_-_warren_street_2.svg' => 'public://icons/',
    'public://img/2019/6/6_0.svg' => 'public://icons/',
    'public://img/2019/5/5.svg' => 'public://icons/',
    'public://img/2019/2/2_0.svg' => 'public://icons/',
    'public://img/2019/1/14.svg' => 'public://icons/',
    'public://img/2019/1/1.svg' => 'public://icons/',
    'public://img/2018/e/experiential_icons_real_estate_taxes.svg' => 'public://icons/experiential/real_estate_taxes.svg',
    'public://img/2018/e/experiential_icons_census_1.svg' => 'public://icons/',
    'public://img/2018/d/department_icons_emergency_management_1_1.svg' => 'public://icons/department/emergency_management__logo.svg',
    'public://img/2018/d/department_icons_emergency_management_1_0.svg' => 'public://icons/department/emergency_management__logo.svg',
    'public://img/2018/a/asset_332_1_1.svg' => 'public://icons/',
    'public://img/2018/a/asset_332_1_0.svg' => 'public://icons/',
    'public://img/2018/a/asset_332_1.svg' => 'public://icons/',
    'public://img/2017/s/svg_hosuing_authority_.svg' => 'public://icons/',
    'public://img/2017/e/experiential_icons_important_6.svg' => 'public://icons/',
    'public://img/2017/e/experiential_icons_fire_operations.svg' => 'public://icons/',
    'public://img/2017/e/experiential_icon_how_to_file_for_a_property_tax_abatement_0.svg' => 'public://icons/',
    'public://img/2017/e/experiential_icon_how_to_file_for_a_property_tax_abatement.svg' => 'public://icons/',
    'public://img/2017/d/department_icons_emergency_management_0.svg' => 'public://icons/department/emergency_management__logo.svg',
    'public://img/2016/e/experientialicon_pay_or_view_your_bills_online.svg' => 'public://icons/experiential/online_purchase.svg',
    'public://img/2016/e/experiential_video_library.svg' => 'public://icons/experiential/video_search.svg',
    'public://img/2016/e/experiential_icons_wifi.svg' => 'public://icons/experiential/wifi.svg',
    'public://img/2016/e/experiential_icons_who_is_my_city_councilor_2.svg' => 'public://icons/experiential/city_council_question.svg',
    'public://img/2016/e/experiential_icons_vote_1.svg' => 'public://icons/experiential/voting_ballot.svg',
    'public://img/2016/e/experiential_icons_trash_downlaod_0.svg' => 'public://icons/experiential/download_recycle_app.svg',
    'public://img/2016/e/experiential_icons_trash_downlaod.svg' => 'public://icons/experiential/download_recycle_app.svg',
    'public://img/2016/e/experiential_icons_trash.svg' => 'public://icons/experiential/cart.svg',
    'public://img/2016/e/experiential_icons_tpass_1.svg' => 'public://icons/experiential/t_pass.svg',
    'public://img/2016/e/experiential_icons_tow_info.svg' => 'public://icons/experiential/tow_truck_updates.svg',
    'public://img/2016/e/experiential_icons_ticket_2.svg' => 'public://icons/experiential/ballot-ticket.svg',
    'public://img/2016/e/experiential_icons_ticket_1.svg' => 'public://icons/experiential/ballot-ticket.svg',
    'public://img/2016/e/experiential_icons_thermostat.svg' => 'public://icons/experiential/temperature.svg',
    'public://img/2016/e/experiential_icons_testify.svg' => 'public://icons/experiential/testify_at_a_city_council.svg',
    'public://img/2016/e/experiential_icons_temporary_public_art.svg' => 'public://icons/experiential/paint_supplies.svg',
    'public://img/2016/e/experiential_icons_snow_plow_0.svg' => 'public://icons/experiential/plows.svg',
    'public://img/2016/e/experiential_icons_smoke_detector_1.svg' => 'public://icons/experiential/fire_alarm.svg',
    'public://img/2016/e/experiential_icons_smoke_detector.svg' => 'public://icons/experiential/fire_alarm.svg',
    'public://img/2016/e/experiential_icons_search_license_1.svg' => 'public://icons/experiential/certificate_search.svg',
    'public://img/2016/e/experiential_icons_search_1.svg' => 'public://icons/experiential/search.svg',
    'public://img/2016/e/experiential_icons_search_0.svg' => 'public://icons/experiential/search.svg',
    'public://img/2016/e/experiential_icons_search.svg' => 'public://icons/experiential/search.svg',
    'public://img/2016/e/experiential_icons_reserve_parking_1.svg' => 'public://icons/experiential/no_parking_reserved_for_moving.svg',
    'public://img/2016/e/experiential_icons_repair.svg' => 'public://icons/experiential/construction_tool.svg',
    'public://img/2016/e/experiential_icons_renew_an_accesible_parking_0.svg' => 'public://icons/experiential/renew_accessible_parking_spot.svg',
    'public://img/2016/e/experiential_icons_poll_worker.svg' => 'public://icons/experiential/vote.svg',
    'public://img/2016/e/experiential_icons_phone_0.svg' => 'public://icons/experiential/phone.svg',
    'public://img/2016/e/experiential_icons_phone.svg' => 'public://icons/experiential/phone.svg',
    'public://img/2016/e/experiential_icons_pdf_doc.svg' => 'public://icons/experiential/document.svg',
    'public://img/2016/e/experiential_icons_pay_your_real_estate_taxes-_0.svg' => 'public://icons/experiential/real_estate_taxes.svg',
    'public://img/2016/e/experiential_icons_pay_your_real_estate_taxes-.svg' => 'public://icons/experiential/real_estate_taxes.svg',
    'public://img/2016/e/experiential_icons_parking_pass_3.svg' => 'public://icons/experiential/parking_pass.svg',
    'public://img/2016/e/experiential_icons_parking_pass_1.svg' => 'public://icons/experiential/parking_pass.svg',
    'public://img/2016/e/experiential_icons_parking_pass_0.svg' => 'public://icons/experiential/parking_pass.svg',
    'public://img/2016/e/experiential_icons_parking_pass.svg' => 'public://icons/experiential/parking_pass.svg',
    'public://img/2016/e/experiential_icons_online_reg.svg' => 'public://icons/experiential/web_persona.svg',
    'public://img/2016/e/experiential_icons_online_payments_0.svg' => 'public://icons/experiential/online_purchase.svg',
    'public://img/2016/e/experiential_icons_noparking_moving.svg' => 'public://icons/experiential/no_parking_reserved_for_moving.svg',
    'public://img/2016/e/experiential_icons_noparking_filming.svg' => 'public://icons/experiential/no_parking_reserved_for_filming.svg',
    'public://img/2016/e/experiential_icons_no_ticket.svg' => 'public://icons/experiential/no_ticket.svg',
    'public://img/2016/e/experiential_icons_neighborhoods_0.svg' => 'public://icons/experiential/du_neighborhoods_info.svg',
    'public://img/2016/e/experiential_icons_neighborhoods.svg' => 'public://icons/experiential/neighborhoods.svg',
    'public://img/2016/e/experiential_icons_meet_archaeologist.svg' => 'public://icons/experiential/schedule.svg',
    'public://img/2016/e/experiential_icons_medical_registration__1.svg' => 'public://icons/experiential/sbmitt_for_certificates.svg',
    'public://img/2016/e/experiential_icons_medical_registration__0.svg' => 'public://icons/experiential/sbmitt_for_certificates.svg',
    'public://img/2016/e/experiential_icons_medical_registration_.svg' => 'public://icons/experiential/sbmitt_for_certificates.svg',
    'public://img/2016/e/experiential_icons_mayor_proclamation_.svg' => 'public://icons/experiential/mayoral_proclamation.svg',
    'public://img/2016/e/experiential_icons_mayor_greeting_letter_.svg' => 'public://icons/experiential/mayoral_letter.svg',
    'public://img/2016/e/experiential_icons_marriage_certificate.svg' => 'public://icons/experiential/marriage_application.svg',
    'public://img/2016/e/experiential_icons_map_1.svg' => 'public://icons/experiential/maps.svg',
    'public://img/2016/e/experiential_icons_map_0.svg' => 'public://icons/experiential/maps.svg',
    'public://img/2016/e/experiential_icons_map.svg' => 'public://icons/experiential/maps.svg',
    'public://img/2016/e/experiential_icons_mail_6.svg' => 'public://icons/experiential/envelope.svg',
    'public://img/2016/e/experiential_icons_mail_4.svg' => 'public://icons/experiential/envelope.svg',
    'public://img/2016/e/experiential_icons_mail_3.svg' => 'public://icons/experiential/envelope.svg',
    'public://img/2016/e/experiential_icons_mail_2.svg' => 'public://icons/experiential/envelope.svg',
    'public://img/2016/e/experiential_icons_mail_1.svg' => 'public://icons/experiential/envelope.svg',
    'public://img/2016/e/experiential_icons_mail_0.svg' => 'public://icons/experiential/envelope.svg',
    'public://img/2016/e/experiential_icons_important_3.svg' => 'public://icons/experiential/alert.svg',
    'public://img/2016/e/experiential_icons_important_10.svg' => 'public://icons/experiential/alert.svg',
    'public://img/2016/e/experiential_icons_important_0.svg' => 'public://icons/experiential/alert.svg',
    'public://img/2016/e/experiential_icons_important.svg' => 'public://icons/experiential/alert.svg',
    'public://img/2016/e/experiential_icons_how_to_watch_a_city_council_hearing_0.svg' => 'public://icons/experiential/watch_city_council.svg',
    'public://img/2016/e/experiential_icons_house_7.svg' => 'public://icons/experiential/house_2.svg',
    'public://img/2016/e/experiential_icons_house_6.svg' => 'public://icons/experiential/house_2.svg',
    'public://img/2016/e/experiential_icons_house_5.svg' => 'public://icons/experiential/house_2.svg',
    'public://img/2016/e/experiential_icons_house_4.svg' => 'public://icons/experiential/house_2.svg',
    'public://img/2016/e/experiential_icons_house_3.svg' => 'public://icons/experiential/house_2.svg',
    'public://img/2016/e/experiential_icons_house_0.svg' => 'public://icons/experiential/house_2.svg',
    'public://img/2016/e/experiential_icons_house.svg' => 'public://icons/experiential/house_2.svg',
    'public://img/2016/e/experiential_icons_game_of_the_week.svg' => 'public://icons/experiential/football.svg',
    'public://img/2016/e/experiential_icons_foreclosure.svg' => 'public://icons/experiential/foreclosure.svg',
    'public://img/2016/e/experiential_icons_food_truck.svg' => 'public://icons/experiential/food_truck.svg',
    'public://img/2016/e/experiential_icons_food_assistance-.svg' => 'public://icons/experiential/fruit_basket.svg',
    'public://img/2016/e/experiential_icons_find_a_career_center.svg' => 'public://icons/experiential/job_search.svg',
    'public://img/2016/e/experiential_icons_find_a_aprk.svg' => 'public://icons/experiential/park_location.svg',
    'public://img/2016/e/experiential_icons_feed_back.svg' => 'public://icons/experiential/report.svg',
    'public://img/2016/e/experiential_icons_emergency_kit.svg' => 'public://icons/experiential/emergency_kit.svg',
    'public://img/2016/e/experiential_icons_download_0.svg' => 'public://icons/experiential/cell_phone_download.svg',
    'public://img/2016/e/experiential_icons_download2.svg' => 'public://icons/experiential/download.svg',
    'public://img/2016/e/experiential_icons_download.svg' => 'public://icons/experiential/cell_phone_download.svg',
    'public://img/2016/e/experiential_icons_domestic_partnership.svg' => 'public://icons/experiential/domestic_partnership.svg',
    'public://img/2016/e/experiential_icons_digital_print.svg' => 'public://icons/experiential/digital_print.svg',
    'public://img/2016/e/experiential_icons_cpr.svg' => 'public://icons/experiential/cpr.svg',
    'public://img/2016/e/experiential_icons_city_tv_.svg' => 'public://icons/experiential/video.svg',
    'public://img/2016/e/experiential_icons_city_councle_2.svg' => 'public://icons/experiential/city_council.svg',
    'public://img/2016/e/experiential_icons_city_council_enacts_laws_1.svg' => 'public://icons/experiential/city_council_legislation.svg',
    'public://img/2016/e/experiential_icons_city_council_enacts_laws_0.svg' => 'public://icons/experiential/city_council_legislation.svg',
    'public://img/2016/e/experiential_icons_city_council_enacts_laws.svg' => 'public://icons/experiential/city_council_legislation.svg',
    'public://img/2016/e/experiential_icons_certificate_1.svg' => 'public://icons/experiential/certificates.svg',
    'public://img/2016/e/experiential_icons_certificate_0.svg' => 'public://icons/experiential/certificates.svg',
    'public://img/2016/e/experiential_icons_certificate.svg' => 'public://icons/experiential/certificates.svg',
    'public://img/2016/e/experiential_icons_census_0.svg' => 'public://icons/experiential/family.svg',
    'public://img/2016/e/experiential_icons_car_1.svg' => 'public://icons/experiential/car.svg',
    'public://img/2016/e/experiential_icons_car_0.svg' => 'public://icons/experiential/car.svg',
    'public://img/2016/e/experiential_icons_car.svg' => 'public://icons/experiential/car.svg',
    'public://img/2016/e/experiential_icons_birth_cert.svg' => 'public://icons/experiential/birth_certifcate.svg',
    'public://img/2016/e/experiential_icons_bike_0.svg' => 'public://icons/experiential/bike.svg',
    'public://img/2016/e/experiential_icons_bike.svg' => 'public://icons/experiential/bike.svg',
    'public://img/2016/e/experiential_icons_archaeological_dig_2.svg' => 'public://icons/experiential/archaeological_dig_questions.svg',
    'public://img/2016/e/experiential_icons_archaeological_dig_1.svg' => 'public://icons/experiential/archaeological_dig_questions.svg',
    'public://img/2016/e/experiential_icons_archaeological_dig.svg' => 'public://icons/experiential/dig_alert.svg',
    'public://img/2016/e/experiential_icons_accesible_parking_0.svg' => 'public://icons/experiential/accessible_parking_spot.svg',
    'public://img/2016/e/experiential_icons_2_ems_detial.svg' => 'public://icons/experiential/ambulance.svg',
    'public://img/2016/e/experiential_icons_2-16.svg' => 'public://icons/experiential/ems.svg',
    'public://img/2016/e/experiential_icons-cal_1.svg' => 'public://icons/experiential/calendars.svg',
    'public://img/2016/e/experiential_icons-cal_0.svg' => 'public://icons/experiential/calendars.svg',
    'public://img/2016/e/experiential_icons-31.svg' => 'public://icons/experiential/weddingrings.svg',
    'public://img/2016/e/experiential_icons-22.svg' => 'public://icons/',
    'public://img/2016/d/deapartment_icons_emergency_management.svg' => 'public://icons/department/emergency_management__logo.svg',
    'public://img/2016/b/budget_infographic-01.svg' => 'public://icons/',
    'public://img/2016/5/5icons_home_repairs.svg' => 'public://icons/',
    'public://img/2016/3/3icons_get_a_home_energy_assessment.svg' => 'public://icons/',
    'public://img/2016/2/2000px-seal_of_massachusetts_variant.svg.png' => 'public://icons/',
    'public://icons/experiential/search.svg' => 'public://icons/',
    'public://icons/experiential/building_permit.svg' => 'public://icons/',
    'public://embed/w/warren_webpage_3.svg' => 'public://icons/',
    'public://embed/o/our_partners_-_warren_street_1.svg' => 'public://icons/',
    'public://embed/e/experiential_icons_who_is_my_city_councilor-.svg' => 'public://icons/',
    'public://embed/6/6_1.svg' => 'public://icons/',
    'public://embed/3/34.svg' => 'public://icons/',
    'public://embed/3/3.svg' => 'public://icons/',
    'public://embed/1/18.svg' => 'public://icons/',
    'public://embed/1/14_0.svg' => 'public://icons/',
    'public://embed/1/11.svg' => 'public://icons/',
    'public://img/icons/transactions/2019/03/tripple_decker_icon.png' => 'public://icons/experiential/tripple_decker.svg',
    'public://img/icons/transactions/2019/05/tripple_decker_-_at_home_renters.png' => 'public://icons/experiential/tripple_decker.svg',
    'public://img/icons/transactions/2019/06/tripple_decker_.png' => 'public://icons/experiential/tripple_decker.svg',
    'public://img/icons/transactions/2019/06/tripple_decker__0.png' => 'public://icons/experiential/tripple_decker.svg',
  ];

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
	          INNER JOIN file_usage u ON f.fid = u.fid
          WHERE (f.uri LIKE '%.svg' 
            OR f.uri LIKE '%.png')
            AND f.status = 1;")->fetchAll();

    if (!empty($svgs)) {
      foreach ($svgs as $svg) {
        $file = File::load($svg->fid);
        if (!empty($file) && NULL != ($new_uri = self::$svgMapping[$svg->uri]) && strpos($new_uri, ".svg")) {
          $new_filename = explode("/", $new_uri);
          $new_filename = array_pop($new_filename);
          $file->setFileUri($new_uri);
          $file->setFilename($new_filename);
          $file->save();
          $cnt++;
          // Try to find this file_id in the media table.
          if (NULL == ($mid = \Drupal::entityQuery("media")->condition("image.target_id", $svg->fid, "=")->execute())) {
            // Not there, so create a new one.
            $media = Media::create(["bundle" => "image"]);
          }
          else {
            $mid = reset($mid);
            $media = Media::load($mid);
          }
          // Update the names etc for the media entity.
          $media->setName($new_filename);
          $media->set("thumbnail", [
            "alt" => $new_filename,
            "target_id" => $svg->fid,
          ]);
          $media->set("image", [
            "alt" => $new_filename,
            "target_id" => $svg->fid,
          ]);
          $media->save();
          $new_uri = NULL;
        }
      }
      printf("[success] Updated %d media entries.\n", $cnt);

    }
    else {
      printf("[warning] no svgs found !!.\n");
    }
    printf("\n");
  }

  /**
   * Manually create the media entity for the map background image.
   */
  public static function fixMap() {
    printf("[action] Will ensure map default image is loaded propoerly.\n");
    // Copy map module icons into expected location.
    _bos_core_install_icons("bos_map");
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
    printf("[action] Will manually copy status_item messages because migration can't handle them.\n");

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

          $entity = \Drupal::entityTypeManager()->getStorage("paragraph");
          if ($source_table == "field_revision_field_date") {
            $entity = $entity->loadRevision($source_row->revision_id);
          }
          else {
            $entity = $entity->load($source_row->entity_id);
          }
          if (!empty($entity)) {
            $entity->field_enabled = $enabled;
            $entity->field_recurrence->value = $start_date;
            $entity->field_recurrence->end_value = $end_date;
            $entity->field_recurrence->rrule = $rules;
            $entity->field_recurrence->infinite = $infinite;
            $entity->save();
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
        $entity = \Drupal::entityTypeManager()->getStorage("node")
          ->load($node->id());
        if (!empty($entity) && !isset($entity->field_enabled->value)) {
          $entity->field_weight = 0;
          $entity->field_enabled = 1;
          $entity->save();
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
      ]
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

}
