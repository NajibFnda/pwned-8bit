<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRateLimitsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
            ],
            'ip_hash' => [
                'type'       => 'CHAR',
                'constraint' => 32,
            ],
            'actor_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['action', 'ip_hash', 'created_at'], false, false, 'idx_rate_lookup');
        $this->forge->addKey(['action', 'actor_id', 'created_at'], false, false, 'idx_rate_actor');
        $this->forge->createTable('rate_limits', true);
    }

    public function down()
    {
        $this->forge->dropTable('rate_limits', true);
    }
}
