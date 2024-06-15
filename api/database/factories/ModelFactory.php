<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(App\User::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->email,
        'password' => bcrypt(str_random(10)),
        'remember_token' => str_random(10),
    ];
});
$factory->define(App\Task::class, function (Faker\Generator $faker) {
    return [
        'title' => $faker->Text,
        'description' => $faker->Lorem,
         'start_date' => $faker->DateTime,
        'end_date' => $faker->DateTime,
        'task_status' => $faker->numberBetween(1,10),
        'user_id' => $faker->DateTime,
        'category_id' => $faker->numberBetween(1,10),
        'created_at' => $faker->numberBetween(1,10),
    ];
});

$factory->define(App\Tag::class, function (Faker\Generator $faker) {
    return [
        'tag' => $faker->Company,
        'task_id' => $faker->numberBetween(1,10),
    ];
});

$factory->define(App\Customer::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->Company,
        'website' => $faker->Internet,
        'phone' => $faker->PhoneNumber,
        'fax' => $faker->PhoneNumber,
        'address' => $faker->Address,
        'postalcode' => $faker->numberBetween(1,10),
        'avatar_url' => $faker->Image,
        'email' => $faker->Email,
        'description' => $faker->paragraph,
        'created_at' => $faker->DateTime,
    ];
});

$factory->define(App\Comment::class, function (Faker\Generator $faker) {
    return [
        'comment_text' => $faker->Text,
        'user_id' => $faker->numberBetween(1,10),
        'task_id' => $faker->numberBetween(1,10),
        'created_at' => $faker->DateTime,
    ];
});

$factory->define(App\Request::class, function (Faker\Generator $faker) {
    return [
        'title' => $faker->Text,
        'description' => $faker->paragraph,
        'attach_url' => $faker->Image,
        'customer_id' => $faker->numberBetween(1,10),
        'category_id' => $faker->numberBetween(1,10),
        'created_at' => $faker->DateTime,
    ];
});