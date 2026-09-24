<?php

/**
 * @file
 * Fill listings with dummy English content. Idempotent by title/term name.
 *
 * Run: ddev drush php:script /var/www/html/scripts/seed_demo_content.php
 */

use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Load or create a taxonomy term by vocabulary and name.
 */
function seed_term(string $vid, string $name): Term {
  $existing = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadByProperties(['vid' => $vid, 'name' => $name]);
  if ($existing) {
    return reset($existing);
  }
  $term = Term::create([
    'vid' => $vid,
    'name' => $name,
    'langcode' => 'en',
  ]);
  $term->save();
  print "Created term $vid:$name ({$term->id()})\n";
  return $term;
}

/**
 * Find a node by type + title, or NULL.
 */
function seed_find_node(string $type, string $title): ?Node {
  $nids = \Drupal::entityQuery('node')
    ->accessCheck(FALSE)
    ->condition('type', $type)
    ->condition('title', $title)
    ->range(0, 1)
    ->execute();
  if (!$nids) {
    return NULL;
  }
  return Node::load(reset($nids));
}

/**
 * Cycle through existing managed image files.
 */
function seed_files(): array {
  $fids = \Drupal::entityQuery('file')
    ->accessCheck(FALSE)
    ->condition('filemime', 'image/', 'STARTS_WITH')
    ->sort('fid')
    ->execute();
  $files = File::loadMultiple($fids);
  if (!$files) {
    throw new \RuntimeException('No managed image files found to reuse.');
  }
  return array_values($files);
}

function seed_image_item(array $files, int &$index, string $alt): array {
  $file = $files[$index % count($files)];
  $index++;
  $file->setPermanent();
  $file->save();
  return [
    'target_id' => $file->id(),
    'alt' => $alt,
  ];
}

$body_news = <<<HTML
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.</p>
<p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim.</p>
<p>Integer posuere erat a ante venenatis dapibus posuere velit aliquet. Curabitur blandit tempus porttitor. Vestibulum id ligula porta felis euismod semper.</p>
HTML;

$body_article = <<<HTML
<p>Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Nullam quis risus eget urna mollis ornare vel eu leo. Donec ullamcorper nulla non metus auctor fringilla.</p>
<p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Maecenas faucibus mollis interdum. Aenean lacinia bibendum nulla sed consectetur, auctor venenatis dapibus posuere.</p>
<p>Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor. Etiam porta sem malesuada magna mollis euismod. Fusce dapibus, tellus ac cursus commodo.</p>
HTML;

$files = seed_files();
$img = 0;

$countries = [];
foreach (['Belgium', 'Netherlands', 'Germany', 'France', 'United Kingdom'] as $name) {
  $countries[$name] = seed_term('countries', $name);
}

$tags = [];
foreach (['Business', 'Innovation', 'News', 'Technology', 'Logistics', 'Sustainability'] as $name) {
  $tags[$name] = seed_term('tags', $name);
}

$office_defs = [
  [
    'title' => 'Office Berlin',
    'country' => 'Germany',
    'tel' => '+49 30 12 34 56',
    'fax' => '+49 30 12 34 57',
    'adress' => 'Friedrichstrasse 100, Berlin, Germany',
    'email' => 'berlin@example.com',
    'contact' => 'Anna Schmidt',
    'alt' => 'Office building in Berlin',
  ],
  [
    'title' => 'Office Paris',
    'country' => 'France',
    'tel' => '+33 1 23 45 67 89',
    'fax' => '+33 1 23 45 67 90',
    'adress' => '12 Rue de Rivoli, Paris, France',
    'email' => 'paris@example.com',
    'contact' => 'Camille Dubois',
    'alt' => 'Office building in Paris',
  ],
  [
    'title' => 'Office London',
    'country' => 'United Kingdom',
    'tel' => '+44 20 7946 0123',
    'fax' => '+44 20 7946 0124',
    'adress' => '1 King William Street, London, United Kingdom',
    'email' => 'london@example.com',
    'contact' => 'James Whitmore',
    'alt' => 'Office building in London',
  ],
  [
    'title' => 'Office Munich',
    'country' => 'Germany',
    'tel' => '+49 89 98 76 54',
    'fax' => '+49 89 98 76 55',
    'adress' => 'Kaufingerstrasse 20, Munich, Germany',
    'email' => 'munich@example.com',
    'contact' => 'Lukas Weber',
    'alt' => 'Office building in Munich',
  ],
];

