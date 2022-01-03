<?php

namespace Drupal\openy_gc_shared_content_server;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\Derivative\DeriverInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\openy_gc_shared_content_server\Entity\SharedContentSource;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * SourceMigration Deriver Class.
 *
 * @package Drupal\openy_gc_shared_content
 */
class SourceMigrationDeriver extends DeriverBase implements DeriverInterface, ContainerDeriverInterface {

  /**
   * EntityQuery service instance.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $sharedContentStorage;

  /**
   * The currently active request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * SourceMigrationDeriver constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   EntityTypeManager service instance.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request stack.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManager $entityTypeManager, RequestStack $requestStack) {
    $this->sharedContentStorage = $entityTypeManager
      ->getStorage('shared_content_source')
      ->getQuery()
      ->condition('sync_enabled', 1);
    $this->request = $requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    $ids = $this->sharedContentStorage->execute();

    if (empty($ids)) {
      return [];
    }

    $sources = SharedContentSource::loadMultiple($ids);
    $urls = $tokens = [];
    foreach ($sources as $source) {
      $urls[$source->getUrl()] = $source->getApiUpdated();
      $tokens[$source->getUrl()] = $source->getToken();
    }

    $params = [
      'include' => implode(',', $this->getRemoteRelationshipsList($base_plugin_definition)),
      'sort[sortByDate][path]' => 'created',
      'sort[sortByDate][direction]' => 'DESC',
      'filter[status]' => 1,
      'filter[field_gc_share]' => 1,
      // Use 'XDEBUG_SESSION_START' => 'PHPSTORM' to test.
    ];

    $jsonapi_uri = '/jsonapi/node/' . $base_plugin_definition['source']['entity_type'] . '?' . http_build_query($params);
    $custom_uri = '/api/virtual-y/shared-content-source/' . $base_plugin_definition['source']['entity_type'];

    foreach ($urls as $url => $updated) {

      $url_long = $url . $jsonapi_uri;
      if ($updated) {
        $url_long = $url . $custom_uri;
      }
      $derivative = $this->getDerivativeValues($base_plugin_definition, $url_long, $url, $tokens[$url], $updated);
      $this->derivatives[$this->getKey($url)] = $derivative;
    }

    return $this->derivatives;
  }

  /**
   * Private method that dynamically updates migration source.
   *
   * @param array $base_plugin_definition
   *   Plugin settings.
   * @param string $url_long
   *   Url with request part.
   * @param string $url
   *   Dynamic url for every Virtual Y content source.
   * @param string $token
   *   Server check token.
   * @param bool $updated
   *   If the server api is updated.
   *
   * @return array
   *   Updated plugin data.
   */
  private function getDerivativeValues(array $base_plugin_definition, $url_long, $url, $token, $updated) {

    // @todo Add $updated logic here to modify the migrations.
    $base_plugin_definition['source']['urls'] = $url_long;
    $base_plugin_definition['source']['headers']['x-shared-referer'] = $this
      ->request
      ->getSchemeAndHttpHost();
    $base_plugin_definition['source']['headers']['authorization'] = 'Bearer ' . $token;

    if (!empty($base_plugin_definition['process']['field_gc_video_media'])) {
      $migration = str_replace(
        'REPLACE_ME',
        $this->getKey($url),
        $base_plugin_definition['process']['field_gc_video_media']['migration']
      );
      $base_plugin_definition['process']['field_gc_video_media']['migration'] = $migration;
      $base_plugin_definition['migration_dependencies']['required'] = [$migration];
    }

    if (!empty($base_plugin_definition['process']['field_vy_blog_image'])) {
      $migration = str_replace(
        'REPLACE_ME',
        $this->getKey($url),
        $base_plugin_definition['process']['field_vy_blog_image']['migration']
      );
      $base_plugin_definition['process']['field_vy_blog_image']['migration'] = $migration;
      $base_plugin_definition['migration_dependencies']['required'] = [$migration];
    }

    if (!empty($base_plugin_definition['process']['field_media_image'])) {
      $migration = str_replace(
        'REPLACE_ME',
        $this->getKey($url),
        $base_plugin_definition['process']['field_media_image']['migration']
      );
      $base_plugin_definition['process']['field_media_image']['migration'] = $migration;
      $base_plugin_definition['migration_dependencies']['required'] = [$migration];
    }

    if (!empty($base_plugin_definition['source']['constants']['DOMAIN'])) {
      $base_plugin_definition['source']['constants']['DOMAIN'] = $url;
    }

    if (!empty($base_plugin_definition['process']['field_gc_video_image'])) {
      $migration = str_replace(
        'REPLACE_ME',
        $this->getKey($url),
        $base_plugin_definition['process']['field_gc_video_image']['migration']
      );
      $base_plugin_definition['process']['field_gc_video_image']['migration'] = $migration;
      $base_plugin_definition['migration_dependencies']['required'][] = $migration;
    }

    if (!empty($base_plugin_definition['process']['field_gc_video_category'])) {
      $migration = str_replace(
        'REPLACE_ME',
        $this->getKey($url),
        $base_plugin_definition['process']['field_gc_video_category']['migration']
      );
      $base_plugin_definition['process']['field_gc_video_category']['migration'] = $migration;
      $base_plugin_definition['migration_dependencies']['required'][] = $migration;
    }

    if (!empty($base_plugin_definition['process']['field_gc_video_equipment'])) {
      $migration = str_replace(
        'REPLACE_ME',
        $this->getKey($url),
        $base_plugin_definition['process']['field_gc_video_equipment']['migration']
      );
      $base_plugin_definition['process']['field_gc_video_equipment']['migration'] = $migration;
      $base_plugin_definition['migration_dependencies']['required'][] = $migration;
    }

    if (!empty($base_plugin_definition['process']['field_gc_video_level'])) {
      $migration = str_replace(
        'REPLACE_ME',
        $this->getKey($url),
        $base_plugin_definition['process']['field_gc_video_level']['migration']
      );
      $base_plugin_definition['process']['field_gc_video_level']['migration'] = $migration;
      $base_plugin_definition['migration_dependencies']['required'][] = $migration;
    }

    // Rewrite fields for backwards-compatibility.
    if (!$updated) {
      if (isset($base_plugin_definition["source"]["fields"])) {
        $base_plugin_definition = $this->rewriteFieldSelectors($base_plugin_definition);
      }
    }

    return $base_plugin_definition;
  }

