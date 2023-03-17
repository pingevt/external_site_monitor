<?php

namespace Drupal\esm_test_base;

trait Dom2ArrayTrait {

  /**
   * Process Dom Document Element and convert to an array.
   */
  protected function dom2Array($root) {
    $array = [];

    // List attributes.
    if ($root->hasAttributes()) {
      foreach ($root->attributes as $attribute) {
        $array['_attributes'][$attribute->name] = $attribute->value;
      }
    }

    // Handle classic node.
    if ($root->nodeType == XML_ELEMENT_NODE) {
      $array['_type'] = $root->nodeName;
      if ($root->hasChildNodes()) {
        $children = $root->childNodes;
        for ($i = 0; $i < $children->length; $i++) {
          $child = $this->dom2Array($children->item($i));

          // don't keep textnode with only spaces and newline.
          if (!empty($child)) {
            $array['_children'][] = $child;
          }
        }
      }

      // Handle text node.
    }
    elseif ($root->nodeType == XML_TEXT_NODE || $root->nodeType == XML_CDATA_SECTION_NODE) {
      $value = $root->nodeValue;
      if (!empty($value)) {
        $array['_type'] = '_text';
        $array['_content'] = $value;
      }
    }

    return $array;
  }
}
