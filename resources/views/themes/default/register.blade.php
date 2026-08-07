@extends ("theme::layouts/app")
@section ("title", "Register")

@section ("main")

    <div class="container mt-4 mb-4">
        <div class="row">
            <div class="offset-4 col-4">
                <h2>Register</h2>

                <form onsubmit="doRegister()">
                    <div class="form-group mt-4">
                        <label class="form-label">Enter Name</label>
                        <input type="text" name="name" class="form-control" required />
                    </div>

                    <div class="form-group mt-3">
                        <label class="form-label">Enter Email</label>
                        <input type="email" name="email" class="form-control" required />
                    </div>

                    <div class="form-group mt-3">
                        <label class="form-label">Enter Password</label>
                        <input type="password" name="password" id="password" class="form-control" required />

                        <label class="mt-3">
                            <input type="checkbox" id="togglePassword">
                            Show Password
                        </label>
                    </div>

                    <input type="submit" name="submit" class="btn btn-outline-primary btn-sm mt-3" value="Register" />
                </form>
            </div>
        </div>
    </div>

    <script>
        async function doRegister() {
            event.preventDefault()
            const form = event.target

            try {
                const formData = new FormData(form)
                form.submit.setAttribute("disabled", "disabled")

                await ajax('/api/register', formData, function (response) {
                    const verification = response.verification;
                    swal.fire("Register", response.message, "success")
                        .then(function () {
                            if (verification) {
                                window.location.href = baseUrl + "/email-verification/" + form.email.value;
                            } else {
                                window.location.href = baseUrl + "/login";
                            }
                        });
                });
            } catch (exp) {
                swal.fire("Error", exp.message, "error")
            } finally {
                form.submit.removeAttribute("disabled")
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            const passwordInput = document.getElementById("password");
            const toggleCheckbox = document.getElementById("togglePassword");

            toggleCheckbox.addEventListener("change", function () {
                if (this.checked) {
                    passwordInput.type = "text";
                } else {
                    passwordInput.type = "password";
                }
            });
        });
    </script>

@endsection