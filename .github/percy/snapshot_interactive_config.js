// @file snapshot_interactive_config.js
// This script defines the screenshots that will be used to visually compare
// the outputs from the site dynamically.

module.exports = [
  {
    name: 'Login',
    url: 'https://boston.lndo.site/user/login',
    waitForSelector: '#user-login-form',
    execute() {
      document.querySelector('#edit-name').value = 'admin';
      document.querySelector('#edit-pass').value = 'admin';
      document.querySelector('#edit-submit').click();
    },
    additionalSnapshots: [
      {
        suffix: ' - Login',
        waitForSelector: '#system-messages',
      }
    ],
  },
  {
    name: 'Create Article',
    url: 'https://boston.lndo.site/node/add/article',
    waitForTimeout: 5000,
    waitForSelector: '#edit-title-0-value',
    execute() {
      document.querySelector('#edit-title-0-value').value = 'Test Article';
      document.querySelector("#edit-field-intro-text-0-value").value = "Test Intro";
      document.querySelector("#edit-body-0-value").value = "Test Body Text";
      document.querySelector("#edit-moderation-state-0-state").value = "draft";
      document.querySelector('#edit-submit').click();
    },
    scope: 'div#page',
    additionalSnapshots: [
      {
        suffix: ' - Created',
        waitForSelector: '#system-messages',
      }
    ],
  },
];
