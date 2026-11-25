<?php

/**
 * @file
 * Migrates saved Canvas pages and autosaves to the simplified theme fields.
 *
 * Run with Drush php:script after rebuilding caches.
 */

declare(strict_types=1);

use Drupal\canvas\AutoSave\AutoSaveManager;
use Drupal\Component\Utility\Html;

$entity_types = \Drupal::entityTypeManager();
$versions = $fields = [];
foreach (['event-hero', 'audience-grid', 'audience-card', 'container', 'title', 'prose'] as $name) {
  $id = "sdc.sfa_theme.$name";
  $component = $entity_types->getStorage('component')->load($id);
  if (!$component?->status()) {
    throw new RuntimeException("Canvas component $id is unavailable. Rebuild caches before running this migration.");
  }
  $versions[$id] = $component->getActiveVersion();
  $fields[$id] = $component->getSettings()['prop_field_definitions'];
}
if (!isset($fields['sdc.sfa_theme.event-hero']['outline_label'])
  || isset($fields['sdc.sfa_theme.audience-grid']['gap'])
  || isset($fields['sdc.sfa_theme.audience-card']['formatted_heading'])) {
  throw new RuntimeException('Rebuild caches to discover the simplified Canvas fields before migrating.');
}

$read_inputs = static fn (array $item): array => is_array($item['inputs'] ?? []) ? ($item['inputs'] ?? []) : json_decode($item['inputs'] ?: '{}', TRUE, flags: JSON_THROW_ON_ERROR);
$to_html = static fn (string $text): array => [
  'value' => '<p>' . nl2br(Html::escape($text), FALSE) . '</p>',
  'format' => 'canvas_full_html',
];
$to_plain = static function (mixed $field, string $uuid): ?string {
  if ($field === NULL || $field === '') {
    return NULL;
  }
  $html = is_array($field) ? ($field['value'] ?? NULL) : NULL;
  if (!is_string($html) || !preg_match('~\A(?:[^<]|</?(?:p|strong|b|em|i|u|s)\s*>|<br\s*/?>)*\z~i', $html)) {
    throw new RuntimeException("Review formatted copy on Audience card $uuid before migrating to plain text.");
  }
  $html = preg_replace(['~</p>\s*<p>~i', '~<br\s*/?>~i'], ["\n\n", "\n"], $html);
  return trim(Html::decodeEntities(strip_tags($html)));
};

