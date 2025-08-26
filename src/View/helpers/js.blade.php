<script>
    $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    beforeSend: function() {
        $('#loader').removeAttr('hidden');
    },
    success: function() {},
    error: function(jqXHR, textStatus, errorThrown) {
        console.log(errorThrown);
        alert("خطأ فى الاتصال .... اعد تحميل الصفحة");
    },
    complete: function() {
        $('#loader').attr('hidden', 'hidden');
    }
});
if(!window.Amer){
    window.Amer={};
}   
if (top !== self) top.location.replace(self.location.href);
jQuery(document).ready(function() {
    var forms = document.querySelectorAll('form');
    var inputs = document.querySelectorAll('input');
    var selects = document.querySelectorAll('select');
    if ((forms.length !== 0) || (inputs.length !== 0) || (selects.length !== 0)) {
        initializeFieldsWithJavascript('form');
    }
});
function loader_div(target, id) {
    html = '';
    html += '<div id="loader" class="container-fluid d-flex justify-content-center full-width-div" area="' + id + '">';
    html += '<div class="my-auto">';
    html += '<div class="spinner-border" role="status">';
    html += '<span class="sr-only">Loading...</span>';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    $('body').prepend(html);
}

function remove_loader_div(id) {
    $('div[area=' + id + ']').remove();
}
$('#loader').attr('hidden', 'hidden');
$('form').on('keyup keypress', function(e) {
    var keyCode = e.keyCode || e.which;
    if (keyCode === 13) {
        e.preventDefault();
        return false;
    }
})
const btns = document.querySelectorAll(".btn");
for (var i = 0; i < btns.length; i++) {
    if (btns[i].hasAttribute('data-mdb-ripple-duration')) {} else { btns[i].setAttribute('data-mdb-ripple-duration', '0.1ms'); }
}
function initializeFieldsWithJavascript(container) {
    var selector;
    if (container instanceof jQuery) {
        selector = container;
    } else {
        selector = $(container);
    }
    selector.find("[data-init-function]").each(function() {
        var element = $(this);
        
        var functionName = element.data('init-function');

        if (typeof window[functionName] === "function") {
            window[functionName](element);
        }
    });
}
</script>