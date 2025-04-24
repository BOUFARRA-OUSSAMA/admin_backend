php artisan tinker

$adminRole = App\Models\Role::where('code', 'admin')->first();

// Create admin user
$admin = App\Models\User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => Hash::make('password'),
    'status' => 'active'
]);

// Assign admin role
$admin->roles()->attach($adminRole->id);

exit;