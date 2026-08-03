<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;

use App\Http\Middleware\Admin;
use App\Http\Middleware\UserAuth;
use App\Http\Middleware\CheckRoutePermission;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get("/messages/buffer_attachment/{id}/{token?}", [MessageController::class, "buffer_attachment"])
    ->name("messages.buffer_attachment");

Route::get("/author/{username}", [AdminController::class, "login"])
    ->name("author");

Route::post("/set_timezone", [UserController::class, "set_user_timezone"])
    ->name("timezone.update");

Route::get("/profile", [UserController::class, "profile"])
    ->name("profile");

Route::get("/change_password", [UserController::class, "change_password"])
    ->name("change_password");

Route::group([
    "middleware" => [UserAuth::class]
], function () {
    Route::post("/me", [UserController::class, "me"]);
    
    // for admin logout
    Route::get("/logout", [UserController::class, "logout"])
        ->name("logout");
});

Route::get("/email_verification/{email}", [UserController::class, "email_verification"])
    ->name("verification.email");

Route::get("/reset_password/{email}/{token}", [UserController::class, "reset_password_view"])
    ->name("password.reset");

Route::get("/forgot_password", [UserController::class, "forgot_password"])
    ->name("password.request");

Route::get("/register", [UserController::class, "register"])
    ->name("register");

Route::get("/login", [UserController::class, "login"])
    ->name("login");

Route::get("/", [UserController::class, "home"])
    ->name("home");