$offices = [];
foreach (['Office Belgium', 'Office Netherlands'] as $existing_title) {
  $node = seed_find_node('offices', $existing_title);
  if ($node) {
    $offices[$existing_title] = $node;
  }
}

foreach ($office_defs as $def) {
  $node = seed_find_node('offices', $def['title']);
  if (!$node) {
    $node = Node::create([
      'type' => 'offices',
      'title' => $def['title'],
      'langcode' => 'en',
      'status' => 1,
      'field_country' => ['target_id' => $countries[$def['country']]->id()],
      'field_tel' => $def['tel'],
      'field_fax' => $def['fax'],
      'field_adress' => $def['adress'],
      'field_email' => $def['email'],
      'field_contact_person' => $def['contact'],
      'field_image' => seed_image_item($files, $img, $def['alt']),
    ]);
    $node->save();
    print "Created office {$def['title']} ({$node->id()})\n";
  }
  $offices[$def['title']] = $node;
}

// Fill missing image on existing news nodes.
$news_nids = \Drupal::entityQuery('node')->accessCheck(FALSE)->condition('type', 'news')->execute();
foreach (Node::loadMultiple($news_nids) as $news) {
  if ($news->get('field_image')->isEmpty()) {
    $news->set('field_image', seed_image_item($files, $img, $news->label()));
    $news->save();
    print "Filled image on news {$news->id()} {$news->label()}\n";
  }
}

$news_defs = [
  'Quarterly update from our European teams',
  'Workshop series on digital collaboration',
  'Customer event scheduled for next spring',
  'New workplace guidelines for hybrid teams',
  'Partnership announced with local universities',
  'Community day highlights from last month',
];

foreach ($news_defs as $title) {
  if (seed_find_node('news', $title)) {
    continue;
  }
  $node = Node::create([
    'type' => 'news',
    'title' => $title,
    'langcode' => 'en',
    'status' => 1,
    'field_body' => [
      'value' => $body_news,
      'format' => 'basic_html',
    ],
    'field_image' => seed_image_item($files, $img, $title),
  ]);
  $node->save();
  print "Created news $title ({$node->id()})\n";
}

$article_defs = [
  [
    'title' => 'How regional hubs support faster delivery',
    'office' => 'Office Belgium',
    'tags' => ['Logistics', 'Business'],
  ],
  [
    'title' => 'Designing quieter and greener workplaces',
    'office' => 'Office Netherlands',
    'tags' => ['Sustainability', 'Innovation'],
  ],
  [
    'title' => 'A practical look at office automation',
    'office' => 'Office Berlin',
    'tags' => ['Technology', 'Innovation'],
  ],
  [
    'title' => 'Training programmes for new project leads',
    'office' => 'Office Paris',
    'tags' => ['Business', 'News'],
  ],
  [
    'title' => 'Measuring energy use across our buildings',
    'office' => 'Office London',
    'tags' => ['Sustainability', 'Technology'],
  ],
  [
    'title' => 'Cross-border teams and daily stand-ups',
    'office' => 'Office Munich',
    'tags' => ['Innovation', 'Business'],
  ],
  [
    'title' => 'What clients asked us most this year',
    'office' => 'Office Belgium',
    'tags' => ['News', 'Logistics'],
  ],
];

foreach ($article_defs as $def) {
  if (seed_find_node('articles', $def['title'])) {
    continue;
  }
  if (empty($offices[$def['office']])) {
    throw new \RuntimeException('Missing office ' . $def['office']);
  }
  $tag_values = [];
  foreach ($def['tags'] as $tag_name) {
    $tag_values[] = ['target_id' => $tags[$tag_name]->id()];
  }
  $node = Node::create([
    'type' => 'articles',
    'title' => $def['title'],
    'langcode' => 'en',
    'status' => 1,
    'field_body' => [
      'value' => $body_article,
      'format' => 'basic_html',
    ],
    'field_image' => seed_image_item($files, $img, $def['title']),
    'field_office' => ['target_id' => $offices[$def['office']]->id()],
    'field_tags' => $tag_values,
  ]);
  $node->save();
  print "Created article {$def['title']} ({$node->id()})\n";
}

echo "=== TOTALS ===\n";
foreach (['news', 'articles', 'offices'] as $bundle) {
  $count = \Drupal::entityQuery('node')->accessCheck(FALSE)->condition('type', $bundle)->count()->execute();
  print "$bundle: $count\n";
}
