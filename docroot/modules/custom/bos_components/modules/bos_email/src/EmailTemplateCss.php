<?php

namespace Drupal\bos_email;

class EmailTemplateCss {

  /**
   * Defines standard css rules for CoB emails.
   *
   * @return string
   */
  public static function getCss() {
    return "
body {
  font-family: Lora, serif;
  font-size: normal;
}
.txt {}
.txt-h {
  font-size: larger;
}
.txt-b {
  font-weight: bold;
}
.button {
  background-color: #1871bd;
  color: #ffffff !important;
  font-family: Montserrat,Arial,sans-serif;
  font-size: normal;
  font-weight: 700;
  letter-spacing: 1px;
  line-height: 16px;
  line-height: 1rem;
  margin: 0;
  padding: 20px;
  padding: 1.25rem;
  text-transform: uppercase;
  text-decoration: none;
  border: none;
  cursor: pointer;
  display: inline-block;
}
a.button:link {
  text-decoration: none;
}
a.button:hover {
  background-color: #d22d23;
}
.visually-hidden {
  position: absolute!important;
  height: 1px;
  width: 1px;
  overflow: hidden;
  clip: rect(1px,1px,1px,1px);
}
    ";
  }
}
