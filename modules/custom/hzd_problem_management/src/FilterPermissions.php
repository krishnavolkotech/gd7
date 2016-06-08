<?php
// in FilterPermissions.php
 
class FilterPermissions {
  public function permissions() {
    $permissions = [];
    // Generate permissions for each text format. Warn the administrator that any
    // of them are potentially unsafe.
    /** @var \Drupal\filter\FilterFormatInterface[] $formats */
    $formats = $this->entityManager->getStorage('filter_format')->loadByProperties(['status' => TRUE]);
    uasort($formats, 'Drupal\Core\Config\Entity\ConfigEntityBase::sort');
    foreach ($formats as $format) {
      if ($permission = $format->getPermissionName()) {
        $permissions[$permission] = [
          'title' => $this->t('Use the <a href="@url">@label</a> text format', ['@url' => $format->url(), '@label' => $format->label()]),
          'description' => String::placeholder($this->t('Warning: This permission may have security implications depending on how the text format is configured.')),
        ];
      }
    }
    return $permissions;
  }
}
?>
