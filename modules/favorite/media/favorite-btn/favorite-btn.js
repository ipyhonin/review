$(document).on('click', 'button.js-favorite-btn', function(event) {
    let btn = $(this);
    let params = {
        "itemType": btn.attr('data-item-type'),
        "itemId": btn.attr('data-item-id'),
        "status": btn.attr('data-status'),
    };
    let url = '/favorite/default/toggle?' + $.param(params);
    let data = [];
    data[yii.getCsrfParam()] = yii.getCsrfToken();

    $.ajax({
        url: url,
        timeout: 10000,
        data: data,
    }).done(function (data) {
        $('#' + data.btnId).attr('data-status', data.newStatus);
        $('#' + data.btnId).attr('title', data.newTitle);
        PNotify.alert(JSON.parse(data.alertOptions, function(key, value) {
            if (key == 'stack') return myStack;
            return value;
        }));
    });
    
    event.preventDefault();
})