<?php

namespace Drupal\openy_gc_auth;

use Drupal\Core\Database\Database;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\node\NodeInterface;

/**
 * Class GCAuthManager for common methods.
 */
class GCAuthManager {

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * GCAuthManager constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler service.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * Check if provided paragraph exists on the node.
   */
  public function checkIfParagraphAtNode(NodeInterface $node, $paragraph_id) {
    $connection = Database::getConnection();

    $result = $connection->select('paragraphs_item_field_data', 'pd')
      ->fields('pd', ['id'])
      ->condition('pd.parent_id', $node->id())
      ->condition('pd.type', $paragraph_id)
      ->countQuery()
      ->execute()
      ->fetchCol();
    return reset($result);
  }

  /**
   * Check if Paragraph or Block with id "gated_content" is present in the node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node to check the paragraph/block for.
   *
   * @return bool
   *   TRUE if paragraph or block with "gated_content" id exists.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function gatedContentExists(NodeInterface $node):bool {
    return $this->gatedContentParagraphBlockExists($node, "gated_content");
  }

  /**
   * Check if Paragraph or Block with id "gated_content_login" is present.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node to check the paragraph/block for.
   *
   * @return bool
   *   TRUE if paragraph/block with id "gated_content_login" exists.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function gatedContentLoginExists(NodeInterface $node):bool {
    return $this->gatedContentParagraphBlockExists($node, "gated_content_login");
  }

  /**
   * Helper method to determine if paragraph or block with given id exists.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node to check the existence of paragraph or block.
   * @param string $id
   *   The id of the block/paragraph.
   *
   * @return bool
   *   TRUE if the paragraph or block exists.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function gatedContentParagraphBlockExists(NodeInterface $node, string $id):bool {
    $result = $this->checkIfParagraphAtNode($node, $id);

    // Paragraph is present. No need to check for block.
    if ($result) {
      return TRUE;
    }

    // The layout builder is not used, the block is not going to be found.
    if (!$this->moduleHandler->moduleExists('layout_builder')) {
      return FALSE;
    }

    // Given content type doesn't have layout builder enabled for it.
    if (!$node->hasField('layout_builder__layout')) {
      return FALSE;
    }

    $sections = $node->get('layout_builder__layout')->getValue();
    foreach ($sections as $delta => $section) {
      /** @var \Drupal\layout_builder\Section $section */
      $section = $section['section'];

      foreach ($section->getComponents() as $component) {
        // The block usually has id like "inline_block:gated_content".
        if ($component->getPluginId() === "inline_block:$id") {
          return TRUE;
        }
      }
    }

    // Neither paragraph nor block are present if the code got to this point.
    return FALSE;
  }

}
