@stack('beforehead')
<title>{{$page_title ?? config('Amer.amer.co_name') ?? 'Amer'}} :: {{config('Amer.amer.co_name') ?? 'Amer'}} </title>
    <base href="{{url('')}}">
    <meta name="theme-color" content    ="{{config('Amer.amer.html.theme-color') ?? 'white'}}">
    <meta name="description" content="{{config('Amer.amer.html.description') ?? 'AmerHendy'}}" />
    <meta property="og:title" content="{{config('Amer.amer.co_name') ?? 'Amer'}}" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{url('')}}" />
    <meta property="og:image" content="{{asset(config('Amer.amer.co_logo')) ?? ''}}" />
    <meta property="og:image:width" content="600" />
    <meta property="og:image:height" content="304" />
    <meta property="og:description" content="{{config('Amer.amer.html.description') ?? 'AmerHendy'}}" />
    <meta property="og:determiner" content="the" />
    <meta property="og:locale" content="{{config('Amer.amer.lang') ?? 'ar-eg'}}" />
    <meta property="og:site_name" content="{{config('Amer.amer.co_name') ?? 'AMER'}}" />
    <meta name="twitter:title" content="{{config('Amer.amer.co_name') ?? 'AMER'}}">
    <meta name="twitter:description" content=" {{config('Amer.amer.html.description') ?? 'AmerHendy'}}">
    <meta name="twitter:image" content="{{asset(config('Amer.amer.co_logo')) ?? ''}}">
    <meta name="twitter:card" content="{{asset(config('Amer.amer.co_logo')) ?? ''}}">
    <meta charset="{{config('Amer.amer.ENCODE') ?? 'UTF-8'}}">
    <meta name="title" content="{{config('Amer.amer.co_name') ?? 'Amer'}}">
    <meta name="description" content="{{config('Amer.amer.html.description') ?? ''}}">
    <meta name="keywords" content="{{config('Amer.amer.html.keywords') ?? 'Amer'}}">
    <meta name="robots" content="index, follow">
    <meta http-equiv="Content-Type" content="text/html; charset={{config('Amer.amer.ENCODE') ?? 'UTF-8'}}">
    <meta name="language" content="Arabic">
    <meta name="revisit-after" content="7 days">
    <meta name="author" content="amer hendy">
    <meta name="generator" content="amer hendy"/>
    <meta name="referrer" content="origin"/>
    <meta name="referrer" content="origin-when-crossorigin"/>
    <meta name="referrer" content="origin-when-cross-origin"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{asset ('images/logo.png') ?? ''}}" rel="icon">
    <link href="{{asset ('images/logo.png') ?? ''}}" rel="apple-touch-icon">
    @show
    @yield('before_styles')
    @stack('before_styles')
    <link rel="stylesheet" href="{{asset('css/bootstrap/bootstrap.min.css')}}">
    <link rel="stylesheet" href="{{asset('css/jquery-steps.css')}}">
    <!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jquery.steps@1.1.1/dist/jquery-steps.min.css">
-->
<!-- font -->
<style>
    :root,
    [data-bs-theme=light] {
    --bs-link-color-rgb: 250, 250, 250;
}
[data-bs-theme=light] {
    --fa--map--maker:#000;
    
}
    @font-face {
        font-family: AmerHendyAli;
        src: url('{{asset("fonts/c.ttf")}}');
    }
    body{
        font-family: 'AmerHendyAli', sans-serif,'Big Shoulders Display', cursive;
        direction: rtl;
    }
    html,body,div,li,nav,ul,a,.breadcrumb,header,.section ,.pace{
            font-family: 'AmerHendyAli'!important; 
            direction: rtl;
        }
</style>
@yield('after_styles')
    @stack('after_styles')
<!-- inc.headsc -->