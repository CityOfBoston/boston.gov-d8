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
			"display": false,
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
			"display": true,
			"cards": {
				"snow_emergency": {
					"display": true,
				},
				"snow_route": {
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
