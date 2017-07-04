<?php


namespace Drupal\cust_group;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Contact entity.
 * @ingroup im_attachments
 */
interface ImAttachmentInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}