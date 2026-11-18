<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSiteSettingsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'setting_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'setting_value' => [
                'type' => 'TEXT',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        $this->forge->addKey('setting_key', true);
        $this->forge->createTable('site_settings', true);
    }

    public function down()
    {
        $this->forge->dropTable('site_settings', true);
    }
}
