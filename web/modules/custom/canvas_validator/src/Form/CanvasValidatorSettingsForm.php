<?php

declare(strict_types=1);

namespace Drupal\canvas_validator\Form;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class CanvasValidatorSettingsForm extends ConfigFormBase {

  public const string CONFIG_NAME = 'canvas_validator.settings';

  public function __construct(
    private readonly AiProviderPluginManager $aiProviderManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('ai.provider'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'canvas_validator_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(self::CONFIG_NAME);
    $options = $this->aiProviderManager->getSimpleProviderModelOptions('chat', TRUE, TRUE);

    $form['provider_model'] = [
      '#type' => 'select',
      '#title' => $this->t('AI provider and model'),
      '#options' => $options,
      '#default_value' => $config->get('provider_model') ?? '',
      '#description' => $this->t('Leave empty to use the AI module default provider/model for chat.'),
    ];

    $form['prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Validation prompt'),
      '#default_value' => $config->get('prompt') ?? '',
      '#rows' => 10,
      '#description' => $this->t('Use {{content}} as a placeholder for the rendered HTML. If omitted, the HTML is appended automatically.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->configFactory->getEditable(self::CONFIG_NAME)
      ->set('provider_model', (string) $form_state->getValue('provider_model'))
      ->set('prompt', (string) $form_state->getValue('prompt'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
