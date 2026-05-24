<?php

namespace Drupal\hr_vacancies\Plugin\Filter;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Provides a filter to render HR Vacancies block via shortcode.
 *
 * @Filter(
 *   id = "hr_vacancies_shortcode",
 *   title = @Translation("HR Vacancies Shortcode"),
 *   description = @Translation("Allows embedding HR Vacancies block using [hr_vacancies] shortcode."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   weight = 10
 * )
 */
class HrVacanciesShortcode extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The block plugin manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a HrVacanciesShortcode object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block plugin manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RendererInterface $renderer, BlockManagerInterface $block_manager, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->renderer = $renderer;
    $this->blockManager = $block_manager;
    $this->logger = $logger_factory->get('hr_vacancies');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer'),
      $container->get('plugin.manager.block'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    // Replace [hr_vacancies] with the actual block content.
    if (strpos($text, '[hr_vacancies]') !== FALSE) {
      try {
        // Create an instance of our block.
        $plugin_block = $this->blockManager->createInstance('hr_vacancies_block', []);

        // Build the block.
        $build = $plugin_block->build();

        // Add block wrapper.
        $build['#theme_wrappers'][] = 'container';
        $build['#attributes']['class'][] = 'block';
        $build['#attributes']['class'][] = 'block-hr-vacancies';

        // Render the block.
        $rendered = $this->renderer->renderInIsolation($build);

        // Remove empty <p> tags Drupal's line break filter wraps around
        // the shortcode.
        $pattern = '/<p[^>]*>\s*\[hr_vacancies]\s*<\/p>/i';
        $text = preg_replace($pattern, '[hr_vacancies]', $text);

        // Replace the shortcode with the rendered content.
        $text = str_replace('[hr_vacancies]', $rendered, $text);

        // Strip empty <p> or <p>&nbsp;</p> tags left before/after
        // the block.
        $text = preg_replace('/<p[^>]*>(\s|&nbsp;)*<\/p>/i', '', $text);
        $result->setProcessedText($text);

        // Attach the library so CSS/JS loads.
        $result->addAttachments([
          'library' => [
            'hr_vacancies/hr_vacancies',
          ],
        ]);
      }
      catch (\Exception $e) {
        $this->logger->error('Error rendering HR Vacancies shortcode: @message', [
          '@message' => $e->getMessage(),
        ]);
        $text = str_replace('[hr_vacancies]', '<div class="error">Error loading HR Vacancies. Check logs for details.</div>', $text);
        $result->setProcessedText($text);
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Use [hr_vacancies] to display the HR Vacancies block.');
  }

}
