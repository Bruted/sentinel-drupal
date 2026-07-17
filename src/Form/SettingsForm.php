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

    $form['forms'] = [
      '#type' => 'details',
      '#title' => $this->t('Protected forms'),
      '#description' => $this->t('Choose which forms show and verify the Sentinel widget. Login and registration are on by default.'),
      '#open' => TRUE,
    ];

    $form['forms']['enable_login'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Login form'),
      '#default_value' => (bool) ($config->get('enable_login') ?? TRUE),
    ];

    $form['forms']['enable_register'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Registration form'),
      '#default_value' => (bool) ($config->get('enable_register') ?? TRUE),
    ];

    $form['forms']['enable_lostpassword'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Password reset (lost password) form'),
      '#default_value' => (bool) ($config->get('enable_lostpassword') ?? FALSE),
    ];

    $form['forms']['enable_contact'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Contact forms (site-wide &amp; personal)'),
      '#default_value' => (bool) ($config->get('enable_contact') ?? FALSE),
    ];

    $form['forms']['enable_logging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log blocked attempts'),
      '#description' => $this->t('Record each blocked submission (form, IP, outcome) to the log. View them at <em>Reports → Recent log messages</em>, type <code>redeyed_sentinel</code>.'),
      '#default_value' => (bool) ($config->get('enable_logging') ?? TRUE),
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

    $form['appearance']['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#description' => $this->t('Optional width for the widget container, e.g. <code>full</code>, <code>100%</code> or <code>340px</code>. Leave empty for the default.'),
      '#default_value' => (string) $config->get('width'),
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
      ->set('enable_login', (bool) $form_state->getValue('enable_login'))
      ->set('enable_register', (bool) $form_state->getValue('enable_register'))
      ->set('enable_lostpassword', (bool) $form_state->getValue('enable_lostpassword'))
      ->set('enable_contact', (bool) $form_state->getValue('enable_contact'))
      ->set('enable_logging', (bool) $form_state->getValue('enable_logging'))
      ->set('widget', trim((string) $form_state->getValue('widget')))
      ->set('theme', trim((string) $form_state->getValue('theme')))
      ->set('scheme', trim((string) $form_state->getValue('scheme')))
      ->set('difficulty', trim((string) $form_state->getValue('difficulty')))
      ->set('width', trim((string) $form_state->getValue('width')))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
