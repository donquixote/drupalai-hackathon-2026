<?php

declare(strict_types=1);

namespace Drupal\team8\Hook;

use Drupal\canvas\Entity\Page;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\file\Entity\File;
use Gotenberg\Gotenberg;

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





    // Example HTML source – replace with your real canvas HTML.
    $url = 'https://www.google.com';

    // Call Gotenberg and generate an image.
    $response = Gotenberg::chromium('http://gotenberg:3000')
      ->screenshot()
      ->url($url);

    $binary = $response->getBody()->getContents();

    if (empty($binary)) {
      return;
    }

    $file_system = \Drupal::service('file_system');
    $file_repository = \Drupal::service('file.repository');

    $directory = 'public://canvas_page_previews';
    $file_system->prepareDirectory(
      $directory,
      FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
    );

    $filename = sprintf(
      'canvas_page_preview_%d_%d.png',
      $page->id(),
      \Drupal::time()->getRequestTime()
    );

    $destination = $directory . '/' . $filename;

    // Write image returned by Gotenberg.
    $image = $file_repository->writeData(
      $binary,
      $destination,
      FileSystemInterface::EXISTS_REPLACE
    );

    $image->setPermanent();
    $image->save();







//    $image->save();
  }

}
