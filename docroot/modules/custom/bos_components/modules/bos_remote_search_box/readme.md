#CityOfBoston - DoIT - Digital Team
##Remote Search Box Component Notes

[Requirements Doc](https://docs.google.com/document/d/1hLPKfbEyrarL8gDzik_xoeL4a2luui43m1KI_VwuNoE/edit#heading=h.c4xtxi2oiqfj)

###Instructions

When adding a **new remote search function** targeting the bos_sql component and the dbconnector endpoint on AWS,
you do this by extending the existing bos_remote_search_box component in the custom modules folder.
(found here: `docroot/modules/custom/bos_components/modules/bos_remote_search_box`).


**Tip: Read all these instructions and plan before you start. Some settings you make early in this process dictate
or limit settings you can make later on.**


####Groundwork
Add your new function to the Remote Search Control field in the bos_remote_search_box component.
1. Navigate to:
`admin/structure/paragraphs_type/remote_search_box/fields/paragraph.remote_search_box.field_remote_search_control/storage`

2. In the allowed values list field, add a new line with your new function name using the key|label syntax.
Note: The string you use for the **key** is important (the label is less important but should be self-explanatory).
The string you use for the **key** will become the new controls **FunctionName**, it must be camel-case with no spaces
will **exactly match** (including case) the class name you will create later.  Important to get this right!
3. Save the Field setting.

**Explanation**: _This will create an option for content authors to add you new search function onto their
page/pages. When your function is added to a page (by a content author), then the form you will define in
the next steps will be shown to the user, and the search functionality you define in later steps will be enabled._

####Search Form
Add a new custom class for your new search to the bos_remote_search_box component by copy-pasting and renaming the
`src/Forms/template.php class` file.  This template class extends the custom class `RemoteSearchBoxFormBase` and
implements the interface`RemoteSearchBoxFormInterface`.

* ***Class RemoteSearchBoxFormBase***: This class handles standard tasks related to templating the search form (incl registering AJAX callbacks) and generally
setting up the search submission workflow and process.  There are also a number of helper and utility functions to
ensure that form search elements and search results are added correctly to the form.

* ***Interface RemoteSearchBoxFormInterface***:
This interface class defines a couple of functions which the `RemoteSearchBoxFormBase` will call dusing the process. These
functions are designed to help you organize where the main parts of customization for your new class will occur.

* ***Class RemoteSearchBoxHelper***:
This PHP class contains a number of utility/helper functions to ensure that elements of the form are constructed
properly. This class contains basic search form elements and will be extended over time.  You are encouraged to update
this class with new search elements that you construct, so that they may be re-used again by other developers that follow.
*You do not have to use the helper class, but are encouraged to do so because Digial commit to maintain it as part of
the bos_remote_search_box module. As and when the search form template format changes, any manual insertions you make
into the form object outside of the helper function are unsupported, and may break.*

**Important:** You **must** rename the file with the FunctionName (plus a .php extension) that you used as the
field list key in the groundwork step 2 above.

* Change the class name (around line 20) to match the FunctionName used in Step 1 above.
* Change the getFormId() function return value to be the FunctionName in lowercase, broken into words with
   underscores separating. This is the FormId for your function and will be needed later.
* Set the form_name to be a sensible name for this form. You can use spaces in this name.

   **Tip:** It can be the same as the FormId if you wish.

##### Customize the form build
Customize your new class’s buildForm function to build the search form that you require.
To do this you must create a valid Drupal Form object.

A Drupal Form object is a themed element and if properly built is ajax enabled and needs nearly no css or other
theme/style changes. The parent class extended by the template file (class `RemoteSearchBoxFormBase`) handles
much of this for you and integrates with the validation and submission handlers, and the site theming.  There is also
a helper class `RemoteSearchBoxHelper` which contains a number of utility/helper functions to ensure that elements
of the form are constructed properly.

1. The first step is to call the parent class’s `buildForm()` function.
   This will create a base form object which integrates with both Drupal and the COB patterns library.

   **Note**: This is a stub, and has no actual input elements.
2. Next add in the main search element. For each form thise is likely to be a single search box. Calling the
helpers' `buildAddressSearch($form)` will add an address search which works with the validate & submit event
handlers and site theming.
Other `buildXxxxSearch($form)` functions will be built and added to the helper class as we determine needs.

3. Finally, add in any additional (usually optional) search elements.  This can be done either by manually
   creating a drupal form element and passing it into the helpers' `addManualCriteria()` function, or by calling
   another helper function in the class. These functions are named `criteriaXxx($form, $form_element)` and are
   designed to work with the validate & submit event handlers and site theming.
   More will be built and added to the helper class as we determine needs.

   **Tip:** using the `#weight` parameter gives very good control over the ordering of elements in your form.
4. Once the $form variable is populated using the parent class functions, you can use code to tweak, typically adding or
   removing class attributes and possibly display text.

- **Form Build Resources:**
   * Checkout the class:
   `docroot/modules/custom/bos_components/modules/bos_remote_search_box/src/Form/StreetSweepingLookup.php`
   * Drupal help for forms:
   `https://www.drupal.org/docs/drupal-apis/form-api/form-render-elements`, and
   `https://api.drupal.org/api/drupal/elements`

##### Customize form validation
Customize your new class’s validateForm function to validate the users input.
If you have only added form components from the `RemoteSearchBoxFormBase` class then you likely won’t have to
alter this function.

**Note**: Drupal core services will validate that required fields are provided and will raise alerts if not.

The `buildForm()` function in the `RemoteSearchBoxFormBase` class registered an AJAX callback for overall form validation
that is conducted when the form is submitted. (TODO: some autocomplete searchboxes may have AJAX which runs each time a
character is added to the searchbox). Most validation which you will need is done laredy, but should you need to add or
edit validation, the function `validateSearch()` in your custom class is called **after** all other validations are
completed, and you can make changes before the validation is completed and the AJAX response returned.

* For ease of use, the form input fields submitted values have been flattened into an array found in the class
variable `$this->submitted_form` and you can test those values and set the `$form_state->setErrorByName()` to
flag if the input data is not validated - check the documentation resources below.

-  **Form Validation Resources**:
  * `https://www.drupal.org/docs/drupal-apis/form-api/introduction-to-form-api#fapi-validation` and
  * `https://drupalize.me/tutorial/validate-form-form-controller`

##### Customize form submission
Update the class’s submitForm function to post the search request to the remote environment.
This is recommended to be completed using the COB Custom class `Drupal\bos_sql\Controller\SQL`.

The basic workflow is to:
1. Convert the submitted form values into a statement that can be understood by the remote environment
2. Submit the statement and save the search results
3. Reformat the dataset and return via AJAX to the original form.
**Note:** Because the form submission is set up to be via ajax, the process does not refresh the page.

To achieve this, the class `RemoteSearchBoxFormInterface` creates a submit callback for the form and when the form is
submitted, it processes the form submitted saves the values in `$this->submitted_form` and then redirects flow to
the `submitToRemote()` function in your custom class to conduct the search and once `submitToRemote()` terminates, the
function `buildSearchResults()` is called to format the response page (form) that will be returned to the user.

***function submitToRemote()***:
You need to write the `submitToRemote` function to perform these 3 tasks:
**Firstly**, it will gather the submitted form information from `$this->submitted_form` (remember that it has already been
validated) and build the query that will be passed to the remote search endpoint.
**Secondly**, it must submit the request to the remote search endpoint.  It is recommended that the bos_sql module be used to
manage this, but it could also be done using CURL etc. Note: this function is designed around a synchronous request-response
process.
**Lastly**, the function must clean up the results supplied from the remote endpoint and populate the class variable
`dataset` (`$this->dataset`) with an _associative array_ which can be used in the next step of the process.

***function buildSearchResults()***:
You need to write the `buildSearchResults` function to reformat the data saved in `$this->dataset` back into the results
field of the form.  Again there are helper functions in the `RemoteSearchBoxHelper` class which will get the job done
efficiently.

## Useful links:
- https://api.drupal.org/api/drupal/core%21core.api.php/group/form_api/8.3.x
