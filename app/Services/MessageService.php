<?php

namespace App\Services;

class MessageService 
{
  public function responseError() 
  {
    return response([
      'message' => 'An error has occurred, please reload the page or try again later. Please contact the administrator if error has re-occured.',
      'status' => false,
      'status_code' => 422,
    ], 422);
    
  }
}