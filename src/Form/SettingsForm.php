<?php

declare(strict_types=1);

namespace Drupal\redeyed_sentinel\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Redeyed Sentinel CAPTCHA keys.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The settings config name.
   */
  const SETTINGS = 'redeyed_sentinel.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'redeyed_sentinel_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [self::SETTINGS];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(self::SETTINGS);

    $form['intro'] = [
      '#markup' => $this->t('Free to use. Grab your keys from the Redeyed Lab: <strong>Developer → Sentinel → Sites + API Keys</strong>. Until both keys are set the widget stays inert and forms are never blocked.'),
    ];

    $form['site_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site key (public)'),
      '#description' => $this->t('Public key used to render the widget. Safe to expose.'),
      '#default_value' => (string) $config->get('site_key'),
      '#maxlength' => 255,
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key (secret)'),
      '#description' => $this->t('Secret key used only for server-side verification. Keep it private.'),
      '#default_value' => (string) $config->get('api_key'),
      '#maxlength' => 255,
    ];

    $form['base_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Base URL'),
      '#description' => $this->t('Sentinel service base URL. Leave as the default unless instructed otherwise.'),
      '#default_value' => (string) ($config->get('base_url') ?: 'https://redeyed.com'),
      '#maxlength' => 255,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $base_url = rtrim(trim((string) $form_state->getValue('base_url')), '/');
    if ($base_url === '') {
      $base_url = 'https://redeyed.com';
    }

    $this->config(self::SETTINGS)
      ->set('site_key', trim((string) $form_state->getValue('site_key')))
      ->set('api_key', trim((string) $form_state->getValue('api_key')))
      ->set('base_url', $base_url)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
