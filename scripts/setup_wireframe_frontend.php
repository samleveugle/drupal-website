<?php

/**
 * @file
 * Configure Views, image styles, displays and blocks for the wireframe frontend.
 *
 * Run: ddev drush php:script scripts/setup_wireframe_frontend.php
 */

use Drupal\block\Entity\Block;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\image\Entity\ImageStyle;
use Drupal\views\Entity\View;

/**
 * Create an image style once.
 */
function drupal_test_ensure_image_style(string $name, string $label, string $effect_id, array $data): void {
  if (ImageStyle::load($name)) {
    return;
  }
  $style = ImageStyle::create([
    'name' => $name,
    'label' => $label,
  ]);
  $style->addImageEffect([
    'id' => $effect_id,
    'data' => $data,
    'weight' => 0,
  ]);
  $style->save();
}

/**
 * Standard "some" pager (fixed number of rows, no pager UI).
 */
function drupal_test_pager_some(int $count): array {
  return [
    'type' => 'some',
    'options' => [
      'offset' => 0,
      'items_per_page' => $count,
    ],
  ];
}

/**
 * Contextual filter: current node ID, optionally excluded.
 */
function drupal_test_argument_current_nid(bool $exclude): array {
  return [
    'id' => 'nid',
    'table' => 'node_field_data',
    'field' => 'nid',
    'relationship' => 'none',
    'group_type' => 'group',
    'admin_label' => '',
    'entity_type' => 'node',
    'entity_field' => 'nid',
    'plugin_id' => 'node_nid',
    'default_action' => 'default',
    'exception' => [
      'value' => 'all',
      'title_enable' => FALSE,
      'title' => 'All',
    ],
    'title_enable' => FALSE,
    'title' => '',
    'default_argument_type' => 'node',
    'default_argument_options' => [],
    'summary_options' => [
      'base_path' => '',
      'count' => TRUE,
      'override' => FALSE,
      'items_per_page' => 25,
    ],
    'summary' => [
      'sort_order' => 'asc',
      'number_of_records' => 0,
      'format' => 'default_summary',
    ],
    'specify_validation' => FALSE,
    'validate' => [
      'type' => 'none',
      'fail' => 'not found',
    ],
    'validate_options' => [],
    'break_phrase' => FALSE,
    'not' => $exclude,
  ];
}

/**
 * Contextual filter: articles that reference the current office node.
 */
function drupal_test_argument_office(): array {
  return [
    'id' => 'field_office_target_id',
    'table' => 'node__field_office',
    'field' => 'field_office_target_id',
    'relationship' => 'none',
    'group_type' => 'group',
    'admin_label' => '',
    'plugin_id' => 'numeric',
    'default_action' => 'default',
    'exception' => [
      'value' => 'all',
      'title_enable' => FALSE,
      'title' => 'All',
    ],
    'title_enable' => FALSE,
    'title' => '',
    'default_argument_type' => 'node',
    'default_argument_options' => [],
    'summary_options' => [
      'base_path' => '',
      'count' => TRUE,
      'override' => FALSE,
      'items_per_page' => 25,
    ],
    'summary' => [
      'sort_order' => 'asc',
      'number_of_records' => 0,
      'format' => 'default_summary',
    ],
    'specify_validation' => FALSE,
    'validate' => [
      'type' => 'none',
      'fail' => 'not found',
    ],
    'validate_options' => [],
    'break_phrase' => FALSE,
    'not' => FALSE,
  ];
}

/**
 * Add or replace a Views block display.
 */
function drupal_test_set_block_display(array &$display, string $id, string $title, array $options): void {
  $display[$id] = [
    'id' => $id,
    'display_title' => $title,
    'display_plugin' => 'block',
    'position' => count($display),
    'display_options' => $options + ['display_extenders' => []],
    'cache_metadata' => [
      'max-age' => -1,
      'contexts' => [
        'languages:language_content',
        'languages:language_interface',
        'url',
        'user.permissions',
      ],
      'tags' => [],
    ],
  ];
}

