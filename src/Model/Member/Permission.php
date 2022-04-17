<?php

namespace Wave\Model\Member;

enum Permission: int {
  case ChangeOthersPermission = 1;
  case AddPeople = 2;
  case RemovePeople = 4;
  case ChangeName = 8;
  case ChangeInfo = 16;
  case ChangePicture = 32;
  case PinMessages = 64;
  case DeleteMessages = 128;
}