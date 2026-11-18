<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBreachAlertsTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 254,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'last_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 16,
                'default'    => 'pending',
            ],
            'last_breach_count' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 0,
            ],
            'last_breach_names_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'alerted_breach_names_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'last_error' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'last_checked_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_alerted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'email'], 'uq_monitor_user_email');
        $this->forge->addKey(['is_active', 'last_checked_at'], false, false, 'idx_monitor_due');
        $this->forge->createTable('monitored_emails', true);

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'monitor_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'monitored_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 254,
            ],
            'recipient_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 254,
            ],
            'new_breaches_json' => [
                'type' => 'TEXT',
            ],
            'new_count' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 0,
            ],
            'delivery_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 16,
            ],
            'delivery_debug' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'created_at'], false, false, 'idx_alert_user_created');
        $this->forge->addKey('created_at', false, false, 'idx_alert_created');
        $this->forge->createTable('breach_alert_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('breach_alert_logs', true);
        $this->forge->dropTable('monitored_emails', true);
    }
}
