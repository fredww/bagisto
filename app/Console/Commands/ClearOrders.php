<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearOrders extends Command
{
    protected $signature = 'orders:clear';

    protected $description = '清除所有订单记录';

    public function handle()
    {
        $count = DB::table('orders')->count();

        DB::beginTransaction();

        try {
            DB::table('orders')->delete();

            DB::commit();

            $this->info("已清除订单记录：{$count} 条");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->error('清除订单失败：' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}

