<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\AtualizacaoCronController;


class VerificacaoCronCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:verificacao';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'verificação se o cron foi executado';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $atualizacaoCronControllerObj = new AtualizacaoCronController();
        $atualizacaoCronControllerObj->verificacao();
    }
}
