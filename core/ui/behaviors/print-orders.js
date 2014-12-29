jQuery(window).ready( function($) {
	var printOrderURL = $('#print-button').attr('href');
	$('body').append('<iframe id="print-receipt" name="receipt" src="' + printOrderURL + '" width="400" height="100" style="display: none;"></iframe>');

	$('#print-button').click(function(event) {
		event.preventDefault();

		// CREATE AN ARRAY OF SELECTED IDS
		var ids = [];
		$('#orders-table th input').each( function () {
			if( this.checked )
			{
				ids.push( $(this).val() );
			}
		});

		var query = '';
		for (var i = 0; i < ids.length; i++) {
			query += '&id[' + i + ']=' + ids[i];
		};

		if (typeof ids == 'undefined' || ids.length < 1)
		{
			alert('You must first select which orders you wish to print');
			return false;
		}

		$('#print-receipt').attr('src', printOrderURL + query).load(showPrintModal);

	});

	function showPrintModal()
	{
		// DEFAULT SHOPP FUNCTIONALITY
		var frame = $( '#print-receipt' ).get( 0 ), fw = frame.contentWindow;

		// Which browser agent?
		var trident = ( -1 !== navigator.userAgent.indexOf( "Trident" ) ); // IE
		var presto = ( -1 !== navigator.userAgent.indexOf( "Presto" ) ); // Opera (pre-webkit)

		if ( trident || presto ) {
			var preview = window.open( fw.location.href+"&print=auto" );
			$( preview ).load( function () {	preview.close(); } );
		} else {
			fw.focus();
			fw.print();
		}
	}
});
