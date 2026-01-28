<?php

declare(strict_types=1);

namespace Drupal\canvas_validator\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validates accessibility requirements using an AI provider.
 *
 * @Constraint(
 *   id = "CanvasAccessibilityConstraint",
 *   label = @Translation("Canvas accessibility validation")
 * )
 */
class CanvasAccessibilityConstraint extends Constraint {
}
