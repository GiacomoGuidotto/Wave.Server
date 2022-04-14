<?php

namespace Wave\Specifications\ErrorCases\Elaboration;

interface WrongState {
  const CODE = 55;
  const MESSAGE = "Wrong group state";
  const DETAILS = "The state between of this group can't allow this directive";
  
}