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
Add a new custom search form to the bos_remote_search_box component by copy-pasting and renaming the
`src/Forms/template.php class` file.

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
much of this for you and integrates with the validation and submission handlers, and the site theming.
1. The first step is to call the parent class’s `buildForm()` function.
   This will create a base form object which integrates with both Drupal and the COB patterns library.

   **Note**: This is a stub, and has no actual input elements.
2. Next add in the main search element. For each form thise is likely to be a single search box. Calling the
parents' `buildAddressSearch($form)` will add an address search which works with the validate & submit event
handlers and site theming.
Other `buildXxxxSearch($form)` functions will be built and added to the parent class as we determine needs.

3. Finally, add in any additional (usually optional) search elements.  This can be done either by manually
   creating a drupal form element and passing it into the parents' `addManualCriteria()` function, or by calling
   a helper function in the parent class. These functions are named `criteriaXxx($form, $form_element)` and are
   designed to work with the validate & submit event handlers and site theming.
   More will be built and added to the parent class as we determine needs.

   **Tip:** using the `#weight` parameter gives very good control over the ordering of elements in your form.
4. Once the $form variable is populated using the parent class functions, you can use code to tweak, typically adding or removing class attributes and possibly display text.

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

**Note**: Drupal will validate that required fields are provided and will raise alerts if not.

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
1. Convert the submitted form values (from `$this->submitted_form`) into a statement (usually SQL) that can be understood by the remote environment (usually a database).
2. Submit the statement and collect the resultant dataset.
3. Reformat the dataset and inject it into the results form element on the form.

**Note:** Because the form submission is set up to be via ajax, the process does not refresh the page.

## Useful links:
- https://api.drupal.org/api/drupal/core%21core.api.php/group/form_api/8.3.x
