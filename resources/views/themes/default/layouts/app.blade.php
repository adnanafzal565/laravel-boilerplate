<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield("title", site_title())</title>

    @hasSection("meta_description")
        <meta name="description" content="@yield('meta_description')" />
    @endif

    @hasSection("meta_keywords")
        <meta name="keywords" content="@yield('meta_keywords')" />
    @endif

    <!-- Open Graph for social sharing -->
    @hasSection("title")
        <meta property="og:title" content="@yield('title', site_title())" />
    @endif

    @hasSection("meta_description")
        <meta property="og:description" content="@yield('meta_description')" />
    @endif

    <meta property="og:url" content="{{ url()->current() }}" />

    @hasSection("type")
        <meta property="og:type" content="@yield('type')" />
    @endif

    <meta name="_token" content="{{ csrf_token() }}" />

    <link rel="canonical" href="{{ url()->current() }}" />

    <link href="{{ asset('themes/' . active_theme() . '/css/style.css?v=' . time()) }}" rel="stylesheet" />
    <script src="{{ asset('themes/' . active_theme() . '/js/app.js') }}"></script>

    <link rel="stylesheet" href="{{ asset('/css/bootstrap.min.css') }}" />
    <script src="{{ asset('/js/jquery.js') }}"></script>
    <script src="{{ asset('/js/bootstrap.bundle.js') }}"></script>

    <script src="{{ asset('/js/react.development.js') }}"></script>
    <script src="{{ asset('/js/react-dom.development.js') }}"></script>
    <script src="{{ asset('/js/babel.min.js') }}"></script>
    <script src="{{ asset('/js/sweetalert2@11.js') }}"></script>
    <script src="{{ asset('/js/axios.min.js') }}"></script>
    <script src="{{ asset('/js/fontawesome.js') }}"></script>
    <script src="{{ asset('/js/script.js?v=' . time()) }}"></script>
