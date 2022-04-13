<?php

namespace Wave\Specifications\ErrorCases\Elaboration;

interface WrongState {
  const CODE = 52;
  const MESSAGE = "Wrong group state";
  const DETAILS = "The stata between of this group can't allow this directive";
  
}