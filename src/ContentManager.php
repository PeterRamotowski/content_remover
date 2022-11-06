<?php

namespace Drupal\content_remover;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContentManager implements ContainerInjectionInterface
{
	use StringTranslationTrait;

  /**
   * @param EntityTypeManagerInterface $entityTypeManager
   * @param EntityTypeRepositoryInterface $entityTypeRepository
   * @param EntityTypeBundleInfoInterface $entityTypeBundle
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityTypeRepositoryInterface $entityTypeRepository,
    protected EntityTypeBundleInfoInterface $entityTypeBundle,
  ) {
    $this->entityTypeManager = $entityTypeManager;
		$this->entityTypeRepository = $entityTypeRepository;
		$this->entityTypeBundle = $entityTypeBundle;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.repository'),
      $container->get('entity_type.bundle.info'),
    );
  }

  /**
   * @return array
   */
  public function getContentEntityTypes(): array
  {
    $entityTypeLabels = $this->entityTypeRepository->getEntityTypeLabels(true);
    $contentGroupKey = (string) $this->t('Content', [], ['context' => 'Entity type group']);

    $entityTypes = [];

    foreach ($entityTypeLabels[$contentGroupKey] as $entityId => $entityType) {
      $entityTypes[$entityId] = (string) $entityType;
    }
    
    return $entityTypes;
  }

  /**
   * @return array
   */
  public function getNonEmptyEntityTypes(): array
  {
    $entityTypes = [];

    foreach($this->getContentEntityTypes() as $entityTypeKey => $entityTypeLabel) {
      $countContent = count($this->getStorageEntities($entityTypeKey));

      if ($countContent > 0) {
        $entityTypes[$entityTypeKey] = sprintf('%s (%s)', $entityTypeLabel, $countContent);
      }
    }

    return $entityTypes;
  }

  /**
   * @param string $entityTypeId
   * @return array
   */
  public function getStorageEntities(string $entityTypeId): array
  {
    $storage = $this->entityTypeManager->getStorage($entityTypeId);
    
    $entities = $storage->loadMultiple();

    // ignore anonymous and superadmin accounts
    if ($entityTypeId === 'user') {
      $entities = array_diff_key($entities, [0, 1]);
    }

    return $entities;
  }

  /**
   * @param string $entityTypeId
   */
  public function deleteEntityContent(string $entityTypeId)
  {
    /** @var ContentEntityBase $entity */
    foreach ($this->getStorageEntities($entityTypeId) as $entity) {
      $entity->delete();
    }
  }

}
