<?php

namespace App\Controllers;

use PDO;

class MemberController extends Controller
{
  public function index($request, $response, $args)
  {
    $sql = "
      SELECT
        m.first_name,
        m.last_name,
        m.date_of_birth,
        u.username as created_by,
        m.created_at
      FROM
        members m
      LEFT JOIN
        users u
      ON
        u.id = m.created_by
    ";

    $stmt = $this->c->db->query($sql);

    $data = $stmt->fetchAll(PDO::FETCH_OBJ);

    if (!($data)) {
      return $response->withJSON(['success' => false, 'status' => 500]);
    }

    return $response->withJSON([
      'data' => $data,
      'success' => true,
      'status' => 200
    ]);
  }
}
