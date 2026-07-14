<?php
// Nova Quill Edit Adapter - include contract:
// input:  $nova_quill_html_semantic
// output: $nova_quill_html_editor

$nova_quill_html_editor = '';
$nova_quill_html_semantic = isset($nova_quill_html_semantic) ? trim((string) $nova_quill_html_semantic) : '';

if ($nova_quill_html_semantic !== '') {
  $nova_quill_html_editor = $nova_quill_html_semantic;

  $nova_dom = new DOMDocument('1.0', 'UTF-8');
  $nova_previous_errors = libxml_use_internal_errors(true);
  $nova_wrapped_html = '<div id="nova-quill-adapter-root">' . $nova_quill_html_editor . '</div>';

  $nova_dom->loadHTML(
    '<?xml encoding="UTF-8">' . $nova_wrapped_html,
    LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
  );

  libxml_clear_errors();
  libxml_use_internal_errors($nova_previous_errors);

  $nova_root = $nova_dom->getElementById('nova-quill-adapter-root');

  if ($nova_root) {
    $nova_pre_nodes = [];

    foreach ($nova_root->getElementsByTagName('pre') as $nova_pre_node) {
      $nova_pre_nodes[] = $nova_pre_node;
    }

    foreach ($nova_pre_nodes as $nova_pre_node) {
      $nova_code_container = $nova_dom->createElement('div');
      $nova_code_container->setAttribute('class', 'ql-code-block-container');
      $nova_code_container->setAttribute('spellcheck', 'false');

      $nova_code_block = $nova_dom->createElement('div');
      $nova_code_block->setAttribute('class', 'ql-code-block');
      $nova_pre_text = preg_replace('/^\R+|\R+$/u', '', $nova_pre_node->textContent);
      $nova_code_block->appendChild($nova_dom->createTextNode($nova_pre_text));
      $nova_code_container->appendChild($nova_code_block);

      $nova_pre_node->parentNode->replaceChild($nova_code_container, $nova_pre_node);
    }

    $nova_list_map = [
      'ol' => 'ordered',
      'ul' => 'bullet'
    ];

    foreach ($nova_list_map as $nova_source_tag => $nova_list_type) {
      $nova_list_nodes = [];

      foreach ($nova_root->getElementsByTagName($nova_source_tag) as $nova_list_node) {
        $nova_list_nodes[] = $nova_list_node;
      }

      foreach ($nova_list_nodes as $nova_list_node) {
        $nova_ol_node = $nova_dom->createElement('ol');

        foreach ($nova_list_node->childNodes as $nova_child_node) {
          if ($nova_child_node->nodeType !== XML_ELEMENT_NODE || strtolower($nova_child_node->nodeName) !== 'li') {
            continue;
          }

          $nova_li_node = $nova_dom->createElement('li');
          $nova_li_node->setAttribute('data-list', $nova_list_type);

          $nova_ui_node = $nova_dom->createElement('span');
          $nova_ui_node->setAttribute('class', 'ql-ui');
          $nova_ui_node->setAttribute('contenteditable', 'false');
          $nova_li_node->appendChild($nova_ui_node);

          while ($nova_child_node->firstChild) {
            $nova_li_node->appendChild($nova_child_node->firstChild);
          }

          $nova_ol_node->appendChild($nova_li_node);
        }

        $nova_list_node->parentNode->replaceChild($nova_ol_node, $nova_list_node);
      }
    }

    $nova_html_output = '';

    foreach ($nova_root->childNodes as $nova_child) {
      $nova_html_output .= $nova_dom->saveHTML($nova_child);
    }

    $nova_quill_html_editor = trim($nova_html_output);
  }
}

unset(
  $nova_child,
  $nova_child_node,
  $nova_code_block,
  $nova_code_container,
  $nova_dom,
  $nova_html_output,
  $nova_li_node,
  $nova_list_map,
  $nova_list_node,
  $nova_list_nodes,
  $nova_list_type,
  $nova_ol_node,
  $nova_pre_node,
  $nova_pre_nodes,
  $nova_pre_text,
  $nova_previous_errors,
  $nova_root,
  $nova_source_tag,
  $nova_ui_node,
  $nova_wrapped_html
);
