<?php

namespace App\Models;

use CodeIgniter\Model;

class TransactionModel extends Model
{
    // Nama tabel di database
    protected $table            = 'transactions';
    
    // Primary key dari tabel
    protected $primaryKey       = 'id';
    
    // Mengaktifkan auto increment untuk ID
    protected $useAutoIncrement = true;
    
    // Tipe data kembalian
    protected $returnType       = 'array';
    
    // Kolom yang boleh diisi melalui query model
    protected $allowedFields    = ['user_id', 'paket', 'harga', 'tanggal'];

    // Fungsi untuk mengambil data transaksi sekaligus menggabungkan dengan nama user
    public function getTransactionsWithUser()
    {
        return $this->select('transactions.*, users.nama')
                    ->join('users', 'users.id = transactions.user_id')
                    ->orderBy('transactions.tanggal', 'DESC')
                    ->findAll();
    }
}