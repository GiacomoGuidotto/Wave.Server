<?php

namespace Wave\Services\WebSocket;

interface WebSocketInterface {
  /**
   * Manage "New contact request" case by creating a new contact reference and sending the packet to
   * the targeted contact
   *
   * @param string $origin  The user who made the API request
   * @param array  $payload The payload from the ZeroMQ packet
   * @return void
   */
  function onContactCreate(
    string $origin,
    array  $payload,
  ): void;
  
  /**
   * Manage "New contact reply", "New contact status" and "New contact information" case by
   * retrieving the contact reference and sending the packet to the targeted contact
   *
   * @param string $origin  The user who request a deletion
   * @param array  $payload The payload from the ZeroMQ packet
   * @return void The references to the packet recipients
   */
  function onContactUpdate(
    string $origin,
    array  $payload,
  ): void;
  
  /**
   * Manage "Delete contact request" case by deleting a specific contact reference and sending the
   * packet to the targeted contact
   *
   * @param string $origin  The user who request a deletion
   * @param array  $payload The payload from the ZeroMQ packet
   * @return void The references to the packet recipients
   */
  function onContactDelete(
    string $origin,
    array  $payload,
  ): void;
  
  /**
   * Manage "New group" case by creating a new group reference and sending the
   * packet to the targeted list of members
   *
   * @param string $origin  The user who request a group
   * @param array  $payload The payload from the ZeroMQ packet
   * @return void The references to the packet recipients
   */
  function onGroupCreate(
    string $origin,
    array  $payload,
  ): void;
  
  /**
   * Manage "New group infos" case by retrieving a specific group reference and sending the
   * packet to the targeted list of members
   *
   * @param string $origin  The user who request a deletion
   * @param array  $payload The payload from the ZeroMQ packet
   * @return void The references to the packet recipients
   */
  function onGroupUpdate(
    string $origin,
    array  $payload,
  ): void;
  
  /**
   * Manage "Delete group" case by deleting a specific group reference and sending the
   * packet to the targeted list of members
   *
   * @param string $origin  The user who request a deletion
   * @param array  $payload The payload from the ZeroMQ packet
   * @return void The references to the packet recipients
   */
  function onGroupDelete(
    string $origin,
    array  $payload,
  ): void;
  
  /**
   * Manage "New member" case by creating a new member reference and sending the
   * packet to the targeted list of other members
   *
   * @param string $origin  The user who request a member addition
   * @param array  $payload The payload from the ZeroMQ packet
   * @return void The references to the packet recipients
   */
  function onMemberCreate(
    string $origin,
    array  $payload,
  ): void;
  
  /**
   * Manage "New member permission" case by retrieving a specific group reference and sending the
   * packet to the targeted list of members
   *
   * @param string $origin  The user who request a member deletion
   * @param array  $payload The payload from the ZeroMQ packet
   * @return void The references to the packet recipients
   */
  function onMemberUpdate(
    string $origin,
    array  $payload,
  ): void;
  
  /**
   * Manage "Removed member" case by removing a specific member reference and sending the
   * packet to the targeted list of other members
   *
   * @param string $origin  The user who request a member deletion
   * @param array  $payload The payload from the ZeroMQ packet
   * @return void The references to the packet recipients
   */
  function onMemberDelete(
    string $origin,
    array  $payload,
  ): void;
  
  /**
   * Manage "New message" case by retrieving either a group reference or a contact reference and
   * sending the packet to the targeted list of chat members
   *
   * @param string $origin  The user who request a member addition
   * @param array  $payload The payload from the ZeroMQ packet
   * @return void The references to the packet recipients
   */
  function onMessageCreate(
    string $origin,
    array  $payload,
  ): void;
  
  /**
   * Manage "Changed message" case by retrieving either a group reference or a contact reference and
   * sending the packet to the targeted list of chat members
   *
   * @param string $origin  The user who request a member deletion
   * @param array  $payload The payload from the ZeroMQ packet
   * @return void The references to the packet recipients
   */
  function onMessageUpdate(
    string $origin,
    array  $payload,
  ): void;
  
  /**
   * Manage "Delete message" case by retrieving either a group reference or a contact reference and
   * sending the packet to the targeted list of chat members
   *
   * @param string $origin  The user who request a member deletion
   * @param array  $payload The payload from the ZeroMQ packet
   * @return void The references to the packet recipients
   */
  function onMessageDelete(
    string $origin,
    array  $payload,
  ): void;
}