/**
 * Place a block once (idempotent).
 */
function drupal_test_place_block(string $id, array $values): void {
  $block = Block::load($id);
  if ($block) {
    $block->setRegion($values['region']);
    $block->setWeight($values['weight'] ?? 0);
    $block->setStatus(TRUE);
    if (!empty($values['visibility'])) {
      foreach ($values['visibility'] as $key => $config) {
        $block->setVisibilityConfig($key, $config);
      }
    }
    if (!empty($values['settings']['label'])) {
      $settings = $block->get('settings');
      $settings['label'] = $values['settings']['label'];
      $settings['label_display'] = $values['settings']['label_display'] ?? 'visible';
      $block->set('settings', $settings);
    }
    $block->save();
    return;
  }
  Block::create($values + [
    'id' => $id,
    'theme' => 'drupal_test',
    'status' => TRUE,
    'provider' => NULL,
  ])->save();
}

drupal_test_ensure_image_style('listing_card', 'Listing card', 'image_scale_and_crop', [
  'width' => 480,
  'height' => 320,
]);
drupal_test_ensure_image_style('hero_banner', 'Hero banner', 'image_scale', [
  'width' => 1920,
  'height' => 480,
  'upscale' => FALSE,
]);
drupal_test_ensure_image_style('detail_media', 'Detail media', 'image_scale', [
  'width' => 720,
  'height' => 720,
  'upscale' => FALSE,
]);

// ---------------------------------------------------------------------------
// Articles view: card listing + latest / related / by-office blocks.
// ---------------------------------------------------------------------------
$articles = View::load('articles');
if (!$articles) {
  throw new \RuntimeException('Articles view is missing.');
}
$display = $articles->get('display');
$default = &$display['default']['display_options'];

$default['css_class'] = 'listing listing--articles';
$default['pager']['type'] = 'full';
$default['pager']['options']['items_per_page'] = 6;
$default['pager']['options']['tags']['next'] = '››';
$default['pager']['options']['tags']['previous'] = '‹‹';
$default['pager']['options']['tags']['first'] = '«';
$default['pager']['options']['tags']['last'] = '»';
$default['fields']['field_image']['settings']['image_style'] = 'listing_card';
$default['fields']['field_image']['settings']['image_link'] = 'content';
$default['fields']['field_tags']['exclude'] = TRUE;
if (!empty($default['filters']['field_tags_target_id']['expose'])) {
  $default['filters']['field_tags_target_id']['expose']['label'] = 'Tags';
  $default['filters']['field_tags_target_id']['expose']['use_operator'] = FALSE;
}

$list_filters = $default['filters'];
unset($list_filters['field_tags_target_id']);

$latest_fields = [
  'title' => $default['fields']['title'],
  'field_body' => $default['fields']['field_body'],
];
$card_fields = [
  'field_image' => $default['fields']['field_image'],
  'title' => $default['fields']['title'],
];

drupal_test_set_block_display($display, 'block_latest', 'Latest articles', [
  'block_description' => 'Latest articles',
  'title' => 'Latest articles',
  'defaults' => [
    'title' => FALSE,
    'pager' => FALSE,
    'use_more' => FALSE,
    'use_more_always' => FALSE,
    'use_more_text' => FALSE,
    'link_display' => FALSE,
    'fields' => FALSE,
    'filters' => FALSE,
    'css_class' => FALSE,
  ],
  'pager' => drupal_test_pager_some(3),
  'use_more' => TRUE,
  'use_more_always' => TRUE,
  'use_more_text' => 'More articles',
  'link_display' => 'page_1',
  'fields' => $latest_fields,
  'filters' => $list_filters,
  'css_class' => 'home-latest home-latest--articles',
]);

