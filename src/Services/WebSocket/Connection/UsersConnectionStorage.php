<?php

namespace Wave\Services\WebSocket\Connection;

use SplObjectStorage;

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