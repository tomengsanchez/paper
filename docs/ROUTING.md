# Routing — Paano Gumagana ang Routes

Lahat ng web routes ay nasa **routes/web.php**. Ang `$router` ay available doon (injected from `public/index.php`).

---

## Syntax

```php
$router->get($uri, 'ControllerName@methodName');
$router->post($uri, 'ControllerName@methodName');
```

- **ControllerName** — Class sa `App\Controllers\ControllerName` (file: `App/Controllers/ControllerName.php`).
- **methodName** — Public method sa controller. Tinatawag na may route parameters bilang arguments.

---

## URI at parameters

- **Fixed path:** `/login`, `/settings`, `/users`.
- **Parameter:** `{name}`. Halimbawa: `{id}`, `{slug}`.

**Halimbawa:**

```php
$router->get('/user/view/{id}', 'UserController@show');
// GET /user/view/5 → UserController::show('5')

$router->get('/post/{slug}/comment/{cid}', 'PostController@comment');
// GET /post/hello-world/comment/3 → PostController::comment('hello-world', '3')
```

Parameter ay **string**; kung kailangan integer, i-cast sa controller: `$id = (int) $id;`.

---

## Order of routes

Ang router ay nagma-match **una** ang unang nag-match. I-place ang mas specific na route **bago** ang mas generic.

Halimbawa:

```php
$router->get('/user/create', 'UserController@create');   // specific first
$router->get('/user/view/{id}', 'UserController@show');   // then parametric
$router->get('/user/{id}', 'UserController@profile');     // generic last
```

---

## 404

Kung walang route na nag-match (method + URI), ang Router ay nagse-set ng **404** at nag-echo ng "404 Not Found". Pwede mong i-customize ang behavior sa `Core/Router.php` (e.g. render a view o redirect).

---

## BASE_URL

Kung may `base_url` sa `config/app.php` (e.g. `/myapp`), ang application ay naka-mount sa subfolder. Ang `REQUEST_URI` na nakikita ng Router ay depende sa server config. Siguraduhin na ang rewrite o document root ay tama para ang path na pumapasok sa Router ay walang base_url prefix (o i-strip sa bootstrap/router kung kinakailangan). Sa kasalukuyang setup, kadalasan ang server na ang nag-handle nito kapag naka-set ang DocumentRoot sa `public/` at naka-subfolder ang app.

---

## Middleware (future)

Ang Router ay may placeholder para sa middlewares sa `addRoute()`. Pwede mong i-extend ang `Core\Router` para tumawag ng middleware (e.g. auth check) bago i-invoke ang controller action.

---

## Halimbawa: CRUD routes

```php
$router->get('/item', 'ItemController@index');
$router->get('/item/create', 'ItemController@create');
$router->post('/item/store', 'ItemController@store');
$router->get('/item/view/{id}', 'ItemController@show');
$router->get('/item/edit/{id}', 'ItemController@edit');
$router->post('/item/update/{id}', 'ItemController@update');
$router->post('/item/delete/{id}', 'ItemController@delete');
```

Sa controller, auth at CSRF:

```php
public function store(): void
{
    $this->requireAuth();
    $this->validateCsrf();
    // ...
}
```

---

Tingnan din: [CREATING_MODULES.md](CREATING_MODULES.md) para sa full CRUD flow.
