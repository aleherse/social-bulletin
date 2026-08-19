# Scaffolding / Folders

## scaffolding-folders-0001: Controllers live in per-aggregate folders

**WHEN** adding a Symfony controller to `apps/api/src/Controller`

**THEN** place it under a per-aggregate subfolder matching the aggregate it serves, with the namespace updated to
match — not flat in `Controller/` with a bare `App\Controller` namespace.

**Example:**

| Wrong                                                                                | Right                                                                                                  |
|--------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------|
| `apps/api/src/Controller/ListCategoriesController.php` (`namespace App\Controller;`) | `apps/api/src/Controller/Movement/ListCategoriesController.php` (`namespace App\Controller\Movement;`) |
| `apps/api/src/Controller/SignInController.php` (`namespace App\Controller;`)         | `apps/api/src/Controller/User/SignInController.php` (`namespace App\Controller\User;`)                 |
