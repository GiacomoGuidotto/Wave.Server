<?php

namespace Wave\Services\Database;

/**
 * Database service
 *
 * Query the MySQL database following the use cases
 */
interface DatabaseService {
  
  // ==== Authentication ===========================================================================
  // ==== Use cases related to the authentication process ==========================================
  
  /**
   * Get the session token.
   *
   * Retrieve the session token with the username/password combination.
   *
   * @param string $username The username to authenticate, extracted from the request
   * @param string $password The user password to authenticate, extracted from the request
   * @param string $device   The session source, extracted from the request
   * @return array           The token used to authenticate the user, saved in an array as object
   */
  public function login(
    string $username,
    string $password,
    string $device,
  ): array;
  
  /**
   * Update the session's TTL.
   *
   * Refresh the session timeout without actions.
   *
   * @param string $token The token used to authenticate the user, extracted from the request
   * @return array|null   The eventual error array as object
   */
  public function poke(
    string $token,
  ): ?array;
  
  /**
   * Delete the session token.
   *
   * Make a specific session token unreachable.
   *
   * @param string $token The token used to authenticate the user, extracted from the request
   * @return array|null   The eventual error array as object
   */
  public function logout(
    string $token,
  ): ?array;
  
  // ==== User =====================================================================================
  // ==== Use cases related to the user management =================================================
  
  /**
   * Create a new user.
   *
   * Create a new user using the given parameters.
   *
   * @param string      $username The user's identifier, extracted from the request
   * @param string      $password The clear password of the user, extracted from the request
   * @param string      $name     The user's name, extracted from the request
   * @param string      $surname  The user's surname, extracted from the request
   * @param string|null $phone    The optional user's phone, extracted from the request
   * @param string|null $picture  The optional user's picture, extracted from the request
   * @return array                The token used to authenticate the user, saved in an array as
   *                              object
   */
  public function createUser(
    string  $username,
    string  $password,
    string  $name,
    string  $surname,
    ?string $phone,
    ?string $picture,
  ): array;
  
  /**
   * Get the user's information.
   *
   * Retrieve the user's information given the session token.
   *
   * @param string $token The token used to authenticate the user, extracted from the request
   * @return array        The public attributes of the user, saved in an array as object
   */
  public function getUserInformation(
    string $token,
  ): array;
  
  /**
   * Change the user's information
   *
   * Change the specific user's information with the specific new value.
   *
   * @param string      $token    The token used to authenticate the user, extracted from the
   *                              request
   * @param string|null $username The optional new user's username, extracted from the request
   * @param string|null $name     The optional new user's name, extracted from the request
   * @param string|null $surname  The optional new user's surname, extracted from the request
   * @param string|null $phone    The optional new user's phone, extracted from the request
   * @param string|null $picture  The optional new user's picture, extracted from the request
   * @param string|null $theme    The optional new user's theme, extracted from the request
   * @param string|null $language The optional new user's language, extracted from the request
   * @return array                The new public attributes of the user, saved in an array as object
   */
  public function changeUserInformation(
    string  $token,
    ?string $username,
    ?string $name,
    ?string $surname,
    ?string $phone,
    ?string $picture,
    ?string $theme,
    ?string $language,
  ): array;
  
  /**
   * Delete a specific user
   *
   * Delete the user associated with a given token.
   * This will trigger the recursive deletion of all the user's properties.
   *
   * @param string $token The token used to authenticate the user, extracted from the request
   * @return array|null   The eventual error array as object
   */
  public function deleteUser(
    string $token,
  ): ?array;
  
  // ==== Contact ==================================================================================
  // ==== Use cases related to the contacts management =============================================
  
  /**
   * Create a new contact request
   *
   * Create a new pending relation (contact) request between the user identified from the token and
   * a second specified user.
   *
   * @param string $token The token used to authenticate the user, extracted from the request
   * @param string $user  The targeted user's username, extracted from the request
   * @return array        The public attributes of the contact, saved in an array as object
   */
  public function contactRequest(
    string $token,
    string $user,
  ): array;
  
  /**
   * Delete a pending contact request
   *
   * Delete a pending relation (contact) request between the user identified from the token and a
   * second specified user.
   *
   * @param string $token The token used to authenticate the user, extracted from the request
   * @param string $user  The targeted user's username, extracted from the request
   * @return array|null   The eventual error array as object
   */
  public function deleteContactRequest(
    string $token,
    string $user,
  ): ?array;
  
