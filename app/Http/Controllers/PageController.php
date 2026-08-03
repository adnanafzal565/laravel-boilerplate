<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use View;
use Storage;
use Validator;
use App\Models\Post;
use App\Models\Page;

class PageController extends Controller
{
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

        $user = auth()->user();
        $id = request()->id ?? 0;

        $page = DB::table("pages")
            ->where("id", "=", $id)
            ->first();

        if ($page == null)
        {
            return response()->json([
                "status" => "error",
                "message" => "Page does not exist."
            ]);
        }

        DB::table("pages")
            ->where("id", "=", $page->id)
            ->delete();

        forget_page_cache($page->slug);

        return response()->json([
            "status" => "success",
            "message" => "Page has been deleted."
        ]);
    }

    public function detail()
    {
        $slug = request()->slug ?? "";

        $page = get_cached_page($slug);
        if ($page != null)
        {
            if (View::exists("theme::pages/" . $page->slug))
            {
                $data = [
                    "page" => $page
                ];

                if ($page->slug === 'reset-password') {
                    $data['email'] = request()->email ?? "";
                    $data['token'] = request()->token ?? "";
                }

                return view("theme::pages/" . $page->slug, $data);
            }

            if (View::exists("theme::pages/detail"))
            {
                return view("theme::pages/detail", [
                    "page" => $page
                ]);
            }
        }

        $post = get_cached_post($slug);
        if ($post != null)
        {
            // Get previous post (lower id)
            $previous_post = Post::where('id', '<', $post->id)
                ->where("is_active", "=", 1)
                ->orderBy('id', 'desc')
                ->limit(1)
                ->first();

            // Get next post (higher id)
            $next_post = Post::where('id', '>', $post->id)
                ->where("is_active", "=", 1)
                ->orderBy('id', 'asc')
                ->limit(1)
                ->first();

            if (View::exists("theme::posts/detail"))
            {
                // Match content inside [code] and [/code] tags
                $pattern = '/\[code\](.*?)\[\/code\]/s';  // 's' modifier allows dot to match newlines
                $replacement = '<pre><code>$1</code></pre>';
                // $post->content = preg_replace($pattern, $replacement, $post->content);

                // dd(substr_count($post->content, "[code]"));

                $post->content = str_replace("[code]", "<code>", $post->content);
                $post->content = str_replace("[/code]", "</code>", $post->content);

                // dd($post->content);

                return view("theme::posts/detail", [
                    "post" => $post,
                    "previous_post" => $previous_post,
                    "next_post" => $next_post
                ]);
            }
        }

        $product = get_cached_product($slug);
        if ($product) {
            if (View::exists("theme::products/detail")) {
                return view("theme::products/detail", [
                    "product" => $product
                ]);
            }
        }
        
        if (view()->exists("theme::pages/" . $slug)) {
            $data = [];
            
            return view("theme::pages/" . $slug, $data);
        }

        abort(404, "Page not found.");
    }

    public function update()
    {
        $validator = Validator::make(request()->all(), [
            "id" => "required",
            "title" => "required",
            "slug" => "required"
        ]);

        if ($validator->fails())
        {
            return response()->json([
                "status" => "error",
                "message" => $validator->errors()->first()
            ]);
        }

        $user = auth()->user();
        $id = request()->id ?? 0;
        $title = request()->title ?? "";
        $slug = request()->slug ?? "";
        $keywords = request()->keywords ?? "";
        $excerpt = request()->excerpt ?? "";
        $content = request()->content ?? "";
        $active = (int) request()->active ?? 0;

        if (!in_array($active, [0, 1]))
        {
            return response()->json([
                "status" => "error",
                "message" => "In-valid value for 'active'."
            ]);
        }

        $page = DB::table("pages")
            ->where("id", "=", $id)
            ->first();

        if ($page == null)
        {
            return response()->json([
                "status" => "error",
                "message" => "Page does not exist."
            ]);
        }

        if ($user->type !== "super_admin")
        {
            if ($page->user_id !== $user->id)
            {
                return response()->json([
                    "status" => "error",
                    "message" => "Unauthorized."
                ]);
            }
        }

        $page_exists = DB::table("pages")
            ->where("id", "!=", $page->id)
            ->where("slug", "=", $slug)
            ->exists();

        if ($page_exists)
        {
            return response()->json([
                "status" => "error",
                "message" => "Page with same slug already exists."
            ]);
        }

        DB::table("pages")
            ->where("id", "=", $page->id)
            ->update([
                "user_id" => $user->id,
                "title" => $title,
                "slug" => $slug,
                "keywords" => $keywords,
                "excerpt" => $excerpt,
                "content" => $content,
                "is_active" => $active,
                "updated_at" => now()->utc()
            ]);

        forget_page_cache($page->slug);

        return response()->json([
            "status" => "success",
            "message" => "Page has been updated."
        ]);
    }

    public function edit()
    {
        $id = request()->id ?? 0;

        $page = DB::table("pages")
            ->where("id", "=", $id)
            ->first();

        if ($page == null)
        {
            abort(404);
        }

        return view("admin/pages/edit", [
            "page" => $page
        ]);
    }

    public function add()
    {
        if (request()->isMethod("post"))
        {
            $validator = Validator::make(request()->all(), [
                "title" => "required",
                "slug" => "required"
            ]);

            if ($validator->fails())
            {
                return response()->json([
                    "status" => "error",
                    "message" => $validator->errors()->first()
                ]);
            }

            $user = auth()->user();
            $title = request()->title ?? "";
            $slug = request()->slug ?? "";
            $keywords = request()->keywords ?? "";
            $excerpt = request()->excerpt ?? "";
            $content = request()->content ?? "";
            $active = (int) (request()->active ?? 0);

            if (!in_array($active, [0, 1]))
            {
                return response()->json([
                    "status" => "error",
                    "message" => "In-valid value for 'active'."
                ]);
            }

            $page = DB::table("pages")
                ->where("slug", "=", $slug)
                ->first();

            if ($page != null)
            {
                return response()->json([
                    "status" => "error",
                    "message" => "Page with same slug already exists."
                ]);
            }

            $id = DB::table("pages")
                ->insertGetId([
                    "user_id" => $user->id,
                    "title" => $title,
                    "slug" => $slug,
                    "keywords" => $keywords,
                    "excerpt" => $excerpt,
                    "content" => $content,
                    "is_active" => $active,
                    "created_at" => now()->utc(),
                    "updated_at" => now()->utc()
                ]);

            return response()->json([
                "status" => "success",
                "message" => "Page has been created.",
                "id" => $id
            ]);
        }

        return view("admin/pages/add");
    }

    public function admin_index()
    {
        set_timezone();

        $pages = DB::table("pages")
            ->orderBy("id", "desc")
            ->paginate();

        foreach ($pages as $key => $value)
        {
            $pages[$key] = Page::map($value);
        }

        return view("admin/pages/index", [
            "pages" => $pages
        ]);
    }
}