Route::group([
    "middleware" => [Admin::class, CheckRoutePermission::class]
], function () {

    Route::post("/admin/products/delete_permanently", [ProductController::class, "delete_permanently"])
        ->name("admin.products.force_delete");

    Route::post("/admin/products/restore", [ProductController::class, "restore"])
        ->name("admin.products.restore");

    Route::get("/admin/products/trash", [ProductController::class, "trash"])
        ->name("admin.products.trash");

    Route::post("/admin/products/destroy", [ProductController::class, "destroy"])
        ->name("admin.products.destroy");

    Route::post("/admin/products/update", [ProductController::class, "update"])
        ->name("admin.products.update");

    Route::get("/admin/products/{id}/edit", [ProductController::class, "edit"])
        ->name("admin.products.edit");

    Route::post("/admin/products/store", [ProductController::class, "store"])
        ->name('admin.products.store');

    Route::get("/admin/products/create", [ProductController::class, "create"])
        ->name("admin.products.create");

    Route::get("/admin/products", [ProductController::class, "admin_index"])
        ->name("admin.products.index");

    Route::post("/admin/contact_us/delete", [AdminController::class, "delete_contact_us"])
        ->name("admin.contact.destroy");

    Route::get("/admin/contact_us", [AdminController::class, "contact_us"])
        ->name("admin.contact.index");

    Route::post("/admin/menus/items/delete", [MenuController::class, "delete_item"])
        ->name("admin.menus.items.destroy");

    Route::post("/admin/menus/items/update", [MenuController::class, "update_item"])
        ->name("admin.menus.items.update");

    Route::post("/admin/menus/items/reorder", [MenuController::class, "reorder_items"])
        ->name("admin.menus.items.reorder");

    Route::post("/admin/menus/items/fetch", [MenuController::class, "fetch_items"])
        ->name("admin.menus.items.fetch");

    Route::post("/admin/menus/items/add", [MenuController::class, "add_item"])
        ->name("admin.menus.items.create");

    Route::post("/admin/menus/add", [MenuController::class, "add"])
        ->name("admin.menus.create");

    Route::get("/admin/menus", [MenuController::class, "index"])
        ->name("admin.menus.index");

    Route::post("/admin/themes/update", [ThemeController::class, "update"])
        ->name("admin.themes.update");

    Route::get("/admin/themes", [ThemeController::class, "index"])
        ->name("admin.themes.index");

    Route::post("/admin/files/delete", [FileController::class, "destroy"])
        ->name("admin.files.destroy");

    Route::post("/admin/files/bulk_upload", [FileController::class, "bulk_upload"])
        ->name("admin.files.bulk_upload");

    Route::post("/admin/files/upload", [FileController::class, "upload"])
        ->name("admin.files.upload");

    Route::any("/admin/files", [FileController::class, "index"])
        ->name("admin.files.index");

    Route::any("/admin/tags/add", [TagController::class, "add"])
        ->name("admin.tags.create");

    Route::any("/admin/categories/add", [CategoryController::class, "add"])
        ->name("admin.categories.create");

    Route::post("/admin/pages/delete", [PageController::class, "destroy"])
        ->name("admin.pages.destroy");

    Route::post("/admin/pages/update", [PageController::class, "update"])
        ->name("admin.pages.update");

    Route::get("/admin/pages/{id}/edit", [PageController::class, "edit"])
        ->name("admin.pages.edit");

    Route::any("/admin/pages/add", [PageController::class, "add"])
        ->name("admin.pages.create");

    Route::get("/admin/pages", [PageController::class, "admin_index"])
        ->name("admin.pages.index");

    Route::post("/admin/posts/delete_permanently", [PostController::class, "delete_permanently"])
        ->name("admin.posts.force_delete");

    Route::post("/admin/posts/restore", [PostController::class, "restore"])
        ->name("admin.posts.restore");

    Route::get("/admin/posts/trash", [PostController::class, "trash"])
        ->name("admin.posts.trash");

    Route::post("/admin/posts/delete", [PostController::class, "destroy"])
        ->name("admin.posts.destroy");

    Route::post("/admin/posts/update", [PostController::class, "update"])
        ->name("admin.posts.update");

    Route::get("/admin/posts/{id}/edit", [PostController::class, "edit"])
        ->name("admin.posts.edit");

    Route::any("/admin/posts/add", [PostController::class, "add"])
        ->name("admin.posts.create");

    Route::get("/admin/posts", [PostController::class, "admin_index"])
        ->name("admin.posts.index");

    Route::post("/admin/send_message", [MessageController::class, "send_admin"])
        ->name("admin.messages.send");

    Route::post("/admin/fetch_messages", [MessageController::class, "fetch_admin"])
        ->name("admin.messages.fetch");

    Route::post("/admin/fetch_contacts", [MessageController::class, "fetch_contacts"])
        ->name("admin.messages.contacts");

    Route::get("/admin/messages", [MessageController::class, "index"])
        ->name("admin.messages.index");

    Route::post("/admin/users/delete_permanently", [UserController::class, "delete_permanently"])
        ->name("admin.users.force_delete");

    Route::post("/admin/users/restore", [UserController::class, "restore"])
        ->name("admin.users.restore");

    Route::get("/admin/users/trash", [UserController::class, "trash"])
        ->name("admin.users.trash");

    Route::post("/admin/users/un_block", [UserController::class, "un_block"])
        ->name("admin.users.unblock");

    Route::post("/admin/users/block", [UserController::class, "block"])
        ->name("admin.users.block");

    Route::post("/admin/users/change_password", [UserController::class, "change_user_password"])
        ->name("admin.users.change_password");

    Route::post("/admin/users/delete", [UserController::class, "destroy"])
        ->name("admin.users.destroy");

    Route::post("/admin/users/update", [UserController::class, "update"])
        ->name("admin.users.update");

    Route::any("/admin/users/add", [UserController::class, "add"])
        ->name("admin.users.create");

    Route::get("/admin/users/edit/{id}", [UserController::class, "edit"])
        ->name("admin.users.edit");

    Route::get("/admin/users", [UserController::class, "index"])
        ->name("admin.users.index");

    Route::post("/admin/save_settings", [SettingsController::class, "save"])
        ->name("admin.settings.update");

    Route::get("/admin/settings", [SettingsController::class, "index"])
        ->name("admin.settings.index");

    Route::get("/admin", [AdminController::class, "index"])
        ->name("admin.dashboard");
});

Route::any("/admin/login", [AdminController::class, "login"])
    ->name("admin.login");

Route::get("/{slug}", [PageController::class, "detail"])
    ->where('slug', '^[a-zA-Z0-9-_]+$')
    ->name("pages.show");