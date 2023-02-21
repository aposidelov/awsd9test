<?php

namespace Drupal\coh2_custom\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for the tabledrag example.
 *
 * This controller only deals with the description path.
 */
class Coh2PagesController extends ControllerBase {

  public function description() {
    $build = [
      '#markup' => $this->t('Coh2 pages'),
    ];
    return $build;
  }

}
