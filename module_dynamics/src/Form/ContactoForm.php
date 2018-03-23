<?php
/**
 * @file
 * Contains \Drupal\module_dynamics\Form\ContactoForm.
 */
namespace Drupal\module_dynamics\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformSubmissionForm;

use Drupal\module_dynamics\Form\Crm;

class ContactoForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'module_dynamics_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

      $form['nombre'] = array(
        '#type' => 'textfield',
        '#placeholder' => t('Name') . ' *',
        '#required' => TRUE,
        '#prefix'     => '<div class="small-12 medium-12 large-6 columns noPaddingLeft clear">',
        '#suffix'     => '</div>',
        '#theme_wrappers' => [],
      );

      $form['apellidos'] = array(
        '#type' => 'textfield',
        '#placeholder' => t('Surnames') . ' *',
        '#required' => TRUE,
        '#prefix'     => '<div class="small-12 medium-12 large-6 columns noPaddingRight">',
        '#suffix'     => '</div>',
        '#theme_wrappers' => [],
      );

      $form['email'] = array(
        '#type' => 'email',
        '#placeholder' => t('Email') . ' *',
        '#required' => TRUE,
        '#prefix'     => '<div class="small-12 columns noPadding">',
        '#suffix'     => '</div>',
        '#theme_wrappers' => [],
      );

      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Suscribir'),
        '#button_type' => 'primary',
        '#attributes' => array('class' => array('btn_formContacto')),
        '#prefix' => '<div class="small-12 medium-12 large-4 columns noPaddingLeft">',
        '#suffix'     => '</div>',

      );

      $form['terms_condition'] = array(
          '#type' =>'checkbox',
          '#title'=>t('He leído y acepto la') . ' <a href="/terminos-condiciones">' . t('Privacy Policy') . '</a>',
          '#required'=>TRUE,
          '#default_value' =>FALSE, // for default checked and false is not checked
          '#prefix'     => '<div class="small-12 columns noPadding type-ws-r grey-t">' ,
          '#suffix'     => '</div>',
      );

      return $form;

  }
  /**
  * {@inheritdoc}
  */
 public function validateForm(array &$form, FormStateInterface $form_state) {
       parent::validateForm($form, $form_state);
 }

 /**
  * {@inheritdoc}
  */
 public function submitForm(array &$form, FormStateInterface $form_state) {

     $values = [
      'webform_id' => 'contacto_form',
      'in_draft' => FALSE,
      'uid' => '1',
      'langcode' => \Drupal::currentUser()->getPreferredLangcode(),
      'token' => 'pgmJREX2l4geg2RGFp0p78Qdfm1ksLxe6IlZ-mN9GZI',
      'uri' => '/webform/contacto_form/api',
      'remote_addr' => '',
      'data' => [
          'nombre'=>$form_state->getValue('nombre'),
          'apellidos'=>$form_state->getValue('apellidos'),          
          'email'=>$form_state->getValue('email'),
      ],
    ];

    $webform = Webform::load($values['webform_id']);
    $is_open = WebformSubmissionForm::isOpen($webform);

    if ($is_open === TRUE) {
      $errors = WebformSubmissionForm::validateValues($values);
      if (!empty($errors)) {
         return $errors;
      }else {
         $webform_submission = WebformSubmissionForm::submitValues($values);

         // registramos la petición en el CRM
         $a = new Crm();
         $a->registrar([
           'nombre'=>$form_state->getValue('nombre'),
           'apellidos'=>$form_state->getValue('apellidos'),
           'email'=>$form_state->getValue('e_mail')
           ]);
         drupal_set_message(t('Thanks for contacting us'));
      }
    }
 }







}
