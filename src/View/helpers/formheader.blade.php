<div class="row">
        <?php
        $enc='';
        $targetroute=route('Setup');
        $enc='enctype="multipart/form-data"';
        ?>
        <form class="form" name="form" action="{{ $targetroute }}" method="post" {{$enc}}>
                {!! csrf_field() !!}
                <input type="hidden" name="_http_referrer" value="{{session('referrer_url_override') ?? old('_http_referrer') ?? \URL::previous() ?? Route($Amer->route.'.index')}}">
        <div class="col-lg">
        <div class="card padding-10">
            <div class="card-header">
                <h2>
                    <small id="currentOperationTitle">currentoperation.</small>
                </h2>
            </div>
                    <div class="card-body bold-labels">
            