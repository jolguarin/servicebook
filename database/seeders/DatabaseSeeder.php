<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingStatusLog;
use App\Models\Category;
use App\Models\Service;
use App\Models\StaffProfile;
use App\Models\User;
use App\Models\WorkingHour;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['admin', 'staff', 'client'] as $name) {
            Role::findOrCreate($name);
        }

        $admin = User::create([
            'name' => 'Demo Admin',
            'email' => 'admin@servicebook.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        $categories = collect([
            ['Hair & Beauty', 'hair-beauty'],
            ['Wellness', 'wellness'],
            ['Repairs', 'repairs'],
            ['Consultations', 'consultations'],
        ])->map(fn ($c) => Category::create(['name' => $c[0], 'slug' => $c[1]]));

        $services = $categories->flatMap(fn ($category) => Service::factory(3)->create([
            'category_id' => $category->id,
        ]));

        $staff = collect(range(1, 4))->map(function ($i) use ($services) {
            $user = User::create([
                'name' => fake()->name(),
                'email' => "staff{$i}@servicebook.test",
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
            $user->assignRole('staff');

            $profile = StaffProfile::create([
                'user_id' => $user->id,
                'bio' => fake()->sentence(10),
            ]);
            $profile->services()->attach($services->random(6)->pluck('id'));

            foreach (range(1, 5) as $day) { // Monday to Friday
                WorkingHour::create([
                    'staff_profile_id' => $profile->id,
                    'day_of_week' => $day,
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                ]);
            }

            return $profile;
        });

        $clients = collect(range(1, 20))->map(function ($i) {
            $user = User::create([
                'name' => fake()->name(),
                'email' => $i === 1 ? 'client@servicebook.test' : "client{$i}@servicebook.test",
                'phone' => fake()->numerify('09#########'),
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
            $user->assignRole('client');

            return $user;
        });

        // About 200 bookings over the last and next 60 days
        foreach (range(1, 200) as $n) {
            $staffProfile = $staff->random();
            $service = $staffProfile->services->random();
            $start = now()->addDays(rand(-60, 60))->setTime(rand(9, 15), [0, 30][rand(0, 1)]);
            $isPast = $start->isPast();
            $status = $isPast
                ? fake()->randomElement([BookingStatus::Completed, BookingStatus::Completed, BookingStatus::Cancelled, BookingStatus::NoShow])
                : fake()->randomElement([BookingStatus::Pending, BookingStatus::Confirmed]);

            $booking = Booking::create([
                'reference_no' => 'SB-'.$start->format('Y').'-'.str_pad($n, 6, '0', STR_PAD_LEFT),
                'client_id' => $clients->random()->id,
                'staff_profile_id' => $staffProfile->id,
                'service_id' => $service->id,
                'starts_at' => $start,
                'ends_at' => $start->copy()->addMinutes($service->duration_minutes),
                'status' => $status,
                'price' => $service->price,
            ]);

            BookingStatusLog::create([
                'booking_id' => $booking->id,
                'from_status' => null,
                'to_status' => $status->value,
                'changed_by' => $admin->id,
            ]);
        }
    }
}