<?php

namespace Drupal\nyhedsbrev_ubivox\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a subscribe form for Ubivox newsletter subscriptions.
 *
 * @ingroup nyhedsbrev_ubivox
 */
class SubcribeForm extends FormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a SubcribeForm object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(LanguageManagerInterface $language_manager, RequestStack $request_stack) {
    $this->languageManager = $language_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'subcribe_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Disable html 5 validation er hardcoded i themes/forside/templates/system/form.html.twig
    // $form['#attributes']['novalidate'] = 'novalidate';.
    $form['abonnementer'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Subscribe to news'),
    ];

    $form['abonnementer']['ubivox_lister'] = [
      '#type' => 'fieldset',
    ];
    $form['abonnementer']['ubivox_lister']['landsdaekkende_nyheder_fra_anklagemyndigheden'] = [
      '#type' => 'checkbox',
      '#title' => $this
        ->t('National news from the Prosecution Service'),
    ];

    /*
    $form['abonnementer']['ubivox_lister']['nyheder_fra_soeik'] = array(
    '#type' => 'checkbox',
    '#title' => $this
    ->t('News from the State Prosecutor for Serious Economic and International Crime'),
    );
     */

    $form['abonnementer']['ubivox_lister']['aarsrapporter_bekendtgoerelser_og_publikationer'] = [
      '#type' => 'checkbox',
      '#title' => $this
        ->t('Annual reports, executive orders and publications'),
    ];

