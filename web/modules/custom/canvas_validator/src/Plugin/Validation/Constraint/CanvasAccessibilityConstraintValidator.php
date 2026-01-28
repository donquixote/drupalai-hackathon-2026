<?php

declare(strict_types=1);

namespace Drupal\canvas_validator\Plugin\Validation\Constraint;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class CanvasAccessibilityConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  public function __construct(
    private readonly AiProviderPluginManager $aiProviderManager,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly RendererInterface $renderer,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('ai.provider'),
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('config.factory'),
      $container->get('logger.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    if ($value === NULL) {
      return;
    }

    if (!$value instanceof FieldItemListInterface) {
      return;
    }

    $entity = $value->getEntity();
    if ($entity->getEntityTypeId() !== 'canvas_page') {
      return;
    }

    if ($entity instanceof EntityPublishedInterface && !$entity->isPublished()) {
      return;
    }

    $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
    $render_array = $view_builder->view($entity, 'default');
    $rendered_content = trim((string) $this->renderer->renderPlain($render_array));
    if ($rendered_content === '') {
      return;
    }

    $option = (string) ($this->configFactory->get('canvas_validator.settings')->get('provider_model') ?? '');
    if ($option === '') {
      $option = $this->aiProviderManager->getSimpleDefaultProviderOptions('chat');
      if ($option === '') {
        $options = $this->aiProviderManager->getSimpleProviderModelOptions('chat', FALSE, TRUE);
        $option = array_key_first($options) ?: '';
      }
    }
    if ($option === '') {
      $this->loggerFactory->get('canvas_validator')->error('Canvas AI validator failed: no provider/model configured.');
      $this->context->buildViolation('Wait! AI provider is not configured for Canvas AI Validator.')->addViolation();
      return;
    }

    $provider = $this->aiProviderManager->loadProviderFromSimpleOption($option);
    $model_id = $this->aiProviderManager->getModelNameFromSimpleOption($option);
    if (!$provider || $model_id === '' || !$provider->isUsable('chat')) {
      $this->loggerFactory->get('canvas_validator')->error('Canvas AI validator failed: provider not usable. Option: {option}, Model: {model}.', [
        'option' => $option,
        'model' => $model_id,
      ]);
      $this->context->buildViolation('Wait! AI provider is not usable for Canvas AI Validator.')->addViolation();
      return;
    }

    $snippet = substr($rendered_content, 0, 4000);
    $prompt_template = trim((string) ($this->configFactory->get('canvas_validator.settings')->get('prompt') ?? ''));
    if ($prompt_template === '') {
      $this->context->buildViolation('Wait! Validation prompt is empty.')->addViolation();
      return;
    }
    if (str_contains($prompt_template, '{{content}}')) {
      $prompt = str_replace('{{content}}', $snippet, $prompt_template);
    }
    else {
      $prompt = $prompt_template . "\n\nCONTENT:\n" . $snippet;
    }

    try {
      $input = new ChatInput([
        new ChatMessage('user', $prompt),
      ]);
      $response = $provider->chat($input, $model_id)->getNormalized()->getText();
      $response_text = trim($response);

      if ($response_text !== 'TRUE') {
        $message = $response_text;
        if (!str_starts_with($message, 'Wait!')) {
          $message = 'Wait! ' . $message;
        }
        $this->context->buildViolation($message)->addViolation();
      }
    }
    catch (\Throwable $e) {
      $this->loggerFactory->get('canvas_validator')->error('Canvas AI validator exception: {message}', [
        'message' => $e->getMessage(),
      ]);
    }
  }

}
