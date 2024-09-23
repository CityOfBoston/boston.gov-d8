<?php

namespace Drupal\bos_search\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class CustomFiltersExtension
 *
 * This class extends the AbstractExtension and provides custom Twig filters.
 * It includes a filter to check for non-Unicode characters in a string.
 */
class CustomFiltersExtension extends AbstractExtension {
  public function getFilters() {
    return [
      new TwigFilter('has_non_english_chars', [$this, 'hasNonEnglishChars']),
    ];
  }

  public function hasNonEnglishChars($string) {
    // Check if the string is valid UTF-8 (Unicode)
    return (preg_match('/[^\x00-\x7F]/', $string) > 0);
  }
}