drupal_test_set_block_display($display, 'block_related', 'Related articles', [
  'block_description' => 'Related articles',
  'title' => 'Related articles',
  'defaults' => [
    'title' => FALSE,
    'pager' => FALSE,
    'use_more' => FALSE,
    'use_more_always' => FALSE,
    'use_more_text' => FALSE,
    'link_display' => FALSE,
    'fields' => FALSE,
    'filters' => FALSE,
    'arguments' => FALSE,
    'css_class' => FALSE,
  ],
  'pager' => drupal_test_pager_some(3),
  'use_more' => TRUE,
  'use_more_always' => TRUE,
  'use_more_text' => 'More articles',
  'link_display' => 'page_1',
  'fields' => $card_fields,
  'filters' => $list_filters,
  'arguments' => [
    'nid' => drupal_test_argument_current_nid(TRUE),
  ],
  'css_class' => 'listing listing--related',
]);

drupal_test_set_block_display($display, 'block_by_office', 'Articles from office', [
  'block_description' => 'Articles from office',
  'title' => 'Articles from this country',
  'defaults' => [
    'title' => FALSE,
    'pager' => FALSE,
    'use_more' => FALSE,
    'use_more_always' => FALSE,
    'use_more_text' => FALSE,
    'link_display' => FALSE,
    'fields' => FALSE,
    'filters' => FALSE,
    'arguments' => FALSE,
    'css_class' => FALSE,
  ],
  'pager' => drupal_test_pager_some(3),
  'use_more' => TRUE,
  'use_more_always' => TRUE,
  'use_more_text' => 'More articles',
  'link_display' => 'page_1',
  'fields' => $card_fields,
  'filters' => $list_filters,
  'arguments' => [
    'field_office_target_id' => drupal_test_argument_office(),
  ],
  'css_class' => 'listing listing--related',
]);

$articles->set('display', $display);
$articles->save();

// ---------------------------------------------------------------------------
// Offices view: image first, country only as a dropdown filter.
// ---------------------------------------------------------------------------
$offices = View::load('offices');
if (!$offices) {
  throw new \RuntimeException('Offices view is missing.');
}
$display = $offices->get('display');
$default = &$display['default']['display_options'];
$fields = $default['fields'];
$ordered = [];
foreach (['field_image', 'title', 'field_country'] as $key) {
  if (isset($fields[$key])) {
    $ordered[$key] = $fields[$key];
  }
}
if (isset($ordered['field_image'])) {
  $ordered['field_image']['settings']['image_style'] = 'listing_card';
  $ordered['field_image']['settings']['image_link'] = 'content';
}
if (isset($ordered['field_country'])) {
  $ordered['field_country']['exclude'] = TRUE;
}
$default['fields'] = $ordered;
$default['css_class'] = 'listing listing--offices';
$default['pager']['type'] = 'full';
$default['pager']['options']['items_per_page'] = 6;
$default['pager']['options']['tags']['next'] = '››';
$default['pager']['options']['tags']['previous'] = '‹‹';
$default['pager']['options']['tags']['first'] = '«';
$default['pager']['options']['tags']['last'] = '»';
if (!empty($default['filters']['field_country_target_id']['expose'])) {
  $default['filters']['field_country_target_id']['expose']['label'] = 'Country';
  $default['filters']['field_country_target_id']['expose']['use_operator'] = FALSE;
}
$offices->set('display', $display);
$offices->save();

// ---------------------------------------------------------------------------
// News view: clone Articles, drop the tag filter, path /news.
// ---------------------------------------------------------------------------
$news = View::load('news');
if (!$news) {
  $news = View::load('articles')->createDuplicate();
  $news->set('id', 'news');
}
$news->set('label', 'News');
$display = $news->get('display');
$default = &$display['default']['display_options'];
$default['title'] = 'News';
$default['css_class'] = 'listing listing--news';
unset($default['fields']['field_tags'], $default['filters']['field_tags_target_id']);
$default['filters']['type']['value'] = ['news' => 'news'];
$default['pager']['type'] = 'full';
$default['pager']['options']['items_per_page'] = 6;
if (isset($default['fields']['field_image'])) {
  $default['fields']['field_image']['settings']['image_style'] = 'listing_card';
  $default['fields']['field_image']['settings']['image_link'] = 'content';
}
$display['page_1']['display_options']['path'] = 'news';

