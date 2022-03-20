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
  public function login(string $username, string $password, string $device): array;
  
  /**
   * Update the session's TTL.
   *
   * Refresh the session timeout without actions.
   *
   * @param string $token The token used to authenticate the user, extracted from the request
   * @return array|null   The eventual error array as object
   */
  public function poke(string $token): array|null;
  
  /**
   * Delete the session token.
   *
   * Make a specific session token unreachable.
   *
   * @param string $token The token used to authenticate the user, extracted from the request
   * @return array|null   The eventual error array as object
   */
  public function logout(string $token): array|null;
  
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
      string      $username,
      string      $password,
      string      $name,
      string      $surname,
      string|null $phone,
      string|null $picture
  ): array;
  
  /**
   * Get the user's information.
   *
   * Retrieve the user's information given the session token.
   *
   * @param string $token The token used to authenticate the user, extracted from the request
   * @return array        The public attributes of the user, saved in an array as object
   */
  public function getUserInformation(string $token): array;
  
  /**
   * Change the user's information
   *
   * Change the specific user's information with the specific new value.
   * This will trigger a ws packet to be sent to every contact of the user.
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
   * @return array                The public attributes of the user, saved in an array as object
   */
  public function changeUserInformation(
      string      $token,
      string|null $username,
      string|null $name,
      string|null $surname,
      string|null $phone,
      string|null $picture,
      string|null $theme,
      string|null $language
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
  public function deleteUser(string $token): array|null;
  
  // ==== Contact ==================================================================================
  // ==== Use cases related to the contacts management =============================================
  
  public function contactRequest();
  
  public function deleteContactRequest();
  
  public function changeContactStatus();
  
  public function getContactInformation();
  
  // ==== Group ====================================================================================
  // ==== Use cases related to the groups management ===============================================
  
  public function createGroup();
  
  public function getGroupInformation();
  
  public function changeGroupStatus();
  
  public function changeGroupInformation();
  
  public function exitGroup();
  
  // ==== Member ===================================================================================
  
  public function addMember();
  
  public function getMemberList();
  
  public function changeMemberPermission();
  
  public function removeMember();
  
  // ==== Message ==================================================================================
  // ==== Use cases related to the messages management =============================================
  
  public function getMessages();
  
  public function writeMessage();
  
  public function changeMessage();
  
  public function deleteMessage();
}