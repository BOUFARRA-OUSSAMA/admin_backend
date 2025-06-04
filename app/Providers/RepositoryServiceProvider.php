<?php

namespace App\Providers;

use App\Repositories\Interfaces\EloquentRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\PatientRepositoryInterface;
use App\Repositories\Interfaces\PermissionRepositoryInterface;
use App\Repositories\Interfaces\ActivityLogRepositoryInterface;
use App\Repositories\Eloquent\PermissionRepository;
use App\Repositories\Eloquent\ActivityLogRepository;
use App\Repositories\Eloquent\BaseRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Eloquent\PatientRepository;
use App\Repositories\Interfaces\AiAnalysisRepositoryInterface;
use App\Repositories\Eloquent\AiAnalysisRepository;
use App\Repositories\Interfaces\BillRepositoryInterface;
use App\Repositories\Eloquent\BillRepository;
use App\Repositories\Interfaces\BillItemRepositoryInterface;
use App\Repositories\Eloquent\BillItemRepository;
use Illuminate\Support\ServiceProvider;
use App\Repositories\Interfaces\PersonalInfoRepositoryInterface;
use App\Repositories\Eloquent\PersonalInfoRepository;


class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(EloquentRepositoryInterface::class, BaseRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(PatientRepositoryInterface::class, PatientRepository::class);
        $this->app->bind(PermissionRepositoryInterface::class, PermissionRepository::class);
        $this->app->bind(ActivityLogRepositoryInterface::class, ActivityLogRepository::class);
        $this->app->bind(AiAnalysisRepositoryInterface::class, AiAnalysisRepository::class);
        $this->app->bind(BillRepositoryInterface::class,BillRepository::class);
        $this->app->bind(BillItemRepositoryInterface::class,BillItemRepository::class);
        $this->app->bind(PersonalInfoRepositoryInterface::class,PersonalInfoRepository::class);
        // Register other repository bindings here
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
