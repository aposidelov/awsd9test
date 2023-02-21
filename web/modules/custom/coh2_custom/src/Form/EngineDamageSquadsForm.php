<?php

namespace Drupal\coh2_custom\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Table drag example simple form.
 *
 * @ingroup coh2_custom
 */
class EngineDamageSquadsForm extends FormBase {

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
    return 'coh2_custom_engine_damage_squads_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['table-row'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t('Sorry, There are no items!'),
      // TableDrag: Each array value is a list of callback arguments for
      // drupal_add_tabledrag(). The #id of the table is automatically
      // prepended; if there is none, an HTML ID is auto-generated.
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'table-sort-weight',
        ],
      ],
    ];

    $results = \Drupal::entityQuery('node')
      ->sort('field_eng_damage_squads_weight', 'ASC')
      ->condition('type', 'unit')
      ->condition('field_is_engine_damage_squads', TRUE)
      ->execute();

    foreach ($results as $vid => $nid) {
      $node = node_load($nid);
      $title = $node->get('field_front_title')->value;
      $weight = $node->get('field_short_range_squad_weight')->value;
      // TableDrag: Mark the table row as draggable.
      $form['table-row'][$nid]['#attributes']['class'][] = 'draggable';
      // TableDrag: Sort the table row according to its existing/configured
      // weight.
      $form['table-row'][$nid]['#weight'] = $weight;

      // Some table columns containing raw markup.
      $form['table-row'][$nid]['name'] = [
        '#markup' => $title,
      ];
      // TableDrag: Weight column element.
      $form['table-row'][$nid]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $title]),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        // Classify the weight element for #tabledrag.
        '#attributes' => ['class' => ['table-sort-weight']],
        '#delta' => 50,
      ];
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save All Changes'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value'  => 'Cancel',
      '#attributes' => [
        'title' => $this->t('Return to TableDrag Overview'),
      ],
      '#submit' => ['::cancel'],
      '#limit_validation_errors' => [],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('coh2_custom.coh2_pages');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $submission = $form_state->getValue('table-row');
    foreach ($submission as $nid => $item) {
      $node = node_load($nid);
      $node->set('field_eng_damage_squads_weight', $item['weight']);
      $node->save();
    }
  }

}
