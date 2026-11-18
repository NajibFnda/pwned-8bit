<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSecurityLogsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'event_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'severity' => [
                'type'       => 'ENUM',
                'constraint' => ['info', 'warning', 'critical'],
                'default'    => 'info',
            ],
            'actor_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'detail' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'default'    => '',
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['event_type', 'created_at'], false, false, 'idx_sec_event_time');
        $this->forge->addKey(['severity', 'created_at'], false, false, 'idx_sec_severity_time');
        $this->forge->createTable('security_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('security_logs', true);
    }
}
