<?php

namespace Wave\Specifications\ErrorCases\WebSocket;

class IncorrectPacketSchema {
  const CODE = 60;
  const MESSAGE = "Incorrect packet schema";
  const DETAILS = "The web socket packet sent from this user doesn't have a correct schema";
}