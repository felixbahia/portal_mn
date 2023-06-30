<?php

use Illuminate\Database\Seeder;

class StatusPedidoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('status_pedido')->insert([[
        	"status" => "Em digitação",
        	"created_at" => date("Y-m-d H:i:s"),
        	"created_by" => 1
        ],
    	[
        	"status" => "Em aprovação",
        	"created_at" => date("Y-m-d H:i:s"),
        	"created_by" => 1
        ],
    	[
        	"status" => "Aguardando decisão",
        	"created_at" => date("Y-m-d H:i:s"),
        	"created_by" => 1
        ],
    	[
        	"status" => "Aprovado",
        	"created_at" => date("Y-m-d H:i:s"),
        	"created_by" => 1
        ],
    	[
        	"status" => "Recusado",
        	"created_at" => date("Y-m-d H:i:s"),
        	"created_by" => 1
        ]]);
    }
}
