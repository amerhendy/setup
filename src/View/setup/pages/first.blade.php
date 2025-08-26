@extends('SetUp::app')
@section('content')
{{trans('AMER::Base.pleaseInstall')}}
 Amerhendy/Security
 {{trans('AMER::Base.presshere')}}
<div class="step-app" id="demo">
  <ul class="step-steps">
    <li data-step-target="step1">SetUp Libraries</li>
    <li data-step-target="step2">Step 2</li>
    <li data-step-target="step3">Step 3</li>
  </ul>
  <div class="step-content">
    <div class="step-tab-panel" data-step="step1">
        @foreach($checkservice as $a=>$b)
        <div class="form-check col-sm-2">
            <input class="form-check-input" type="checkbox" value="{{$b}}" name="checkservice[]" id="flexCheckDefault">
            <label class="form-check-label" for="flexCheckDefault">
                {{$b}}
            </label>
        </div>
        @endforeach
    </div>
    <div class="step-tab-panel" data-step="step2">
      ...
    </div>
    <div class="step-tab-panel" data-step="step3">
      ...
    </div>
  </div>
  <div class="step-footer">
    <button data-step-action="prev" class="step-btn">Previous</button>
    <button data-step-action="next" class="step-btn">Next</button>
    <button data-step-action="finish" class="step-btn">Finish</button>
  </div>
</div>  
@endsection
@push('after_scripts')
<script>
    var loader = {
      isLoading: false,
      show: function() {
        loader.isLoading = true;
        $('.loader').show();
      },
      hide: function() {
        loader.isLoading = false;
        $('.loader').hide();
      }
    };

    var xhr = null;
    var isAllowChangeToNextStep = false;
    var targetStepIndex = 0; // step2
    var steps = $('#demo').steps({
      onChange: function(currentIndex, newIndex, stepDirection) {
        if (isAllowChangeToNextStep && !loader.isLoading) {
          isAllowChangeToNextStep = false;
          return true;
        }
        if (currentIndex === targetStepIndex) {
          if (stepDirection === 'forward') {

            if (!loader.isLoading) {
              loader.show();
              $data=new Array();
              $.each($('input[id=flexCheckDefault]:checked'),function(k,v){
                $data.push($(v).val());
              });
              xhr = $.ajax({
                //url: 'https://jsonplaceholder.typicode.com/todos/4'
                url:'http://localhost/lotfy/loginsystem_Copy/jobs/setup',
                method:'post',
                data:{'packages':$data}
              })
              .done(function(response) {
                loader.hide();
                console.log(response);  
                // success
                if (response.completed) {
                  isAllowChangeToNextStep = true;
                  var stepIndex = steps_api.getStepIndex();
                  if (stepIndex === targetStepIndex) {
                    steps_api.next();
                  }
                }
              });

            }

            return false;
          }
        }

        if (xhr) {
          xhr.abort();
          loader.hide();
        }

        return true;
      },
      onFinish: function() {
        alert('Wizard Completed');
      }
    });

    var steps_api = steps.data('plugin_Steps');
  </script>
@endpush