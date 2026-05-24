<?php

namespace Drupal\nyhedsbrev_ubivox\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class UnsubcribeForm.
 *
 * @package Drupal\nyhedsbrev_ubivox\Form
 *
 * @ingroup nyhedsbrev_ubivox
 */
class UnsubcribeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unsubcribe_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // File.
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('E-mail:'),
      '#required' => TRUE,
    ];

    $form['ubivox_lister'] = [
      '#type' => 'fieldset',
    ];
    $form['ubivox_lister']['landsdaekkende_nyheder_fra_anklagemyndigheden'] = [
      '#type' => 'checkbox',
      '#title' => $this
        ->t('National news from the Prosecution Service'),
    ];

    /*
    $form['ubivox_lister']['nyheder_fra_soeik'] = [
    '#type' => 'checkbox',
    '#title' => $this
    ->t('News from the State Prosecutor for Serious Economic and International Crime'),
    ];
     */

    $form['ubivox_lister']['aarsrapporter_bekendtgoerelser_og_publikationer'] = [
      '#type' => 'checkbox',
      '#title' => $this
        ->t('Annual reports, executive orders and publications'),
    ];
    // Actions.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Unsubscribe'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if (!$form_state->getValue('email') || !filter_var($form_state->getValue('email'), FILTER_VALIDATE_EMAIL)) {
      $form_state->setErrorByName('email', $this->t('The email address is not valid.'));
    }
    if ($form_state->getValue('landsdaekkende_nyheder_fra_anklagemyndigheden') == 0 &&
        $form_state->getValue('aarsrapporter_bekendtgoerelser_og_publikationer') == 0) {
      $form_state->setErrorByName('ubivox_lister', $this->t('Please select at least one list to unsubscribe from.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Frameld alle lister på hhv produktion eller TEST
    // ikke global opt-out så man kan teste frameld uden at påvirke PRD tilmeldinger.
    $email = $form_state->getValue('email');
    $unsubscribed = FALSE;

    $antalAfkrydset = 0;
    $landsdaekkende_nyheder_fra_anklagemyndigheden = $form_state->getValue('landsdaekkende_nyheder_fra_anklagemyndigheden');
    // $nyheder_fra_soeik = $form_state->getValue('nyheder_fra_soeik');
    $aarsrapporter_bekendtgoerelser_og_publikationer = $form_state->getValue('aarsrapporter_bekendtgoerelser_og_publikationer');

    // $antalAfkrydset = $landsdaekkende_nyheder_fra_anklagemyndigheden + $nyheder_fra_soeik + $aarsrapporter_bekendtgoerelser_og_publikationer
    $antalAfkrydset = $landsdaekkende_nyheder_fra_anklagemyndigheden + $aarsrapporter_bekendtgoerelser_og_publikationer;

    // Unsubscribe in Ubivox
    // Check kode på IVÆKST: sites/all/themes/ivaekst3/includes/php/ubivox.php.
    // Husk backslash pga klasse udenfor namespace.
    $ubivox_config = \Drupal::config('nyhedsbrev_ubivox.settings');
    $client = new \UbivoxAPI(
      $ubivox_config->get('ubivox_username'),
      $ubivox_config->get('ubivox_password'),
      $ubivox_config->get('ubivox_url')
    );

    // Ubivox lister.
    $list_id_landsdaekkende_nyheder = (int) $ubivox_config->get('list_id_landsdaekkende_nyheder');
    $list_id_TEST_landsdaekkende_nyheder = (int) $ubivox_config->get('list_id_test_landsdaekkende_nyheder');

    // $list_id_nyheder_soeik = 48995;
    // $list_id_TEST_nyheder_soeik = 64326;
    $list_id_aarsrapporter_bekendtgoerelser_publikationer = (int) $ubivox_config->get('list_id_aarsrapporter');
    $list_id_TEST_aarsrapporter_bekendtgoerelser_publikationer = (int) $ubivox_config->get('list_id_test_aarsrapporter');

    // Check om email er tilmeldt mindst en liste. Adskilt på PRD og TEST/localhost.
    try {
      $response = $client->call('ubivox.get_subscriber',
        [$email]);
    }
    // Husk backslash pga namespace.
    catch (\UbivoxAPIError $e) {
      // Raises:    1001: Invalid e-mail address
      // betyder at email ikke findes.
      switch ($e->getCode()) {
        case 1001:
          \Drupal::logger('nyhedsbrev_ubivox')
            ->error('ubivoxApi error 1001 Email findes ikke eller er ugyldig');
          // \Drupal::messenger()->addMessage('ubivoxApi error 98', 'status');
          break;

        default:
          \Drupal::logger('nyhedsbrev_ubivox')->info('ubivoxApi error 134');
          // \Drupal::messenger()->addMessage('ubivoxApi error 1102', 'status');
          break;
      }

    }

    if (isset($response) && $response) {

      $antal_tilmeldte_lister = count($response['subscriptions']);
      $subscribed_lists = "";
      $active_lists = 0;

      if ($antal_tilmeldte_lister > 0) {
        foreach ($response['subscriptions'] as $lists) {
          if ($lists['state'] == 'active') {
            $active_lists++;
            $subscribed_lists .= $lists['list_title'] . ", ";

            if ($_SERVER['HTTP_HOST'] == "anklagemyndigheden.dk") {
              // Produktion.
              switch ($lists['list_id']) {
                case $list_id_landsdaekkende_nyheder:
                  if ($landsdaekkende_nyheder_fra_anklagemyndigheden == 1) {
                    try {
                      $client->call("ubivox.cancel_subscription", [
                        $email,
                        [
                          $list_id_landsdaekkende_nyheder,
                        ],
                      ]);
                    }
                    // Husk backslash pga namespace.
                    catch (\UbivoxAPIError $e) {
                      \Drupal::messenger()->addMessage($e->getCode() . " | " . $e->getMessage(), 'status');
                    }

                    \Drupal::logger('nyhedsbrev_ubivox')
                      ->info('@msg %email fra listen: %list_name',
                        [
                          '@msg' => 'Unsubscribed email:',
                          '%email' => $email,
                          '%list_name' => $lists['list_title'],
                        ]);
                    \Drupal::messenger()->addMessage(t('@email is now unsubscribed from @list', [
                      '@email' => $email,
                      '@list' => $lists['list_title'],
                    ]), 'status');

                    $unsubscribed = TRUE;
                  }
                  break;

                /*
                case $list_id_nyheder_soeik:
                if ($nyheder_fra_soeik == 1) {
                try {
                $client->call("ubivox.cancel_subscription", [
                $email,
                [
                $list_id_nyheder_soeik,
                ],
                ]);
                } // Husk backslash pga namespace
                catch (\UbivoxAPIError $e) {
                \Drupal::messenger()->addMessage($e->getCode() . " | " . $e->getMessage(), 'status');
                }

                \Drupal::logger('nyhedsbrev_ubivox')
                ->info('@msg %email fra listen: %list_name',
                [
                '@msg' => 'Unsubscribed email:',
                '%email' => $email,
                '%list_name' => $lists['list_title'],
                ]);
                \Drupal::messenger()->addMessage(t('@email is now unsubscribed from @list', [
                '@email' => $email,
                '@list' => $lists['list_title'],
                ]), 'status');

                $unsubscribed = TRUE;
                }
                break;
                 */

                case $list_id_aarsrapporter_bekendtgoerelser_publikationer:
                  if ($aarsrapporter_bekendtgoerelser_og_publikationer == 1) {
                    try {
                      $client->call("ubivox.cancel_subscription", [
                        $email,
                        [
                          $list_id_aarsrapporter_bekendtgoerelser_publikationer,
                        ],
                      ]);
                    }
                    // Husk backslash pga namespace.
                    catch (\UbivoxAPIError $e) {
                      \Drupal::messenger()->addMessage($e->getCode() . " | " . $e->getMessage(), 'status');
                    }

                    \Drupal::logger('nyhedsbrev_ubivox')
                      ->info('@msg %email fra listen: %list_name',
                        [
                          '@msg' => 'Unsubscribed email:',
                          '%email' => $email,
                          '%list_name' => $lists['list_title'],
                        ]);
                    \Drupal::messenger()->addMessage(t('@email is now unsubscribed from @list', [
                      '@email' => $email,
                      '@list' => $lists['list_title'],
                    ]), 'status');

                    $unsubscribed = TRUE;
                  }
                  break;
              }
            }
            else {
              // TEST og localhost.
              switch ($lists['list_id']) {
                case $list_id_TEST_landsdaekkende_nyheder:
                  if ($landsdaekkende_nyheder_fra_anklagemyndigheden == 1) {
                    try {
                      $client->call("ubivox.cancel_subscription", [
                        $email,
                        [
                          $list_id_TEST_landsdaekkende_nyheder,
                        ],
                      ]);
                    }
                    // Husk backslash pga namespace.
                    catch (\UbivoxAPIError $e) {
                      \Drupal::messenger()->addMessage($e->getCode() . " | " . $e->getMessage(), 'status');
                    }
                    \Drupal::logger('nyhedsbrev_ubivox')
                      ->info('@msg %email fra listen: %list_name',
                        [
                          '@msg' => 'Unsubscribed email:',
                          '%email' => $email,
                          '%list_name' => $lists['list_title'],
                        ]);
                    \Drupal::messenger()->addMessage(t('@email is now unsubscribed from @list', [
                      '@email' => $email,
                      '@list' => $lists['list_title'],
                    ]), 'status');

                    $unsubscribed = TRUE;
                  }
                  break;

                /*
                case $list_id_TEST_nyheder_soeik:
                if ($nyheder_fra_soeik == 1) {
                try {
                $client->call("ubivox.cancel_subscription", [
                $email,
                [
                $list_id_TEST_nyheder_soeik,
                ],
                ]);
                } // Husk backslash pga namespace
                catch (\UbivoxAPIError $e) {
                \Drupal::messenger()->addMessage($e->getCode() . " | " . $e->getMessage(), 'status');
                }

                \Drupal::logger('nyhedsbrev_ubivox')
                ->info('@msg %email fra listen: %list_name',
                [
                '@msg' => 'Unsubscribed email:',
                '%email' => $email,
                '%list_name' => $lists['list_title'],
                ]);
                \Drupal::messenger()->addMessage(t('@email is now unsubscribed from @list', [
                '@email' => $email,
                '@list' => $lists['list_title'],
                ]), 'status');

                $unsubscribed = TRUE;
                }
                break;
                 */

                case $list_id_TEST_aarsrapporter_bekendtgoerelser_publikationer:
                  if ($aarsrapporter_bekendtgoerelser_og_publikationer == 1) {
                    try {
                      $client->call("ubivox.cancel_subscription", [
                        $email,
                        [
                          $list_id_TEST_aarsrapporter_bekendtgoerelser_publikationer,
                        ],
                      ]);
                    }
                    // Husk backslash pga namespace.
                    catch (\UbivoxAPIError $e) {
                      \Drupal::messenger()->addMessage($e->getCode() . " | " . $e->getMessage(), 'status');
                    }

                    \Drupal::logger('nyhedsbrev_ubivox')
                      ->info('@msg %email fra listen: %list_name',
                        [
                          '@msg' => 'Unsubscribed email:',
                          '%email' => $email,
                          '%list_name' => $lists['list_title'],
                        ]);
                    \Drupal::messenger()->addMessage(t('@email is now unsubscribed from @list', [
                      '@email' => $email,
                      '@list' => $lists['list_title'],
                    ]), 'status');

                    $unsubscribed = TRUE;

                  }
                  break;
              }
            }
          }
        }
      }
    }

    \Drupal::logger('nyhedsbrev_ubivox')
      ->info('Email %email har %antal_aktive_lister aktive lister: %subscribed_lists',
        [
          '%email' => $form_state->getValue('email'),
          '%antal_aktive_lister' => $active_lists,
          '%subscribed_lists' => $subscribed_lists,

        ]);

    if ($unsubscribed == FALSE) {
      \Drupal::messenger()->addMessage($email . ' ' . t(' was not subscribed to selected newsletters from anklagemyndigheden.dk'), 'status');
    }
  }

}
