<?php

declare(strict_types=1);

namespace Drupal\team8\Hook;

use Drupal\canvas\Entity\Page;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\file\Entity\File;

class CanvasPagePreviewImageUpdate {

  #[Hook('canvas_page_presave')]
  public function canvasPagePresave(Page $page): void {
    if (!$page->get('preview_image')->isEmpty()) {
      return;
    }

    $image = File::create();
    $image->setFileUri('core/misc/druplicon.png');
    $image->setFilename(basename($image->getFileUri()));
    $image->save();

    $page->set('preview_image', $image);
  }

  #[Hook('canvas_page_update')]
  #[Hook('canvas_page_insert')]
  public function canvasPageUpsert(Page $page): void {
    $image = $page->get('preview_image')->referencedEntities()[0] ?? NULL;
    if (!$image) {
      return;
    }
    $image->save();
  }

}
