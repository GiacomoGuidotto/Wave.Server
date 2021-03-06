<?php

namespace Wave\Model\Member;

/**
 * Chat's permission
 * The set of a group member's permission
 *
 * @author Giacomo Guidotto
 */
enum Permission: int {
  case ChangeOthersPermission = 1;
  case AddPeople = 2;
  case RemovePeople = 4;
  case ChangeName = 8;
  case ChangeInfo = 16;
  case ChangePicture = 32;
  case PinMessages = 64;
  case ChangeMessages = 128;
  case DeleteMessages = 256;
}