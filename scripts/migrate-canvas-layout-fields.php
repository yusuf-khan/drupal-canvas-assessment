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
$versions = [];
foreach (['event-hero', 'audience-grid', 'container', 'action-link'] as $name) {
  $id = "sdc.sfa_theme.$name";
  $component = $entity_types->getStorage('component')->load($id);
  if (!$component?->status()) {
    throw new RuntimeException("Canvas component $id is unavailable. Rebuild caches before running this migration.");
  }
  $versions[$id] = $component->getActiveVersion();
}

$to_html = static fn (string $text): string => '<p>' . nl2br(Html::escape($text), FALSE) . '</p>';
$format = 'canvas_full_html';
$button_uuids = [];
$migrate = static function (array $items) use ($versions, $to_html, $format, &$button_uuids): array {
  $desktop_slots = [];
  foreach ($items as $item) {
    if (($item['slot'] ?? '') === 'desktop_actions') {
      $desktop_slots[$item['parent_uuid']] = TRUE;
    }
  }
  $updated = [];
  foreach ($items as $item) {
    $id = $item['component_id'];
    if (!isset($versions[$id]) || $id === 'sdc.sfa_theme.action-link') {
      $updated[] = $item;
      continue;
    }
    $input = json_decode($item['inputs'] ?: '{}', TRUE, flags: JSON_THROW_ON_ERROR);
    $before = $input;
    $buttons = [];
    if ($id === 'sdc.sfa_theme.event-hero') {
      $second = $input['heading_second_line'] ?? '';
      $heading = $input['formatted_heading']['value'] ?? '';
      if ($heading === '' && !empty($input['heading'])) {
        $input['formatted_heading'] = [
          'value' => $to_html($input['heading'] . ($second !== '' ? "\n$second" : '')),
          'format' => $format,
        ];
      }
      elseif ($heading !== '' && $second !== '') {
        $suffix = '<br>' . Html::escape($second);
        $input['formatted_heading']['value'] = preg_match('/<\/p>\s*$/i', $heading)
          ? preg_replace_callback('/<\/p>\s*$/i', static fn (array $match): string => $suffix . $match[0], $heading)
          : $heading . $suffix;
      }
      if (empty($input['formatted_summary']['value']) && !empty($input['summary'])) {
        $input['formatted_summary'] = ['value' => $to_html($input['summary']), 'format' => $format];
      }
      $uuid = $item['uuid'];
      foreach (['secondary' => 'outline', 'primary' => 'filled'] as $prefix => $variant) {
        $label = $input[$prefix . '_label'] ?? '';
        $url = $input[$prefix . '_url'] ?? [];
        if (is_string($url)) {
          $url = ['uri' => $url, 'options' => []];
        }
        if (!isset($desktop_slots[$uuid]) && $label !== '' && !empty($url['uri'])) {
          $child_uuid = $button_uuids[$uuid][$variant] ??= \Drupal::service('uuid')->generate();
          $buttons[] = [
            'uuid' => $child_uuid,
            'parent_uuid' => $uuid,
            'slot' => 'desktop_actions',
            'component_id' => 'sdc.sfa_theme.action-link',
            'component_version' => $versions['sdc.sfa_theme.action-link'],
            'inputs' => json_encode(['label' => $label, 'url' => $url, 'variant' => $variant], JSON_THROW_ON_ERROR),
          ];
        }
        unset($input[$prefix . '_label'], $input[$prefix . '_url']);
      }
      unset($input['heading'], $input['heading_second_line'], $input['heading_level'], $input['summary']);
    }
    elseif ($id === 'sdc.sfa_theme.audience-grid') {
      if (empty($input['formatted_heading']['value']) && !empty($input['heading'])) {
        $input['formatted_heading'] = ['value' => $to_html($input['heading']), 'format' => $format];
      }
      unset($input['heading']);
    }
    elseif ($item['component_version'] !== $versions[$id] && !isset($input['width'])) {
      $input['width'] = 'full-boxed';
    }
    if ($before !== $input) {
      $item['inputs'] = json_encode($input, JSON_THROW_ON_ERROR);
    }
    $item['component_version'] = $versions[$id];
    $updated[] = $item;
    array_push($updated, ...$buttons);
  }
  return $updated;
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
