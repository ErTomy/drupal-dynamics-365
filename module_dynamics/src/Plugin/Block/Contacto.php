<?php

namespace Drupal\module_dynamics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormInterface;


/**
 * Provides a 'Contacto' block.
 *
 * @Block(
 * id = "Contacto",
 * admin_label = @Translation("Formulario contacto"),
 * )
 */

class Contacto extends BlockBase {

  /**
   * {@inheritdoc}
   */



   public function build() {

    $form = \Drupal::formBuilder()->getForm('Drupal\module_dynamics\Form\ContactoForm');

    return array('#theme' => 'contacto',
                 '#info'=>$block->body->value,
                 '#formulario' => $form);
   }
}
