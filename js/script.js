let filterFormAutoSubmitInputClass = 'filter-form__input_auto-submit';
let ionSliderFromInputClass = 'ion-slider__input_from';
let ionSliderToInputClass = 'ion-slider__input_to';
let activeFilterHiddenInputId = 'id-filter-form__input_active-filter';
let pjaxContainerId = 'id-public-index__pjax-container';

function showFilterModal(dataItem) {
    let toggleElem = $(".objects-filter-item[data-item=" + dataItem + "]");
    let targetElem = $(".objects-filter-modal-item[data-item=" + dataItem + "]");
    /** Контейнер, относительно которого позиционируются контейнер модального окна фильтра */
    let posRef = $(".objects-filter");
    /** Контейнер модального окна фильтра */
    let modal = $('.objects-filter-modal');

    /** Показ */
    toggleElem.addClass('active');
    modal.addClass('active');
    targetElem.addClass('active');

    /** Позиционирование */
    modal.css('height', ($(document).height() - posRef.offset().top) + 'px');
    if ((modal[0].offsetWidth - toggleElem.offset().left - targetElem[0].offsetWidth) > 0) {
        targetElem.css('left', toggleElem.offset().left);
    } else {
        targetElem.css('right', Math.max(20, (modal[0].offsetWidth - toggleElem.offset().left - toggleElem[0].offsetWidth)));
    }
}

function hideFilterModal() {
    $('.objects-filter-modal').removeClass('active');
    $('.objects-filter-modal-item').removeClass('active');
    $('.objects-filter-item').removeClass('active');
    $("#" + activeFilterHiddenInputId).attr('name', '');
}

$(document).on('click', '.objects-filter-item', function () {
    if ($(this).hasClass('active')) {
        hideFilterModal();
    } else {
        let dataItem = $(this).attr('data-item');
        showFilterModal(dataItem);
    }
});

$(document).on('click', '.objects-filter-modal-overlay', function () {
    hideFilterModal();
});

$(document).on('click', '.objects-filter-modal-item-close', function () {
    hideFilterModal();
});

function updateIonSliderInputs(data) {
    data.input.parent().find("input[data-from]").prop("value", data.from);
    data.input.parent().find("input[data-to]").prop("value", data.to);
}

$(document).on("change", "." + ionSliderFromInputClass, function () {
    var _this = $(this);
    var val = _this.prop("value");
    var ionSliderSelector = _this.attr("data-target");
    var ionSliderInstance = $(ionSliderSelector).data("ionRangeSlider");
    var min = ionSliderInstance.options.min;
    var to = ionSliderInstance.old_to;

    if (val < min) {
        val = min;
    } else if (val > to) {
        val = to;
    }

    ionSliderInstance.update({
        from: val
    });

    _this.prop("value", val);
});

$(document).on("change", "." + ionSliderToInputClass, function () {
    var _this = $(this);
    var val = _this.prop("value");
    var ionSliderSelector = _this.attr("data-target");
    var ionSliderInstance = $(ionSliderSelector).data("ionRangeSlider");
    var max = ionSliderInstance.options.max;
    var from = ionSliderInstance.old_from;

    if (val < from) {
        val = from;
    } else if (val > max) {
        val = max;
    }

    ionSliderInstance.update({
        to: val
    });

    _this.prop("value", val);
});

function submitFilterForm() {
    let activeFilter = $(".objects-filter-item.active");
    let hiddenActiveFilterInput = $("#" + activeFilterHiddenInputId);
    if (activeFilter) {
        let name = hiddenActiveFilterInput.attr('data-name');
        hiddenActiveFilterInput.attr('name', name);
        hiddenActiveFilterInput.val(activeFilter.attr('data-item'));
    } else {
        hiddenActiveFilterInput.attr('name', '');
    }
    hiddenActiveFilterInput.parents("form").submit();
}

$(document).on("change", "." + filterFormAutoSubmitInputClass, submitFilterForm);

$(document).on("pjax:complete", "#" + pjaxContainerId, function () {
    let hiddenActiveFilterInput = $("#" + activeFilterHiddenInputId);
    let dataItem = hiddenActiveFilterInput.val();
    if (String(dataItem).length > 0) {
        showFilterModal(dataItem);
    }
});

$(document).on('pjax:complete', '[id^="nextPageContainer_"]', function (event) {
    hideFilterModal();
    event.stopPropagation();
});
