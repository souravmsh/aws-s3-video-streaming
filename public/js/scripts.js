/* globals Chart:false, feather:false */ 
(function () {
  'use strict'

  // feather icons
  feather.replace();

  // popover everywhere
  $('[data-toggle="popover"]').popover({
    trigger: 'hover'
  });

  // table search 
  $("input[type=search]").on("input", function() {
    var value = $(this).val().toLowerCase();
    $("table tr").filter(function() {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
  });

  // back to top
  var offset   = 220;
  var duration = 500;
  $('body').append('<a href="#" class="back-to-top">&uarr;</a>');
  $(window).scroll(function() {
      if ($(this).scrollTop() > offset) {
          $('body .back-to-top').fadeIn(duration);
      } else {
          $('body .back-to-top').fadeOut(duration);
      }
  });

  $('body').on('click', '.back-to-top', function(event) {
      event.preventDefault();
      $('html, body').animate({scrollTop: 0}, duration);
      return false;
  });

}())