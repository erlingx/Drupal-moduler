<?php

namespace Drupal\erik_lab\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Config form for erik_lab settings.
 *
 * ConfigFormBase is the Drupal pattern for forms that read/write Config API.
 * Route: /erik-lab/form  (defined in erik_lab.routing.yml)
 *
 * Config key: erik_lab.settings
 * → Stored in DB config table, exportable via drush cex
 * → YML file: config/install/erik_lab.settings.yml  (default values)
 *
 * Drupal form API concepts demonstrated:
 *   - #type textfield, checkbox, select
 *   - #states (conditional visibility)
 *   - #required validation
 *   - Custom validateForm()
 *   - submitForm() writes to Config API
 */
class LabForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'erik_lab_settings_form';
  }

  /**
   * Config object(s) managed by this form.
   */
  protected function getEditableConfigNames(): array {
    return ['erik_lab.settings'];
  }

  /**
   * Build the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('erik_lab.settings');

    $form['greeting'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Greeting message'),
      '#default_value' => $config->get('greeting') ?? 'Hello from erik_lab!',
      '#description'   => $this->t('Stored in Config API (erik_lab.settings). Exportable via drush cex.'),
      '#required'      => TRUE,
      '#maxlength'     => 255,
    ];

    $form['enable_logging'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Enable verbose logging'),
      '#default_value' => $config->get('enable_logging') ?? FALSE,
    ];

    // #states — show extra field only when checkbox is checked.
    // Pure JS, no custom JS needed.
    $form['log_channel'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Log channel name'),
      '#default_value' => $config->get('log_channel') ?? 'erik_lab',
      '#states'        => [
        'visible' => [
          ':input[name="enable_logging"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Validate before saving.
   *
   * SetErrorByName() highlights the specific field in red.
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $greeting = $form_state->getValue('greeting');
    if (strlen($greeting) < 3) {
      $form_state->setErrorByName('greeting', $this->t('Greeting must be at least 3 characters.'));
    }
  }

  /**
   * Save to Config API.
   *
   * After submit: drush cex → check config/sync/erik_lab.settings.yml.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('erik_lab.settings')
      ->set('greeting', $form_state->getValue('greeting'))
      ->set('enable_logging', (bool) $form_state->getValue('enable_logging'))
      ->set('log_channel', $form_state->getValue('log_channel'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
