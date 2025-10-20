<?php

namespace Drupal\yse_views_stratify\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service for stratifying views queries based on display machine names.
 */
class ViewsStratifyService {

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Static cache for stratified entity IDs per request.
   *
   * @var array
   */
  protected static $stratifiedIdsCache = [];

  /**
   * Static cache for views that have stratification enabled.
   *
   * @var array
   */
  protected static $stratificationEnabledCache = [];

  /**
   * Constructs a ViewsStratifyService object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(
    LoggerChannelFactoryInterface $logger_factory,
    RequestStack $request_stack,
  ) {
    $this->loggerFactory = $logger_factory;
    $this->requestStack = $request_stack;
  }

  /**
   * Alters a view query to apply stratification based on display names.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view being altered.
   * @param \Drupal\views\Plugin\views\query\QueryPluginBase $query
   *   The query plugin for the view.
   */
  public function alterQuery(ViewExecutable $view, QueryPluginBase $query) {
    // Check if stratification should be applied to this view.
    if (!$this->shouldApplyStratification($view)) {
      return;
    }

    $display_id = $view->current_display;

    // Validate display name for conflicts.
    try {
      $this->validateDisplay($display_id);
    }
    catch (\InvalidArgumentException $e) {
      $this->loggerFactory->get('yse_views_stratify')->error(
        'Invalid display configuration: @message',
        ['@message' => $e->getMessage()]
      );
      return;
    }

    // Determine if this is an exclusive or remainder display.
    $is_exclusive = $this->isExclusiveDisplay($display_id);
    $is_remainder = $this->isRemainderDisplay($display_id);

    // Skip if this display doesn't need stratification.
    if (!$is_exclusive && !$is_remainder) {
      return;
    }

    // Get the stratified entity IDs.
    $entity_ids = $this->getStratifiedEntityIds($view);

    // Apply the appropriate filter.
    if ($is_exclusive) {
      $this->applyExclusiveFilter($query, $entity_ids);
    }
    elseif ($is_remainder) {
      $this->applyRemainderFilter($query, $entity_ids);
    }
  }

  /**
   * Adds appropriate cache tags to stratified views.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view being rendered.
   */
  public function addCacheTags(ViewExecutable $view) {
    // Check if stratification is applied to this view.
    if (!$this->shouldApplyStratification($view)) {
      return;
    }

    $display_id = $view->current_display;
    $is_exclusive = $this->isExclusiveDisplay($display_id);
    $is_remainder = $this->isRemainderDisplay($display_id);

    // Only add cache tags to stratified displays.
    if (!$is_exclusive && !$is_remainder) {
      return;
    }

    // Add view config cache tag.
    $view->element['#cache']['tags'][] = 'config:views.view.' . $view->id();

    // Add node list tag if this is a node-based view.
    if (isset($view->storage) && $view->storage->get('base_table') === 'node_field_data') {
      $view->element['#cache']['tags'][] = 'node_list';
    }

  }

  /**
   * Determines if stratification should be applied to a view.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to check.
   *
   * @return bool
   *   TRUE if stratification should be applied, FALSE otherwise.
   */
  protected function shouldApplyStratification(ViewExecutable $view): bool {
    $view_id = $view->id();

    // Check static cache first.
    if (isset(static::$stratificationEnabledCache[$view_id])) {
      return static::$stratificationEnabledCache[$view_id];
    }

    // Check if the view has an embed_stratify_query display.
    $displays = $view->storage->get('display');
    $has_stratify_query = isset($displays['embed_stratify_query']);

    // Validate that it's actually an embed display.
    if ($has_stratify_query) {
      $display_plugin = $displays['embed_stratify_query']['display_plugin'] ?? NULL;
      if ($display_plugin !== 'embed') {
        $this->loggerFactory->get('yse_views_stratify')->warning(
          'Display embed_stratify_query in view @view_id must be of type Embed. Found: @type',
          [
            '@view_id' => $view_id,
            '@type' => $display_plugin ?? 'unknown',
          ]
        );
        $has_stratify_query = FALSE;
      }
    }

    // Cache the result.
    static::$stratificationEnabledCache[$view_id] = $has_stratify_query;

    return $has_stratify_query;
  }

  /**
   * Checks if a display is an exclusive rows display.
   *
   * @param string $display_id
   *   The display machine name.
   *
   * @return bool
   *   TRUE if the display contains 'exclusive_rows', FALSE otherwise.
   */
  protected function isExclusiveDisplay(string $display_id): bool {
    return strpos($display_id, 'exclusive_rows') !== FALSE;
  }

