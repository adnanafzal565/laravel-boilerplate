<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use Str;
use View;
use Mail;
use Storage;
use Validator;

use App\Models\User;
use App\Jobs\AddUserJob;

class UserController extends Controller
{
    public function set_user_timezone()
    {
        session()->put(
            config("config.session_timezone_key"),
            request()->timezone ?? ""
        );
    }

    public function delete_permanently()
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

        $me = auth()->user();
        $id = request()->id ?? 0;

        $user = User::onlyTrashed()
            ->where("id", $id)
            ->where("type", "!=", "super_admin")
            ->first();

        if (!$user) {
            return response()->json([
                "status" => "error",
                "message" => "User does not exist."
            ]);
        }

        $user->forceDelete();

        return response()->json([
            "status" => "success",
            "message" => "User has been removed."
        ]);
    }

    public function restore()
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

        $me = auth()->user();
        $id = request()->id ?? 0;

        $user = User::onlyTrashed()
            ->where("id", $id)
            ->first();

        if (!$user) {
            return response()->json([
                "status" => "error",
                "message" => "User does not exist."
            ]);
        }

        $user->restore();

        return response()->json([
            "status" => "success",
            "message" => "User has been restored."
        ]);
    }

    public function trash()
    {
        set_timezone();

        $users = User::onlyTrashed()
            ->orderBy("deleted_at", "desc")
            ->paginate(config("config.PER_PAGE"));

        return view("admin/users/trash", [
            "users" => $users
        ]);
    }

    public function send_contact_us_message(Request $request)
    {
        $contact_time = (int) ($request->contact_time ?? 0);

        $abort = !empty($request->website) || $contact_time === 0 || ((time() - $contact_time) < 3);

        if ($abort) {
            abort(404);
        }

        $validator = Validator::make(request()->all(), [
            "name" => "required",
            "email" => "required",
            "message" => "required"
        ]);

        if ($validator->fails())
        {
            return response()->json([
                "status" => "error",
                "message" => $validator->errors()->first()
            ]);
        }

        $name = request()->name ?? "";
        $email = request()->email ?? "";
        $message = request()->message ?? "";
        $ip = request()->ip();

        DB::table("contact_us")
            ->insertGetId([
                "name" => $name,
                "email" => $email,
                "message" => $message,
                "ip" => $ip,
                "created_at" => now()->utc(),
                "updated_at" => now()->utc()
            ]);

        return response()->json([
            "status" => "success",
            "message" => "Thank You. Your message has been received. We will reply as soon as we can."
        ]);
    }

    public function un_block()
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

        $id = request()->id ?? 0;

        $user = DB::table("users")
            ->where("id", "=", $id)
            ->first();

        if ($user == null)
        {
            return response()->json([
                "status" => "error",
                "message" => "User not found."
            ]);
        }

        DB::table("users")
            ->where("id", "=", $user->id)
            ->update([
                "is_block" => 0,
                "updated_at" => now()->utc()
            ]);

        return response()->json([
            "status" => "success",
            "message" => "User has been un-blocked."
        ]);
    }

    public function block()
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

        $id = request()->id ?? 0;

        $user = DB::table("users")
            ->where("id", "=", $id)
            ->first();

        if ($user == null)
        {
            return response()->json([
                "status" => "error",
                "message" => "User not found."
            ]);
        }

        DB::table("users")
            ->where("id", "=", $user->id)
            ->update([
                "is_block" => 1,
                "updated_at" => now()->utc()
            ]);

        return response()->json([
            "status" => "success",
            "message" => "User has been blocked."
        ]);
    }

    public function change_user_password()
    {
        $validator = Validator::make(request()->all(), [
            "id" => "required",
            "password" => "required"
        ]);

        if ($validator->fails())
        {
            return response()->json([
                "status" => "error",
                "message" => $validator->errors()->first()
            ]);
        }

        $admin = auth()->user();

        $id = request()->id ?? 0;
        $password = request()->password ?? "";

        $user = DB::table("users")
            ->where("id", "=", $id)
            ->where("type", "!=", "super_admin")
            ->first();

        if ($user == null)
        {
            return response()->json([
                "status" => "error",
                "message" => "User not found."
            ]);
        }

        DB::table("users")
            ->where("id", "=", $user->id)
            ->update([
                "password" => password_hash($password, PASSWORD_DEFAULT),
                "updated_at" => now()->utc()
            ]);

        return response()->json([
            "status" => "success",
            "message" => "Password has been set."
        ]);
    }

    public function update()
    {
        $validator = Validator::make(request()->all(), [
            "id" => "required",
            "name" => "required",
            "type" => "required|in:user,admin"
        ]);

        if ($validator->fails())
        {
            return response()->json([
                "status" => "error",
                "message" => $validator->errors()->first()
            ]);
        }

        $me = auth()->user();

        $id = request()->id ?? 0;
        $name = request()->name ?? "";
        $type = request()->type ?? "";
        $routes = request()->routes ?? [];

        $user = User::where("id", $id)
            ->where("type", "!=", "super_admin")
            ->first();

        if (!$user) {
            return response()->json([
                "status" => "error",
                "message" => "User not found."
            ]);
        }

        if ($me->is_super_admin()) {
            $user->route_permissions()->delete();

            foreach ($routes as $route_name) {
                $user->route_permissions()->create([
                    'route_name' => $route_name
                ]);
            }
        }

        $user->name = $name;
        $user->type = $type;
        $user->save();

        return response()->json([
            "status" => "success",
            "message" => "User has been updated."
        ]);
    }

    public function add(Request $request)
    {
        if (request()->isMethod("post")) {
            $validator = Validator::make(request()->all(), [
                "name" => "required",
                "email" => "required",
                "password" => "required",
                "type" => "required|in:user,admin"
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "status" => "error",
                    "message" => $validator->errors()->first()
                ]);
            }

            $me = auth()->user();
            $routes = request()->routes ?? [];

            $email = $request->email;
            $username = strtok($email, "@");

            $user = User::where("email", $email)
                ->orWhere("username", $username)
                ->exists();

            if ($user) {
                return response()->json([
                    "status" => "error",
                    "message" => "User with same email/username already exists."
                ]);
            }

            $user = User::create([
                'name' => $request->name,
                'username' => $username,
                'email' => $email,
                'password' => $request->password,
                'type' => $request->type,
                'email_verified_at' => now()->utc()
            ]);

            if ($me->is_super_admin()) {
                foreach ($routes as $route_name) {
                    $user->route_permissions()->create([
                        'route_name' => $route_name
                    ]);
                }
            }

            if ($request->has('send_password_email')) {
                dispatch(new AddUserJob($user, $request->password));
            }

            return response()->json([
                "status" => "success",
                "message" => "User has been created.",
                "user" => $user
            ]);
        }

        $routes = fetch_routes();

        return view("admin/users/add", [
            'routes' => $routes
        ]);
    }

    public function edit()
    {
        $id = request()->id ?? 0;

        $user = User::where("id", $id)
            ->where("type", "!=", "super_admin")
            ->first();

        if (!$user)
            abort(404);

        $routes = fetch_routes();

        return view("admin/users/edit", [
            "id" => $id,
            "user" => $user,
            'routes' => $routes
        ]);
    }

    public function destroy()
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

        $admin = auth()->user();
        $id = request()->id ?? 0;

        $user = User::where("id", $id)
            ->where("type", "!=", "super_admin")
            ->first();

        if (!$user) {
            return response()->json([
                "status" => "error",
                "message" => "User not found."
            ]);
        }

        $user->delete();

        return response()->json([
            "status" => "success",
            "message" => "User has been deleted."
        ]);
    }

    public function index()
    {
        set_timezone();
        $not_type = ["super_admin"];

        $me = auth()->user();
        $users = User::query();

        if (!$me->is_super_admin())
            $not_type[] = "admin";

        $users = $users->whereNotIn("type", $not_type)
            ->orderBy("id", "desc")
            ->paginate(config("config.PER_PAGE"));

        return view("admin/users/index", [
            "users" => $users
        ]);
    }

    public function change_password()
    {
        if (request()->isMethod("post"))
        {
            $validator = Validator::make(request()->all(), [
                "current_password" => "required",
                "new_password" => "required"
            ]);

            if ($validator->fails())
            {
                return response()->json([
                    "status" => "error",
                    "message" => $validator->errors()->first()
                ]);
            }

            $user = auth()->user();
            $current_password = request()->current_password ?? "";
            $new_password = request()->new_password ?? "";

            if (!password_verify($current_password, $user->password))
            {
                return response()->json([
                    "status" => "error",
                    "message" => "In-correct password."
                ]);
            }

            DB::table("users")
                ->where("id", "=", $user->id)
                ->update([
                    "password" => password_hash($new_password, PASSWORD_DEFAULT),
                    "updated_at" => now()->utc()
                ]);

            return response()->json([
                "status" => "success",
                "message" => "Password has been changed."
            ]);
        }

        return view("theme::change_password");
    }

    public function home()
    {
        if (!View::exists("theme::home"))
        {
            abort(404);
        }
        return view("theme::home");
    }

    public function verify_email()
    {
        $validator = Validator::make(request()->all(), [
            "email" => "required",
            "code" => "required"
        ]);

        if (!$validator->passes() && count($validator->errors()->all()) > 0)
        {
            return response()->json([
                "status" => "error",
                "message" => $validator->errors()->all()[0]
            ]);
        }

        $email = request()->email ?? "";
        $code = request()->code ?? "";

        $user = DB::table("users")
            ->where("email", "=", $email)
            ->where("verification_code", "=", $code)
            ->first();

        if ($user == null)
        {
            return response()->json([
                "status" => "error",
                "message" => "Verification code expired."
            ]);
        }

        DB::table("users")
            ->where("id", "=", $user->id)
            ->update([
                // "verification_code" => null,
                "email_verified_at" => now()->utc(),
                "updated_at" => now()->utc()
            ]);

        return response()->json([
            "status" => "success",
            "message" => "Account has been verified. You can login now."
        ]);
    }

    public function reset_password()
    {
        $validator = Validator::make(request()->all(), [
            "email" => "required",
            "token" => "required",
            "password" => "required",
            "password_confirmation" => "required"
        ]);

        if (!$validator->passes() && count($validator->errors()->all()) > 0)
        {
            return response()->json([
                "status" => "error",
                "message" => $validator->errors()->all()[0]
            ]);
        }

        $email = request()->email ?? "";
        $token = request()->token ?? "";
        $password = request()->password ?? "";
        $password_confirmation = request()->password_confirmation ?? "";

        $password_reset_token = DB::table("password_reset_tokens")
            ->where("email", "=", $email)
            ->where("token", "=", $token)
            ->first();

        if ($password_reset_token == null)
        {
            return response()->json([
                "status" => "error",
                "message" => "Reset link is expired."
            ]);
        }

        if ($password != $password_confirmation)
        {
            return response()->json([
                "status" => "error",
                "message" => "Password mis-match."
            ]);
        }

        DB::table("password_reset_tokens")
            ->where("email", "=", $email)
            ->where("token", "=", $token)
            ->delete();

        DB::table("users")
            ->where("email", "=", $email)
            ->update([
                "password" => password_hash($password, PASSWORD_DEFAULT),
                "updated_at" => now()->utc()
            ]);

        return response()->json([
            "status" => "success",
            "message" => "Password has been reset."
        ]);
    }

    public function send_password_reset_link()
    {
        $validator = Validator::make(request()->all(), [
            "email" => "required"
        ]);

        if (!$validator->passes() && count($validator->errors()->all()) > 0)
        {
            return response()->json([
                "status" => "error",
                "message" => $validator->errors()->all()[0]
            ]);
        }

        $email = request()->email ?? "";

        $user = DB::table("users")
            ->where("email", "=", $email)
            ->first();

        if ($user == null)
        {
            return response()->json([
                "status" => "error",
                "message" => "User not found."
            ]);
        }

        // $reset_token = time() . md5($email);
        $reset_token = Str::random(60);

        $message = "<p>Please click the link below to reset your password</p>";
        $message .= "<a href='" . url("/reset-password?email=" . $email . "&token=" . $reset_token) . "'>";
            $message .= "Reset password";
        $message .= "</a>";

        $mail_error = $this->send_mail($email, $user->name, "Password reset link", $message);
        if (!empty($mail_error))
        {
            return response()->json([
                "status" => "error",
                "message" => $mail_error
            ]);
        }

        DB::table("password_reset_tokens")
            ->insertGetId([
                "email" => $email,
                "token" => $reset_token,
                "created_at" => now()->utc()
            ]);

        return response()->json([
            "status" => "success",
            "message" => "Instructions to reset password has been sent."
        ]);
    }

    public function profile()
    {
        if (request()->isMethod("post"))
        {
            $validator = Validator::make(request()->all(), [
                "name" => "required"
            ]);

            if ($validator->fails())
            {
                return response()->json([
                    "status" => "error",
                    "message" => $validator->errors()->first()
                ]);
            }

            $user = auth()->user();
            $name = request()->name ?? "";
            $file_path = $user->profile_image;

            if (request()->file("profile_image"))
            {
                if ($user->profile_image && Storage::exists("public/" . $user->profile_image))
                {
                    Storage::delete("public/" . $user->profile_image);
                }

                $file = request()->file("profile_image");
                $file_path = "users/" . $user->id . "-" . uniqid() . "." . $file->getClientOriginalExtension();
                $file->storeAs("/public", $file_path);

                chmod(storage_path("app/public/users"), 0755);
            }

            DB::table("users")
                ->where("id", "=", $user->id)
                ->update([
                    "name" => $name,
                    "profile_image" => $file_path,
                    "updated_at" => now()->utc()
                ]);

            return response()->json([
                "status" => "success",
                "message" => "Profile has been saved."
            ]);
        }

        return view("theme::profile");
    }

    public function logout()
    {
        if (request()->expectsJson())
        {
            $user = auth()->user();

            // $user->tokens()->delete();

            $user->currentAccessToken()->delete();

            // $user->tokens()->where("id", $token_id)->delete();

            return response()->json([
                "status" => "success",
                "message" => "User has been logged-out."
            ]);
        }

        auth()->logout();
        return redirect()->back();
    }

    public function me()
    {
        $user = auth()->user();

        /*$client_ip = $_SERVER['REMOTE_ADDR'] ?? "";
        // $client_ip = "223.123.88.250";
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? "";

        $timestamp = strtotime($user->last_location_at);

        $current_timestamp = time();

        $difference = $current_timestamp - $timestamp;

        $twenty_four_hours_in_seconds = 24 * 60 * 60;

        if ($difference >= $twenty_four_hours_in_seconds)
        {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "http://www.geoplugin.net/json.gp?ip=" . $client_ip,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([]),
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json"
                ]
            ]);
            $response = curl_exec($curl);

            if (curl_errno($curl))
            {
                $error = curl_error($curl);
            }
            else
            {
                $response = json_decode($response);
                if ($response->geoplugin_status == 200)
                {
                    $location = [
                        "city" => $response->geoplugin_city,
                        "continent" => $response->geoplugin_continentName,
                        "continentCode" => $response->geoplugin_continentCode,
                        "country" => $response->geoplugin_countryName,
                        "countryCode" => $response->geoplugin_countryCode,
                        "currencyCode" => $response->geoplugin_currencyCode,
                        "currencySymbol" => $response->geoplugin_currencySymbol,
                        "currencyConverter" => $response->geoplugin_currencyConverter,
                        "latitude" => $response->geoplugin_latitude,
                        "longitude" => $response->geoplugin_longitude,
                        "region" => $response->geoplugin_region,
                        "ipAddress" => $response->geoplugin_request,
                        "timezone" => $response->geoplugin_timezone,
                        "user_agent" => $user_agent
                    ];

                    DB::table("users")
                        ->where("id", "=", $user->id)
                        ->update([
                            "location" => json_encode($location),
                            "last_location_at" => now()->utc()
                        ]);
                }
            }
            curl_close($curl);
        }*/

        $new_messages = DB::table("notifications")
            ->where("user_id", "=", $user->id)
            ->where("is_read", "=", 0)
            ->where("type", "=", "new_message")
            ->count();

        // request()->session()->put($this->user_session_key, $user->id);

        return response()->json([
            "status" => "success",
            "message" => "Data has been fetched.",
            "user" => $user,
            "new_messages" => $new_messages
        ]);
    }

    public function login()
    {
        if (request()->isMethod("post"))
        {
            $validator = Validator::make(request()->all(), [
                "username" => "required",
                "password" => "required"
            ]);

            if ($validator->fails())
            {
                return response()->json([
                    "status" => "error",
                    "message" => $validator->errors()->first()
                ]);
            }

            $username = request()->username ?? "";
            $password = request()->password ?? "";

            $user = User::where("username", "=", $username)
                ->orWhere("email", "=", $username)
                ->first();

            if ($user === null)
            {
                return response()->json([
                    "status" => "error",
                    "message" => "Username or email does not exist."
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

            // if (auth()->attempt([
            //     "email" => $user->email,
            //     "password" => $password
            // ], true))
            // {
                // if (request()->expectsJson())
                // {
                    $token = $user->createToken(config("config.token_secret"))->plainTextToken;

                    return response()->json([
                        "status" => "success",
                        "message" => "Login successfully.",
                        "access_token" => $token
                    ]);
                // }

                // return response()->json([
                //     "status" => "success",
                //     "message" => "Login successfully."
                // ]);
            // }

            // return response()->json([
            //     "status" => "error",
            //     "message" => "In-valid credentials."
            // ]);
        }

        return view("theme::login");
    }

    public function email_verification()
    {
        $email = request()->email ?? "";

        return view("email-verification", [
            "email" => $email
        ]);
    }

    public function reset_password_view()
    {
        $token = request()->token ?? "";
        $email = request()->email ?? "";

        return view("theme::reset_password", [
            "email" => $email,
            "token" => $token
        ]);
    }

    public function forgot_password()
    {
        return view("theme::forgot_password");
    }

    public function register()
    {
        if (request()->isMethod("post"))
        {
            $validator = Validator::make(request()->all(), [
                "name" => "required",
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

            $name = request()->name ?? "";
            $email = request()->email ?? "";
            $username = strtolower(strtok($email, "@"));
            $username = preg_replace("/[^a-z0-9_]/", "", $username);

            if (User::where("username", $username)->exists()) {
                $username .= "_" . substr(uniqid(), -6);
            }
            $password = request()->password ?? "";

            if (User::where("email", $email)->exists())
            {
                return response()->json([
                    "status" => "error",
                    "message" => "Email already exists."
                ]);
            }

            $user_arr = [
                "name" => $name,
                "username" => $username,
                "email" => $email,
                "password" => password_hash($password, PASSWORD_DEFAULT),
                "type" => "user",
                "created_at" => now()->utc(),
                "updated_at" => now()->utc()
            ];

            $setting_verify_email = DB::table("settings")
                ->where("key", "=", "verify_email")
                ->where("value", "=", "yes")
                ->first();

            if ($setting_verify_email == null)
            {
                $user_arr["email_verified_at"] = now()->utc();
            }
            else
            {
                $verification_code = Str::random(6);
                $user_arr["verification_code"] = $verification_code;

                $message = '<p>Your verification code is: <b style="font-size: 30px;">' . $verification_code . '</b></p>';
                $this->send_mail($email, $name, "Email verification", $message);
            }

            DB::table("users")
                ->insertGetId($user_arr);

            if ($setting_verify_email == null)
            {
                return response()->json([
                    "status" => "success",
                    "message" => "Account has been created. Please login now.",
                    "verification" => false
                ]);
            }

            return response()->json([
                "status" => "success",
                "message" => "Please check your email, a verification code has been sent to you.",
                "verification" => true
            ]);
        }

        return view("theme::register");
    }
}
