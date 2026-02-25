# Stubs — Template Files for New Modules

Copy these files when creating a new CRUD module. **Find & replace** the placeholders with your resource name.

| File | Copy to | Replace |
|------|--------|--------|
| **Controller.stub.php** | `App/Controllers/YourNameController.php` | `ResourceName` → YourName (PascalCase), `resource_name` → your_name (snake_case for view path/currentPage) |
| **Model.stub.php** | `App/Models/YourName.php` | `ResourceName` → YourName, `resource_names` → your table name |
| **migration.stub.php** | `database/migration_XXX_your_name.php` | `XXX` → next migration number, `resource_names` → your table name |

## Example: "Item" module

1. **Migration:** Copy `migration.stub.php` → `database/migration_013_items.php`. Replace `resource_names` with `items`, and `name` in return array with `migration_013_items`. Run `php cli/migrate.php`.
2. **Model:** Copy `Model.stub.php` → `App/Models/Item.php`. Replace `ResourceName` with `Item`, `resource_names` with `items`. Adjust columns to match migration.
3. **Controller:** Copy `Controller.stub.php` → `App/Controllers/ItemController.php`. Replace `ResourceName` with `Item`, `resource_name` with `item`.
4. **Views:** Create `App/Views/item/index.php`, `form.php`, `view.php` (see docs/CREATING_MODULES.md).
5. **Routes:** In `routes/web.php` add GET/POST routes for `/item`, `/item/create`, `/item/store`, `/item/view/{id}`, etc.

See **docs/CREATING_MODULES.md** for full step-by-step and view examples.
