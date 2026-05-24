<?php

namespace Drupal\hr_vacancies\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\hr_vacancies\Service\VacancyFetcher;

/**
 * Provides a 'HR Vacancies' Block.
 *
 * @Block(
 *   id = "hr_vacancies_block",
 *   admin_label = @Translation("HR Vacancies"),
 *   category = @Translation("Anklagemyndigheden"),
 * )
 */
class HrVacanciesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The vacancy fetcher service.
   *
   * @var \Drupal\hr_vacancies\Service\VacancyFetcher
   */
  protected VacancyFetcher $vacancyFetcher;

  /**
   * Constructs a new HrVacanciesBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\hr_vacancies\Service\VacancyFetcher $vacancy_fetcher
   *   The vacancy fetcher service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, VacancyFetcher $vacancy_fetcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->vacancyFetcher = $vacancy_fetcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('hr_vacancies.fetcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $html = $this->vacancyFetcher->fetchVacancies();

    return [
      '#theme' => 'hr_vacancies_block',
      '#html' => $html,
      '#attached' => [
        'library' => [
          'hr_vacancies/hr_vacancies',
        ],
      ],
      '#cache' => [
      // Cache for 1 hour.
        'max-age' => 3600,
      ],
    ];
  }

}
