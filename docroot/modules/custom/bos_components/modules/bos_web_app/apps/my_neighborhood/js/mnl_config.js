const configProps = {
	"globals": {
		// Sets URL path for browser history functionality in REACT application.
		"path": window.location.pathname,
		// Sets URL path for image icons in REACT application.
		"pathImage": "https://assets.boston.gov/icons/experiential_icons/",
	},
	"sections": {
		// Turns display on and off for sections and cards.
		"city_services": {
			"display": true,
			"cards":{
				"trash_and_recycling":{
					"display": true,
				},
				"street_sweeping": {
					"display": true,
				},
			}		
		},
		"city_spaces": {
			"display": true,
			"cards":{
				"library":{
					"display": true,
				},
				"community_center": {
					"display": true,
				},
				"park": {
					"display": true,
				},
				"historic_district": {
					"display": true,
				},
			},
		},
		"representation": {
			"display": true,
			"cards": {
				"polling_information": {
					"display": true,
				},
				"mayor": {
					"display": true,
					"name": "Martin J. Walsh",
					"image": "https://www.boston.gov/sites/default/files/styles/person_photo_profile_large_360x360_/public/img/2016/w/walsh-bio.jpg",
					"url": "/departments/mayors-office/martin-j-walsh",
				},
				"city_councilor": {
					"display": true,
				},
				"neighborhood_contact": {
					"display": true,
				},
				"early_voting": {
					"display": false,
				},
				"state_federal_reps": {
					"display": true,
				},
			},
		},
		"public_safety": {
			"display": true,
			"cards": {
				"police_station": {
					"display": true,
				},
				"fire_station": {
					"display": true,
				},
			},
		},
		"summer": {
			"display": true,
			"cards": {
				"pool": {
					"display": true,
				},
				"splash_pad": {
					"display": true,
				},
			},
		},
		"winter": {
			"display": false,
			"cards": {
				"snow_emergency": {
					"display": true,
				},
				"snow_route": {
					"display": true,
				},
			},
		},
		"newsletter": {
			"display": true,
		},
		"bos_311": {
			"display": true,
		},
	}
}