  /**
   * Checks if a display is a remainder rows display.
   *
   * @param string $display_id
   *   The display machine name.
   *
   * @return bool
   *   TRUE if the display contains 'remainder_rows', FALSE otherwise.
   */
  protected function isRemainderDisplay(string $display_id): bool {
    return strpos($display_id, 'remainder_rows') !== FALSE;
  }

  /**
   * Validates a display for conflicting stratification markers.
   *
   * @param string $display_id
   *   The display machine name.
   *
   * @throws \InvalidArgumentException
   *   If the display has both exclusive_rows and remainder_rows markers.
   */
  protected function validateDisplay(string $display_id): void {
    if ($this->isExclusiveDisplay($display_id) && $this->isRemainderDisplay($display_id)) {
      throw new \InvalidArgumentException(
        sprintf('Display %s has conflicting stratification markers (both exclusive_rows and remainder_rows)', $display_id)
      );
    }
  }

  /**
   * Gets the stratified entity IDs from the embed_stratify_query display.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The current view.
   *
   * @return array
   *   Array of entity IDs to stratify.
   */
  protected function getStratifiedEntityIds(ViewExecutable $view): array {
    // Get contextual arguments FIRST before generating cache key.
    $args = $this->getContextualArguments($view);

    // Generate cache key based on the actual args we're using.
    $cache_key = $this->getCacheKey($view, $args);

    // Check static cache first.
    if (isset(static::$stratifiedIdsCache[$cache_key])) {
      return static::$stratifiedIdsCache[$cache_key];
    }

    try {
      // Load the same view fresh to execute the stratify query.
      $stratify_view = Views::getView($view->id());

      if (!$stratify_view) {
        $this->loggerFactory->get('yse_views_stratify')->warning(
          'Could not load view @view_id for stratification',
          ['@view_id' => $view->id()]
        );
        return [];
      }

      // Set the embed_stratify_query display.
      if (!$stratify_view->setDisplay('embed_stratify_query')) {
        $this->loggerFactory->get('yse_views_stratify')->warning(
          'Could not set embed_stratify_query display for view @view_id',
          ['@view_id' => $view->id()]
        );
        return [];
      }

      // If we have arguments, try to inherit contextual filter configuration
      // from the current display to the stratify query.
      if (!empty($args)) {
        $this->inheritContextualFilters($view, $stratify_view, $args);
      }

      // Execute the stratify view.
      $stratify_view->execute();

      // Extract entity IDs from results.
      $entity_ids = [];
      foreach ($stratify_view->result as $row) {
        // Try direct ID properties first.
        if (isset($row->nid)) {
          $entity_ids[] = $row->nid;
        }
        elseif (isset($row->uid)) {
          $entity_ids[] = $row->uid;
        }
        elseif (isset($row->tid)) {
          $entity_ids[] = $row->tid;
        }
        // Fallback to entity object.
        elseif (isset($row->_entity) && $row->_entity->id()) {
          $entity_ids[] = $row->_entity->id();
        }
      }

      // Cache the result for this request.
      static::$stratifiedIdsCache[$cache_key] = $entity_ids;

      return $entity_ids;

    }
    catch (\Exception $e) {
      $this->loggerFactory->get('yse_views_stratify')->error(
        'Error executing stratify query: @message',
        ['@message' => $e->getMessage()]
      );
      return [];
    }
  }

  /**
   * Inherits contextual filter configuration from source to target view.
   *
   * @param \Drupal\views\ViewExecutable $source_view
   *   The view to copy contextual filters from.
   * @param \Drupal\views\ViewExecutable $target_view
   *   The view to add contextual filters to.
   * @param array $args
   *   The arguments to pass to the target view.
   */
  protected function inheritContextualFilters(ViewExecutable $source_view, ViewExecutable $target_view, array $args): void {
    // Get contextual filter handlers from the source display.
    $source_arguments = $source_view->display_handler->getHandlers('argument');

    if (empty($source_arguments)) {
      return;
    }

    // Get existing arguments from the target view.
    $target_arguments = $target_view->display_handler->getHandlers('argument');

    // Only inherit if target has no arguments configured, or if we want to
    // override them. For now, we'll add them dynamically to the query instead.
    // This is safer than modifying the display handler configuration.

    // Apply the contextual filters by directly modifying the query in
    // hook_views_query_alter. For now, we'll pass arguments and let Views
    // handle it, but we need to ensure the stratify query has matching filters.
    // If it doesn't, we can add them to the query directly.
    $target_view->setArguments($args);

    // If target has no contextual filters configured, we need to apply them
    // directly to the query. We'll do this by adding a query alter.
    if (empty($target_arguments) && !empty($source_arguments)) {
      // Store the filter info so we can apply it in the query alter.
      $target_view->yse_inherited_filters = [
        'filters' => $source_arguments,
        'args' => $args,
      ];
    }
  }

