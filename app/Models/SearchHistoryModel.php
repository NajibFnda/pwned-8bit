<?php

namespace App\Models;

use CodeIgniter\Model;

class SearchHistoryModel extends Model
{
    protected $table      = 'search_history';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'email_checked', 'created_at'];

    public function countUserSearchesToday($user_id)
    {
        return $this->where('user_id', $user_id)
                    ->where('DATE(created_at)', date('Y-m-d'))
                    ->countAllResults();
    }
}