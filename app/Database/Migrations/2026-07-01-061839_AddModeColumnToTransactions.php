<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddModeColumnToTransactions extends Migration
{
    public function up()
    {
        $this->forge->addColumn('transactions', [
            'mode' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'bulanan',
                'after'      => 'paket',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('transactions', 'mode');
    }
}