  /**
   * Change a contact status
   *
   * Either respond to a first pending request or update an existing contact from a specific user,
   * depending from the given response.
   *
   * @param string $token     The token used to authenticate the user, extracted from the request
   * @param string $user      The targeted user's username, extracted from the request
   * @param string $directive The command to apply to the request, extracted from the request
   * @return array            The new public attributes of the contact, saved in an array as object
   */
  public function changeContactStatus(
    string $token,
    string $user,
    string $directive,
  ): array;
  
  /**
   * Get one or all contact's information
   *
   * Retrieve the list of the user's contacts.
   * If a contact's name is given, the information of that specified contact are retrieved.
   *
   * @param string      $token The token used to authenticate the user, extracted from the request
   * @param string|null $user  The optional targeted user's username, extracted from the request
   * @return array             The list of contacts or the single contact, saved in an array as
   *                           object
   */
  public function getContactInformation(
    string  $token,
    ?string $user,
  ): array;
  
  // ==== Group ====================================================================================
  // ==== Use cases related to the groups management ===============================================
  
  /**
   * Create a new group
   *
   * Create a new group with the given parameters.
   *
   * @param string      $token   The token used to authenticate the user, extracted from the
   *                             request
   * @param string      $name    The new group's name, extracted from the request
   * @param string|null $info    The eventual group's info, extracted from the request
   * @param string|null $picture The eventual group's picture, extracted from the request
   * @param array|null  $users   The eventual list of group's new members, extracted from the
   *                             request
   * @return array               The public attributes of the group, saved in an array as object
   */
  public function createGroup(
    string  $token,
    string  $name,
    ?string $info,
    ?string $picture,
    ?array  $users,
  ): array;
  
  /**
   * Get one or all groups' information
   *
   * Retrieve the list of the user's groups.
   * If a group's name is given, the information of that specified group are retrieved.
   *
   * @param string      $token The token used to authenticate the user, extracted from the request
   * @param string|null $group The optional identifier to the specific group, extracted from the
   *                           request
   * @return array             The list of groups or the single group, saved in an array as object
   */
  public function getGroupInformation(
    string  $token,
    ?string $group,
  ): array;
  
  /**
   * Change a group's status for the user
   *
   * Change a group's status, either its place (archived, pinned) or its notifications mode (mute).
   *
   * @param string $token     The token used to authenticate the user, extracted from the request
   * @param string $group     The identifier to the specific group, extracted from the request
   * @param string $directive The command to apply to the request, extracted from the request
   * @return array            The new public attributes of the group, saved in an array as object
   */
  public function changeGroupStatus(
    string $token,
    string $group,
    string $directive,
  ): array;
  
  /**
   * Change a group's information
   *
   * Change a specific group's information with the specific new value.
   *
   * @param string      $token   The token used to authenticate the user, extracted from the request
   * @param string      $group   The identifier to the specific group, extracted from the request
   * @param string|null $name    The eventual new group's name, extracted from the request
   * @param string|null $info    The eventual new group's info, extracted from the request
   * @param string|null $picture The eventual new group's picture, extracted from the request
   * @return array               The new public attributes of the group, saved in an array as object
   */
  public function changeGroupInformation(
    string  $token,
    string  $group,
    ?string $name,
    ?string $info,
    ?string $picture,
  ): array;
  
  /**
   * Exit from the group
   *
   * Delete a group participation. If its the last one, delete the group itself.
   *
   * @param string $token The token used to authenticate the user, extracted from the request
   * @param string $group The identifier to the specific group, extracted from the request
   * @return array        The new group's list, saved in an array as object
   */
  public function exitGroup(
    string $token,
    string $group,
  ): array;
  
  // ==== Member ===================================================================================
  
  /**
   * Add a group's member
   *
   * Add a specific user to a specific group.
   *
   * @param string $token The token used to authenticate the user, extracted from the request
   * @param string $group The identifier to the specific group, extracted from the request
   * @param string $user  The targeted user's username, extracted from the request
   * @return array        The new member's list, saved in an array as object
   */
  public function addMember(
    string $token,
    string $group,
    string $user,
  ): array;
  
  /**
   * Get one or all group's members
   *
   * Retrieve the list of the group's members.
   * If a member's name is given, the information of that specified member are retrieved.
   *
   * @param string      $token The token used to authenticate the user, extracted from the request
   * @param string      $group The identifier to the specific group, extracted from the request
   * @param string|null $user  The optional identifier to the specific member, extracted from the
   *                           request
   * @return array             The list of members or the single member, saved in an array as object
   */
  public function getMemberList(
    string  $token,
    string  $group,
    ?string $user,
  ): array;
  