$news_filters = $default['filters'];
$news_latest_fields = [
  'title' => $default['fields']['title'],
  'field_body' => $default['fields']['field_body'],
];
$news_card_fields = [
  'field_image' => $default['fields']['field_image'],
  'title' => $default['fields']['title'],
];

drupal_test_set_block_display($display, 'block_latest', 'Latest news', [
  'block_description' => 'Latest news',
  'title' => 'Latest news',
  'defaults' => [
    'title' => FALSE,
    'pager' => FALSE,
    'use_more' => FALSE,
    'use_more_always' => FALSE,
    'use_more_text' => FALSE,
    'link_display' => FALSE,
    'fields' => FALSE,
    'filters' => FALSE,
    'css_class' => FALSE,
  ],
  'pager' => drupal_test_pager_some(3),
  'use_more' => TRUE,
  'use_more_always' => TRUE,
  'use_more_text' => 'More news',
  'link_display' => 'page_1',
  'fields' => $news_latest_fields,
  'filters' => $news_filters,
  'css_class' => 'home-latest home-latest--news',
]);

drupal_test_set_block_display($display, 'block_related', 'Related news', [
  'block_description' => 'Related news',
  'title' => 'Related news',
  'defaults' => [
    'title' => FALSE,
    'pager' => FALSE,
    'use_more' => FALSE,
    'use_more_always' => FALSE,
    'use_more_text' => FALSE,
    'link_display' => FALSE,
    'fields' => FALSE,
    'filters' => FALSE,
    'arguments' => FALSE,
    'css_class' => FALSE,
  ],
  'pager' => drupal_test_pager_some(3),
  'use_more' => FALSE,
  'fields' => $news_card_fields,
  'filters' => $news_filters,
  'arguments' => [
    'nid' => drupal_test_argument_current_nid(TRUE),
  ],
  'css_class' => 'listing listing--related',
]);

// Drop leftover article-only displays if we cloned the updated articles view.
unset($display['block_by_office']);
$news->set('display', $display);
$news->save();

// ---------------------------------------------------------------------------
// Quiet homepage route so the front page can host the two latest-column blocks.
// ---------------------------------------------------------------------------
$homepage = View::load('homepage');
if (!$homepage) {
  $homepage = View::create([
    'id' => 'homepage',
    'label' => 'Homepage',
    'module' => 'views',
    'description' => 'Front page route. Content comes from Latest news/articles blocks.',
    'tag' => '',
    'base_table' => 'node_field_data',
    'base_field' => 'nid',
    'display' => [
      'default' => [
        'id' => 'default',
        'display_title' => 'Default',
        'display_plugin' => 'default',
        'position' => 0,
        'display_options' => [
          'title' => '',
          'access' => [
            'type' => 'perm',
            'options' => ['perm' => 'access content'],
          ],
          'cache' => ['type' => 'tag', 'options' => []],
          'query' => ['type' => 'views_query', 'options' => []],
          'exposed_form' => ['type' => 'basic'],
          'pager' => drupal_test_pager_some(0),
          'style' => ['type' => 'default'],
          'row' => ['type' => 'fields'],
          'fields' => [
            'nid' => [
              'id' => 'nid',
              'table' => 'node_field_data',
              'field' => 'nid',
              'entity_type' => 'node',
              'entity_field' => 'nid',
              'plugin_id' => 'field',
              'exclude' => TRUE,
              'alter' => [],
              'element_default_classes' => TRUE,
            ],
          ],
          'filters' => [
            'nid' => [
              'id' => 'nid',
              'table' => 'node_field_data',
              'field' => 'nid',
              'entity_type' => 'node',
              'entity_field' => 'nid',
              'plugin_id' => 'numeric',
              'operator' => '=',
              'value' => ['value' => '0'],
            ],
          ],
          'sorts' => [],
          'header' => [],
          'footer' => [],
          'empty' => [],
          'arguments' => [],
          'relationships' => [],
          'display_extenders' => [],
        ],
      ],
      'page_1' => [
        'id' => 'page_1',
        'display_title' => 'Page',
        'display_plugin' => 'page',
        'position' => 1,
        'display_options' => [
          'path' => 'home',
          'display_extenders' => [],
        ],
      ],
    ],
  ]);
  $homepage->save();
}

