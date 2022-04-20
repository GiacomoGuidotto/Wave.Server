<?php

namespace Wave\Services\WebSocket\ConnectionStorage;

use SplObjectStorage;

/**
 * The user's channel connection storage
 *
 * A SplObjectStorage extended with a method that allow a search in the storage from the info
 * associated to an object
 *
 * @author Giacomo Guidotto
 */
class UsersConnectionStorage extends SplObjectStorage {
  /**
   * @see https://stackoverflow.com/questions/28792051/find-object-in-splobjectstorage-by-attached-info
   */
  public function getFromInfo($info): ?object {
    $this->rewind();
    while ($this->valid()) {
      $object = $this->current();
      $data = $this->getInfo();
      if ($info === $data) {
        $this->rewind();
        
        return $object;
      }
      $this->next();
    }
    
    return null;
  }
}