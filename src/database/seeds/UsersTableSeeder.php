<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'name' => '先生太郎',
            'email' => 'teacher@example.com',
            'password' => bcrypt('password'),
            'is_teacher' => 1,
        ]);

        $students = ['田中太郎','山田太郎','佐藤太郎'];
        $emails = ['taro1@example.com','taro2@example.com','taro3@example.com'];
        for ($i = 0; $i<3; $i++) {
            DB::table('users')->insert([
                'attend_num' => $i+1,
                'name' => $students[$i],
                'email' => $emails[$i],
                'password' => bcrypt('password'),
                'teacher_id' => 1,
                'is_teacher' => 0,
            ]);
        }
    }
}
