<?php
/**
 * @file
 * Class that would be used for getting tree of Pages to be migrated into Drupal CT.
 */

namespace Drupal\ymca_migrate\Plugin\migrate;


use Drupal\Core\Database\Connection;
use Drupal\migrate\Row;

/**
 * Class YmcaBlogTree.
 *
 * @package Drupal\ymca_migrate
 */
class YmcaPageTree extends AmmCtTree {

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Row that is processed within a Tree.
   *
   * @var \Drupal\migrate\Row
   */
  protected $row;

  /**
   * Tree of components.
   *
   * $var array
   */
  protected $tree;

  /**
   * @var \Drupal\ymca_migrate\Plugin\migrate\AmmCtTree
   */
  static private $instance;

  /**
   * YmcaBlogTree constructor.
   *
   * @param array $skipIds
   *   Array of IDs to be skipped.
   * @param \Drupal\Core\Database\Connection $database
   *   SqlBase plugin for dealing with DB.
   * @param \Drupal\migrate\Row $row
   *   Row that is processed within a Tree
   *
   * @return \Drupal\ymca_migrate\Plugin\migrate\YmcaPageTree $this
   *   Returns itself.
   */
  protected function __construct($skipIds, Connection $database, Row $row) {
    $this->database = $database;
    $this->row = $row;
    $this->tree = [];
    parent::__construct('page', $skipIds);
  }

  /**
   * {@inheritdoc}
   */
  public function getTree() {

    // Some pages have NULL title, so create one.
    if (!$this->row->getSourceProperty('page_title')) {
      $this->row->setSourceProperty('page_title', t('Title'));
    }

    // Get all component data.
    $select = $this->database->select('amm_site_page_component', 'c');
    $select->fields('c')
      ->condition(
        'site_page_id',
        $this->row->getSourceProperty('site_page_id')
      );
    $select->orderBy('content_area_index', 'ASC');
    $select->orderBy('sequence_index', 'ASC');
    $components = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);

    // Write parents.
    foreach ($components as $item) {
      if (is_null($item['parent_component_id'])) {
        $components_tree[$item['site_page_component_id']] = $item;
      }
    }

    // Write children.
    foreach ($components as $item) {
      if (!is_null($item['parent_component_id'])) {
        $components_tree[$item['parent_component_id']]['children'][$item['site_page_component_id']] = $item;
      }
    }

    foreach ($components as $item) {
      if (is_null($item['parent_component_id'])) {
        $this->tree[$item['blog_post_component_id']] = $item;
      }
      else {
        $this->tree[$item['parent_component_id']]['children'][$item['blog_post_component_id']] = $item;
      }
    }

    // @todo Sort components withing the same area by weight.
    return $this->tree;
  }

  /**
   * {@inheritdoc}
   */
  static public function init($skipIds, Connection $database, Row $row) {
    if (isset(self::$instance)) {
      return self::$instance;
    }
    return new self($skipIds, $database, $row);
  }
}
