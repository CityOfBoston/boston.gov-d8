const configProps = {
	"globals": {
		// Sets URL path for browser history functionality in REACT application.
		"path": window.location.pathname,
		// Sets URL path for image icons in REACT application.
		"pathImage": "https://assets.boston.gov/icons/experiential_icons/",
	},
	"sections": {
		// Turns display on and off for sections and cards.
    "representation": {
      "display": true,
      "cards": {
        "polling_information": {
          "display": true,
        },
        "mayor": {
          "display": true,
          "name": "Kim Janey",
          "image": "https://www.boston.gov/sites/default/files/styles/person_profile_card_173x173_/public/img/library/photos/2021/03/new-janey-headshot.jpg",
          "url": "/departments/mayors-office",
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
        "city_councilor_at_large": {
          "display": true,
        },
      },
    },
    "newsletter": {
      "display": true,
    },
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
        "snow_parking_restrictions": {
          "display": true,
        },
			},
		},
		"bos_311": {
			"display": true,
		},
	},
	"frame_google": function(){
		const page = location.href;
		const pageGoogle = page.toLowerCase().indexOf("google");
    const pageTranslate = page.toLowerCase().indexOf("translate");
    if (pageGoogle >= 0 || pageTranslate >= 0) {
			return true;
		}else{
      return false;
		}
	}
}