$section_component_ids = [];
$migrate = static function (array $items) use ($versions, $fields, $read_inputs, $to_html, $to_plain, &$section_component_ids): array {
  $slot_links = $remove = [];
  foreach ($items as $item) {
    if (in_array($item['slot'] ?? NULL, ['desktop_actions', 'mobile_actions'], TRUE)) {
      $slot_links[$item['parent_uuid']][$item['slot']][] = $item;
    }
  }

  $updated = [];
  foreach ($items as $item) {
    $id = $item['component_id'];
    $uuid = $item['uuid'];
    if (!isset($versions[$id]) || in_array($id, ['sdc.sfa_theme.title', 'sdc.sfa_theme.prose'], TRUE) || $item['component_version'] === $versions[$id]) {
      $updated[] = $item;
      continue;
    }
    $input = $before = $read_inputs($item);

    if ($id === 'sdc.sfa_theme.event-hero') {
      if (!empty($input['heading']) && empty($input['formatted_heading']['value'])) {
        $input['formatted_heading'] = $to_html($input['heading']);
      }
      if (!empty($input['heading_second_line'])) {
        $suffix = '<br>' . Html::escape($input['heading_second_line']);
        $heading = $input['formatted_heading']['value'] ?? '';
        $heading = preg_replace_callback('~</p>\s*$~', static fn (array $match): string => $suffix . $match[0], $heading, 1, $replaced);
        $input['formatted_heading'] = [
          'value' => $replaced ? $heading : $heading . $suffix,
          'format' => $input['formatted_heading']['format'] ?? 'canvas_full_html',
        ];
      }
      if (!empty($input['summary']) && empty($input['formatted_summary']['value'])) {
        $input['formatted_summary'] = $to_html($input['summary']);
      }

      $links_by_slot = [];
      foreach ($slot_links[$uuid] ?? [] as $slot => $children) {
        if (count($children) > 2) {
          throw new RuntimeException("Review the $slot links on Event hero $uuid. There is space for two links.");
        }
        $buttons = [];
        foreach ($children as $child) {
          $link = $read_inputs($child);
          if ($child['component_id'] !== 'sdc.sfa_theme.action-link'
            || !is_string($link['label'] ?? NULL)
            || trim($link['label']) === ''
            || empty($link['url']['uri'])) {
            throw new RuntimeException("Review the $slot links on Event hero $uuid. Each style can hold one complete label and URL.");
          }
          $buttons[] = $link;
          $remove[$child['uuid']] = TRUE;
        }
        $styles = ['outline', 'filled'];
        $first = $buttons[0]['variant'] ?? 'text';
        if (count($buttons) === 1) {
          $styles = [$first === 'outline' ? 'outline' : 'filled'];
        }
        else {
          $second = $buttons[1]['variant'] ?? 'text';
          if (($first === 'filled' && $second !== 'filled') || ($second === 'outline' && $first !== 'outline')) {
            $styles = ['filled', 'outline'];
          }
        }
        foreach ($buttons as $index => $button) {
          $style = $styles[$index];
          $links_by_slot[$slot][$style . '_label'] = $button['label'];
          $links_by_slot[$slot][$style . '_url'] = $button['url'];
        }
      }

      $desktop = $links_by_slot['desktop_actions'] ?? [];
      $mobile = $links_by_slot['mobile_actions'] ?? [];
      if ($desktop && $mobile && $desktop != $mobile) {
        throw new RuntimeException("Review the different desktop and mobile links on Event hero $uuid before combining them.");
      }
      foreach (['secondary' => 'outline', 'primary' => 'filled'] as $legacy => $style) {
        foreach (['_label', '_url'] as $suffix) {
          if (isset($input[$legacy . $suffix]) && !isset($input[$style . $suffix])) {
            $value = $input[$legacy . $suffix];
            $input[$style . $suffix] = $suffix === '_url' && is_string($value) ? ['uri' => $value, 'options' => []] : $value;
          }
        }
      }
      $input = ($desktop ?: $mobile) + $input;
    }
    elseif ($id === 'sdc.sfa_theme.audience-grid') {
      // Preserve the group's authored introduction as standalone content.
      foreach ([
        'sdc.sfa_theme.title' => ['text' => $input['heading'] ?? NULL, 'formatted' => $input['formatted_heading'] ?? NULL],
        'sdc.sfa_theme.prose' => ['text' => $input['summary'] ?? NULL, 'formatted' => $input['formatted_summary'] ?? NULL],
      ] as $component => $copy) {
        $copy = array_filter($copy, static fn (mixed $value): bool => $value !== NULL && $value !== '');
        if ($copy) {
          $child = $item;
          $child['uuid'] = $section_component_ids[$uuid][$component] ??= \Drupal::service('uuid')->generate();
          $child['component_id'] = $component;
          $child['component_version'] = $versions[$component];
          $child['inputs'] = json_encode($copy, JSON_THROW_ON_ERROR);
          $updated[] = $child;
        }
      }
    }
    elseif ($id === 'sdc.sfa_theme.audience-card') {
      $heading = $to_plain($input['formatted_heading'] ?? NULL, $uuid);
      if ($heading !== NULL && $heading !== '') {
        if (preg_match('/[\r\n]/', $heading)) {
          throw new RuntimeException("Review the multiple heading lines on Audience card $uuid before migrating.");
        }
        $input['heading'] = $heading;
      }
      $summary = $to_plain($input['formatted_summary'] ?? NULL, $uuid);
      if ($summary !== NULL && $summary !== '') {
        $input['summary'] = $summary;
      }
    }
    elseif (!isset($input['width'])) {
      $input['width'] = 'full-boxed';
    }

    $input = array_intersect_key($input, $fields[$id]);
    if ($before !== $input) {
      $item['inputs'] = is_array($item['inputs'] ?? NULL) ? $input : json_encode((object) $input, JSON_THROW_ON_ERROR);
    }
    $item['component_version'] = $versions[$id];
    $updated[] = $item;
  }
  return array_values(array_filter($updated, static fn (array $item): bool => !isset($remove[$item['uuid']])));
};

