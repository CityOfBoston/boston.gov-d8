###CityOfBoston - DoIT - Digital Team
####Webform Component Notes
This module contains a single paragraph component called cob_webform_component.

The component creates webform paragraph which has some standardized webform theming
using twig templates. There is a css extension file for non-patterns defined styling,
and a css overrides file to override css supplied from patterns. There is also a
javascript file included which can be used to provide more cusomised UX/UI.

The component is best used on the node_landing_page content type, which has a layout
that is most likely to suit a webform application needs. The webform can be positioned
between other city components such as cards, drawers, grids and text components to
produce the desired page.

Additional twig templates can be added by the bos_webform_theme hook and using preprocess
and suggestions hooks.  Some basic examples are provided to show how the submit/update/delete
buttons can be themed using twig and css.
