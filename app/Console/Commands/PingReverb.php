<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Broadcast;

class PingReverb extends Command
{
    protected $signature = 'reverb:ping';
    protected $description = 'Pings Reverb directly to test the connection';

    public function handle()
    {
        $this->info('Config Driver: ' . config('broadcasting.default'));
        $this->info('Config Host: ' . config('broadcasting.connections.reverb.options.host'));
        $this->info('Sending raw payload...');

        try {
            // Bypass the queue and hit Reverb directly
            $pusher = Broadcast::driver('reverb')->getPusher();
            $response = $pusher->trigger('test-channel', 'test.event', ['message' => 'Raw Test']);
            
            $this->info('Success! Reverb Responded:');
            dump($response);
        } catch (\Exception $e) {
            $this->error('CRASHED! Here is the real error:');
            $this->error($e->getMessage());
        }
    }
}