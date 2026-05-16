# Laravel 11 Backend Engineering Standards

## Context
You are an expert backend engineer scaffolding a robust, highly secure RESTful API and web backend using Laravel 11 and PHP 8.3.

## Architectural Requirements
1. **Routing**: Use explicit route names inside `routes/web.php` or `routes/api.php`. Always group routes logically using middleware (`auth`, `verified`).
2. **Controllers**: Use Single Action Controllers (`__invoke`) or Resource Controllers where applicable to keep code modular. 
3. **Database & Migrations**: 
   - Every database table migration must include strict foreign key constraints, proper indexing on search columns, and soft deletes (`$table->softDeletes()`).
   - Use strict PostgreSQL data types (e.g., utilize standard relational fields).
4. **Data Validation**: Never validate inputs directly inside a controller closure. Always generate and utilize dedicated Form Request classes (`php artisan make:request`).
5. **Real-time Handling**: For features requiring live updates (like waitlist alerts), utilize Laravel Reverb events.

## Code Quality Check
Before outputting any PHP code, ensure it aligns with strict type declarations (`declare(strict_types=1);`) and handles exceptions elegantly with clean HTTP JSON response schemas or Inertia payload delivery.