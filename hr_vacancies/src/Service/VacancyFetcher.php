<?php

namespace Drupal\hr_vacancies\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\Markup;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Service to generate HR Manager vacancy HTML.
 */
class VacancyFetcher {

  /**
   * The old HR Manager iframe URL (statens_erekruttering).
   * Hide by adding .hr-vacancies-old { display: none; } when no longer needed.
   */
  const API_URL_OLD = 'https://candidate.hr-manager.net/vacancies/list.aspx?customer=statens_erekruttering&departmentid=6139';

  /**
   * RSS feed for the new HR Manager system.
   */
  const RSS_URL_NEW = 'https://recruiter-api.hr-manager.net/jobportal.svc/statensrekrutteringsloesning_tr/positionlist/rss/?depid=20171&incads=1';

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new VacancyFetcher.
   */
  public function __construct(ClientInterface $http_client, LoggerChannelFactoryInterface $logger_factory) {
    $this->httpClient = $http_client;
    $this->logger = $logger_factory->get('hr_vacancies');
  }

  /**
   * Get the HR Manager vacancy HTML (old iframe + new RSS list).
   *
   * @return \Drupal\Core\Render\Markup
   *   HTML for embedding.
   */
  public function fetchVacancies(): Markup {
    $html = '';

    // Old iframe (transition period).
    $html .= $this->buildOldIframe();

    // New: RSS-rendered vacancies.
    $html .= $this->buildNewVacanciesFromRss();

    return Markup::create($html);
  }

  /**
   * Builds the old iframe HTML.
   */
  protected function buildOldIframe(): string {
    $unique_id = 'hrv-old-' . uniqid();

    $html  = '<div class="hr-vacancies-old">';
    $html .= '<div class="hr-vacancies-iframe-wrapper" id="' . $unique_id . '">';
    $html .= '  <div class="hr-vacancies-spinner" id="spinner-' . $unique_id . '">';
    $html .= '    <div class="spinner"></div>';
    $html .= '  </div>';
    $html .= '  <iframe';
    $html .= '    id="iframe-' . $unique_id . '"';
    $html .= '    class="hr-vacancies-iframe"';
    $html .= '    src="' . self::API_URL_OLD . '"';
    $html .= '    frameborder="0"';
    $html .= '    scrolling="no"';
    $html .= '    title="Ledige stillinger (gammelt system)"';
    $html .= '    data-wrapper-id="' . $unique_id . '"';
    $html .= '  ></iframe>';
    $html .= '</div>';
    $html .= '</div>';

    return $html;
  }

  /**
   * Fetches the new vacancies from RSS and renders them as native HTML.
   */
  protected function buildNewVacanciesFromRss(): string {
    $items = $this->fetchRssItems();

    $html = '<div class="hr-vacancies-new">';

    if (empty($items)) {
      $html .= '<p class="hr-vacancies-empty">Ingen ledige stillinger i øjeblikket.</p>';
    }
    else {
      foreach ($items as $item) {
        $html .= $this->renderVacancyItem($item);
      }
    }

    $html .= '</div>';

    // Job agent + applicant login footer.
    $html .= '<div class="hr-vacancies-footer">';
    $html .= '  <div class="hr-vacancies-footer-item">';
    $html .= '    <h3>Tilmeld jobagent</h3>';
    $html .= '    <p>Ønsker du at blive informeret, når der dukker jobmuligheder op i Anklagemyndigheden.</p>';
    $html .= '    <a class="hr-vacancies-footer-link" href="https://candidate.hr-manager.net/Agent/Subscription.aspx?customer=statensrekrutteringsloesning_tr&amp;departmentid=20171" target="_blank" rel="noopener">Registrér dig her</a>';
    $html .= '  </div>';
    $html .= '  <div class="hr-vacancies-footer-item">';
    $html .= '    <h3>Ansøger log ind</h3>';
    $html .= '    <p>Hvis du tidligere har søgt en stilling hos os, kan du logge ind her og opdatere dine oplysninger.</p>';
    $html .= '    <a class="hr-vacancies-footer-link" href="https://candidate.hr-manager.net/Login.aspx?customer=statensrekrutteringsloesning_tr&amp;departmentid=20171" target="_blank" rel="noopener">Log ind</a>';
    $html .= '  </div>';
    $html .= '</div>';

    return $html;
  }

  /**
   * Fetches and parses the RSS feed.
   *
   * @return array
   *   Array of vacancy item arrays.
   */
  protected function fetchRssItems(): array {
    try {
      $response = $this->httpClient->get(self::RSS_URL_NEW, [
        'timeout' => 10,
      ]);
      $xml = simplexml_load_string((string) $response->getBody());
    }
    catch (RequestException $e) {
      $this->logger->error('Failed to fetch RSS: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to parse RSS: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }

    if (!$xml || !isset($xml->channel->item)) {
      return [];
    }

    // Atom namespace for <a10:updated>.
    $atom_ns = 'http://www.w3.org/2005/Atom';

    $items = [];
    foreach ($xml->channel->item as $item) {
      $atom = $item->children($atom_ns);

      // Description is HTML-entity-encoded full job ad HTML.
      $description_html = html_entity_decode((string) $item->description, ENT_QUOTES, 'UTF-8');

      $items[] = [
        'title'       => (string) $item->title,
        'link'        => (string) $item->link,
        'description' => $description_html,
        'pubDate'     => (string) $item->pubDate,
        'updated'     => (string) ($atom->updated ?? ''),
      ];
    }

    return $items;
  }

  /**
   * Extracts plain text from the first N <p> elements of an HTML string.
   */
  protected function extractTeaser(string $html): string {
    if (empty($html)) {
      return '';
    }

    $dom = new \DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR);

    foreach ($dom->getElementsByTagName('p') as $p) {
      $text = trim($p->textContent);
      // Only use paragraphs that are at least one line long (>50 chars).
      if (strlen($text) > 50) {
        return $text . '…';
      }
    }

    return '';
  }

  /**
   * Renders a single vacancy item as HTML.
   */
  protected function renderVacancyItem(array $item): string {
    $title   = htmlspecialchars($item['title'] ?? '', ENT_QUOTES, 'UTF-8');
    $link    = htmlspecialchars($item['link'] ?? '#', ENT_QUOTES, 'UTF-8');
    $teaser  = htmlspecialchars($this->extractTeaser($item['description'] ?? ''), ENT_QUOTES, 'UTF-8');

    // Format pubDate.
    $published = '';
    if (!empty($item['pubDate'])) {
      try {
        $published = (new \DateTime($item['pubDate']))->format('d-m-Y');
      }
      catch (\Exception $e) {
        $published = $item['pubDate'];
      }
    }

    $html  = '<div class="hr-vacancy-item">';
    $html .= '  <a class="hr-vacancy-link" href="' . $link . '" target="_blank" rel="noopener">';
    $html .= '    <div class="hr-vacancy-title">' . $title . '</div>';
    if ($teaser) {
      $html .= '  <div class="hr-vacancy-teaser">' . $teaser . '</div>';
    }
    $html .= '    <div class="hr-vacancy-meta">';
    if ($published) {
      $html .= '<span class="hr-vacancy-published"><strong>Publiceret:</strong> ' . $published . '</span>';
    }
    $html .= '    </div>';
    $html .= '  </a>';
    $html .= '</div>';

    return $html;
  }

}