$pages = $entity_types->getStorage('canvas_page');
$autosaves = \Drupal::service(AutoSaveManager::class);
$draft_store = \Drupal::keyValue(AutoSaveManager::AUTO_SAVE_STORE);
$drafts = [];
foreach ($draft_store->getAll() as $key => $draft) {
  if (($draft['entity_type'] ?? '') !== 'canvas_page' || empty($draft['data']['components'])) {
    continue;
  }
  $page = $pages->load($draft['entity_id']);
  if ($page?->hasTranslation($draft['langcode'])) {
    $drafts[$key] = ['record' => $draft, 'had_conflict' => $autosaves->getUnresolvedConflictForEntity($page->getTranslation($draft['langcode'])) !== NULL];
  }
}

$database = \Drupal::database();
$transaction = $database->startTransaction();
$updated_trees = 0;
$updated_drafts = 0;
try {
  foreach (['canvas_page__components', 'canvas_page_revision__components'] as $table) {
    $trees = [];
    $rows = $database->select($table, 'c')->fields('c')->orderBy('delta')->execute();
    foreach ($rows->fetchAll(\PDO::FETCH_ASSOC) as $row) {
      $trees[$row['entity_id']][$row['revision_id']][$row['langcode']][$row['deleted']][] = $row;
    }
    foreach ($trees as $entity_id => $revisions) {
      foreach ($revisions as $revision_id => $languages) {
        foreach ($languages as $langcode => $states) {
          foreach ($states as $deleted => $rows) {
            $items = array_map(static function (array $row): array {
              $item = [];
              foreach ($row as $key => $value) {
                if (str_starts_with($key, 'components_')) {
                  $item[substr($key, strlen('components_'))] = $value;
                }
              }
              return $item;
            }, $rows);
            $updated = $migrate($items);
            if ($updated === $items) {
              continue;
            }
            $identity = [
              'entity_id' => $entity_id,
              'revision_id' => $revision_id,
              'langcode' => $langcode,
              'deleted' => $deleted,
              'bundle' => $rows[0]['bundle'],
            ];
            $delete = $database->delete($table);
            foreach ($identity as $key => $value) {
              $delete->condition($key, $value);
            }
            $delete->execute();
            $insert = $database->insert($table)->fields(array_keys($rows[0]));
            foreach ($updated as $delta => $item) {
              $row = $identity + ['delta' => $delta];
              foreach ($item as $key => $value) {
                $row['components_' . $key] = $value;
              }
              $insert->values(array_map(static fn (string $key): mixed => $row[$key] ?? NULL, array_keys($rows[0])));
            }
            $insert->execute();
            $updated_trees++;
          }
        }
      }
    }
  }

  $pages->resetCache();
  foreach ($drafts as $key => ['record' => $draft, 'had_conflict' => $had_conflict]) {
    $items = $migrate($draft['data']['components']);
    if ($items !== $draft['data']['components']) {
      if ($draft_store->get($key) !== $draft) {
        throw new RuntimeException("Canvas draft $key changed during migration. Retry after editing is complete.");
      }
      $draft['data']['components'] = $items;
      $draft_store->set($key, $draft);
      $updated_drafts++;
    }
    if (!$had_conflict) {
      $page = $pages->load($draft['entity_id'])->getTranslation($draft['langcode']);
      $autosaves->resolveConflict($page, AutoSaveManager::getConflictId($page));
    }
  }
}
catch (Throwable $error) {
  $transaction->rollBack();
  throw $error;
}
\Drupal::service('cache_tags.invalidator')->invalidateTags([AutoSaveManager::CACHE_TAG, 'canvas_page_list']);
printf("Updated %d saved component trees and %d Canvas autosaves.\n", $updated_trees, $updated_drafts);
