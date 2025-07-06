<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Str;

class UpdateNullUsernames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:update-null-usernames';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update users with NULL username with a default value';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $usersWithNullUsername = User::whereNull('username')->get();
        $count = $usersWithNullUsername->count();

        if ($count === 0) {
            $this->info('Tidak ada pengguna dengan username NULL.');
            return;
        }

        $this->info("Ditemukan {$count} pengguna dengan username NULL.");
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($usersWithNullUsername as $user) {
            // Membuat username dari email pengguna
            $baseUsername = explode('@', $user->email)[0];
            $username = $baseUsername;
            $counter = 1;

            // Memastikan username unik
            while (User::where('username', $username)->where('id', '!=', $user->id)->exists()) {
                $username = $baseUsername . $counter;
                $counter++;
            }

            $user->username = $username;
            $user->save();

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Username berhasil diperbarui untuk semua pengguna.');
    }
}
