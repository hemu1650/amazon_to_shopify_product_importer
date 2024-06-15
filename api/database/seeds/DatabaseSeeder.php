<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Faker\Factory as Faker;
use App\User;
use App\News;
class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        foreach(range(1, 50) as $index)
        {
            User::create(array(
                'email' => $faker->email,
                'name' => $faker->userName,
                'password' => bcrypt(str_random(10)),
            ));
        }



        foreach(range(1, 50) as $index)
        {
            App\Task::create(array(
                'title' => $faker->Text,
                'description' => $faker->Text,
                'start_date' => $faker->DateTime,
                'end_date' => $faker->DateTime,
                'task_status' => $faker->numberBetween(1,10),
                'user_id' => $faker->DateTime,
                'category_id' => $faker->numberBetween(1,10),
                'created_at' => $faker->numberBetween(1,10),

            ));
        }
        foreach(range(1, 50) as $index)
        {
            App\Tag::create(array(
                'tag' => $faker->Company,
                'task_id' => $faker->numberBetween(1,10),
            ));
        }
         foreach(range(1, 50) as $index)
         {
             App\Customer::create(array(
                 'name' => $faker->Company,

                 'phone' => $faker->PhoneNumber,
                 'fax' => $faker->PhoneNumber,
                 'address' => $faker->Address,
                 'postalcode' => $faker->numberBetween(1,10),
                 'avatar_url' => $faker->Image,
                 'email' => $faker->Email,
                 'description' => $faker->paragraph,
                 'created_at' => $faker->DateTime,
             ));
         }
        foreach(range(1, 50) as $index)
        {
            App\Comment::create(array(
                'comment_text' => $faker->Text,
                'user_id' => $faker->numberBetween(1,10),
                'task_id' => $faker->numberBetween(1,10),
                'created_at' => $faker->DateTime,

            ));
        }
        foreach(range(1, 50) as $index)
        {
            App\Request::create(array(
                'title' => $faker->Text,
                'description' => $faker->paragraph,
                'attach_url' => $faker->Image,
                'customer_id' => $faker->numberBetween(1,10),
                'category_id' => $faker->numberBetween(1,10),
                'created_at' => $faker->DateTime,

            ));
        }


    }
}
