<?php
namespace Drupal\gazzet_notices\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Gazzet Notices routes.
 */
class GazzetNoticesController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function info(Request $request) {
    // Prevent caching of this page
    $build = [
      '#cache' => [
        'max-age' => 0,
      ],
      '#type' => 'container',
      '#attributes' => ['class' => ['gazzet-notices-container']],
    ];
    
    try {
      $page = $this->getCurrentPage($request);
      $apiData = $this->fetchApiData($page);
      
      // Add the table
      $build['table'] = $this->buildTable($apiData);
      
      // Add the pagination
      $build['pagination'] = $this->buildPagination($apiData, $page);
      
      return $build;
    }
    catch (RequestException $e) {
      $build['error'] = [
        '#type' => 'markup',
        '#markup' => $this->t('Error fetching data from API: @error', ['@error' => $e->getMessage()]),
        '#prefix' => '<div class="messages messages--error">',
        '#suffix' => '</div>',
      ];
      return $build;
    }
    catch (\Exception $e) {
      $build['error'] = [
        '#type' => 'markup',
        '#markup' => $this->t('An error occurred: @error', ['@error' => $e->getMessage()]),
        '#prefix' => '<div class="messages messages--error">',
        '#suffix' => '</div>',
      ];
      return $build;
    }
  }

  /**
   * Get current page from request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return int
   *   Current page number.
   */
  protected function getCurrentPage(Request $request): int {
    // Get the page parameter from the request query
    $page = $request->query->get('results-page', 1);
    
    // Ensure it's a positive integer
    return max(1, (int) $page);
  }

  /**
   * Fetch data from API.
   *
   * @param int $page
   *   Page number to fetch.
   *
   * @return array
   *   Decoded JSON response.
   *
   * @throws \GuzzleHttp\Exception\RequestException
   */
  protected function fetchApiData(int $page): array {
    $client = \Drupal::httpClient();
    $response = $client->get('https://www.thegazette.co.uk/all-notices/notice/data.json', [
      'query' => ['results-page' => $page],
      'verify' => FALSE,
    ]);
    
    $data = (string) $response->getBody();
    $json_data = json_decode($data, TRUE);
    
    if ($json_data === NULL) {
      throw new \Exception('Error decoding JSON response.');
    }
    
    return $json_data;
  }

  /**
   * Build table render array from API data.
   *
   * @param array $apiData
   *   API response data.
   *
   * @return array
   *   Table render array.
   */
  protected function buildTable(array $apiData): array {
    $entries = $apiData['entry'] ?? [];
    
    $header = [
      'ID' => $this->t('ID'),
      'Title' => $this->t('Title'),
      'Status' => $this->t('Status'),
    ];
    
    $rows = [];
    foreach ($entries as $entry) {
      // Extract the ID from the full URL
      $id = $entry['id'] ?? '';
      $id_parts = explode('/', $id);
      $short_id = end($id_parts);
      
      $rows[] = [
        'ID' => $short_id,
        'Title' => $entry['title'] ?? '',
        'Status' => $entry['f:status'] ?? '',
      ];
    }
    
    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No notices found.'),
      '#attributes' => [
        'style' => 'width: 100%; border-collapse: collapse; margin-bottom: 20px;',
      ],
      '#prefix' => '<div style="overflow-x:auto;">',
      '#suffix' => '</div>',
    ];
  }

  /**
   * Build pagination render array.
   *
   * @param array $apiData
   *   API response data.
   * @param int $currentPage
   *   Current page number.
   *
   * @return array
   *   Pagination render array.
   */
  protected function buildPagination(array $apiData, int $currentPage): array {
    // Extract pagination info from the response using the correct keys
    $totalResults = 0;
    $itemsPerPage = 10; // Default value
    $totalPages = 1; // Default value
    
    // Try to get pagination data from the 'feed' section first
    if (isset($apiData['feed'])) {
      $feedData = $apiData['feed'];
      
      // Get total results
      if (isset($feedData['f:total'])) {
        $totalResults = (int)$feedData['f:total'];
      }
      
      // Get items per page
      if (isset($feedData['f:page-size'])) {
        $itemsPerPage = (int)$feedData['f:page-size'];
      }
      
      // Get current page number
      if (isset($feedData['f:page-number'])) {
        $currentPage = (int)$feedData['f:page-number'];
      }
    }
    
    // If we didn't find the data in 'feed', try direct access
    if ($totalResults === 0 && isset($apiData['f:total'])) {
      $totalResults = (int)$apiData['f:total'];
    }
    
    if ($itemsPerPage === 10 && isset($apiData['f:page-size'])) {
      $itemsPerPage = (int)$apiData['f:page-size'];
    }
    
    // Calculate total pages
    if ($itemsPerPage > 0) {
      $totalPages = ceil($totalResults / $itemsPerPage);
    }
    
    // Build pagination links
    $pagination = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['pagination-container'],
        'style' => 'display: flex; justify-content: space-between; align-items: center; margin: 20px 0; padding: 10px; background-color: #f5f5f5; border-radius: 4px;',
      ],
    ];
    
    // Previous button
    if ($currentPage > 1) {
      $pagination['prev'] = [
        '#type' => 'link',
        '#title' => $this->t('Previous'),
        '#url' => Url::fromUri('internal:/gazzet-notices/info', ['query' => ['results-page' => $currentPage - 1]]),
        '#attributes' => [
          'class' => ['button', 'button--primary'],
          'style' => 'padding: 8px 16px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; display: inline-block; margin-right: 10px;',
        ],
      ];
    }
    
    // Current page indicator
    $pagination['current'] = [
      '#type' => 'markup',
      '#markup' => '<div style="padding: 0 10px; font-weight: bold; font-size: 16px; text-align: center; flex-grow: 1;">' . 
                   $this->t('Page @page of @total', ['@page' => $currentPage, '@total' => $totalPages]) . 
                   '</div>',
    ];
    
    // Next button
    if ($currentPage < $totalPages) {
      $pagination['next'] = [
        '#type' => 'link',
        '#title' => $this->t('Next'),
        '#url' => Url::fromUri('internal:/gazzet-notices/info', ['query' => ['results-page' => $currentPage + 1]]),
        '#attributes' => [
          'class' => ['button', 'button--primary'],
          'style' => 'padding: 8px 16px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; display: inline-block; margin-left: 10px;',
        ],
      ];
    }
    
    return $pagination;
  }
}
?>