<!-- app -->
<!DOCTYPE html>
<html lang="{{config('amer.lang') ?? 'ar-eg'}}" dir="{{config('amer.lang') ?? 'rtl'}} " prefix="{{config('amer.co_name') ?? 'HCWW'}}" data-bs-theme="auto">
<head>
@include('SetUp::helpers.head')
</head>
@stack('beforebody')
<body>
@stack('afterbody')
<div id='loader' class="container-fluid justify-content-center full-width-div">
    <div class="my-auto">
        <div class="spinner-grow m-5" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
</div>
<header class="section page-header">
    <div class="container">
        <div class="row bg-primary" >
            <div class="col-sm-9">Amer SetUp</div>
            <div class="col-sm-3">
                <a class="btn btn-outline-light" href="#" role="button">View in GitHub</a>
            </div>
        </div>
    </div>
</header>
<main class="section fixed container-fluid">
    <div class="container">
        @include('SetUp::helpers.formheader')
        @yield('content')
        @include('SetUp::helpers.formfooter')
    </div>
</main>
<!-- app.blade.php -->
<footer class="bg-dark text-center text-lg-start page-footer font-small">


</footer>
<!-- app.blade.php -->
@yield('AFTERFOOTER')
@stack('AFTERFOOTER')
</body>
<script type="application/javascript">
var websitelink="{{url('')}}";
var api=websitelink+'api/'
</script>
<!--
<script src="https://code.jquery.com/jquery-3.7.0.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/0a7174e0d3.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery.steps@1.1.1/dist/jquery-steps.min.js"></script>
-->
<script src="{{asset('js/jquery/jquery-3.6.0.min.js')}}"></script>
<script src="{{asset('js/bootstrap/bootstrap.bundle.min.js')}}"></script>
<script src="{{asset('js/jquery/jquery-steps.min.js')}}"></script>
@include('SetUp::helpers.js')
@yield('scripts')
@stack('scripts')
@yield('before_scripts')
@stack('before_scripts')
@yield('after_scripts')
@stack('after_scripts')
</html>
