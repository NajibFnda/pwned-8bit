<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRedeemCodesTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
            ],
            'plan' => [
                'type'       => 'ENUM',
                'constraint' => ['plus', 'pro'],
                'default'    => 'plus',
            ],
            'duration_days' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 30,
            ],
            'max_uses' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 1,
            ],
            'used_count' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 0,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code', 'uniq_redeem_code');
        $this->forge->addKey(['is_active', 'expires_at'], false, false, 'idx_redeem_active');
        $this->forge->createTable('redeem_codes', true);

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'redeem_code_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'claimed_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['redeem_code_id', 'user_id'], 'uniq_code_user');
        $this->forge->addKey('user_id', false, false, 'idx_claim_user');
        $this->forge->createTable('redeem_claims', true);
    }

    public function down()
    {
        $this->forge->dropTable('redeem_claims', true);
        $this->forge->dropTable('redeem_codes', true);
    }
}
