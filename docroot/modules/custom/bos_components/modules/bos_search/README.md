# AI-Enabled Search
This component has several elements;
- a configuration form,
- a button,
- a modal form, and
- an integration framework to connect to AI services/models.

## Button
The button is an html element to allow the user to launch the modal form.
The button is located somewhere on the page using either
- a **snippet** (so it can be added to the top menu), or
- a **paragraph** (so it can be added as a page or sidebar component).

The button is permission-aware so the user-group who can access the button can be controlled.

The button has some configuration, so that the AIEngine preset to be used can be selected when embedding the button.

Two buttons on the same page using different presets allows direct comparision of AI models and settings.

## Search Form
The modal Search Form is where the search is performed.

The Search Form is an AJAX driven form which is deployed as a block, and should be added to the bottom of pages where AI-enabled search is desired.

The Search Form
- can only be launched from the button,
- provides a conversation-based search experience,
- remembers previous searches performed by the user and "picks-up" and continues the conversation from earlier in the session, and
- is AI Model agnostic.

## Configuration Form
The Configuration Form allows the administrator to set various behiavors for the Search Form.

The Configuration Form also configures **_Presets_** which are "designers" for the various AI Model integrations.
A button must define a single preset -and it passes the preset-info to the Search Form.

## Intergration with AI Models
Standard interfaces are implemented. Custom Drupal AI Model modules which implement these interfaces can send and receive instructions and results with the Search Form (e.g. _bos_google_cloud::GcSearch_).
This way we can add as many AI Models as we desire hopefully without the need to alter the Search Form, or the component Configuration Form.
