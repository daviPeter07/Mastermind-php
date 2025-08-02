<?php
// src/Controllers/HealthController.php

namespace App\Controllers;

class HealthController
{
  public function check()
  {
    http_response_code(200);
    echo json_encode(['status' => 'Mastermind API is healthy and running!']);
  }
}
