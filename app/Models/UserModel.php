<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'id';
    
    // Pastikan 'nama' dan 'role' sudah ditambahkan di sini!
    protected $allowedFields = [
        'nama', 
        'email', 
        'password', 
        'role', 
        'subscription_plan', 
        'expire_date'
    ];
}