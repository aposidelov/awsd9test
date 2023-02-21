<?php

namespace Drupal\coh2_varnish\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * VarnishForm
 *
 * @ingroup coh2_varnish
 */
class VarnishPurgeForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  /**
   * Construct a form.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'coh2_varnish_purge_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['page_url'] = [
      '#type' => 'textfield',
      '#title' => 'Page URL',
      '#default_value' => '',
    ];
    $form['purge'] = [
      '#type' => 'submit',
      '#value' => 'Purge',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $host = \Drupal::request()->getSchemeAndHttpHost();
    $page_url = $form_state->getValue('page_url');
    $output=null;
    $retval=null;
    $url = $host.$page_url;
    exec('curl -I -X PURGE '.$url, $output, $retval);
    \Drupal::messenger()->addMessage("$url page has been purged", 'status');
    \Drupal::messenger()->addMessage('<pre>'.print_r($output, TRUE).'</pre>', 'warning');
  }

}
