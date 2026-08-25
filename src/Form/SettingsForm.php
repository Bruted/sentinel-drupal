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
   * Fetch the challenge types / themes / schemes this Sentinel deployment
   * accepts, from GET {base_url}/captcha/capabilities.
   *
   * Hardcoding these lists is what let a stale value like "checkbox" sit in
   * this module for months doing nothing: an unrecognised data-widget is not an
   * error, it silently falls back to the site default. Reading the list from
   * the server means new challenge types appear here without a module release.
   *
   * Cached for 12 hours. Fails soft: on any error the caller falls back to a
   * built-in list, so the settings form still works offline. No keys are sent —
   * the endpoint is public.
   *
   * @return array|null
   *   Decoded capabilities, or NULL when unavailable.
   */
  protected function fetchCapabilities(string $base_url): ?array {
    $cid = 'redeyed_sentinel:capabilities:' . md5($base_url);
    $cache = \Drupal::cache()->get($cid);
    if ($cache && is_array($cache->data)) {
      return $cache->data ?: NULL;
    }

    try {
      $response = \Drupal::httpClient()->get(rtrim($base_url, '/') . '/captcha/capabilities', [
        'timeout' => 5,
        'headers' => ['Accept' => 'application/json'],
      ]);
      $body = json_decode((string) $response->getBody(), TRUE);
    }
    catch (\Throwable $e) {
      $body = NULL;
    }

    if (!is_array($body) || empty($body['types']['concrete'])) {
      // Cache the failure briefly so a broken endpoint cannot make every
      // settings page load wait on a 5-second timeout.
      \Drupal::cache()->set($cid, [], time() + 300);
      return NULL;
    }

    \Drupal::cache()->set($cid, $body, time() + 43200);
    return $body;
  }

  /**
   * Keep a stored value selectable even when the server no longer lists it, so
   * opening this form can never silently change a site's configuration.
   */
  protected function preserveStored(array $options, string $current): array {
    if ($current !== '' && !isset($options[$current])) {
      $options[$current] = $this->t('@value (not currently offered)', ['@value' => $current]);
    }
    return $options;
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

    // Options come from the server so new challenge types and colour schemes
    // appear here without a module release. NULL means the fetch failed.
    $caps = $this->fetchCapabilities((string) $config->get('base_url') ?: 'https://redeyed.com');
    $offline_note = $caps === NULL
      ? ' ' . $this->t('<em>Showing a built-in list — the Sentinel server could not be reached.</em>')
      : '';

    $widget_options = [
      'adaptive' => $this->t('Adaptive — escalate by risk (recommended)'),
      'all' => $this->t('Random — a different type per visitor'),
    ];
    foreach ($caps['types']['concrete'] ?? ['behavioral', 'pow', 'press_hold', 'text_math', 'image_pick'] as $type) {
      $widget_options[$type] = $type;
    }

    $form['appearance']['widget'] = [
      '#type' => 'select',
      '#title' => $this->t('Widget type'),
      '#description' => $this->t('Which challenge the widget renders. <strong>Adaptive</strong> is recommended — it starts with a low-friction proof and escalates only when the risk score calls for it.') . $offline_note,
      '#options' => $this->preserveStored($widget_options, (string) $config->get('widget')),
      '#empty_option' => $this->t('Use the Sentinel default'),
      '#default_value' => (string) $config->get('widget'),
    ];

    $theme_options = [];
    foreach ($caps['themes'] ?? ['auto', 'light', 'dark'] as $theme) {
      $theme_options[$theme] = $theme === 'auto'
        ? $this->t('auto — follow the system setting')
        : $theme;
    }

    $form['appearance']['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Theme'),
      '#description' => $this->t('Colour theme for the widget.') . $offline_note,
      '#options' => $this->preserveStored($theme_options, (string) $config->get('theme')),
      '#empty_option' => $this->t('Use the Sentinel default'),
      '#default_value' => (string) $config->get('theme'),
    ];

    $scheme_options = [];
    if (!empty($caps['schemes'])) {
      foreach ($caps['schemes'] as $scheme) {
        if (empty($scheme['name'])) {
          continue;
        }
        $name = (string) $scheme['name'];
        // A premium scheme on a free plan silently renders as `default`, so say
        // so rather than offering a choice that quietly does nothing.
        $scheme_options[$name] = empty($scheme['premium'])
          ? $name
          : $this->t('@name (paid plans only)', ['@name' => $name]);
      }
    }
    else {
      foreach (['default', 'ocean', 'forest', 'sunset', 'graphite'] as $name) {
        $scheme_options[$name] = $name;
      }
    }

    $form['appearance']['scheme'] = [
      '#type' => 'select',
      '#title' => $this->t('Colour scheme'),
      '#description' => $this->t('Colour scheme for the widget.') . $offline_note,
      '#options' => $this->preserveStored($scheme_options, (string) $config->get('scheme')),
      '#empty_option' => $this->t('Use the Sentinel default'),
      '#default_value' => (string) $config->get('scheme'),
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
