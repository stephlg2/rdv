( function () {
	console.log( '🔵 Tripzzy enquiry-prefill.js chargé' );

	// Fonction pour trouver les champs dans le formulaire
	const findFormFields = () => {
		const wrapper = document.getElementById( 'tripzzy-enquiry-form-wrapper' );
		if ( ! wrapper ) {
			console.log( '❌ Wrapper non trouvé' );
			return null;
		}
		
		// Vérifier si le drawer est ouvert
		const isOpen = wrapper.classList.contains( 'tripzzy-drawer--open' );
		if ( ! isOpen ) {
			console.log( '⏳ Drawer pas encore ouvert' );
		}
		
		const form = wrapper.querySelector( 'form' );
		if ( ! form ) {
			console.log( '❌ Formulaire non trouvé dans le wrapper' );
			return null;
		}
		
		// Chercher tous les champs possibles
		const allInputs = Array.from( form.querySelectorAll( 'input, select' ) );
		const inputInfo = allInputs.map( el => {
			const label = el.closest( 'div' )?.querySelector( 'label' ) || 
			             ( el.id ? form.querySelector( 'label[for="' + el.id + '"]' ) : null );
			return { 
				name: el.name, 
				id: el.id, 
				type: el.type,
				tagName: el.tagName,
				label: label?.textContent?.trim() || null
			};
		} );
		
		console.log( '📋 Tous les champs du formulaire:', inputInfo );
		
		// Chercher le champ durée - méthode exhaustive
		let dureeField = null;
		
		// 1. Par ID exact (priorité car plus fiable)
		dureeField = form.querySelector( '#duree' );
		
		// 2. Par nom exact (form.php et form-full.php)
		if ( ! dureeField ) {
			dureeField = form.querySelector( 'select[name="duree"], input[name="duree"]' );
		}
		
		// 3. Par nom legacy (form-legacy.php)
		if ( ! dureeField ) {
			dureeField = form.querySelector( 'select[name="duree-sejour"]' );
		}
		
		// 3. Par label contenant "durée" ou "séjour"
		if ( ! dureeField ) {
			const labels = Array.from( form.querySelectorAll( 'label' ) );
			const dureeLabel = labels.find( label => {
				const text = label.textContent.toLowerCase();
				return text.includes( 'durée' ) || text.includes( 'sejour' ) || text.includes( 'séjour' );
			} );
			
			if ( dureeLabel ) {
				// Chercher par attribut for
				const forId = dureeLabel.getAttribute( 'for' );
				if ( forId ) {
					dureeField = form.querySelector( '#' + forId );
				}
				
				// Si pas trouvé, chercher dans le parent ou suivant
				if ( ! dureeField ) {
					const parent = dureeLabel.closest( 'div' );
					if ( parent ) {
						dureeField = parent.querySelector( 'select, input[type="text"]' );
					}
				}
				
				// Si toujours pas trouvé, chercher le suivant
				if ( ! dureeField && dureeLabel.nextElementSibling ) {
					dureeField = dureeLabel.nextElementSibling;
				}
			}
		}
		
		// 4. Chercher dans tous les selects/inputs par placeholder ou attributs
		if ( ! dureeField ) {
			dureeField = allInputs.find( el => {
				const placeholder = el.placeholder?.toLowerCase() || '';
				return placeholder.includes( 'durée' ) || placeholder.includes( 'sejour' );
			} );
		}
		
		// Chercher le champ budget - méthode exhaustive
		let budgetField = null;
		
		// 1. Par ID exact (priorité car plus fiable)
		budgetField = form.querySelector( '#budget' );
		
		// 2. Par nom exact (form.php et form-full.php)
		if ( ! budgetField ) {
			budgetField = form.querySelector( 'input[name="budget"]' );
		}
		
		// 3. Par nom legacy (form-legacy.php)
		if ( ! budgetField ) {
			budgetField = form.querySelector( 'input[name="budget-sejour"]' );
		}
		
		// 3. Par label contenant "budget"
		if ( ! budgetField ) {
			const labels = Array.from( form.querySelectorAll( 'label' ) );
			const budgetLabel = labels.find( label => {
				const text = label.textContent.toLowerCase();
				return text.includes( 'budget' );
			} );
			
			if ( budgetLabel ) {
				// Chercher par attribut for
				const forId = budgetLabel.getAttribute( 'for' );
				if ( forId ) {
					budgetField = form.querySelector( '#' + forId );
				}
				
				// Si pas trouvé, chercher dans le parent
				if ( ! budgetField ) {
					const parent = budgetLabel.closest( 'div' );
					if ( parent ) {
						budgetField = parent.querySelector( 'input[type="text"], input:not([type="hidden"])' );
					}
				}
				
				// Si toujours pas trouvé, chercher le suivant
				if ( ! budgetField && budgetLabel.nextElementSibling ) {
					budgetField = budgetLabel.nextElementSibling;
				}
			}
		}
		
		// 4. Chercher par placeholder
		if ( ! budgetField ) {
			budgetField = allInputs.find( el => {
				const placeholder = el.placeholder?.toLowerCase() || '';
				return placeholder.includes( 'budget' );
			} );
		}
		
		const fields = {
			wrapper: wrapper,
			form: form,
			depart: form.querySelector( 'input[name="date-sejour-depart"], input[name="depart"]' ),
			retour: form.querySelector( 'input[name="date-sejour-retour"], input[name="retour"]' ),
			duree: dureeField,
			budget: budgetField,
			titleElement: wrapper.querySelector( '[data-tripzzy-enquiry-title]' ),
			titleMain: wrapper.querySelector( '.tripzzy-drawer-title-main' ),
			dateSpan: wrapper.querySelector( '.date-devis' )
		};
		
		console.log( '✅ Champs trouvés:', {
			hasDepart: !!fields.depart,
			hasRetour: !!fields.retour,
			hasDuree: !!fields.duree,
			hasBudget: !!fields.budget,
			dureeInfo: fields.duree ? { name: fields.duree.name, id: fields.duree.id, type: fields.duree.type } : null,
			budgetInfo: fields.budget ? { name: fields.budget.name, id: fields.budget.id, type: fields.budget.type } : null
		} );
		
		return fields;
	};

	// Fonction pour calculer la durée
	const calculateDuration = ( startDate, endDate ) => {
		if ( ! startDate || ! endDate ) return null;
		const start = new Date( startDate );
		const end = new Date( endDate );
		if ( Number.isNaN( start.getTime() ) || Number.isNaN( end.getTime() ) ) return null;
		const diffTime = Math.abs( end - start );
		return Math.ceil( diffTime / ( 1000 * 60 * 60 * 24 ) );
	};

	// Fonction pour formater la durée
	const formatDuration = ( days, selectElement ) => {
		if ( ! days || days < 1 || ! selectElement || selectElement.tagName !== 'SELECT' ) return null;
		const options = Array.from( selectElement.options ).map( opt => opt.value ).filter( opt => opt && opt !== '' );
		console.log( '📋 Options durée disponibles:', options );
		if ( options.includes( 'De 7 à 15 jours' ) ) {
			return days <= 15 ? 'De 7 à 15 jours' : 'Plus de 15 jours';
		}
		if ( options.includes( '7-10 jours' ) ) {
			if ( days <= 10 ) return '7-10 jours';
			if ( days <= 15 ) return '10-15 jours';
			if ( days <= 20 ) return '15-20 jours';
			return 'Plus de 20 jours';
		}
		return null;
	};

	// Fonction pour extraire le prix avec "A partir de" et le symbole €
	const extractPrice = ( text ) => {
		if ( ! text ) return null;
		// Chercher le pattern "A partir de" ou "À partir de" suivi du prix
		const match = text.match( /[Àà]\s*partir\s*de\s*(\d[\d\s]{3,})\s*€/i );
		if ( match ) {
			const price = match[ 1 ].replace( /\s/g, '' );
			return `A partir de ${ price }€`;
		}
		// Sinon, chercher juste le prix et ajouter "A partir de"
		const priceMatch = text.match( /(\d[\d\s]{3,})\s*€/ );
		if ( priceMatch ) {
			const price = priceMatch[ 1 ].replace( /\s/g, '' );
			return `A partir de ${ price }€`;
		}
		return null;
	};

	// Fonction pour rendre un champ en lecture seule (grisé comme le champ durée)
	const setReadOnly = ( input, readonly ) => {
		if ( ! input ) return;
		if ( readonly ) {
			input.setAttribute( 'readonly', 'readonly' );
			input.setAttribute( 'disabled', 'disabled' );
			// Style grisé cohérent avec le champ durée (select disabled)
			input.style.backgroundColor = '#f5f5f5';
			input.style.color = '#999';
			input.style.cursor = 'not-allowed';
			input.style.opacity = '0.7';
		} else {
			input.removeAttribute( 'readonly' );
			input.removeAttribute( 'disabled' );
			input.style.backgroundColor = '';
			input.style.color = '';
			input.style.cursor = '';
			input.style.opacity = '';
		}
	};

	// Fonction pour remplir les champs avec retry
	const fillFields = ( bookingData, retryCount = 0 ) => {
		console.log( `🔄 Tentative ${ retryCount + 1 } de remplissage des champs` );
		
		if ( retryCount > 20 ) {
			console.log( '❌ Arrêt après 20 tentatives' );
			return;
		}

		const fields = findFormFields();
		if ( ! fields || ! fields.depart || ! fields.retour ) {
			console.log( `⏳ Champs dates non disponibles, retry dans 300ms (tentative ${ retryCount + 1 })` );
			setTimeout( () => fillFields( bookingData, retryCount + 1 ), 300 );
			return;
		}
		
		// Si les champs durée ou budget ne sont pas trouvés mais que les dates le sont,
		// continuer à retry pour trouver durée et budget
		if ( ( ! fields.duree || ! fields.budget ) && retryCount < 20 ) {
			console.log( `⏳ Champs durée/budget non disponibles, retry dans 300ms (tentative ${ retryCount + 1 })` );
			setTimeout( () => fillFields( bookingData, retryCount + 1 ), 300 );
			return;
		}

		const startDate = bookingData.start_date;
		const endDate = bookingData.end_date || bookingData.start_date;

		console.log( '📅 Dates à remplir:', { startDate, endDate } );

		// Remplir les dates
		fields.depart.value = startDate;
		fields.retour.value = endDate;

		// Mettre à jour le titre
		const tripHeading = document.querySelector( 'h2.entry-title[itemprop="name"]' );
		const baseTripName = tripHeading ? tripHeading.textContent.trim() : '';
		if ( baseTripName && ( fields.titleMain || fields.titleElement ) ) {
			const tripName = baseTripName.length > 25 ? baseTripName.slice( 0, 25 ) + '...' : baseTripName;
			const start = new Date( startDate ).toLocaleDateString( 'fr-FR', { day: '2-digit', month: 'long', year: 'numeric' } );
			const end = new Date( endDate ).toLocaleDateString( 'fr-FR', { day: '2-digit', month: 'long', year: 'numeric' } );
			if ( fields.titleMain ) {
				fields.titleMain.textContent = `Votre demande de devis pour ${ tripName }`;
			}
			if ( fields.dateSpan ) {
				fields.dateSpan.innerHTML = `<i class="fa fa-light fa-calendar"></i> Du ${ start } au ${ end }`;
			}
		}

		// Remplir la durée
		if ( fields.duree ) {
			console.log( '⏱️ Tentative de remplissage de la durée', { 
				fieldName: fields.duree.name, 
				fieldId: fields.duree.id,
				fieldType: fields.duree.tagName 
			} );
			const durationDays = calculateDuration( startDate, endDate );
			console.log( '📊 Durée calculée en jours:', durationDays );
			if ( durationDays ) {
				const durationText = formatDuration( durationDays, fields.duree );
				console.log( '📝 Durée formatée:', durationText );
				if ( durationText ) {
					if ( fields.duree.tagName === 'SELECT' ) {
						const option = Array.from( fields.duree.options ).find( opt => opt.value === durationText );
						if ( option ) {
							fields.duree.value = durationText;
							setReadOnly( fields.duree, true );
							console.log( '✅ Durée remplie:', durationText );
						} else {
							console.log( '❌ Option durée non trouvée. Options disponibles:', 
								Array.from( fields.duree.options ).map( opt => opt.value ) );
						}
					} else {
						// Si c'est un input
						fields.duree.value = durationDays + ' jours';
						setReadOnly( fields.duree, true );
						console.log( '✅ Durée remplie (input):', durationDays + ' jours' );
					}
				}
			}
		} else {
			console.log( '❌ Champ durée non trouvé - retry nécessaire' );
			// Si les champs durée/budget ne sont pas trouvés, continuer à retry
			if ( retryCount < 20 ) {
				setTimeout( () => fillFields( bookingData, retryCount + 1 ), 300 );
				return;
			}
		}

		// Remplir le budget
		if ( fields.budget ) {
			console.log( '💰 Tentative de remplissage du budget', { 
				fieldName: fields.budget.name, 
				fieldId: fields.budget.id,
				fieldType: fields.budget.type 
			} );
			let priceValue = null;
			
			// Chercher dans bookingData
			const price = bookingData.price || bookingData.price_per_person || bookingData.starting_price || bookingData.amount;
			console.log( '💵 Prix dans bookingData:', price );
			if ( price ) {
				const priceString = String( price );
				// Si le prix contient déjà "A partir de", vérifier qu'il a le symbole €
				if ( /[Àà]\s*partir\s*de/i.test( priceString ) ) {
					priceValue = priceString;
					// S'assurer que le symbole € est présent
					if ( ! priceValue.includes( '€' ) ) {
						// Extraire le nombre et ajouter €
						const numMatch = priceValue.match( /(\d+)/ );
						if ( numMatch ) {
							priceValue = `A partir de ${ numMatch[ 1 ] }€`;
						}
					}
					console.log( '✅ Prix avec "A partir de" trouvé dans bookingData:', priceValue );
				} else {
					// Sinon, extraire le nombre et ajouter "A partir de" et €
					const match = priceString.match( /(\d[\d\s]+)/ );
					if ( match ) {
						const priceNum = match[ 1 ].replace( /\s/g, '' );
						priceValue = `A partir de ${ priceNum }€`;
						console.log( '✅ Prix extrait de bookingData avec "A partir de":', priceValue );
					}
				}
			}
			
			// Si pas trouvé, chercher dans le texte de la disponibilité cliquée
			if ( ! priceValue ) {
				const dateContainer = document.querySelector( '.tripzzy-dates-content[data-trip-booking]' );
				if ( dateContainer ) {
					priceValue = extractPrice( dateContainer.textContent );
					console.log( '💵 Prix extrait du container:', priceValue );
				}
			}
			
			// Si toujours pas trouvé, chercher dans tous les containers de dates
			if ( ! priceValue ) {
				const allContainers = document.querySelectorAll( '.tripzzy-dates-content' );
				console.log( '🔍 Recherche dans tous les containers:', allContainers.length );
				for ( const container of allContainers ) {
					priceValue = extractPrice( container.textContent );
					if ( priceValue ) {
						console.log( '✅ Prix trouvé dans un container:', priceValue );
						break;
					}
				}
			}
			
			if ( priceValue ) {
				// S'assurer que "A partir de" est présent
				if ( ! /[Àà]\s*partir\s*de/i.test( priceValue ) ) {
					// Si c'est juste un nombre, ajouter "A partir de"
					const numMatch = priceValue.match( /(\d+)/ );
					if ( numMatch ) {
						priceValue = `A partir de ${ numMatch[ 1 ] }€`;
					}
				} else {
					// Si "A partir de" est présent mais pas le symbole €, l'ajouter
					if ( ! priceValue.includes( '€' ) ) {
						const numMatch = priceValue.match( /(\d+)/ );
						if ( numMatch ) {
							priceValue = `A partir de ${ numMatch[ 1 ] }€`;
						}
					}
				}
				fields.budget.value = priceValue;
				setReadOnly( fields.budget, true );
				console.log( '✅ Budget rempli:', priceValue );
			} else {
				console.log( '❌ Aucun prix trouvé' );
			}
		} else {
			console.log( '❌ Champ budget non trouvé - retry nécessaire' );
			// Si les champs durée/budget ne sont pas trouvés, continuer à retry
			if ( retryCount < 20 ) {
				setTimeout( () => fillFields( bookingData, retryCount + 1 ), 300 );
				return;
			}
		}
	};

	// Variable pour stocker les données de booking en attente
	let pendingBookingData = null;

	// Observer pour détecter l'ouverture du drawer
	const setupDrawerObserver = () => {
		const wrapper = document.getElementById( 'tripzzy-enquiry-form-wrapper' );
		if ( ! wrapper ) {
			return;
		}

		const observer = new MutationObserver( ( mutations ) => {
			mutations.forEach( ( mutation ) => {
				if ( mutation.type === 'attributes' && mutation.attributeName === 'class' ) {
					const drawer = mutation.target;
					if ( drawer.classList.contains( 'tripzzy-drawer--open' ) && pendingBookingData ) {
						console.log( '🎯 Drawer ouvert détecté, démarrage du remplissage' );
						setTimeout( () => {
							fillFields( pendingBookingData );
							pendingBookingData = null; // Réinitialiser après utilisation
						}, 500 );
					}
				}
			} );
		} );

		observer.observe( wrapper, { attributes: true, attributeFilter: [ 'class' ] } );
	};

	// Essayer de configurer l'observer immédiatement
	const wrapper = document.getElementById( 'tripzzy-enquiry-form-wrapper' );
	if ( wrapper ) {
		setupDrawerObserver();
	} else {
		// Attendre que le wrapper soit créé
		const checkWrapper = setInterval( () => {
			const w = document.getElementById( 'tripzzy-enquiry-form-wrapper' );
			if ( w ) {
				clearInterval( checkWrapper );
				setupDrawerObserver();
			}
		}, 500 );
		setTimeout( () => clearInterval( checkWrapper ), 10000 );
	}

	// Fonction pour réinitialiser les champs pré-remplis
	const resetPrefilledFields = () => {
		console.log( '🔄 Réinitialisation des champs pré-remplis' );
		
		const wrapper = document.getElementById( 'tripzzy-enquiry-form-wrapper' );
		if ( ! wrapper ) {
			return;
		}
		
		const form = wrapper.querySelector( 'form' );
		if ( ! form ) {
			return;
		}
		
		// Réinitialiser les champs durée et budget
		const dureeField = form.querySelector( '#duree, select[name="duree"], select[name="duree-sejour"]' );
		if ( dureeField ) {
			dureeField.value = '';
			setReadOnly( dureeField, false );
			console.log( '✅ Champ durée réinitialisé' );
		}
		
		const budgetField = form.querySelector( '#budget, input[name="budget"], input[name="budget-sejour"]' );
		if ( budgetField ) {
			budgetField.value = '';
			setReadOnly( budgetField, false );
			console.log( '✅ Champ budget réinitialisé' );
		}
		
		// Réinitialiser les dates
		const departField = form.querySelector( 'input[name="date-sejour-depart"], input[name="depart"]' );
		if ( departField ) {
			departField.value = '';
		}
		
		const retourField = form.querySelector( 'input[name="date-sejour-retour"], input[name="retour"]' );
		if ( retourField ) {
			retourField.value = '';
		}
		
		// Réinitialiser le titre
		const titleMain = wrapper.querySelector( '.tripzzy-drawer-title-main' );
		if ( titleMain ) {
			titleMain.textContent = 'Votre demande de devis';
		}
		
		const dateSpan = wrapper.querySelector( '.date-devis' );
		if ( dateSpan ) {
			dateSpan.innerHTML = '';
		}
		
		// Réinitialiser les données en attente
		pendingBookingData = null;
	};

	// Écouter les clics sur les boutons de demande de devis
	document.addEventListener( 'click', ( event ) => {
		const trigger = event.target.closest( '[data-tripzzy-drawer-trigger]' );
		if ( ! trigger ) {
			return;
		}

		console.log( '🖱️ Clic sur bouton demande de devis détecté' );

		// Vérifier si c'est un bouton lié aux disponibilités (avec data-trip-booking)
		const dateContainer = trigger.closest( '.tripzzy-dates-content' );
		const bookingAttr = dateContainer ? dateContainer.getAttribute( 'data-trip-booking' ) : null;

		// Si c'est le bouton normal (sans data-trip-booking), réinitialiser les champs
		if ( ! bookingAttr ) {
			console.log( '🔄 Bouton normal détecté, réinitialisation des champs' );
			// Attendre que le drawer s'ouvre puis réinitialiser
			setTimeout( () => {
				resetPrefilledFields();
			}, 300 );
			return;
		}

		console.log( '📦 Données booking trouvées:', bookingAttr );

		try {
			const bookingData = JSON.parse( bookingAttr );
			console.log( '✅ Données booking parsées:', bookingData );

			// Stocker les données pour l'observer
			pendingBookingData = bookingData;

			// Aussi essayer directement après un délai (au cas où l'observer ne fonctionne pas)
			setTimeout( () => {
				if ( pendingBookingData ) {
					console.log( '🚀 Démarrage du remplissage après délai (fallback)' );
					fillFields( pendingBookingData );
				}
			}, 1500 );
		} catch ( error ) {
			console.error( '❌ Erreur parsing bookingData:', error );
		}
	} );
}() );