\Drupal::configFactory()->getEditable('system.site')->set('page.front', '/home')->save();

// ---------------------------------------------------------------------------
// Manage display: hide labels, use image styles, hide unused extras.
// ---------------------------------------------------------------------------
$display_map = [
  'news' => [
    'hide' => ['uid', 'created', 'links'],
    'components' => [
      'field_image' => [
        'type' => 'image',
        'label' => 'hidden',
        'weight' => 0,
        'settings' => [
          'image_link' => '',
          'image_style' => 'detail_media',
          'image_loading' => ['attribute' => 'lazy'],
        ],
      ],
      'field_body' => [
        'type' => 'text_default',
        'label' => 'hidden',
        'weight' => 1,
      ],
    ],
  ],
  'articles' => [
    'hide' => ['uid', 'created', 'links', 'field_office'],
    'components' => [
      'field_image' => [
        'type' => 'image',
        'label' => 'hidden',
        'weight' => 0,
        'settings' => [
          'image_link' => '',
          'image_style' => 'detail_media',
          'image_loading' => ['attribute' => 'lazy'],
        ],
      ],
      'field_body' => [
        'type' => 'text_default',
        'label' => 'hidden',
        'weight' => 1,
      ],
      'field_tags' => [
        'type' => 'entity_reference_label',
        'label' => 'hidden',
        'weight' => 2,
        'settings' => ['link' => TRUE],
      ],
    ],
  ],
  'offices' => [
    'hide' => ['uid', 'created', 'links', 'field_country'],
    'components' => [
      'field_image' => [
        'type' => 'image',
        'label' => 'hidden',
        'weight' => 0,
        'settings' => [
          'image_link' => '',
          'image_style' => 'detail_media',
          'image_loading' => ['attribute' => 'lazy'],
        ],
      ],
      'field_tel' => ['type' => 'string', 'label' => 'hidden', 'weight' => 1],
      'field_fax' => ['type' => 'string', 'label' => 'hidden', 'weight' => 2],
      'field_adress' => ['type' => 'string', 'label' => 'hidden', 'weight' => 3],
      'field_email' => ['type' => 'email_mailto', 'label' => 'hidden', 'weight' => 4],
      'field_contact_person' => ['type' => 'string', 'label' => 'hidden', 'weight' => 5],
    ],
  ],
];

foreach ($display_map as $bundle => $setup) {
  $view_display = EntityViewDisplay::load("node.$bundle.default");
  if (!$view_display) {
    continue;
  }
  foreach ($setup['hide'] as $name) {
    $view_display->removeComponent($name);
  }
  foreach ($setup['components'] as $name => $component) {
    $existing = $view_display->getComponent($name) ?: [];
    $view_display->setComponent($name, $component + $existing);
  }
  $view_display->save();
}

// ---------------------------------------------------------------------------
// Blocks: chrome cleanup + latest / related placements.
// New regions in .info.yml are only known after a theme refresh.
// ---------------------------------------------------------------------------
\Drupal::service('theme_handler')->refreshInfo();
\Drupal::service('theme.registry')->reset();

$help = Block::load('drupal_test_help');
if ($help) {
  $help->setRegion('highlighted');
  $help->save();
}

$branding = Block::load('drupal_test_site_branding');
if ($branding) {
  $branding->setStatus(FALSE);
  $branding->save();
}

