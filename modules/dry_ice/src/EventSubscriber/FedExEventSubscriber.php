<?php

namespace Drupal\commerce_fedex_dry_ice\EventSubscriber;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_fedex\Event\CommerceFedExEvents;
use Drupal\commerce_fedex\Event\ConfigurationFormEvent;
use Drupal\commerce_fedex\Event\RateRequestEvent;
use Drupal\physical\MeasurementType;
use Drupal\physical\WeightUnit;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class FedExEventSubscriber
 *
 * @package Drupal\commerce_fedex_dry_ice\EventSubscriber
 */
class FedExEventSubscriber implements EventSubscriberInterface
{
  use StringTranslationTrait;

  /**
   * @param \Drupal\commerce_fedex\Event\RateRequestEvent $event
   */
  public function beforeRateRequest(RateRequestEvent $event) {
    $rateRequest = $event->getRateRequest();
    $shipment = $event->getShipment();

  }

  /**
   * Configuration form alter
   *
   * @param \Drupal\commerce_fedex\Event\ConfigurationFormEvent $event
   * @return \Drupal\commerce_fedex\Event\ConfigurationFormEvent
   */
  public function buildConfigurationForm(ConfigurationFormEvent $event){
    $form = $event->getForm();
    $configuration = $event->getConfiguration();

    $packageTypeManager = \Drupal::service('plugin.manager.commerce_package_type');
    $package_types = $packageTypeManager->getDefinitionsByShippingMethod('fedex');
    $package_types = array_map(function ($package_type) {
      return $package_type['label'];
    }, $package_types);

    $form['dry_ice'] = [
      '#type' => 'details',
      '#title' => $this->t('Dry Ice Configuration'),
      '#description' => $this->t('Enter your configuration for Dry ice'),
      '#weight' => 10,
    ];
    $form['dry_ice']['package_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Dry ice package type'),
      '#options' => $package_types,
      '#default_value' => !empty($configuration['dry_ice']['package_type'])?$configuration['dry_ice']['package_type']:NULL,
      '#required' => TRUE,
      '#access' => count($package_types) > 1,
    ];
    $form['dry_ice']['weight']  = [
      '#type' => 'physical_measurement',
      '#measurement_type' => 'weight',
      '#title' => $this->t('Dry Ice Weight'),
      '#default_value' => !empty($configuration['dry_ice']['weight'])?$configuration['dry_ice']['weight']:['number' => 0, 'unit' => WeightUnit::KILOGRAM],
      '#size' => 5,
      '#maxlength' => 4,
      '#required' => TRUE,
    ];
    $form['dry_ice']['intl_package_type'] = [
      '#type' => 'select',
      '#title' => $this->t('International dry ice package type'),
      '#options' => $package_types,
      '#default_value' => !empty($configuration['dry_ice']['intl_package_type'])?$configuration['dry_ice']['intl_package_type']:NULL,
      '#required' => TRUE,
      '#access' => count($package_types) > 1,
    ];
    $form['dry_ice']['intl_weight']  = [
      '#type' => 'physical_measurement',
      '#measurement_type' => 'weight',
      '#title' => $this->t('International dry Ice Weight'),
      '#default_value' => !empty($configuration['dry_ice']['intl_weight'])?$configuration['dry_ice']['intl_weight']:['number' => 0, 'unit' => WeightUnit::KILOGRAM],
      '#size' => 5,
      '#maxlength' => 4,
      '#required' => TRUE,
    ];
    $event->setForm($form);
    return $event;
  }

  /**
   * Configuration Form Submit Event
   *
   * @param \Drupal\commerce_fedex\Event\ConfigurationFormEvent $event
   * @return \Drupal\commerce_fedex\Event\ConfigurationFormEvent
   */
  public function submitConfigurationForm(ConfigurationFormEvent $event){
    $configuration = $event->getConfiguration();
    $form_state = $event->getFormState();
    $form = $event->getForm();
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $configuration['dry_ice']['package_type'] = $values['dry_ice']['package_type'];
      $configuration['dry_ice']['weight'] = $values['dry_ice']['weight'];
    }
    return $event;
  }

  /**
   * @inheritdoc
   */
  static function getSubscribedEvents() {
    $events = [];

    $events[CommerceFedExEvents::BEFORE_RATE_REQUEST][] = ['beforeRateRequest'];
    $events[CommerceFedExEvents::BUILD_CONFIGURATION_FORM][] = ['buildConfigurationForm'];
    $events[CommerceFedExEvents::SUBMIT_CONFIGURATION_FORM][] = ['submitConfigurationForm'];
    return $events;
  }

}