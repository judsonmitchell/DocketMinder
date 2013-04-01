$(document).ready(function () {

    //Cases
    $('.table_ph').on('click', 'a.delete', function (event) {
        event.preventDefault();
        var caseid  = $(this).attr('data-id');
        //var row = $(this).closest('tr');
        $.ajax({
            url: 'delete' + '/' + caseid,
            success: function (data) {
                $('.message').html(data).show().fadeOut(1600);
                $('.table_ph').load('refresh_table');
            }
        });
    });

    $('a.add').click(function (event) {
        event.preventDefault();
        $(this).hide();
        $('form.new-case').show();

        //Handle cancel add
        $('a.cancel').on('click', function (event) {
            event.preventDefault();
            $('form.new-case').hide();
            $('a.add').show();
        });

        //Submit new case
        $('a.add_case').on('click', function (event) {
            event.preventDefault();
            $.post('add', $('form.new-case').serialize(), function (data) {
                var serverResponse = $.parseJSON(data);
                var target = $('.message span');
                if (serverResponse.status === 'success') {
                    target.addClass('alert-success');
                }
                else {
                    target.addClass('alert-error');
                }
                target.html(serverResponse.message).show().fadeOut(3600);
                $('.table_ph').load('refresh_table');
                $('form.new-case').hide();
                $('form.new-case')[0].reset();
                $('a.add').show();
            });
        });
    });

    //Case Name Lookup Magic
    $('form input[name="number"]').blur(function () {
        var userInput = $(this).val();
        var caseNum = userInput.replace(/\D/g, '');
        var target = $(this).next();
        $.ajax({
            url: '../opcso' + '/' + caseNum,
            beforeSend: function () {
                target.val('Looking up case...');
            },
            success: function (data) {
                target.val(data);
            }
        });

    });

    //Cookie
    if (typeof $.cookie('docketminder_user') !== 'undefined')
    {
        $('input[name="username"]').val($.cookie('docketminder_user'));
    }

    $('form').submit(function () {
        if ($('input[name="remember"]').is(':checked'))
        {
            $.cookie('docketminder_user', $('input[name="username"]').val(), { expires: 365 });
        }
        else
        {
            $.removeCookie('docketminder_user');
        }
    });

    //validate
    $('form').validate({
        rules: {
            confirm: {equalTo: '#password'},
            password: {minlength: 6}
        },
        messages: {confirm: 'Password fields must match.'},
        showErrors: function (errorMap, errorList) {
            $.each(this.successList, function (index, value) {
                return $(value).popover('hide');
            });
            return $.each(errorList, function (index, value) {
                var _popover;
                _popover = $(value.element).popover({
                    trigger: 'manual',
                    placement: 'right',
                    content: value.message,
                    template: '<div class=\"popover\"><div class=\"arrow\"></div><div class=\"popover-inner\"><div class=\"popover-content\"><p></p></div></div></div>'
                });
                _popover.data('popover').options.content = value.message;
                return $(value.element).popover('show');
            });
        }
    });

    //Users
    //Forgot password
    $('input[name="forgot_email"]').blur(function () {
        $.post('check_email', {'forgot_email': $(this).val()}, function (data) {
            var serverRepsonse = $.parseJSON(data);
            var target = $('span.help-inline');
            if (serverRepsonse.status === 'success') {
                target.addClass('alert-success');
                target.html(serverRepsonse.message);
            }
            else {
                target.addClass('alert-error');
                $('#reset_submit').prop('disabled', true);
                target.html(serverRepsonse.message);
            }
        });
    });

    $('#reset_submit').click(function (event) {
        event.preventDefault();
        $.post('reset_stage', {'forgot_email': $('input[name="forgot_email"]').val()}, function (data) {
            var serverRepsonse = $.parseJSON(data);
            var target = $('div.container:eq(1)');
            if (serverRepsonse.status === 'success') {
                target.addClass('alert-success');
                target.html(serverRepsonse.message);
            }
            else {
                target.addClass('alert-error');
                $('#reset_submit').prop('disabled', true);
                target.html(serverRepsonse.message);
            }
        });
    });
});