  /**
   * Change a group's member permissions
   *
   * Change a specific group's member with the given permission.
   *
   * @param string $token      The token used to authenticate the user, extracted from the request
   * @param string $group      The identifier to the specific group, extracted from the request
   * @param string $user       The targeted user's username, extracted from the request
   * @param string $permission The new member's permission, extracted from the request
   * @return array             The new public attributes of the member, saved in an array as object
   */
  public function changeMemberPermission(
    string $token,
    string $group,
    string $user,
    string $permission,
  ): array;
  
  /**
   * Remove a group's member
   *
   * Remove a specific user from a specific group.
   *
   * @param string $token The token used to authenticate the user, extracted from the request
   * @param string $group The identifier to the specific group, extracted from the request
   * @param string $user  The targeted user's username, extracted from the request
   * @return array        The new member's list, saved in an array as object
   */
  public function removeMember(
    string $token,
    string $group,
    string $user,
  ): array;
  
  // ==== Message ==================================================================================
  // ==== Use cases related to the messages management =============================================
  
  /**
   * Get the chat's messages in various ways
   *
   * Retrieve the messages of either a specified group or a specified contact.
   * If a time period's start and end is given, the messages are retrieved based on that time
   * range.
   * If a pinned flag is given, only the pinned messages are retrieved.
   * If a message's key is given, only the public information of that message are retrieved.
   *
   * @param string      $token   The token used to authenticate the user, extracted from the
   *                             request
   * @param string|null $group   The identifier to the specific group, extracted from the request
   * @param string|null $contact The targeted contact's username, extracted from the request
   * @param string|null $from    The optional start of the time range, extracted from the request
   * @param string|null $to      The optional end of the time range, extracted from the request
   * @param bool|null   $pinned  The optional pinned flag, extracted from the request
   * @param string|null $message The optional identifier of the specific message, extracted from
   *                             the request
   * @return array               The list of messages or the single message, saved in an array as
   *                             object
   */
  public function getMessages(
    string  $token,
    ?string $group,
    ?string $contact,
    ?string $from,
    ?string $to,
    ?bool   $pinned,
    ?string $message,
  ): array;
  
  /**
   * Write a message
   *
   * Write a message with the given data.
   *
   * @param string      $token   The token used to authenticate the user, extracted from the request
   * @param string|null $group   The identifier to the specific group, extracted from the request
   * @param string|null $contact The targeted contact's username, extracted from the request
   * @param string      $content The content of the message, extracted from the request
   * @param string|null $text    The eventual text of the message, extracted from the request
   * @param string|null $media   The eventual media of the message, extracted from the request
   * @return array               The public attributes of the message, saved in an array as object
   */
  public function writeMessage(
    string  $token,
    ?string $group,
    ?string $contact,
    string  $content,
    ?string $text,
    ?string $media,
  ): array;
  
  /**
   * Change a message's content
   *
   * Change a specific message content.
   *
   * @param string      $token   The token used to authenticate the user, extracted from the
   *                             request
   * @param string|null $group   The identifier to the specific group, extracted from the request
   * @param string|null $contact The targeted contact's username, extracted from the request
   * @param string      $message The identifier of the specific message, extracted from the request
   * @param string|null $content The eventual new message's content, extracted from the request
   * @param string|null $text    The eventual new message's text, extracted from the request
   * @param string|null $media   The eventual new message's media, extracted from the request
   * @param bool|null   $pinned  The eventual new message's pinned state, extracted from the request
   * @return array               The new public attributes of the message, saved in an array as
   *                             object
   */
  public function changeMessage(
    string  $token,
    ?string $group,
    ?string $contact,
    string  $message,
    ?string $content,
    ?string $text,
    ?string $media,
    ?bool   $pinned,
  ): array;
  
  /**
   * Delete a chat's message
   *
   * Delete a specific message from a specific chat.
   *
   * @param string      $token   The token used to authenticate the user, extracted from the request
   * @param string|null $group   The identifier to the specific group, extracted from the request
   * @param string|null $contact The targeted contact's username, extracted from the request
   * @param string      $message The identifier to the specific message, extracted from the request
   * @return array|null          The eventual error array as object
   */
  public function deleteMessage(
    string  $token,
    ?string $group,
    ?string $contact,
    string  $message,
  ): ?array;
}