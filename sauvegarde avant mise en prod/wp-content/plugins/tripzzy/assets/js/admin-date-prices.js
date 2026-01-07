( function ( wp ) {
	if ( ! wp || ! wp.hooks || ! wp.element || ! wp.components ) {
		return;
	}

	const { addFilter } = wp.hooks;
	const { createElement } = wp.element;
	const { TextControl } = wp.components;
	const { __ } = wp.i18n || { __: ( str ) => str };

	const getUpdatedTripData = ( tripData, index, value ) => {
		const nextData = { ...tripData };
		const trips = { ...( nextData.trips || {} ) };
		const fixedDates = Array.isArray( trips.fixed_dates ) ? [ ...trips.fixed_dates ] : [];
		const dateEntry = { ...( fixedDates[ index ] || {} ), custom_price: value };

		fixedDates[ index ] = dateEntry;
		trips.fixed_dates = fixedDates;
		nextData.trips = trips;

		return nextData;
	};

	addFilter(
		'tripzzyAfterTripFixedStartDatesFields',
		'tripzzy/custom-date-price-field',
		( fields, tripData, dateEntry, index ) => {
			if ( ! tripData || ! tripData.trips ) {
				return fields;
			}

			const fieldId = `tripzzy-custom-date-price-${ index }`;
			const value = dateEntry && dateEntry.custom_price ? dateEntry.custom_price : '';
			const onChange = ( newValue ) => {
				if ( ! wp.data || ! wp.data.dispatch ) {
					return;
				}
				const store = wp.data.dispatch( 'Tripzzy/Trip' );
				if ( ! store || 'function' !== typeof store.updateTrip ) {
					return;
				}
				const nextData = getUpdatedTripData( tripData, index, newValue );
				store.updateTrip( nextData );
			};

			const field = createElement(
				'div',
				{
					className: 'tripzzy-form-field tripzzy-form-field-custom-price',
					key: `tripzzy-custom-price-${ index }`,
				},
				createElement(
					'div',
					{ className: 'components-base-control' },
					createElement(
						'div',
						{ className: 'components-base-control__field' },
						createElement(
							'label',
							{
								className: 'components-base-control__label',
								htmlFor: fieldId,
							},
							__( 'Prix personnalisé', 'tripzzy' )
						),
						createElement(
							'div',
							{ className: 'tripzzy-input-field' },
							createElement( TextControl, {
								id: fieldId,
								type: 'number',
								min: '0',
								step: '0.01',
								value,
								onChange,
								placeholder: __( 'Saisir un prix pour cette date', 'tripzzy' ),
							} )
						),
						createElement(
							'p',
							{ className: 'description' },
							__(
								'Ce montant remplace le prix “à partir de” affiché pour cette date.',
								'tripzzy'
							)
						)
					)
				)
			);

			return [ ...fields, field ];
		}
	);
}( window.wp ) );