  /**
   * Gets contextual arguments for a view, handling nested view contexts.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to get arguments for.
   *
   * @return array
   *   Array of contextual arguments.
   */
  protected function getContextualArguments(ViewExecutable $view): array {
    // Start with the view's own args (set via preview() or setArguments()).
    $args = $view->args ?? [];

    if (!empty($args)) {
      return $args;
    }

    // If view has no args but is nested (has parent views), try to get args
    // from the request. This handles cases where the view area handler's
    // inherit_arguments setting might not have been applied yet.
    if (empty($args) && !empty($view->parent_views)) {
      $request = $this->requestStack->getCurrentRequest();
      if ($request) {
        // Try to extract arguments from route parameters.
        $route_params = $request->attributes->get('_raw_variables');
        if ($route_params) {
          $args = array_values($route_params->all());
        }
      }
    }

    return $args;
  }

  /**
   * Generates a cache key for stratified entity IDs.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view.
   * @param array $args
   *   The contextual arguments being used.
   *
   * @return string
   *   The cache key.
   */
  protected function getCacheKey(ViewExecutable $view, array $args = []): string {
    $args_hash = md5(serialize($args));
    return "stratify:{$view->id()}:{$args_hash}";
  }

  /**
   * Applies an exclusive filter to show only stratified entities.
   *
   * @param \Drupal\views\Plugin\views\query\QueryPluginBase $query
   *   The query to alter.
   * @param array $entity_ids
   *   Array of entity IDs to include.
   */
  protected function applyExclusiveFilter(QueryPluginBase $query, array $entity_ids): void {
    // Ensure we're working with a SQL query.
    if (!method_exists($query, 'addWhere')) {
      return;
    }

    // Get the base table and field.
    $base_table = $query->view->storage->get('base_table');
    $base_field = $this->getBaseField($base_table);

    if (empty($entity_ids)) {
      // No results - add impossible condition.
      $query->addWhere(0, "{$base_table}.{$base_field}", NULL, 'IS NULL');
      $query->addWhere(0, "{$base_table}.{$base_field}", NULL, 'IS NOT NULL');
    }
    else {
      // Add IN condition for the entity IDs.
      $query->addWhere(0, "{$base_table}.{$base_field}", $entity_ids, 'IN');
    }
  }

  /**
   * Applies a remainder filter to exclude stratified entities.
   *
   * @param \Drupal\views\Plugin\views\query\QueryPluginBase $query
   *   The query to alter.
   * @param array $entity_ids
   *   Array of entity IDs to exclude.
   */
  protected function applyRemainderFilter(QueryPluginBase $query, array $entity_ids): void {
    // Ensure we're working with a SQL query.
    if (!method_exists($query, 'addWhere')) {
      return;
    }

    // If there are no entity IDs, don't add any filter (show all results).
    if (empty($entity_ids)) {
      return;
    }

    // Get the base table and field.
    $base_table = $query->view->storage->get('base_table');
    $base_field = $this->getBaseField($base_table);

    // Add NOT IN condition for the entity IDs.
    $query->addWhere(0, "{$base_table}.{$base_field}", $entity_ids, 'NOT IN');
  }

  /**
   * Gets the base field name for a given base table.
   *
   * @param string $base_table
   *   The base table name.
   *
   * @return string
   *   The base field name (e.g., 'nid', 'uid', 'tid').
   */
  protected function getBaseField(string $base_table): string {
    // Map common base tables to their ID fields.
    $field_map = [
      'node_field_data' => 'nid',
      'users_field_data' => 'uid',
      'taxonomy_term_field_data' => 'tid',
      'media_field_data' => 'mid',
      'comment_field_data' => 'cid',
    ];

    return $field_map[$base_table] ?? 'id';
  }

}
