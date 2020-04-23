(function($) {
	/**
	 * Fügt Popover dem Info-Icon hinzu, um GAV Koordinaten und Gradle Informationen
	 * via Rest vom SAMS abzufragen.
	 */
  $('.artifact-info-icon').click(function() {
    let repoPath = $(this).closest('tr').children('[data-th="Repository"]').html();
    let download = $(this).closest('td').children('a').first().attr('href');
    $('.popover').popover('destroy');
    let artifact = $(this).attr('artifact');
    var element = this;
    $.ajax({
      // @todo Url aus Konfiguration ziehen (base-url aus Drupal.Konfiguration)
      url: 'https://betriebsportal-konsens.hessen.testa-de.net/artifact_info_callback',
      method: 'post',
      data: {
        repo: repoPath,
        link: download
      }
    })
    .done(function( data ) {
      $(element).popover({
        placement: 'right',
        html: true,
        title: artifact + '<button type="button" class="close" aria-label="Close" onclick="jQuery(&quot;.popover&quot;).popover(&quot;hide&quot;);">&times;</button>',
        // title: artifact + '<button type="button" class="close" aria-label="Close" onclick="console.log(&quot;test&quot;);">&times;</button>',
        content: data.body,
      }).popover('show');
      $('.btn-info').click(function () {
        let copyText = this.nextSibling;
        copyText.select();
        copyText.setSelectionRange(0, 99999); /*For mobile devices*/
        document.execCommand("copy");
        $(this).children().replaceWith('<span class="glyphicon glyphicon-ok"></span>');
      });
    })
    .fail(function() {
      alert("Fehler.");
    });
    return false;
  });

	/**
	 *  Popover schließen, wenn daneben geklickt wird.
	 */
  $('body').on('click', function (e) {
    if ($(e.target).data('toggle') !== 'popover'
        && $(e.target).parents('.popover.in').length === 0) {
        $('.popover').popover('destroy');
    }
  });

	/**
	 *  Info-Icon wird entsperrt, sobald alles fertig geladen ist.
	 */
	Drupal.behaviors.enableInfoIcon = {
		attach: function (context, settings) {
			$('.artifact-info-icon').once().each(function () {
				$(this).removeClass('disabled');
			});
		}
	}
})(jQuery, Drupal);

