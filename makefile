init:
	php artisan migrate:fresh --seed
	php artisan shield:generate --all
	php artisan shield:super-admin
	php artisan optimize:clear
	php artisan route:cache
	php artisan filament:optimize

optimize:
	php artisan route:cache
	php artisan filament:optimize
	php artisan shield:generate --all

route_list:
	php artisan route:list

model:
	php artisan make:model $(name) -m

create_migration:
	php artisan make:migration create_$(name)_table --create=$(name)

alter_migration:
	php artisan make:migration alter_$(name)_table --table=$(name)

fresource:
	php artisan migrate # run migration first
	php artisan make:filament-resource $(name) --generate --simple # auto generate resource and its content based on the model

fresource_only:
	php artisan make:filament-resource $(name) --generate --simple

fpage:
	php artisan make:filament-page $(name) --generate --simple

migrate:
	php artisan migrate