<?php

namespace App\Models;

use CodeIgniter\Model;

class TransactionModel extends Model
{
    protected $table            = 'transactions';
    
    protected $primaryKey       = 'id';
    
    protected $useAutoIncrement = true;
    
    protected $returnType       = 'array';
    
    protected $allowedFields    = ['user_id', 'paket', 'mode', 'harga', 'tanggal'];

    
    public function getTransactionsWithUser()
    {
        return $this->select('transactions.*, users.nama')
                    ->join('users', 'users.id = transactions.user_id')
                    ->orderBy('transactions.tanggal', 'DESC')
                    ->findAll();
    }
}