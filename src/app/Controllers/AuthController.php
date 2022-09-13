<?php

namespace App\Controllers;

use App\Helpers\AuthHelper;
use PDO;
use PDOException;
use \Firebase\JWT\JWT;

class AuthController extends Controller
{
  /**
   * TODO: login user
   * @param request get params
   * @param response json response
   * @return object token and user data
   */
  public function login($request, $response)
  {
    try {
      $sql = "
				SELECT 
          id,
          username
				FROM 
					users
				WHERE 
					username = :username
				AND 
					password = :password
			";

      $stmt = $this->c->db->prepare($sql);

      $stmt->execute([
        ':username' => $request->getParam('username'),
        ':password' => $request->getParam('password')
      ]);

      // Get logged in user's data
      $user = $stmt->fetch(PDO::FETCH_OBJ);

      /* It checks if the user exists in the database. If it doesn't, it returns a 404 error. */
      if (empty($user)) {
        return $response->withJSON([
          'success' => false,
          'status' => 404
        ]);
      }

      $token = [
        'iss' => 'utopian',
        'iat' => time(),
        'exp' => time() + 1000,
        'data' => $user
      ];

      $jwt = JWT::encode($token, $this->c->settings['jwt']['key']);

      return $response->withJson([
        'success' => true,
        'status' => 200,
        'jwt' => $jwt
      ]);
    } catch (PDOException $e) {
      // Catch all database errors
      return $response->withJson([
        'message' => $e->getMessage()
      ]);
    }
  }

  /**
   * TODO: register a new user
   * @param request get params
   * @param response json response
   * @return json
   */
  public function register($request, $response)
  {
    $auth_helper = new AuthHelper();
    $user = [
      'username' => $request->getParam('username'),
      'password' => $request->getParam('password'),
      'confirm_password' => $request->getParam('confirm_password')
    ];
    $password_length = 8;

    try {
      $sql = "
				INSERT INTO users (
          username, 
          password, 
          created_at
        )
				VALUES (
          :username,
          :password,
          :created_at
        )
			";

      $stmt = $this->c->db->prepare($sql);

      // Checked if user's email exist in database
      if ($this->username_exist($user['username'])) {
        return $response->withJSON([
          'message' => 'Username already exists.',
          'status' => 500
        ]);
      }

      // Check if password is same with confirm password
      if (!($auth_helper->confirm_password($user['password'], $user['confirm_password']))) {
        return $response->withJSON([
          'message' => "Password are not same.",
          'status' => 500
        ]);
      }

      // Check if password length is greater than passed value
      if (!($auth_helper->password_length($password_length, $user['password']))) {
        return $response->withJSON([
          'message' => 'Password need at least ' . $password_length . ' characters.',
          'status' => 500
        ]);
      }

      $stmt->execute([
        ':username' => $user['username'],
        ':password' => $user['password'],
        ':created_at' => date('Y-m-d H:i:s')
      ]);

      return $response->withJSON([
        'message' => 'success',
        'status' => 200
      ]);
    } catch (PDOException $e) {
      return $response->withJSON([
        'message' => $e->getMessage()
      ]);
    }
  }

  /**
   * TODO: It checks if the username already exists in the database.
   * @param string email The email address to check
   * @return a boolean value.
   */
  private function username_exist(string $email)
  {
    $sql = "
			SELECT 
				id 
			FROM 
				users
			WHERE 
				username = :username
		";

    $stmt = $this->c->db->prepare($sql);

    $stmt->execute([
      ':username' => $email
    ]);

    $user = $stmt->fetch(PDO::FETCH_OBJ);

    return !empty($user);
  }
}