    $form['abonnementer']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#title_display' => 'inline',
          // '#placeholder'=> $this->t('Email'),
      '#required' => TRUE,
      '#attributes' => ['autocomplete' => 'email'],
      '#prefix' => '<span class="label">' . $this->t('E-mail') . '</span>',
    ];

    $form['abonnementer']['navn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#title_display' => 'inline',
          // '#placeholder' => $this->t('Name'),
      '#required' => TRUE,
      '#attributes' => ['autocomplete' => 'name'],
      '#prefix' => '<span class="label">' . $this->t('Name') . '</span>',
    ];
    $form['abonnementer']['organisation'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organization'),
      '#title_display' => 'inline',
          // '#placeholder' => $this->t('Organization'),
      '#attributes' => ['autocomplete' => 'organization'],
      '#prefix' => '<span class="label">' . $this->t('Organization') . '</span>',
    ];
    // Actions.
    $form['abonnementer']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Subscribe'),
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

    $landsdaekkende_nyheder_fra_anklagemyndigheden = $form_state->getValue('landsdaekkende_nyheder_fra_anklagemyndigheden');
    // $nyheder_fra_soeik = $form_state->getValue('nyheder_fra_soeik');
    $aarsrapporter_bekendtgoerelser_og_publikationer = $form_state->getValue('aarsrapporter_bekendtgoerelser_og_publikationer');
    // If ($landsdaekkende_nyheder_fra_anklagemyndigheden == 0 && $nyheder_fra_soeik == 0 && $aarsrapporter_bekendtgoerelser_og_publikationer == 0) {.
    if ($landsdaekkende_nyheder_fra_anklagemyndigheden == 0 && $aarsrapporter_bekendtgoerelser_og_publikationer == 0) {
      $form_state->setErrorByName('ubivox_lister', $this->t('Please Select at Least One List to Subscribe.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $landsdaekkende_nyheder_fra_anklagemyndigheden = $form_state->getValue('landsdaekkende_nyheder_fra_anklagemyndigheden');
    // $nyheder_fra_soeik = $form_state->getValue('nyheder_fra_soeik');
    $aarsrapporter_bekendtgoerelser_og_publikationer = $form_state->getValue('aarsrapporter_bekendtgoerelser_og_publikationer');

    $email = $form_state->getValue('email');
    $navn = $form_state->getValue('navn');
    $organisation = $form_state->getValue('organisation');
    $language = $this->languageManager->getCurrentLanguage()->getId();

    // Husk backslash pga klasse udenfor namespace.
    $ubivox_config = $this->config('nyhedsbrev_ubivox.settings');
    $client = new \UbivoxAPI(
      $ubivox_config->get('ubivox_username'),
      $ubivox_config->get('ubivox_password'),
      $ubivox_config->get('ubivox_url')
    );

    // Ubivox lister.
    $list_id_landsdaekkende_nyheder = (int) $ubivox_config->get('list_id_landsdaekkende_nyheder');
    $list_id_TEST_landsdaekkende_nyheder = (int) $ubivox_config->get('list_id_test_landsdaekkende_nyheder');

    $list_id_aarsrapporter_bekendtgoerelser_publikationer = (int) $ubivox_config->get('list_id_aarsrapporter');
    $list_id_TEST_aarsrapporter_bekendtgoerelser_publikationer = (int) $ubivox_config->get('list_id_test_aarsrapporter');

    $allerede_tilmeldt = FALSE;
    $host = $this->requestStack->getCurrentRequest()->getHost();

    // Tilmeld 1-3 lister.
    if ($host == "anklagemyndigheden.dk") {
      // Produktion.
      if ($landsdaekkende_nyheder_fra_anklagemyndigheden == 1) {
        try {
          // Using opt-in to list ID for user@example.com.
          $client->call("ubivox.create_subscription", [
            $email,
            $list_id_landsdaekkende_nyheder,
            TRUE,
          ]);
        }
        catch (\UbivoxAPIError $e) {
          if ($e->getCode() == 1003) {
            // Already subscribed - handled below.
          }
          $this->messenger()->addMessage($e->getMessage(), 'status');
        }
      }

      /*
      if ($nyheder_fra_soeik == 1) {
      try {
      // Using opt-in to list ID for user@example.com
      $client->call("ubivox.create_subscription", [
      $email,
      $list_id_nyheder_soeik,
      true
      ]);
      } catch (\UbivoxAPIError $e) {
      if ($e->getCode() == 1003) {
      // \Drupal::messenger()->addMessage(t('You are already subscribed"'), 'status');
      }
      // \Drupal::messenger()->addMessage(t($e->getMessage()), 'status');
      }
      }
       */

      if ($aarsrapporter_bekendtgoerelser_og_publikationer == 1) {
        try {
          // Using opt-in to list ID for user@example.com.
          $client->call("ubivox.create_subscription", [
            $email,
            $list_id_aarsrapporter_bekendtgoerelser_publikationer,
            TRUE,
          ]);
        }
        catch (\UbivoxAPIError $e) {
          if ($e->getCode() == 1003) {
            // Already subscribed - handled below.
          }
        }
      }
      // If ($landsdaekkende_nyheder_fra_anklagemyndigheden == 1 || $nyheder_fra_soeik == 1 || ...) {.
      if ($landsdaekkende_nyheder_fra_anklagemyndigheden == 1 || $aarsrapporter_bekendtgoerelser_og_publikationer == 1) {
        try {
          $client->call("ubivox.set_subscriber_data", [
            $email,
            ["Navn" => $navn, "Organisation" => $organisation, "Sprog" => $language],
          ]);
        }
        catch (\UbivoxAPIError $e) {
          // You can use your own error message, by checking the $e->getCode() parameter
          // Or use the one Ubivox supplies for you, available in $e->getMessage().
          $this->messenger()->addMessage($e->getMessage(), 'status');
        }
      }
    }
    else {
      // Localhost og TEST server.
      $list_array = [];
      if ($landsdaekkende_nyheder_fra_anklagemyndigheden == 1) {
        array_push($list_array, $list_id_TEST_landsdaekkende_nyheder);
      }

      /*
      if ($nyheder_fra_soeik == 1) {
      array_push($list_array, $list_id_TEST_nyheder_soeik);

      // try {
      // Not using opt-in to list ID for user@example.com
      $client->call("ubivox.create_subscription", [
      $email,
      $list_id_TEST_nyheder_soeik,
      true
      ]);
      } catch (\UbivoxAPIError $e) {
      if ($e->getCode() == 1003) {
      \Drupal::messenger()->addMessage(t('You are already subscribed"'), 'status');
      }
      \Drupal::messenger()->addMessage(t($e->getMessage()), 'status');
      }
      }
       */

      if ($aarsrapporter_bekendtgoerelser_og_publikationer == 1) {
        array_push($list_array, $list_id_TEST_aarsrapporter_bekendtgoerelser_publikationer);
      }
      try {
        // Using opt-in to list ID for user@example.com.
        $client->call("ubivox.create_subscription", [
          $email,
          $list_array,
          TRUE,
        ]);
      }
      catch (\UbivoxAPIError $e) {
        if ($e->getCode() == 1003) {
          $allerede_tilmeldt = TRUE;
        }
        else {
          $this->messenger()->addMessage($e->getMessage(), 'status');
        }
      }

      // If ($landsdaekkende_nyheder_fra_anklagemyndigheden == 1 || $nyheder_fra_soeik == 1 || ...) {.
      if ($landsdaekkende_nyheder_fra_anklagemyndigheden == 1 || $aarsrapporter_bekendtgoerelser_og_publikationer == 1) {
        try {
          $client->call("ubivox.set_subscriber_data", [
            $email,
            ["Navn" => $navn, "Organisation" => $organisation, "Sprog" => $language],
          ]);
        }
        catch (\UbivoxAPIError $e) {
          // You can use your own error message, by checking the $e->getCode() parameter
          // Or use the one Ubivox supplies for you, available in $e->getMessage().
          $this->messenger()->addMessage($e->getMessage(), 'status');
          $this->logger('nyhedsbrev_ubivox')->error('Error setting subscriber data: @message', [
            '@message' => $e->getMessage(),
          ]);
        }
      }
    }

    $this->logger('nyhedsbrev_ubivox')->info('Nyhedsbrev tilmeldt: %email | %navn | %organisation | %sprog', [
      '%email' => $email,
      '%navn' => $navn,
      '%organisation' => $organisation,
      '%sprog' => $language,
    ]);
    if ($allerede_tilmeldt == TRUE) {
      $this->messenger()->addMessage($this->t('You are already subscribed.'), 'status');
    }
    else {
      // Dobbelt opt in.
      $this->messenger()->addMessage($email . ' ' . $this->t('have received a confirmation email. We ask for your confirmation to protect you from receiving unwanted email. If you do not respond, your email address will NOT be added to this list.'), 'status');
    }
  }

}
