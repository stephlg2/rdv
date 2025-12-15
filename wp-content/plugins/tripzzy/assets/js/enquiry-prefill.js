( function () {
	const formWrapper = document.getElementById( 'tripzzy-enquiry-form-wrapper' );
	if ( ! formWrapper ) {
		return;
	}

	const form = formWrapper.querySelector( 'form' );
	if ( ! form ) {
		return;
	}

	const departInput = form.querySelector( 'input[name="date-sejour-depart"]' );
	const retourInput = form.querySelector( 'input[name="date-sejour-retour"]' );
	const titleElement = formWrapper.querySelector( '[data-tripzzy-enquiry-title]' );
	const titleMain = formWrapper.querySelector( '.tripzzy-drawer-title-main' );
	const dateSpan = formWrapper.querySelector( '.date-devis' );
	const tripHeading = document.querySelector( 'h2.entry-title[itemprop="name"]' );
	const baseTripName = tripHeading ? tripHeading.textContent.trim() : '';

	if ( ! departInput || ! retourInput ) {
		return;
	}

	const truncateTripName = ( name ) => {
		const limit = 25;
		if ( ! name || name.length <= limit ) {
			return name;
		}
		const truncated = name.slice( 0, limit );
		const lastSpace = truncated.lastIndexOf( ' ' );
		if ( lastSpace === -1 ) {
			return `${ truncated }...`;
		}
		return `${ truncated.slice( 0, lastSpace ) }...`;
	};

	const formatDate = ( dateString ) => {
		if ( ! dateString ) {
			return '';
		}
		const date = new Date( dateString );
		if ( Number.isNaN( date.getTime() ) ) {
			return dateString;
		}
		return date.toLocaleDateString( 'fr-FR', {
			day: '2-digit',
			month: 'long',
			year: 'numeric',
		} );
	};

	const updateTitle = ( startDate, endDate ) => {
		if ( ! titleElement || ! baseTripName ) {
			return;
		}
		const tripName = truncateTripName( baseTripName );
		const start = formatDate( startDate );
		const end = formatDate( endDate || startDate );
		if ( ! start ) {
			return;
		}
		if ( titleMain ) {
			titleMain.textContent = `Votre demande de devis pour ${ tripName }`;
		} else if ( titleElement ) {
			titleElement.textContent = `Votre demande de devis pour ${ tripName }`;
		}
		if ( dateSpan ) {
			dateSpan.innerHTML = `<i class="fa fa-light fa-calendar"></i> Du ${ start } au ${ end }`;
		}
	};

	const fillDates = ( bookingData ) => {
		if ( ! bookingData || ! bookingData.start_date ) {
			return;
		}
		const startDate = bookingData.start_date;
		const endDate = bookingData.end_date || bookingData.start_date;

		departInput.value = startDate;
		retourInput.value = endDate;
		updateTitle( startDate, endDate );
	};

	document.addEventListener( 'click', ( event ) => {
		const trigger = event.target.closest( '[data-tripzzy-drawer-trigger]' );
		if ( ! trigger ) {
			return;
		}

		const dateContainer = trigger.closest( '.tripzzy-dates-content' );
		if ( ! dateContainer ) {
			return;
		}

		const bookingAttr = dateContainer.getAttribute( 'data-trip-booking' );
		if ( ! bookingAttr ) {
			return;
		}

		try {
			const bookingData = JSON.parse( bookingAttr );
			fillDates( bookingData );
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Tripzzy enquiry prefill error', error );
		}
	} );
}() );

