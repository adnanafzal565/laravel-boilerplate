@extends ("theme::layouts/app")
@section ("title", "Login")

@section ("main")

    <div class="container mt-4 mb-4">
        <div class="row">
            <div class="offset-4 col-4">
                <h2>Login</h2>

                <form onsubmit="doLogin()">
                    <div class="form-group mt-4">
                        <label class="form-label">Enter Email</label>
                        <input type="text" name="username" class="form-control" required />
                    </div>

                    <div class="form-group mt-3">
                        <label class="form-label">Enter Password</label>
                        <input type="password" name="password" class="form-control" required />
                    </div>

                    <input type="submit" name="submit" class="btn btn-outline-primary btn-sm mt-3" value="Login" />
                </form>

                <p class="mt-4">
                    Don't have an account?
                    <a href="{{ route('register') }}"
                        class="text-dark">Register</a>
                </p>

                <p class="mt-4">
                    <a href="{{ route('password.request') }}"
                        class="text-dark">Forgot Password?</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        async function doLogin() {
            event.preventDefault()
            const form = event.target

            try {
                const formData = new FormData(form)
                form.submit.setAttribute("disabled", "disabled")

                await ajax('/api/login', formData, function (response) {
                    const accessToken = response.access_token
                    localStorage.setItem(accessTokenKey, accessToken)

                    const urlSearchParams = new URLSearchParams(window.location.search)
                    const redirect = urlSearchParams.get("redirect") || ""
                    if (redirect == "") {
                        window.location.href = baseUrl
                    } else {
                        window.location.href = redirect
                    }
                });
            } catch (exp) {
                swal.fire("Error", exp.message, "error")
            } finally {
                form.submit.removeAttribute("disabled")
            }
        }
    </script>

@endsection