<?php
// Nova Quill Sanitizer - include contract:
// input:  $nova_quill_html_raw
// output: $nova_quill_html_clean

$nova_quill_html_clean = '';
$nova_quill_html_raw = isset($nova_quill_html_raw) ? trim((string) $nova_quill_html_raw) : '';

if ($nova_quill_html_raw !== '') {
  $nova_allowed_tags = [
    'p' => true,
    'br' => true,
    'strong' => true,
    'em' => true,
    'u' => true,
    's' => true,
    'ol' => true,
    'ul' => true,
    'li' => true,
    'pre' => true,
    'a' => true
  ];

  $nova_drop_with_content = [
    'script' => true,
    'style' => true,
    'iframe' => true,
    'object' => true,
    'embed' => true,
    'form' => true,
    'input' => true,
    'button' => true,
    'textarea' => true,
    'select' => true,
    'svg' => true,
    'math' => true,
    'img' => true,
    'video' => true,
    'audio' => true,
    'canvas' => true
  ];

  $nova_dom = new DOMDocument('1.0', 'UTF-8');
  $nova_previous_errors = libxml_use_internal_errors(true);
  $nova_wrapped_html = '<div id="nova-quill-sanitizer-root">' . $nova_quill_html_raw . '</div>';

  $nova_dom->loadHTML(
    '<?xml encoding="UTF-8">' . $nova_wrapped_html,
    LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
  );

  libxml_clear_errors();
  libxml_use_internal_errors($nova_previous_errors);

  $nova_root = $nova_dom->getElementById('nova-quill-sanitizer-root');

  if ($nova_root) {
    $nova_nodes = [$nova_root];

    while (!empty($nova_nodes)) {
      $nova_node = array_pop($nova_nodes);

      if ($nova_node->nodeType !== XML_ELEMENT_NODE) {
        continue;
      }

      if ($nova_node === $nova_root) {
        for ($nova_i = $nova_node->childNodes->length - 1; $nova_i >= 0; $nova_i--) {
          $nova_nodes[] = $nova_node->childNodes->item($nova_i);
        }

        continue;
      }

      $nova_tag = strtolower($nova_node->nodeName);

      if (isset($nova_drop_with_content[$nova_tag])) {
        $nova_node->parentNode->removeChild($nova_node);
        continue;
      }

      if (!isset($nova_allowed_tags[$nova_tag])) {
        $nova_unwrapped_children = [];

        for ($nova_i = $nova_node->childNodes->length - 1; $nova_i >= 0; $nova_i--) {
          $nova_unwrapped_children[] = $nova_node->childNodes->item($nova_i);
        }

        while ($nova_node->firstChild) {
          $nova_node->parentNode->insertBefore($nova_node->firstChild, $nova_node);
        }

        $nova_node->parentNode->removeChild($nova_node);

        foreach ($nova_unwrapped_children as $nova_unwrapped_child) {
          $nova_nodes[] = $nova_unwrapped_child;
        }

        continue;
      }

      for ($nova_i = $nova_node->childNodes->length - 1; $nova_i >= 0; $nova_i--) {
        $nova_nodes[] = $nova_node->childNodes->item($nova_i);
      }

      if ($nova_node->hasAttributes()) {
        $nova_attributes_to_remove = [];

        foreach ($nova_node->attributes as $nova_attribute) {
          $nova_attribute_name = strtolower($nova_attribute->nodeName);
          $nova_keep_attribute = false;

          if (
            $nova_tag === 'a' &&
            in_array($nova_attribute_name, ['href', 'target', 'rel'], true)
          ) {
            $nova_keep_attribute = true;
          }

          if (
            $nova_tag === 'pre' &&
            $nova_attribute_name === 'data-language'
          ) {
            $nova_keep_attribute = true;
          }

          if (!$nova_keep_attribute || str_starts_with($nova_attribute_name, 'on')) {
            $nova_attributes_to_remove[] = $nova_attribute->nodeName;
          }
        }

        foreach ($nova_attributes_to_remove as $nova_attribute_name) {
          $nova_node->removeAttribute($nova_attribute_name);
        }
      }

      if ($nova_tag === 'a') {
        $nova_href = trim($nova_node->getAttribute('href'));

        if (
          $nova_href !== '' &&
          !preg_match('/^(https?:|mailto:)/i', $nova_href)
        ) {
          $nova_node->removeAttribute('href');
          $nova_href = '';
        }

        if ($nova_href !== '') {
          $nova_node->setAttribute('target', '_blank');
          $nova_node->setAttribute('rel', 'noopener noreferrer');
        } else {
          $nova_node->removeAttribute('target');
          $nova_node->removeAttribute('rel');
        }
      }

      if ($nova_tag === 'pre') {
        $nova_language = strtolower(trim($nova_node->getAttribute('data-language')));
        $nova_language = preg_replace('/[^a-z0-9_-]/', '', $nova_language);

        if ($nova_language === '') {
          $nova_language = 'plain';
        }

        $nova_node->setAttribute('data-language', $nova_language);

        $nova_pre_text = preg_replace('/^\R+|\R+$/u', '', $nova_node->textContent);

        while ($nova_node->firstChild) {
          $nova_node->removeChild($nova_node->firstChild);
        }

        $nova_node->appendChild($nova_dom->createTextNode($nova_pre_text));
      }
    }

    $nova_html_output = '';

    foreach ($nova_root->childNodes as $nova_child) {
      $nova_html_output .= $nova_dom->saveHTML($nova_child);
    }

    $nova_quill_html_clean = trim($nova_html_output);
  }
}

unset(
  $nova_allowed_tags,
  $nova_attribute,
  $nova_attribute_name,
  $nova_attributes_to_remove,
  $nova_child,
  $nova_dom,
  $nova_drop_with_content,
  $nova_href,
  $nova_html_output,
  $nova_i,
  $nova_keep_attribute,
  $nova_language,
  $nova_node,
  $nova_nodes,
  $nova_previous_errors,
  $nova_pre_text,
  $nova_quill_html_raw,
  $nova_root,
  $nova_tag,
  $nova_unwrapped_child,
  $nova_unwrapped_children,
  $nova_wrapped_html
);
