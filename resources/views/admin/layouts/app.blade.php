<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta name="robots" content="noindex, nofollow" />

  <title>@yield("title", "Admin Panel")</title>

  <meta name="_token" content="{{ csrf_token() }}" />

  <!-- Favicons -->
  <link href="{{ asset('/administrator/img/favicon.png') }}" rel="icon">
  <link href="{{ asset('/administrator/img/apple-touch-icon.png') }}" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <!-- <link href="https://fonts.gstatic.com" rel="preconnect"> -->
  <!-- <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet"> -->

  <!-- Vendor CSS Files -->
  <link href="{{ asset('/administrator/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('/administrator/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
  <link href="{{ asset('/administrator/vendor/boxicons/css/boxicons.min.css') }}" rel="stylesheet">
  <link href="{{ asset('/administrator/vendor/quill/quill.snow.css') }}" rel="stylesheet">
  <link href="{{ asset('/administrator/vendor/quill/quill.bubble.css') }}" rel="stylesheet">
  <link href="{{ asset('/administrator/vendor/remixicon/remixicon.css') }}" rel="stylesheet">
  <link href="{{ asset('/administrator/vendor/simple-datatables/style.css') }}" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="{{ asset('/administrator/css/style.css') }}" rel="stylesheet" />
  <link href="{{ asset('/administrator/css/custom.css?v=' . time()) }}" rel="stylesheet" />
  <link href="{{ asset('/css/fontawesome.css') }}" rel="stylesheet" />
  <script src="{{ asset('/js/fontawesome.js') }}"></script>

  <!-- =======================================================
  * Template Name: NiceAdmin
  * Template URL: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/
  * Updated: Apr 7 2024 with Bootstrap v5.3.3
  * Author: BootstrapMade.com
  * License: https://bootstrapmade.com/license/
  ======================================================== -->

  <!-- <script src="{{ asset('/administrator/js/jquery-3.6.0.min.js') }}"></script> -->
  <script src="{{ asset('/js/jquery.js') }}"></script>
  <script src="{{ asset('/administrator/js/bootstrap.min.js') }}"></script>

  <!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
  <!-- <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script> -->

  <!-- Vendor JS Files -->
  <script src="{{ asset('/administrator/vendor/apexcharts/apexcharts.min.js') }}"></script>
  <script src="{{ asset('/administrator/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('/administrator/vendor/chart.js/chart.umd.js') }}"></script>
  <script src="{{ asset('/administrator/vendor/echarts/echarts.min.js') }}"></script>
  <script src="{{ asset('/administrator/vendor/quill/quill.min.js') }}"></script>
  <script src="{{ asset('/administrator/vendor/simple-datatables/simple-datatables.js') }}"></script>
  <script src="{{ asset('/administrator/vendor/tinymce/tinymce.min.js') }}"></script>
  <script src="{{ asset('/administrator/vendor/php-email-form/validate.js') }}"></script>

  <link rel="stylesheet" type="text/css" href="{{ asset('/richtext/richtext.min.css') }}" />
  <script src="{{ asset('/richtext/jquery.richtext.min.js') }}"></script>
  
  <link rel="stylesheet" type="text/css" href="{{ asset('/datetimepicker/jquery.datetimepicker.min.css') }}" />
  <script src="{{ asset('/datetimepicker/jquery.datetimepicker.full.js') }}"></script>

  <!-- Template Main JS File -->
  <script src="{{ asset('/administrator/js/main.js') }}"></script>
  <script src="{{ asset('/js/chart.js') }}"></script>

  <script src="{{ asset('/js/react.development.js') }}"></script>
  <script src="{{ asset('/js/react-dom.development.js') }}"></script>
  <script src="{{ asset('/js/babel.min.js') }}"></script>
  <script src="{{ asset('/js/axios.min.js') }}"></script>
  <script src="{{ asset('/js/sweetalert2@11.js') }}"></script>
  <script src="{{ asset('/js/html-react-parser.min.js') }}"></script>
  <script src="{{ asset('/js/script.js?v=' . time()) }}"></script>
  <script src="{{ asset('/administrator/js/script.js?v=' . time()) }}"></script>
</head>