$page_title = Block::load('drupal_test_page_title');
if ($page_title) {
  $page_title->setVisibilityConfig('request_path', [
    'id' => 'request_path',
    'negate' => TRUE,
    'pages' => "<front>\n/home\n/node/*",
  ]);
  $page_title->save();
}

$front_only = [
  'request_path' => [
    'id' => 'request_path',
    'negate' => FALSE,
    'pages' => "<front>\n/home",
  ],
];

drupal_test_place_block('drupal_test_latest_news', [
  'plugin' => 'views_block:news-block_latest',
  'region' => 'home_news',
  'weight' => 0,
  'settings' => [
    'id' => 'views_block:news-block_latest',
    'label' => 'Latest news',
    'label_display' => 'visible',
    'provider' => 'views',
    'views_label' => 'Latest news',
    'items_per_page' => 'none',
  ],
  'visibility' => $front_only,
]);

drupal_test_place_block('drupal_test_latest_articles', [
  'plugin' => 'views_block:articles-block_latest',
  'region' => 'home_articles',
  'weight' => 0,
  'settings' => [
    'id' => 'views_block:articles-block_latest',
    'label' => 'Latest articles',
    'label_display' => 'visible',
    'provider' => 'views',
    'views_label' => 'Latest articles',
    'items_per_page' => 'none',
  ],
  'visibility' => $front_only,
]);

drupal_test_place_block('drupal_test_related_news', [
  'plugin' => 'views_block:news-block_related',
  'region' => 'content',
  'weight' => 10,
  'settings' => [
    'id' => 'views_block:news-block_related',
    'label' => 'Related news',
    'label_display' => 'visible',
    'provider' => 'views',
    'views_label' => 'Related news',
    'items_per_page' => 'none',
  ],
  'visibility' => [
    'entity_bundle:node' => [
      'id' => 'entity_bundle:node',
      'negate' => FALSE,
      'context_mapping' => ['node' => '@node.node_route_context:node'],
      'bundles' => ['news' => 'news'],
    ],
  ],
]);

drupal_test_place_block('drupal_test_related_articles', [
  'plugin' => 'views_block:articles-block_related',
  'region' => 'content',
  'weight' => 10,
  'settings' => [
    'id' => 'views_block:articles-block_related',
    'label' => 'Related articles',
    'label_display' => 'visible',
    'provider' => 'views',
    'views_label' => 'Related articles',
    'items_per_page' => 'none',
  ],
  'visibility' => [
    'entity_bundle:node' => [
      'id' => 'entity_bundle:node',
      'negate' => FALSE,
      'context_mapping' => ['node' => '@node.node_route_context:node'],
      'bundles' => ['articles' => 'articles'],
    ],
  ],
]);

drupal_test_place_block('drupal_test_articles_by_office', [
  'plugin' => 'views_block:articles-block_by_office',
  'region' => 'content',
  'weight' => 10,
  'settings' => [
    'id' => 'views_block:articles-block_by_office',
    'label' => 'Articles from this country',
    'label_display' => 'visible',
    'provider' => 'views',
    'views_label' => '',
    'items_per_page' => 'none',
  ],
  'visibility' => [
    'entity_bundle:node' => [
      'id' => 'entity_bundle:node',
      'negate' => FALSE,
      'context_mapping' => ['node' => '@node.node_route_context:node'],
      'bundles' => ['offices' => 'offices'],
    ],
  ],
]);

drupal_test_place_block('drupal_test_footer_menu', [
  'plugin' => 'system_menu_block:main',
  'region' => 'footer',
  'weight' => 0,
  'settings' => [
    'id' => 'system_menu_block:main',
    'label' => 'Footer menu',
    'label_display' => '0',
    'provider' => 'system',
    'level' => 1,
    'depth' => 1,
    'expand_all_items' => FALSE,
  ],
]);

print "Wireframe frontend config saved.\n";
