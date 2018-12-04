
#!/bin/bash

lando drush config-delete core.base_field_override.paragraph.cabinet.created
lando drush config-delete core.base_field_override.paragraph.cabinet.moderation_state
lando drush config-delete core.base_field_override.paragraph.cabinet.status
lando drush config-delete core.base_field_override.paragraph.cabinet.uid
lando drush config-delete core.base_field_override.taxonomy_term.contact.changed
lando drush config-delete core.base_field_override.taxonomy_term.contact.description
lando drush config-delete core.base_field_override.taxonomy_term.contact.metatag
lando drush config-delete core.base_field_override.taxonomy_term.contact.name
lando drush config-delete core.base_field_override.taxonomy_term.contact.path
lando drush config-delete core.entity_form_display.paragraph.cabinet.default
lando drush config-delete core.entity_form_display.taxonomy_term.contact.default
lando drush config-delete core.entity_form_display.paragraph.cabinet.default
lando drush config-delete core.entity_view_display.paragraph.cabinet.listing
lando drush config-delete core.entity_view_display.paragraph.cabinet.paragraphs_editor_preview
lando drush config-delete core.entity_view_display.paragraph.cabinet.separated_title
lando drush config-delete core.entity_view_mode.paragraph.listing
lando drush config-delete core.entity_view_mode.paragraph.separated_title
lando drush config-delete field.field.paragraph.cabinet.field_contacts
lando drush config-delete field.field.paragraph.cabinet.field_person
lando drush config-delete field.field.paragraph.cabinet.field_short_title
lando drush config-delete field.field.paragraph.cabinet.field_title
lando drush config-delete field.field.taxonomy_term.contact.field_department_legacy_id
lando drush config-delete field.field.taxonomy_term.contact.field_department_profile
lando drush config-delete field.storage.paragraph.field_contacts
lando drush config-delete field.storage.paragraph.field_description
lando drush config-delete field.storage.paragraph.field_person
lando drush config-delete field.storage.taxonomy_term.field_department_legacy_id
lando drush config-delete field.storage.taxonomy_term.field_department_profile
lando drush config-delete language.content_settings.paragraph.cabinet
lando drush config-delete language.content_settings.taxonomy_term.contact
lando drush config-delete node.type.department_profile
lando drush config-delete node.type.person_profile
lando drush config-delete paragraphs.paragraphs_type.cabinet
lando drush config-delete taxonomy.vocabulary.contact
lando drush config-delete core.entity_view_display.paragraph.cabinet.default