<body>

  @php
    $user = null;
  @endphp

  @if (auth()->check())
    @php
      $user = auth()->user();
    @endphp

    <input type="hidden" id="user" value="{{ json_encode([
      'id' => $user->id ?? 0,
      'name' => $user->name ?? '',
      'email' => $user->email ?? '',
      'type' => $user->type ?? ''
    ]) }}" />
  @endif

  <input type="hidden" id="baseUrl" value="{{ url('/') }}" />

  <script>
    const baseUrl = document.getElementById("baseUrl").value;

    let user = null;

    if (document.getElementById("user") != null) {
      user = JSON.parse(document.getElementById("user").value);
    }

    if (user != null) {
      const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
      fetch(baseUrl + "/set_timezone", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          "_token": document.querySelector("meta[name='_token']").content,
          "timezone": timezone
        })
      });
    }
  </script>

  <!-- ======= Header ======= -->
  <header id="header-app" class="header fixed-top d-flex align-items-center">
    <div class="d-flex align-items-center justify-content-between">
      <a href="{{ url('/admin') }}" class="logo d-flex align-items-center">
        <img src="{{ asset('/administrator/img/logo.png') }}" alt="" />
        <span class="d-none d-lg-block">Admin panel</span>
      </a>
      <i class="bi bi-list toggle-sidebar-btn"
        onclick="toggleSidebar();"></i>
    </div>

    <div class="search-bar">
      <form class="search-form d-flex align-items-center" method="POST" action="#">
        <input type="text" name="query" placeholder="Search" title="Enter search keyword" />
        <button type="submit" title="Search"><i class="bi bi-search"></i></button>
      </form>
    </div>

    <nav class="header-nav ms-auto">
      <ul class="d-flex align-items-center">

        <li class="nav-item d-block d-lg-none">
          <a class="nav-link nav-icon search-bar-toggle " href="#">
            <i class="bi bi-search"></i>
          </a>
        </li>

        <li class="nav-item dropdown pe-3">

          <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
            <img src="{{ asset('/administrator/img/profile-img.jpg') }}" alt="Profile" class="rounded-circle" />
            <span class="d-none d-md-block dropdown-toggle ps-2">{{ auth()->user()->name ?? "" }}</span>
          </a>

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
            <li class="dropdown-header">
              <h6>{{ auth()->user()->name ?? "" }}</h6>
              <span>{{ auth()->user()->email ?? "" }}</span>
            </li>

            <li>
              <hr class="dropdown-divider" />
            </li>

            <li>
              <a class="dropdown-item d-flex align-items-center" href="{{ url('/logout') }}">
                <i class="bi bi-box-arrow-right"></i>
                <span>Sign Out</span>
              </a>
            </li>

          </ul>
        </li>

      </ul>
    </nav>
  </header><!-- End Header -->

  <!-- ======= Sidebar ======= -->
  <aside id="sidebar" class="sidebar">

    <ul class="sidebar-nav" id="sidebar-nav">

      @php
        $allowed_routes = auth()->user()->allowed_routes();
        $is_super_admin = (auth()->user()->type === "super_admin");
      @endphp

      @foreach (config('admin_menu') as $item)
        @if($is_super_admin || in_array($item['permission'], $allowed_routes))
          <li class="nav-item">
            <a
              class="nav-link {{ request()->routeIs($item['permission']) ? '' : 'collapsed' }}"
              href="{{ url($item['url']) }}"
            >
              <i class="{{ $item['icon'] }}"></i>&nbsp;
              <span>{{ $item['title'] }}</span>

              @if ($item['title'] === 'Contact us')
                @if ($unread_contact_us > 0)
                  <span class="badge bg-primary badge-notification">{{ $unread_contact_us }}</span>
                @endif
              @endif
            </a>
          </li>
        @endif
      @endforeach
    </ul>

  </aside><!-- End Sidebar-->

  <main id="main" class="main">

    @yield("main")

  </main><!-- End #main -->

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">
    <div class="copyright">
      &copy; Copyright. All Rights Reserved
    </div>
  </footer><!-- End Footer -->

  <!-- Modal -->
  <div class="modal" id="example-modal" tabindex="-1">
      <div class="modal-dialog" role="document">
          <div class="modal-content">
              <div class="modal-header" style="display: inline-block;">
                  <h5 class="modal-title" style="display: contents;">Title</h5>

                  <button type="button" class="close btn btn-danger btn-sm" data-dismiss="modal" aria-label="Close"
                      style="float: right;">
                      <span aria-hidden="true">&times;</span>
                  </button>
              </div>

              <div class="modal-body">
                  Modal body
              </div>

              <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                  <button type="button" name="submit" class="btn btn-primary">Save changes</button>
              </div>
          </div>
      </div>
  </div>

  <div class="custom-modal">
    <div class="modal" id="mediaModal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Select Media</h3>
          <button class="close-btn" onclick="fileManager.closeMediaModal()">&times;</button>
        </div>

        <!-- Tabs -->
        <div class="tabs">
          <div class="tab active" onclick="fileManager.showTab('upload')">📤 Upload</div>
          <div class="tab" onclick="fileManager.showTab('existing')">📂 Existing</div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" id="tab-upload">
          <form enctype="multipart/form-data" method="POST" onsubmit="fileManager.upload(event)">
            <input type="hidden" name="type" value="public" />

            <div class="mb-3">
              <label for="file" class="form-label">Select File</label>
              <input type="file" class="form-control" name="file" id="file" required>
            </div>

            <div class="mb-3">
              <label for="name" class="form-label">File Name</label>
              <input type="text" class="form-control" name="name" id="name">
            </div>

            <div class="mb-3">
              <label for="alt" class="form-label">Alt Text</label>
              <input type="text" class="form-control" name="alt" id="alt">
            </div>

            <div class="mb-3">
              <label for="caption" class="form-label">Caption</label>
              <input type="text" class="form-control" name="caption" id="caption">
            </div>

            <div class="mb-3">
              <label for="description" class="form-label">Description</label>
              <textarea class="form-control" name="description" id="description" rows="3"></textarea>
            </div>

            <button type="submit" name="submit" class="btn btn-primary">Upload</button>
          </form>
        </div>

        <div class="tab-content" id="tab-existing" style="display: none;
          max-height: 500px;
          overflow: auto;">
          <div class="media-grid">
            
          </div>
        </div>
      </div>
    </div>
  </div>

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <style>
    .timezone {
      display: none;
    }
    .table-container {
      width: 100%;
      overflow-x: auto; /* Enable horizontal scroll if needed */
    }

    .table-container table {
      width: 100%;
      border-collapse: collapse;
      min-width: 600px; /* Minimum width to preserve layout */
    }

    .table-container th,
    .table-container td {
      padding: 12px;
      border: 1px solid #ccc;
      text-align: left;
    }

    @media (max-width: 600px) {
      .table-container th,
      .table-container td {
        padding: 8px;
      }
    }
  </style>

  <script>
    function toggleSidebar() {
      let node = document.getElementById("sidebar");
      if (node.style.left == "0px") {
        node.style.left = "-300px";
      } else {
        node.style.left = "0px";
      }
    }
  </script>

</body>

</html>