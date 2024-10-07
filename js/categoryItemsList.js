var categoryItemsListMediaQuery = window.matchMedia('(min-width: 993px)');

function categoryItemsListMediaQueryHandler(x) {
    if (x.matches) {
        $('a.projects-item').each(function() {
            let link = $(this).find('.projects-item-link').attr('data-link');
            $(this).attr('href', link);
        });
    } else {
        $('a.projects-item').attr('href', null);
    }
}

categoryItemsListMediaQueryHandler(categoryItemsListMediaQuery);
categoryItemsListMediaQuery.addEventListener('change', categoryItemsListMediaQueryHandler);

/**
 * ipyhonin: Тормозящий (throttling) декоратор
 * См. https://learn.javascript.ru/call-apply-decorators#tormozyaschiy-throttling-dekorator
 */
 function throttle(func, ms) {
    let isThrottled = false, savedArgs, savedThis;

    function wrapper() {
        if (isThrottled) {
            savedArgs = arguments;
            savedThis = this;
            return;
        }

        func.apply(this, arguments);
        isThrottled = true;

        setTimeout(function() {
            isThrottled = false;
            if (savedArgs) {
                wrapper.apply(savedThis, savedArgs);
                savedArgs = savedThis = null;
            }
        }, ms);
    }

    return wrapper;
}

$(document).on('click', '.pagination .next a', function (event) {
    var container = $(this).closest('[data-pjax-container]');
    var containerSelector = '#' + container.attr('id');
    $.pjax.click(event, {
        container: containerSelector,
        timeout: 10000,
        fragment: '.projects-list',
        scrollTo: false
    });
});

function initSliders() {
    let fade = categoryItemsListMediaQuery.matches ? true : false;
    let speed = categoryItemsListMediaQuery.matches ? 500 : 1000;
    let rtl = (document.documentElement.dir == 'rtl');
    $('.projects-item-picture').each(function() {
        if (!$(this).hasClass('slick-slider')) {
            let navContainer = $(this).parents('.projects-item')[0];
            $(this).slick({
                slidesToShow: 1,
                slidesToScroll: 1,
                dots: true,
                appendDots: navContainer,
                appendArrows: navContainer,
                fade: fade,
                speed: speed,
                rtl: rtl
            });
        };
    });
};

$(document).ready(initSliders);

$(document).on('pjax:success', initSliders);

function goToSlide() {
    let slider = $(this).parents('.projects-item').find('.projects-item-picture');
    let slideNumber = parseInt($(this).children('button').text()) - 1;
    slider.slick('slickGoTo', slideNumber);
}

let throttleGoToSlide = throttle(goToSlide, 500);

$(document).on('mouseenter', '.projects-item .slick-dots li', throttleGoToSlide)

$(document).on('click', '.projects-item-picture', function() {
    let link = $(this).parents('.projects-item').find('.projects-item-link').attr('data-link');
    window.location.href = link;
});

$(document).on('click', '.projects-item-link', function() {
    let link = $(this).attr('data-link');
    window.location.href = link;
});
