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
      '#markup' => $this->t('Free to use. Grab your keys from the Redeyed Lab: <strong>Sentinel → Sites</strong>. The Secret Key is shown only once when you create the site. Until both keys are set the widget stays inert and forms are never blocked.'),
    ];

    $form['site_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site key (public)'),
      '#description' => $this->t('Public key used to render the widget. Safe to expose.'),
      '#default_value' => (string) $config->get('site_key'),
      '#maxlength' => 255,
    ];

    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret key'),
      '#description' => $this->t('Secret key used only for server-side verification. Keep it private. Shown once in the Redeyed Lab.'),
      '#default_value' => (string) $config->get('secret_key'),
      '#maxlength' => 255,
    ];

    $form['base_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Base URL'),
      '#description' => $this->t('Sentinel service base URL. Leave as the default unless instructed otherwise.'),
      '#default_value' => (string) ($config->get('base_url') ?: 'https://redeyed.com'),
      '#maxlength' => 255,
    ];

    $form['appearance'] = [
      '#type' => 'details',
      '#title' => $this->t('Widget customization (optional)'),
      '#description' => $this->t('All optional. Leave any field empty to use the Sentinel widget defaults.'),
      '#open' => FALSE,
    ];

    $form['appearance']['widget'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Widget type'),
      '#description' => $this->t('Widget style, e.g. <code>behavioral</code>, <code>checkbox</code>, <code>press_hold</code> or <code>image_pick</code>. Leave empty for the default.'),
      '#default_value' => (string) $config->get('widget'),
      '#maxlength' => 255,
    ];

    $form['appearance']['theme'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Theme'),
      '#description' => $this->t('Widget theme: <code>auto</code>, <code>light</code> or <code>dark</code>. Leave empty for the default.'),
      '#default_value' => (string) $config->get('theme'),
      '#maxlength' => 255,
    ];

    $form['appearance']['scheme'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Colour scheme'),
      '#description' => $this->t('Named colour scheme for the widget. Leave empty for the default.'),
      '#default_value' => (string) $config->get('scheme'),
      '#maxlength' => 255,
    ];

    $form['appearance']['difficulty'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Difficulty'),
      '#description' => $this->t('Optional minimum challenge strength: <code>easy</code>, <code>medium</code>, <code>hard</code>, <code>max</code> (or <code>1</code>–<code>6</code>). This only <strong>raises</strong> challenge strength above the adaptive baseline; it never lowers it. Leave empty for the default.'),
      '#default_value' => (string) $config->get('difficulty'),
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
      ->set('secret_key', trim((string) $form_state->getValue('secret_key')))
      ->set('base_url', $base_url)
      ->set('widget', trim((string) $form_state->getValue('widget')))
      ->set('theme', trim((string) $form_state->getValue('theme')))
      ->set('scheme', trim((string) $form_state->getValue('scheme')))
      ->set('difficulty', trim((string) $form_state->getValue('difficulty')))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
