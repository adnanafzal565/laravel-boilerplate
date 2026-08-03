<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use Storage;
use Validator;

use App\Models\User;
use App\Helpers\Constants;

class AdminController extends Controller
{
    public function delete_contact_us()
    {
        $validator = Validator::make(request()->all(), [
            "id" => "required"
        ]);

        if ($validator->fails())
        {
            return response()->json([
                "status" => "error",
                "message" => $validator->errors()->first()
            ]);
        }

        $id = (int) (request()->id ?? 0);

        DB::table("contact_us")
            ->where("id", "=", $id)
            ->delete();

        return response()->json([
            "status" => "success",
            "message" => "Message has been deleted."
        ]);
    }

    public function contact_us()
    {
        set_timezone();
        
        $data = DB::table("contact_us")
            ->orderBy("created_at", "desc")
            ->orderBy("is_read", "desc")
            ->paginate();

        $ids = [];
        foreach ($data as $d)
        {
            $ids[] = $d->id;
        }

        DB::table("contact_us")
            ->whereIn("id", $ids)
            ->update([
                "is_read" => 1,
                "updated_at" => now()->utc()
            ]);

        return view("admin/contact_us", [
            "data" => $data
        ]);
    }
    
    public function index()
    {
        $user = auth()->user();

        $user_labels = [];
        $user_counts = 0;
        $users = $posts = $pages = 0;

        if ($user->has_route_access('admin.users.index')) {
            $users = DB::table("users")->count();

            $user_data = DB::table('users')
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupByRaw('DATE(created_at)')
                ->orderByRaw('DATE(created_at)')
                ->get();

            // Convert to format suitable for Chart.js
            $user_labels = $user_data->pluck('date')->toArray();
            $user_counts = $user_data->pluck('count')->toArray();
        }

        if ($user->has_route_access('admin.posts.index')) {
            $posts = DB::table("posts")->count();
        }

        if ($user->has_route_access('admin.pages.index')) {
            $pages = DB::table("pages")->count();
        }

        return view("admin/index", [
            "users" => $users,
            "user_labels" => $user_labels,
            "user_counts" => $user_counts,
            "posts" => $posts,
            "pages" => $pages,
        ]);
    }

    public function login()
    {
        if (request()->isMethod("post"))
        {
            $validator = Validator::make(request()->all(), [
                "email" => "required",
                "password" => "required"
            ]);

            if ($validator->fails())
            {
                return response()->json([
                    "status" => "error",
                    "message" => $validator->errors()->first()
                ]);
            }

            $email = request()->email ?? "";
            $password = request()->password ?? "";

            $user = User::where("email", "=", $email)->first();

            if ($user == null)
            {
                return response()->json([
                    "status" => "error",
                    "message" => "Email does not exist."
                ]);
            }

            if (!password_verify($password, $user->password))
            {
                return response()->json([
                    "status" => "error",
                    "message" => "In-correct password."
                ]);
            }

            if (is_null($user->email_verified_at))
            {
                return response()->json([
                    "status" => "error",
                    "message" => "Email not verified."
                ]);
            }

            if (!in_array($user->type, ["admin", "super_admin"]))
            {
                return response()->json([
                    "status" => "error",
                    "message" => "Un-authorized."
                ]);
            }

            if (auth()->attempt([
                "email" => $email,
                "password" => $password
            ], true))
            {
                /*if (request()->expectsJson())
                {
                    $token = $user->createToken(config("config.token_secret"))->plainTextToken;

                    return response()->json([
                        "status" => "success",
                        "message" => "Login successfully.",
                        "access_token" => $token
                    ]);
                }*/

                return response()->json([
                    "status" => "success",
                    "message" => "Login successfully."
                ]);
            }

            return response()->json([
                "status" => "error",
                "message" => "In-valid credentials."
            ]);
        }
        return view("admin/login");
    }
}