</head>
<body>

    <input type="hidden" id="route_login" value="{{ route('login') }}" />
    <input type="hidden" id="route_register" value="{{ route('register') }}" />
    <input type="hidden" id="route_profile" value="{{ route('pages.show', ['slug' => 'profile']) }}" />
    <input type="hidden" id="route_admin_dashboard" value="{{ route('admin.dashboard') }}" />

    <script>
        const route_login = document.getElementById('route_login').value;
        const route_register = document.getElementById('route_register').value;
        const route_profile = document.getElementById('route_profile').value;
        const route_admin_dashboard = document.getElementById('route_admin_dashboard').value;
    </script>

    @php
        $title_parts = explode(" ", site_title());
    @endphp

    <!-- Header -->
    <header class="site-header">
        <div class="container header-inner">
            <div class="logo">
                <a href="{{ route('home') }}">
                    @if (count($title_parts) > 0)
                        {!! $title_parts[0] . ((count($title_parts) > 1) ? ("<span>" . $title_parts[1] . "</span>") : "") !!}
                    @endif
                </a>
            </div>

            <nav class="main-nav">
                <ul>
                    @foreach (menu_items("Main menu") as $menu_item)
                        <li>
                            <a href="{{ $menu_item->url ?? '' }}">{{ $menu_item->title ?? "" }}</a>
                        </li>
                    @endforeach

                    <li class="nav-item dropdown" id="header_user_view_app">
                        <a
                            class="nav-link dropdown-toggle"
                            href="#"
                            id="navbarDropdown"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >
                            Account
                        </a>

                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li>
                                <a href="{{ route('login') }}"
                                    class="dropdown-item">Login</a>
                            </li>

                            <li>
                                <a href="{{ route('register') }}"
                                    class="dropdown-item">Sign Up</a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        @yield("main")
    </main>

    <footer>
        <div class="footer-container">

          <div class="footer-column">
            <h4>About Us</h4>
            <p style="color: #aaa;">
              We build modern web apps, CMS, and SaaS solutions for agencies and entrepreneurs.
            </p>
          </div>

          <div class="footer-column">
            <h4>Contact Us</h4>
            <ul>
              <li>Email: <a href="mailto:support@adnan-tech.com">support@adnan-tech.com</a></li>
              <li>WhatsApp: +923105461304</li>
            </ul>
          </div>

          <div class="footer-column">
            <h4>Follow Us</h4>
            <ul>
              <li><a href="https://web.facebook.com/ComputerProgrammingTutorial" target="_blank">Facebook</a></li>
              <li><a href="https://youtube.com/c/AdnanAfzal565" target="_blank">YouTube</a></li>
            </ul>
          </div>

        </div>

        <div class="footer-bottom">
          &copy; {{ date('Y') }} {{ site_title() }}. All rights reserved.
        </div>
    </footer>

    <div id="chat-app"></div>
    <script type="text/babel" src="{{ asset('/components/Chat.js?v=' . time()) }}"></script>
    <link rel="stylesheet" href="{{ asset('/css/chat.css') }}" />

    <script type="text/babel">
        function HeaderUserViewApp() {

            const [state, set_state] = React.useState(globalState.state);
            const [logging_out, set_logging_out] = React.useState(false);

            async function onInit() {
                if (!localStorage.getItem(accessTokenKey)) {
                    return;
                }

                await ajax('/api/me', null, function (response) {
                    window.user = response.user;
                    const unread_notifications = response.unread_notifications;

                    if (unread_notifications > 0 && document.getElementById("name-notifications-count")) {
                        document.getElementById("name-notifications-count").innerHTML = `(${unread_notifications})`;
                    }

                    // for non-React
                    if (typeof on_user_fetch !== "undefined") {
                        on_user_fetch();
                    }

                    // for React
                    globalState.setState({
                        user: response.user
                    });
                });
            }

            async function do_logout(event) {
                event.preventDefault();
                
                set_logging_out(true);
                await ajax('/api/logout', null);
                localStorage.removeItem(accessTokenKey);
                set_logging_out(false);
                window.location.href = baseUrl;
            }

            React.useEffect(() => {
                globalState.listen((new_state, updated_state) => {
                    set_state(new_state);
                });

                onInit();
            }, []);

            return (
                <>
                    <a
                        className="nav-link dropdown-toggle username"
                        href="#"
                        id="navbarDropdown"
                        role="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >
                        {truncate_text(state.user?.name || "Account")}
                    </a>

                    <ul className="dropdown-menu" aria-labelledby="navbarDropdown">
                        { state.user ? (
                            <>
                                { ["admin", "super_admin"].includes(state.user.type) && (
                                    <li>
                                        <a className="dropdown-item"
                                            href={ route_admin_dashboard }>
                                            Admin Panel
                                        </a>
                                    </li>
                                ) }

                                <li>
                                    <a className="dropdown-item"
                                        href={ route_profile }>
                                        Profile
                                    </a>
                                </li>

                                <li>
                                    <a href="#"
                                        className="dropdown-item"
                                        onClick={ do_logout }
                                    >
                                        { logging_out ? 'Logging out...' : 'Logout' }
                                    </a>
                                </li>
                            </>
                        ) : (
                            <>
                                <li>
                                    <a href={ route_login }
                                        className="dropdown-item">Login</a>
                                </li>

                                <li>
                                    <a href={ route_register }
                                        className="dropdown-item">Sign Up</a>
                                </li>
                            </>
                        ) }
                    </ul>
                </>
            );
        }

        ReactDOM.createRoot(
            document.getElementById('header_user_view_app')
        ).render(<HeaderUserViewApp />);
    </script>

    <style>
        footer {
          background-color: #000;
          color: #ccc;
          padding: 40px 20px;
        }

        .footer-container {
          max-width: 1200px;
          margin: auto;
          display: flex;
          flex-wrap: wrap;
          justify-content: space-between;
          gap: 30px;
        }

        .footer-column {
          flex: 1;
          min-width: 200px;
        }

        .footer-column h4 {
          color: #fff;
          margin-bottom: 15px;
        }

        .footer-column ul {
          list-style: none;
          padding: 0;
        }

        .footer-column ul li {
          margin: 8px 0;
        }

        .footer-column ul li a {
          color: #ccc;
          text-decoration: none;
        }

        .footer-column ul li a:hover {
          color: #fff;
        }

        .footer-bottom {
          text-align: center;
          color: #777;
          padding-top: 20px;
          border-top: 1px solid #222;
          font-size: 14px;
          margin-top: 20px;
        }

        @media (max-width: 768px) {
          .footer-container {
            flex-direction: column;
            align-items: center;
          }
        }
    </style>

</body>
</html>