  /**
   * Helper function that checks if we need includes to jsonapi request.
   *
   * @param array $base_plugin_definition
   *   Migration array.
   *
   * @return array
   *   Includes array for JSON:API
   */
  private function getRemoteRelationshipsList(array $base_plugin_definition) {
    return !empty($base_plugin_definition['source']['json_includes']) ? $base_plugin_definition['source']['json_includes'] : [];
  }

  /**
   * Helper function that prepare entity key from url.
   *
   * @param string $url
   *   Url to source.
   *
   * @return mixed
   *   Machine name url for key.
   */
  private function getKey($url) {
    $url_key = str_replace('http://', '', $url);
    $url_key = str_replace('https://', '', $url_key);
    $url_key = str_replace(['.', '-'], '_', $url_key);
    return $url_key;
  }

  /**
   * Helper function to rewrite fields to old values.
   *
   * @param array $base_plugin_definition
   *   Migration array.
   *
   * @return array
   *   Updated plugin data.
   */
  private function rewriteFieldSelectors(array $base_plugin_definition) {
    $selectors = [
      '/attributes/changed' => '/changed/0/value',
      '/attributes/created' => '/created/0/value',
      '/attributes/drupal_internal__nid' => '/nid/0/value',
      '/attributes/field_gc_video_description/value' => '/field_gc_video_description/0/value',
      '/attributes/field_gc_video_duration' => '/field_gc_video_duration/0/value',
      '/attributes/field_gc_video_instructor' => '/field_gc_video_instructor/0/value',
      '/attributes/field_media_in_library' => '/field_media_in_library/0/value',
      '/attributes/field_media_source' => '/field_media_source/0/value',
      '/attributes/field_media_video_embed_field' => '/field_media_video_embed_field/0/value',
      '/attributes/field_media_video_id' => '/field_media_video_id/0/value',
      '/attributes/field_vy_blog_description/value' => '/field_vy_blog_description/0/value',
      '/attributes/filemime' => '/filemime/0/value',
      '/attributes/filename' => '/filename/0/value',
      '/attributes/name' => '/name/0/value',
      '/attributes/status' => '/status/0/value',
      '/attributes/title' => '/title/0/value',
      '/attributes/uri/url' => '/uri/0/url',
      '/id' => '/uuid/0/value',
      '/relationships/field_gc_video_category/data/id' => '/field_gc_video_category/0/target_uuid',
      '/relationships/field_gc_video_equipment/data/id' => '/field_gc_video_equipment/0/target_uuid',
      '/relationships/field_gc_video_image/data/id' => '/field_gc_video_image/0/target_uuid',
      '/relationships/field_gc_video_level/data/id' => '/field_gc_video_level/0/target_uuid',
      '/relationships/field_gc_video_media/data/id' => '/field_gc_video_media/0/target_uuid',
      '/relationships/field_media_image/data/id' => '/field_media_image/0/target_uuid',
      '/relationships/field_vy_blog_image/data/id' => '/field_vy_blog_image/0/target_uuid',
    ];
    $old_values = array_keys($selectors);
    $new_values = array_values($selectors);

    foreach ($base_plugin_definition["source"]["fields"] as $index => $field) {
      $replacement = str_replace($new_values, $old_values, $field['selector']);
      $base_plugin_definition["source"]["fields"][$index]['selector'] = $replacement;
    }

    return $base_plugin_definition;
  }

}
