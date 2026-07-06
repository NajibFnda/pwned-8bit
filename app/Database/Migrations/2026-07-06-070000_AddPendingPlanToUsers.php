<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPendingPlanToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'pending_plan' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
                'default'    => null,
                'after'      => 'subscription_plan',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'pending_plan');
    }
}
