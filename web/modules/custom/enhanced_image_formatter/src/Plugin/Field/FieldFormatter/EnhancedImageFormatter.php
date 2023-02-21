<?php

namespace Drupal\enhanced_image_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\Xss;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * @FieldFormatter(
 *   id = "image",
 *   label = @Translation("Image"),
 *   field_types = {
 *     "image"
 *   },
 *   quickedit = {
 *     "editor" = "image"
 *   }
 * );
 */
class EnhancedImageFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'tokenizer' => [
          'alt_text' => '',
          'title_text' => '',
        ],
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $element = parent::settingsForm($form, $form_state);

    // Enchanced image (token alt, title)
    $element['tokenizer'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Tokenized ALT and TITLE'),
      '#tree' => TRUE,
    ];
    $element['tokenizer']['alt_text'] = [
      '#title' => t('Alternative text'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('tokenizer')['alt_text'],
      '#description' => t('This field supports tokens.'),
      '#maxlength' => 255,
    ];
    $element['tokenizer']['title_text'] = [
      '#title' => t('Title text'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('tokenizer')['title_text'],
      '#description' => t('This field supports tokens.'),
      '#maxlength' => 255,
    ];
    $element['tokenizer']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => [$form['#entity_type'] == 'taxonomy_term' ? 'term' : $form['#entity_type']],
      '#dialog' => TRUE,
    ];

    // Image link formatter
    $element['image_link']['#options'] = array_merge($element['image_link']['#options'], $this->getLinkFieldOptions());

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();

    // Image link formatter
    $link_types = [
      'content' => t('Linked to content'),
      'file' => t('Linked to file'),
    ];
    $image_link_setting = $settings['image_link'];

    if (!isset($link_types[$image_link_setting])) {
      $summary[] = t('Linked to @field', ['@field' => $image_link_setting]);
    }

    // Enchanced image (token alt, title)
    if (!empty($settings['tokenizer']['alt_text'])) {
      $summary[] = t('Alternative text: @value', ['@value' => $settings['tokenizer']['alt_text']]);
    }

    if (!empty($settings['tokenizer']['title_text'])) {
      $summary[] = t('Title text: @value', ['@value' => $settings['tokenizer']['title_text']]);
    }

    return array_merge(parent::settingsSummary(), $summary);
  }

  /**
   * Get all of the link fields which are attached to this entity and bundle.
   *
   * @return FieldDefinitionInterface[]
   *   An array of link field definitions.
   */
  private function getLinkFields() {
    return array_filter(\Drupal::service('entity_field.manager')->getFieldDefinitions($this->fieldDefinition->getTargetEntityTypeId(), $this->fieldDefinition->getTargetBundle()), function (FieldDefinitionInterface $element) {
      return $element->getType() === 'link';
    });
  }

  /**
   * Get an options array of link fields.
   *
   * @return array
   *   An array of fields keyed by id.
   */
  private function getLinkFieldOptions() {
    $options = [];
    $fields = $this->getLinkFields();
    foreach ($fields as $field) {
      $options[$field->getName()] = t('@label field', ['@label' => $field->getLabel()]);
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $settings = $this->getSettings();
    $entity = $items->getEntity();

    // Image link formatter
    $link_types = ['content', 'file', ''];
    $image_link_setting = $settings['image_link'];

    // Enchanced image (token alt, title)
    $entityType = $entity->getEntityType()->id();
    if ($entityType == 'taxonomy_term') {
      $entityType = 'term';
    }


    foreach ($elements as &$element) {
      // Image link formatter
      if (!in_array($image_link_setting, $link_types)) {
        $field = $entity->{$image_link_setting}->get(0);
        $element['#url'] = $field ? $field->getUrl() : null;
      }
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
      if ($entity->hasTranslation($language)) {
        $entity = $entity->getTranslation($language);
      }
      // Enchanced image (token alt, title)
      $alt_text = \Drupal::token()->replace($settings['tokenizer']['alt_text'], [$entityType => $entity], ['clear' => TRUE]);
      $element['#item_attributes']['alt'] = Xss::filter($alt_text, Xss::getHtmlTagList());

      $title_text = \Drupal::token()->replace($settings['tokenizer']['title_text'], [$entityType => $entity], ['clear' => TRUE]);
      $element['#item_attributes']['title'] = Xss::filter($title_text, Xss::getHtmlTagList());

    }

    return $elements;
  }
}
