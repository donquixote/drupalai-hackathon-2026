<?php

declare(strict_types=1);

namespace Drupal\team8\Hook;

use Drupal\canvas\Entity\Page;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Hook\Attribute\Hook;

class CanvasPageEntityType {

  /**
   * Implements hook_entity_base_field_info_alter().
   */
  #[Hook('entity_base_field_info')]
  public function entityTypeAlter(EntityTypeInterface $entity_type) {
    // Change this to your entity type ID.
    if ($entity_type->id() !== Page::ENTITY_TYPE_ID) {
      return [];
    }

    $fields = [];
    $fields['preview_image'] = BaseFieldDefinition::create('image')
      ->setName('preview_image')
      ->setLabel(t('Preview image'))
      ->setDescription(t('A preview image used in the template explorer for cloning.'))
      ->setRequired(FALSE)

      // File settings
      ->setSetting('file_directory', 'canvas-page-preview-images')
      ->setSetting('file_extensions', 'png jpg jpeg gif')
      ->setSetting('max_filesize', '2 MB')
      ->setSetting('alt_field', TRUE)
      ->setSetting('alt_field_required', FALSE)
      ->setSetting('title_field', FALSE)

      // Display settings
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => 10,
        'settings' => [
          'progress_indicator' => 'throbber',
          'preview_image_style' => 'thumbnail',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)

      ->setDisplayOptions('view', [
        'type' => 'image',
        'label' => 'hidden',
        'weight' => 10,
        'settings' => [
          'image_style' => 'large',
          'image_link' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
