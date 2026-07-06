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
      '#type' => 'select',
      '#title' => $this->t('Widget type'),
      '#description' => $this->t('Which challenge widget to render. Leave on <em>Server default</em> to let Sentinel choose.'),
      '#options' => [
        '' => $this->t('Server default'),
        'behavioral' => $this->t('Behavioral'),
        'pow' => $this->t('Proof of work'),
        'text_math' => $this->t('Text / math'),
        'image_puzzle' => $this->t('Image puzzle'),
        'rotate_align' => $this->t('Rotate & align'),
        'press_hold' => $this->t('Press & hold'),
      ],
      '#default_value' => (string) $config->get('widget'),
    ];

    $form['appearance']['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Theme'),
      '#description' => $this->t('Widget colour theme.'),
      '#options' => [
        'auto' => $this->t('Auto (match visitor)'),
        'light' => $this->t('Light'),
        'dark' => $this->t('Dark'),
      ],
      '#default_value' => (string) ($config->get('theme') ?: 'auto'),
    ];

    $form['appearance']['scheme'] = [
      '#type' => 'select',
      '#title' => $this->t('Colour scheme'),
      '#description' => $this->t('Named colour scheme for the widget.'),
      '#options' => [
        'default' => $this->t('Default'),
        'ocean' => $this->t('Ocean'),
        'forest' => $this->t('Forest'),
        'sunset' => $this->t('Sunset'),
        'graphite' => $this->t('Graphite'),
        'royalty' => $this->t('Royalty'),
        'ruby' => $this->t('Ruby'),
        'hacker' => $this->t('Hacker'),
        'monochrome' => $this->t('Monochrome'),
        'midnight' => $this->t('Midnight'),
        'aurora' => $this->t('Aurora'),
      ],
      '#default_value' => (string) ($config->get('scheme') ?: 'default'),
    ];

    $form['appearance']['difficulty'] = [
      '#type' => 'select',
      '#title' => $this->t('Difficulty'),
      '#description' => $this->t('Minimum challenge strength. <em>Adaptive</em> lets Sentinel scale difficulty to risk; a fixed level only <strong>raises</strong> the baseline, never lowers it.'),
      '#options' => [
        '' => $this->t('Adaptive'),
        'easy' => $this->t('Easy'),
        'medium' => $this->t('Medium'),
        'hard' => $this->t('Hard'),
        'max' => $this->t('Max'),
      ],
      '#default_value' => (string) $config->get('difficulty'),
    ];

    $form['appearance']['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#description' => $this->t('Widget width, e.g. <code>full</code>, <code>100%</code> or <code>340px</code>. Leave empty for the default.'),
      '#default_value' => (string) $config->get('width'),
      '#maxlength' => 64,
    ];

    $form['appearance']['form'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form key'),
      '#description' => $this->t('Optional form identifier passed to Sentinel for per-form analytics. Leave empty unless instructed.'),
      '#default_value' => (string) $config->get('form'),
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
      ->set('width', trim((string) $form_state->getValue('width')))
      ->set('form', trim((string) $form_state->getValue('form')))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
